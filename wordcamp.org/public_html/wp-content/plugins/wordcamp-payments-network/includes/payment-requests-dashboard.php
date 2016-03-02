<?php

class Payment_Requests_Dashboard {
	public static $list_table;
	public static $db_version = 7;
	public static $import_results = null;

	/**
	 * Runs during plugins_loaded, doh.
	 */
	public static function plugins_loaded() {
		$current_site = get_current_site();

		// Schedule the aggregate event only on the main blog in the network.
		if ( get_current_blog_id() == $current_site->blog_id && ! wp_next_scheduled( 'wordcamp_payments_aggregate' ) )
			wp_schedule_event( time(), 'hourly', 'wordcamp_payments_aggregate' );

		add_action( 'wordcamp_payments_aggregate', array( __CLASS__, 'aggregate' ) );
		add_action( 'admin_enqueue_scripts',  array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'network_admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'upgrade' ) );
		add_action( 'init', array( __CLASS__, 'process_export_request' ) );
		add_action( 'init', array( __CLASS__, 'process_import_request' ) );

		// Dashboard actions.
		add_action( 'init', array( __CLASS__, 'process_action_approve' ) );
		add_action( 'init', array( __CLASS__, 'process_action_set_pending_payment' ) );

		// Diff-based updates to the index.
		add_action( 'save_post', array( __CLASS__, 'save_post' ) );
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ) );

		if ( ! empty( $_GET['wcp-debug-network'] ) && current_user_can( 'manage_network' ) )
			add_action( 'admin_init', function() { do_action( 'wordcamp_payments_aggregate' ); }, 99 );
	}

	/**
	 * Returns the name of the custom table.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->get_blog_prefix(0) . 'wordcamp_payments_index';
	}

	/**
	 * Upgrade routine, makes sure that our schema is up to date.
	 */
	public static function upgrade() {
		global $wpdb;

		// Don't attempt to perform upgrades outside of the dashboard.
		if ( ! is_admin() )
			return;

		$current_version = get_site_option( 'wcp_network_db_version', 0 );
		if ( version_compare( $current_version, self::$db_version, '>=' ) )
			return;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
		$sql = sprintf( "CREATE TABLE %s (
			id int(11) unsigned NOT NULL auto_increment,
			blog_id int(11) unsigned NOT NULL default '0',
			post_id int(11) unsigned NOT NULL default '0',
			created int(11) unsigned NOT NULL default '0',
			updated int(11) unsigned NOT NULL default '0',
			paid int(11) unsigned NOT NULL default '0',
			category varchar(255) NOT NULL default '',
			method varchar(255) NOT NULL default '',
			due int(11) unsigned NOT NULL default '0',
			status varchar(255) NOT NULL default '',
			keywords text NOT NULL default '',
			PRIMARY KEY  (id),
			KEY blog_post_id (blog_id, post_id),
			KEY due (due),
			KEY status (status)
		) %s;", self::get_table_name(), $charset_collate );

		dbDelta( $sql );

		update_site_option( 'wcp_network_db_version', self::$db_version );
	}

	/**
	 * Runs on a cron job, reads data from all sites in the network
	 * and builds an index table for future queries.
	 */
	public static function aggregate() {
		global $wpdb;

		// Register the custom payment statuses so that we can filter posts to include only them, in order to exclude trashed posts
		require_once( WP_PLUGIN_DIR . '/wordcamp-payments/includes/payment-request.php' );
		WCP_Payment_Request::register_post_statuses();

		// Truncate existing table.
		$wpdb->query( sprintf( "TRUNCATE TABLE %s;", self::get_table_name() ) );

		$blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM `{$wpdb->blogs}` WHERE site_id = %d ORDER BY last_updated DESC LIMIT %d;", $wpdb->siteid, 1000 ) );
		foreach ( $blogs as $blog_id ) {
			switch_to_blog( $blog_id );

			$paged = 1;
			while ( $requests = get_posts( array(
				'paged' => $paged++,
				'post_status' => 'any',
				'post_type' => 'wcp_payment_request',
				'posts_per_page' => 20,
			) ) ) {
				foreach ( $requests as $request ) {
					$wpdb->insert( self::get_table_name(), self::prepare_for_index( $request ) );
				}
			}

			restore_current_blog();
		}
	}

	/**
	 * Given a $request (could be a post_id) create an array that can
	 * be used with $wpdb->update() or $wpdb->insert() to add or update
	 * an index entry.
	 */
	public static function prepare_for_index( $request ) {
		$request = get_post( $request );
		$categories = WordCamp_Budgets::get_payment_categories();

		// All things search.
		$keywords = array( $request->post_title );

		$category_slug = get_post_meta( $request->ID, '_camppayments_payment_category', true );
		if ( ! empty( $categories[ $category_slug ] ) )
			$keywords[] = $categories[ $category_slug ];

		$payment_method = get_post_meta( $request->ID, '_camppayments_payment_method', true );
		if ( ! empty( $payment_method ) )
			$keywords[] = $payment_method;

		$vendor_name = get_post_meta( $request->ID, '_camppayments_vendor_name', true );
		if ( ! empty( $vendor_name ) ) {
			$keywords[] = $vendor_name;
		}

		$amount = get_post_meta( $request->ID, '_camppayments_payment_amount', true );
		if ( ! empty( $amount) ) {
			$keywords[] = $amount;
		}

		$back_compat_statuses = array(
			'unpaid' => 'draft',
			'incomplete' => 'wcb-incomplete',
			'paid' => 'wcb-paid',
		);

		// Map old statuses to new statuses.
		if ( array_key_exists( $request->post_status, $back_compat_statuses ) ) {
			$request->post_status = $back_compat_statuses[ $request->post_status ];
		}

		// One of these timestamps.
		while ( true ) {
			$updated_timestamp = absint( get_post_meta( $request->ID, '_wcb_updated_timestamp', time() ) );
			if ( $updated_timestamp ) break;

			$updated_timestamp = strtotime( $request->post_modified_gmt );
			if ( $updated_timestamp ) break;

			$updated_timestamp = strtotime( $request->post_date_gmt );
			if ( $updated_timestamp ) break;

			$updated_timestamp = strtotime( $request->post_date );
			break;
		}

		return array(
			'blog_id' => get_current_blog_id(),
			'post_id' => $request->ID,
			'created' => get_post_time( 'U', true, $request->ID ),
			'updated' => $updated_timestamp,
			'paid'    => absint( get_post_meta( $request->ID, '_camppayments_date_vendor_paid', true ) ),
			'due' => absint( get_post_meta( $request->ID, '_camppayments_due_by', true ) ),
			'status' => $request->post_status,
			'method' => $payment_method,
			'category' => $category_slug,
			'keywords' => json_encode( $keywords ),
		);
	}

	/**
	 * Runs during save_post, make sure our index is up to date.
	 */
	public static function save_post( $post_id ) {
		global $wpdb;

		$request = get_post( $post_id );
		if ( 'wcp_payment_request' != $request->post_type )
			return;

		$table_name = self::get_table_name();
		$entry_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE `blog_id` = %d AND `post_id` = %d LIMIT 1;", get_current_blog_id(), $request->ID ) );

		// Insert or update this record.
		if ( empty( $entry_id ) ) {
			$wpdb->insert( $table_name, self::prepare_for_index( $request ) );
		} else {
			$wpdb->update( $table_name, self::prepare_for_index( $request ), array( 'id' => $entry_id ) );
		}
	}

	/**
	 * Delete an index query when a request post has been deleted.
	 */
	public static function delete_post( $post_id ) {
		global $wpdb;

		$request = get_post( $post_id );
		if ( 'wcp_payment_request' != $request->post_type )
			return;

		$table_name = self::get_table_name();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE `blog_id` = %d AND `post_id` = %d LIMIT 1;", get_current_blog_id(), $request->ID ) );
	}

	/**
	 * Create a network admin menu item entry.
	 */
	public static function network_admin_menu() {
		$dashboard = add_submenu_page(
			'wordcamp-budgets-dashboard',
			'WordCamp Vendor Payments',
			'Vendor Payments',
			'manage_network',
			'wcp-dashboard',
			array( __CLASS__, 'render_dashboard' )
		);

		add_action( 'load-' . $dashboard, array( __CLASS__, 'pre_render_dashboard' ) );
	}

	/**
	 * Enqueue scripts and stylesheets
	 *
	 * @param string $hook
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'index_page_wcp-dashboard' == $hook && 'export' == self::get_current_tab() ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'wp-datepicker-skins' );
		}
	}

	/**
	 * Renders the Dashboard - Payments screen.
	 */
	public static function render_dashboard() {
		?>

		<div class="wrap">
			<h1>Vendor Payments</h1>

			<?php do_action( 'admin_notices' ); ?>
			<?php settings_errors(); ?>

			<h3 class="nav-tab-wrapper"><?php self::render_dashboard_tabs(); ?></h3>

			<?php
				if ( 'export' == self::get_current_tab() ) {
					self::render_export_tab();
				} elseif ( 'import' == self::get_current_tab() ) {
					self::render_import_tab();
				}
				else {
					self::render_table_tabs();
				}
			?>

		</div> <!-- /wrap -->

		<?php
	}

	/**
	 * Render the table tabs, like Overview, Pending, etc
	 */
	protected static function render_table_tabs() {
		?>

		<?php self::$list_table->print_inline_css(); ?>

		<div id="wcp-list-table">
			<?php self::$list_table->prepare_items(); ?>

			<form id="posts-filter" action="" method="get">
				<input type="hidden" name="page" value="wcp-dashboard" />
				<input type="hidden" name="wcp-section" value="<?php echo esc_attr( self::get_current_tab() ); ?>" />
				<?php self::$list_table->search_box( __( 'Search Payments', 'wordcamporg' ), 'wcp' ); ?>
				<?php self::$list_table->display(); ?>
			</form>
		</div>

		<?php
	}

	/**
	 * Process Approve button in network admin
	 */
	public static function process_action_approve() {
		if ( ! current_user_can( 'manage_network' ) )
			return;

		if ( empty( $_GET['wcb-approve'] ) || empty( $_GET['_wpnonce'] ) )
			return;

		list( $blog_id, $post_id ) = explode( '-', $_GET['wcb-approve'] );

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], sprintf( 'wcb-approve-%d-%d', $blog_id, $post_id ) ) ) {
			add_action( 'admin_notices', function() {
				?><div class="notice notice-error is-dismissible">
					<p><?php _e( 'Error! Could not verify nonce.', 'wordcamporg' ); ?></p>
				</div><?php
			});
			return;
		}

		switch_to_blog( $blog_id );
		$post = get_post( $post_id );
		if ( $post->post_type == 'wcp_payment_request' ) {
			$post->post_status = 'wcb-approved';
			wp_insert_post( $post );

			WordCamp_Budgets::log( $post->ID, get_current_user_id(), 'Request approved via Network Admin', array(
				'action' => 'approved',
			) );

			add_action( 'admin_notices', function() {
				?><div class="notice notice-success is-dismissible">
					<p><?php _e( 'Success! Request has been marked as approved.', 'wordcamporg' ); ?></p>
				</div><?php
			});
		}
		restore_current_blog();
	}

	/**
	 * Process "Set as Pending Payment" dashboard action.
	 */
	public static function process_action_set_pending_payment() {
		if ( ! current_user_can( 'manage_network' ) )
			return;

		if ( empty( $_GET['wcb-set-pending-payment'] ) || empty( $_GET['_wpnonce'] ) )
			return;

		list( $blog_id, $post_id ) = explode( '-', $_GET['wcb-set-pending-payment'] );

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], sprintf( 'wcb-set-pending-payment-%d-%d', $blog_id, $post_id ) ) ) {
			add_action( 'admin_notices', function() {
				?><div class="notice notice-error is-dismissible">
					<p><?php _e( 'Error! Could not verify nonce.', 'wordcamporg' ); ?></p>
				</div><?php
			});
			return;
		}

		switch_to_blog( $blog_id );
		$post = get_post( $post_id );
		if ( $post->post_type == 'wcp_payment_request' ) {
			$post->post_status = 'wcb-pending-payment';
			wp_insert_post( $post );

			WordCamp_Budgets::log( $post->ID, get_current_user_id(), 'Request set as Pending Payment via Network Admin', array(
				'action' => 'set-pending-payment',
			) );

			add_action( 'admin_notices', function() {
				?><div class="notice notice-success is-dismissible">
					<p><?php _e( 'Success! Request has been marked as Pending Payment.', 'wordcamporg' ); ?></p>
				</div><?php
			});
		}
		restore_current_blog();
	}

	/**
	 * Get available export options.
	 *
	 * @return array
	 */
	public static function get_export_types() {
		return array(
			'default' => array(
				'label' => 'Default',
				'mime_type' => 'text/csv',
				'callback' => array( __CLASS__, '_generate_payment_report_default' ),
				'filename' => 'wordcamp-payments-%s-%s-default.csv',
			),
			'jpm_wires' => array(
				'label' => 'JP Morgan Access - Wire Payments',
				'mime_type' => 'text/csv',
				'callback' => array( __CLASS__, '_generate_payment_report_jpm_wires' ),
				'filename' => 'wordcamp-payments-%s-%s-jpm-wires.csv',
			),
			'jpm_ach' => array(
				'label' => 'JP Morgan - NACHA',
				'mime_type' => 'text/plain',
				'callback' => array( __CLASS__, '_generate_payment_report_jpm_ach' ),
				'filename' => 'wordcamp-payments-%s-%s-jpm-ach.ach',
			),
			'jpm_checks' => array(
				'label' => 'JP Morgan - Quick Checks',
				'mime_type' => 'text/csv',
				'callback' => array( __CLASS__, '_generate_payment_report_jpm_checks' ),
				'filename' => 'wordcamp-payments-%s-%s-jpm-checks.csv',
			),
		);
	}

	/**
	 * Process export requests
	 */
	public static function process_export_request() {
		if ( empty( $_POST['submit'] ) || 'export' != self::get_current_tab() ) {
			return;
		}

		if ( ! current_user_can( 'manage_network' ) || ! check_admin_referer( 'export', 'wcpn_request_export' ) ) {
			return;
		}

		$export_types = self::get_export_types();

		if ( array_key_exists( $_POST['wcpn_export_type'], $export_types ) ) {
			$export_type = $export_types[ $_POST['wcpn_export_type'] ];
		} else {
			$export_type = $export_types['default'];
		}

		$status = $_POST['wcpn_export_status'];
		if ( ! in_array( $status, array( 'wcb-approved', 'wcb-paid' ) ) )
			$status = 'wcb-approved';

		$start_date = strtotime( $_POST['wcpn_export_start_date'] . ' 00:00:00' );
		$end_date   = strtotime( $_POST['wcpn_export_end_date']   . ' 23:59:59' );
		$filename = sprintf( $export_type['filename'], date( 'Ymd', $start_date ), date( 'Ymd', $end_date ) );
		$filename = sanitize_file_name( $filename );

		$report = self::generate_payment_report( $status, $start_date, $end_date, $export_type );

		if ( is_wp_error( $report ) ) {
			add_settings_error( 'wcp-dashboard', $report->get_error_code(), $report->get_error_message() );
		} else {
			header( sprintf( 'Content-Type: %s', $export_type['mime_type'] ) );
			header( sprintf( 'Content-Disposition: attachment; filename="%s"', $filename ) );
			header( 'Cache-control: private' );
			header( 'Pragma: private' );
			header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );

			echo $report;
			die();
		}
	}

	/*
	 * Generate and return the raw payment report contents
	 *
	 * @param string $date_type 'paid' | 'created'
	 * @param int $start_date
	 * @param int $end_date
	 * @param string $type
	 *
	 * @return string | WP_Error
	 */
	protected static function generate_payment_report( $status, $start_date, $end_date, $export_type ) {
		global $wpdb;

		if ( ! is_int( $start_date ) || ! is_int( $end_date ) ) {
			return new WP_Error( 'wcpn_bad_dates', 'Invalid start or end date.' );
		}

		$table_name = self::get_table_name();
		$date_type = 'updated';

		if ( $status == 'wcb-paid' )
			$date_type = 'paid';

		$request_indexes = $wpdb->get_results( $wpdb->prepare( "
			SELECT *
			FROM   `{$table_name}`
			WHERE  `{$date_type}` BETWEEN %d AND %d",
			$start_date,
			$end_date
		) );

		if ( ! is_callable( $export_type['callback'] ) )
			return new WP_Error( 'wcpn_invalid_type', 'The export type is invalid.' );

		$args = array(
			'request_indexes' => $request_indexes,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'export_type' => $export_type,
			'status' => $status,
		);

		return call_user_func( $export_type['callback'], $args );
	}

	/**
	 * Default CSV report
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected static function _generate_payment_report_default( $args ) {
		$args = wp_parse_args( $args, array(
			'request_indexes' => array(),
			'status' => '',
		) );

		$column_headings = array(
			'WordCamp', 'ID', 'Title', 'Status', 'Date Vendor was Paid', 'Creation Date', 'Due Date', 'Amount',
			'Currency', 'Category', 'Payment Method','Vendor Name', 'Vendor Contact Person', 'Vendor Country',
			'Check Payable To', 'URL', 'Supporting Documentation Notes',
		);

		ob_start();
		$report = fopen( 'php://output', 'w' );

		fputcsv( $report, $column_headings );

		foreach( $args['request_indexes'] as $index ) {
			$row = self::get_report_row( $index, $args );
			if ( ! empty( $row ) ) {
				fputcsv( $report, $row );
			}
		}

		fclose( $report );
		return ob_get_clean();
	}

	/**
	 * Quick Checks via JP Morgan
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected static function _generate_payment_report_jpm_checks( $args ) {
		$args = wp_parse_args( $args, array(
			'request_indexes' => array(),
			'status' => '',
		) );

		$options = apply_filters( 'wcb_payment_req_check_options', array(
			'pws_customer_id' => '',
			'account_number'  => '',
			'contact_email'   => '',
			'contact_phone'   => '',
		) );

		$report = fopen( 'php://output', 'w' );
		ob_start();

		// File Header
		fputcsv( $report, array( 'FILHDR', 'PWS', $options['pws_customer_id'], date( 'm/d/Y' ), date( 'Hi' ) ), ',', '|' );

		$total = 0;
		$count = 0;

		if ( false !== get_site_transient( '_wcb_jpm_checks_counter_lock' ) ) {
			wp_die( 'JPM Checks Export is locked. Please try again later or contact support.' );
		}

		// Avoid at least *some* race conditions.
		set_site_transient( '_wcb_jpm_checks_counter_lock', 1, 30 );
		$start = absint( get_site_option( '_wcb_jpm_checks_counter', 0 ) );

		foreach ( $args['request_indexes'] as $index ) {
			switch_to_blog( $index->blog_id );
			$post = get_post( $index->post_id );

			if ( $args['status'] && $post->post_status != $args['status'] )
				continue;

			if ( get_post_meta( $post->ID, '_camppayments_payment_method', true ) != 'Check' )
				continue;

			$count++;
			$amount = round( floatval( get_post_meta( $post->ID, '_camppayments_payment_amount', true ) ), 2 );
			$total += $amount;

			$payable_to = WCP_Encryption::maybe_decrypt( get_post_meta( $post->ID, '_camppayments_payable_to', true ) );
			$payable_to = html_entity_decode( $payable_to ); // J&amp;J to J&J
			$countries = WordCamp_Budgets::get_valid_countries_iso3166();
			$vendor_country_code = get_post_meta( $post->ID, '_camppayments_vendor_country_iso3166', true );
			if ( ! empty( $countries[ $vendor_country_code ] ) ) {
				$vendor_country_code = $countries[ $vendor_country_code ]['alpha3'];
			}

			$description = sanitize_text_field( get_post_meta( $post->ID, '_camppayments_description', true ) );
			$description = html_entity_decode( $description );
			$invoice_number = get_post_meta( $post->ID, '_camppayments_invoice_number', true );
			if ( ! empty( $invoice_number ) ) {
				$description = sprintf( 'Invoice %s. %s', $invoice_number, $description );
			}

			// Payment Header
			fputcsv( $report, array(
				'PMTHDR',
				'USPS',
				'QKCHECKS',
				date( 'm/d/Y' ),
				number_format( $amount, 2, '.', '' ),
				$options['account_number'],
				$start + $count, // must be globally unique?
				$options['contact_email'],
				$options['contact_phone'],
			), ',', '|' );

			// Payee Name Record
			fputcsv( $report, array(
				'PAYENM',
				substr( $payable_to, 0, 35 ),
				'',
				sprintf( '%d-%d', $index->blog_id, $index->post_id ),
			), ',', '|' );

			// Payee Address Record
			fputcsv( $report, array(
				'PYEADD',
				substr( get_post_meta( $post->ID, '_camppayments_vendor_street_address', true ), 0, 35 ),
				'',
			), ',', '|' );

			// Additional Payee Address Record
			fputcsv( $report, array( 'ADDPYE', '', '' ), ',', '|' );

			// Payee Postal Record
			fputcsv( $report, array(
				'PYEPOS',
				substr( get_post_meta( $post->ID, '_camppayments_vendor_city', true ), 0, 35 ),
				substr( get_post_meta( $post->ID, '_camppayments_vendor_state', true ), 0, 35 ),
				substr( get_post_meta( $post->ID, '_camppayments_vendor_zip_code', true ), 0, 10 ),
				substr( $vendor_country_code, 0, 3 ),
			), ',', '|' );

			// Payment Description
			fputcsv( $report, array(
				'PYTDES',
				substr( $description, 0, 122 ),
			), ',', '|' );

			restore_current_blog();
		}

		// File Trailer
		fputcsv( $report, array( 'FILTRL', $count * 6 + 2 ), ',', '|' );

		// Update counter and unlock
		$start = absint( get_site_option( '_wcb_jpm_checks_counter', 0 ) );
		update_site_option( '_wcb_jpm_checks_counter', $start + $count );
		delete_site_transient( '_wcb_jpm_checks_counter_lock' );

		fclose( $report );
		return ob_get_clean();
	}

	/**
	 * NACHA via JP Morgan
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected static function _generate_payment_report_jpm_ach( $args ) {
		$args = wp_parse_args( $args, array(
			'request_indexes' => array(),
			'status' => '',
		) );

		$ach_options = apply_filters( 'wcb_payment_req_ach_options', array(
			'bank-routing-number' => '', // Immediate Destination (bank routing number)
			'company-id'          => '', // Company ID
			'financial-inst'      => '', // Originating Financial Institution
		) );

		ob_start();

		// File Header Record

		echo '1'; // Record Type Code
		echo '01'; // Priority Code
		echo ' ' . str_pad( substr( $ach_options['bank-routing-number'], 0, 9 ), 9, '0', STR_PAD_LEFT );
		echo str_pad( substr( $ach_options['company-id'], 0, 10 ), 10, '0', STR_PAD_LEFT ); // Immediate Origin (TIN)
		echo date( 'ymd' ); // Transmission Date
		echo date( 'Hi' ); // Transmission Time
		echo 'A'; // File ID Modifier
		echo '094'; // Record Size
		echo '10'; // Blocking Factor
		echo '1'; // Format Code
		echo str_pad( 'JPMORGANCHASE', 23 ); // Destination
		echo str_pad( 'WCEXPORT', 23 ); // Origin
		echo str_pad( '', 8 ); // Reference Code (optional)
		echo PHP_EOL;

		// Batch Header Record

		echo '5'; // Record Type Code
		echo '200'; // Service Type Code
		echo 'WordCamp Communi'; // Company Name
		echo str_pad( '', 20 ); // Blanks
		echo str_pad( substr( $ach_options['company-id'], 0, 10 ), 10 ); // Company Identification

		// Get the first one in the set.
		// @todo Split batches by account type.
		foreach ( $args['request_indexes'] as $index ) {
			switch_to_blog( $index->blog_id );
			$post = get_post( $index->post_id );
			$account_type = get_post_meta( $post->ID, '_camppayments_ach_account_type', true );
			restore_current_blog();

			break;
		}

		$entry_class = $account_type == 'Personal' ? 'PPD' : 'CCD';
		echo $entry_class; // Standard Entry Class

		echo 'Vendor Pay'; // Entry Description
		echo date( 'ymd', self::_next_business_day_timestamp() ); // Company Description Date
		echo date( 'ymd', self::_next_business_day_timestamp() ); // Effective Entry Date
		echo str_pad( '', 3 ); // Blanks
		echo '1'; // Originator Status Code
		echo str_pad( substr( $ach_options['financial-inst'], 0, 8 ), 8 ); // Originating Financial Institution
		echo '0000001'; // Batch Number
		echo PHP_EOL;

		$count = 0;
		$total = 0;
		$hash = 0;

		foreach ( $args['request_indexes'] as $index ) {
			switch_to_blog( $index->blog_id );
			$post = get_post( $index->post_id );

			if ( $args['status'] && $post->post_status != $args['status'] )
				continue;

			if ( get_post_meta( $post->ID, '_camppayments_payment_method', true ) != 'Direct Deposit' )
				continue;

			$count++;

			// Entry Detail Record

			echo '6'; // Record Type Code

			$transaction_code = $account_type == 'Personal' ? '27' : '22';
			echo $transaction_code; // Transaction Code

			// Transit/Routing Number of Destination Bank + Check digit
			$routing_number = get_post_meta( $post->ID, '_camppayments_ach_routing_number', true );
			$routing_number = WCP_Encryption::maybe_decrypt( $routing_number );
			$routing_number = substr( $routing_number, 0, 8 + 1 );
			$routing_number = str_pad( $routing_number, 8 + 1 );
			$hash += absint( substr( $routing_number, 0, 8 ) );
			echo $routing_number;

			// Bank Account Number
			$account_number = get_post_meta( $post->ID, '_camppayments_ach_account_number', true );
			$account_number = WCP_Encryption::maybe_decrypt( $account_number );
			$account_number = substr( $account_number, 0, 17 );
			$account_number = str_pad( $account_number, 17 );
			echo $account_number;

			// Amount
			$amount = round( floatval( get_post_meta( $post->ID, '_camppayments_payment_amount', true ) ), 2 );
			$total += $amount;
			$amount = str_pad( number_format( $amount, 2, '', '' ), 10, '0', STR_PAD_LEFT );
			echo $amount;

			// Individual Identification Number
			echo str_pad( sprintf( '%d-%d', $index->blog_id, $index->post_id ), 15 );

			// Individual Name
			$name = get_post_meta( $post->ID, '_camppayments_ach_account_holder_name', true );
			$name = WCP_Encryption::maybe_decrypt( $name );
			$name = substr( $name, 0, 22 );
			$name = str_pad( $name, 22 );
			echo $name;

			echo '  '; // User Defined Data
			echo '0'; // Addenda Record Indicator

			// Trace Number
			echo str_pad( substr( $ach_options['bank-routing-number'], 0, 8 ), 8, '0', STR_PAD_LEFT ); // routing number
			echo str_pad( $count, 7, '0', STR_PAD_LEFT ); // sequence number
			echo PHP_EOL;
		}

		// Batch Trailer Record

		echo '8'; // Record Type Code
		echo '200'; // Service Class Code
		echo str_pad( $count, 6, '0', STR_PAD_LEFT ); // Entry/Addenda Count
		echo str_pad( substr( $hash, -10 ), 10, '0', STR_PAD_LEFT ); // Entry Hash
		echo str_pad( number_format( $total, 2, '', '' ), 12, '0', STR_PAD_LEFT ); // Total Debit Entry Dollar Amount
		echo str_pad( 0, 12, '0', STR_PAD_LEFT ); // Total Credit Entry Dollar Amount
		echo str_pad( substr( $ach_options['company-id'], 0, 10 ), 10 ); // Company ID
		echo str_pad( '', 25 ); // Blanks
		echo str_pad( substr( $ach_options['financial-inst'], 0, 8 ), 8 ); // Originating Financial Institution
		echo '0000001'; // Batch Number
		echo PHP_EOL;


		// File Trailer Record

		echo '9'; // Record Type Code
		echo '000001'; // Batch Count
		echo str_pad( ceil( $count / 10 ), 6, '0', STR_PAD_LEFT ); // Block Count
		echo str_pad( $count, 8, '0', STR_PAD_LEFT ); // Entry/Addenda Count
		echo str_pad( substr( $hash, -10 ), 10, '0', STR_PAD_LEFT ); // Entry Hash
		echo str_pad( number_format( $total, 2, '', '' ), 12, '0', STR_PAD_LEFT ); // Total Debit Entry Dollar Amount
		echo str_pad( 0, 12, '0', STR_PAD_LEFT ); // Total Credit Entry Dollar Amount
		echo str_pad( '', 39 ); // Blanks
		echo PHP_EOL;

		// The file must have a number of lines that is a multiple of 10 (e.g. 10, 20, 30).
		echo str_repeat( PHP_EOL, 10 - ( ( 4 + $count ) % 10 ) - 1 );
		return ob_get_clean();
	}

	/**
	 * Exclude weekends and JPM holidays.
	 *
	 * Needs to be updated every year.
	 *
	 * @return int Timestamp.
	 */
	private static function _next_business_day_timestamp() {
		static $timestamp;

		if ( isset( $timestamp ) )
			return $timestamp;

		$holidays = array(
			date( 'Ymd', strtotime( 'Friday, January 1, 2016' ) ),
			date( 'Ymd', strtotime( 'Monday, January 18, 2016' ) ),
			date( 'Ymd', strtotime( 'Monday, February 15, 2016' ) ),
			date( 'Ymd', strtotime( 'Monday, May 30, 2016' ) ),
			date( 'Ymd', strtotime( 'Monday, July 4, 2016' ) ),
			date( 'Ymd', strtotime( 'Monday, September 5, 2016' ) ),
			date( 'Ymd', strtotime( 'Friday, November 11, 2016' ) ),
			date( 'Ymd', strtotime( 'Thursday, November 24, 2016' ) ),
			date( 'Ymd', strtotime( 'Monday, December 26, 2016' ) ),
		);

		$timestamp = strtotime( 'today + 1 weekday' );
		$attempts = 5;

		while ( in_array( date( 'Ymd', $timestamp ), $holidays ) ) {
			$timestamp = strtotime( '+ 1 weekday', $timestamp );
			$attempts--;

			if ( ! $attempts )
				break;
		}

		return $timestamp;
	}

	/**
	 * Wires via JP Morgan
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected static function _generate_payment_report_jpm_wires( $args ) {
		$args = wp_parse_args( $args, array(
			'request_indexes' => array(),
			'status' => '',
		) );

		ob_start();
		$report = fopen( 'php://output', 'w' );

		// JPM Header
		fputcsv( $report, array( 'HEADER', gmdate( 'YmdHis' ), '1' ) );

		$total = 0;
		$count = 0;

		foreach ( $args['request_indexes'] as $index ) {
			switch_to_blog( $index->blog_id );
			$post = get_post( $index->post_id );

			if ( $args['status'] && $post->post_status != $args['status'] )
				continue;

			// Only wires here.
			if ( get_post_meta( $post->ID, '_camppayments_payment_method', true ) != 'Wire' )
				continue;

			$amount = round( floatval( get_post_meta( $post->ID, '_camppayments_payment_amount', true ) ), 2);
			$total += $amount;
			$count += 1;

			// If account starts with two letters, it's most likely an IBAN
			$account = get_post_meta( $post->ID, '_camppayments_beneficiary_account_number', true );
			$account = WCP_Encryption::maybe_decrypt( $account );
			$account = preg_replace( '#\s#','', $account );
			$account_type = preg_match( '#^[a-z]{2}#i', $account ) ? 'IBAN' : 'ACCT';

			$row = array(
				'1-input-type' => 'P',
				'2-payment-method' => 'WIRES',
				'3-debit-bank-id' => apply_filters( 'wcb_payment_req_bank_id', '' ), // external file
				'4-account-number' => apply_filters( 'wcb_payment_req_bank_number', '' ), // external file
				'5-bank-to-bank' => 'N',
				'6-txn-currency' => get_post_meta( $post->ID, '_camppayments_currency', true ),
				'7-txn-amount' => $amount,
				'8-equiv-amount' => '',
				'9-clearing' => '',
				'10-ben-residence' => '',
				'11-rate-type' => '',
				'12-blank' => '',
				'13-value-date' => '',

				'14-id-type' => $account_type,
				'15-id-value' => $account,
				'16-ben-name' => substr( WCP_Encryption::maybe_decrypt(
					get_post_meta( $post->ID, '_camppayments_beneficiary_name', true ) ), 0, 35 ),
				'17-address-1' => substr( WCP_Encryption::maybe_decrypt(
					get_post_meta( $post->ID, '_camppayments_beneficiary_street_address', true ) ), 0, 35 ),
				'18-address-2' => '',
				'19-city-state-zip' => substr( sprintf( '%s %s %s',
						WCP_Encryption::maybe_decrypt( get_post_meta( $post->ID, '_camppayments_beneficiary_city', true ) ),
						WCP_Encryption::maybe_decrypt( get_post_meta( $post->ID, '_camppayments_beneficiary_state', true ) ),
						WCP_Encryption::maybe_decrypt( get_post_meta( $post->ID, '_camppayments_beneficiary_zip_code', true ) )
					), 0, 32 ),
				'20-blank' => '',
				'21-country' => WCP_Encryption::maybe_decrypt(
					get_post_meta( $post->ID, '_camppayments_beneficiary_country_iso3166', true ) ),
				'22-blank' => '',
				'23-blank' => '',

				'24-id-type' => 'SWIFT',
				'25-id-value' => get_post_meta( $post->ID, '_camppayments_bank_bic', true ),
				'26-ben-bank-name' => substr( get_post_meta( $post->ID, '_camppayments_bank_name', true ), 0, 35 ),
				'27-ben-bank-address-1' => substr( get_post_meta( $post->ID, '_camppayments_bank_street_address', true ), 0, 35 ),
				'28-ben-bank-address-2' => '',
				'29-ben-bank-address-3' => substr( sprintf( '%s %s %s',
						get_post_meta( $post->ID, '_camppayments_bank_city', true ),
						get_post_meta( $post->ID, '_camppayments_bank_state', true ),
						get_post_meta( $post->ID, '_camppayments_bank_zip_code', true )
			 		), 0, 35 ),
				'30-ben-bank-country' => get_post_meta( $post->ID, '_camppayments_bank_country_iso3166', true ),
				'31-supl-id-type' => '',
				'32-supl-id-value' => '',

				'33-blank' => '',
				'34-blank' => '',
				'35-blank' => '',
				'36-blank' => '',
				'37-blank' => '',
				'38-blank' => '',
				'39-blank' => '',

				// Filled out later if not empty.
				'40-id-type' => '',
				'41-id-value' => '',
				'42-interm-bank-name' => '',
				'43-interm-bank-address-1' => '',
				'44-interm-bank-address-2' => '',
				'45-interm-bank-address-3' => '',
				'46-interm-bank-country' => '',
				'47-supl-id-type' => '',
				'48-supl-id-value' => '',

				'49-id-type' => '',
				'50-id-value' => '',
				'51-party-name' => '',
				'52-party-address-1' => '',
				'53-party-address-2' => '',
				'54-party-address-3' => '',
				'55-party-country' => '',

				'56-blank' => '',
				'57-blank' => '',
				'58-blank' => '',
				'59-blank' => '',
				'60-blank' => '',
				'61-blank' => '',
				'62-blank' => '',
				'63-blank' => '',
				'64-blank' => '',
				'65-blank' => '',
				'66-blank' => '',
				'67-blank' => '',
				'68-blank' => '',
				'69-blank' => '',
				'70-blank' => '',
				'71-blank' => '',
				'72-blank' => '',
				'73-blank' => '',

				'74-ref-text' => substr( get_post_meta( $post->ID, '_camppayments_invoice_number', true ), 0, 16 ),
				'75-internal-ref' => '',
				'76-on-behalf-of' => '',

				'77-detial-1' => '',
				'78-detial-2' => '',
				'79-detial-3' => '',
				'80-detail-4' => '',

				'81-blank' => '',
				'82-blank' => '',
				'83-blank' => '',
				'84-blank' => '',
				'85-blank' => '',
				'86-blank' => '',
				'87-blank' => '',
				'88-blank' => '',

				'89-reporting-code' => '',
				'90-country' => '',
				'91-inst-1' => '',
				'92-inst-2' => '',
				'93-inst-3' => '',
				'94-inst-code-1' => '',
				'95-inst-text-1' => '',
				'96-inst-code-2' => '',
				'97-inst-text-2' => '',
				'98-inst-code-3' => '',
				'99-inst-text-3' => '',

				'100-stor-code-1' => '',
				'101-stor-line-2' => '', // Hmm?
				'102-stor-code-2' => '',
				'103-stor-line-2' => '',
				'104-stor-code-3' => '',
				'105-stor-line-3' => '',
				'106-stor-code-4' => '',
				'107-stor-line-4' => '',
				'108-stor-code-5' => '',
				'109-stor-line-5' => '',
				'110-stor-code-6' => '',
				'111-stor-line-6' => '',

				'112-priority' => '',
				'113-blank' => '',
				'114-charges' => '',
				'115-blank' => '',
				'116-details' => '',
				'117-note' => substr( sprintf( 'wcb-%d-%d', $index->blog_id, $index->post_id ), 0, 70 ),
			);

			// If an intermediary bank is given.
			$interm_swift = get_post_meta( $post->ID, '_camppayments_interm_bank_swift', true );
			if ( ! empty( $iterm_swift ) ) {
				$row['40-id-type'] = 'SWIFT';
				$row['41-id-value'] = $interm_swift;

				$row['42-interm-bank-name'] = substr( get_post_meta( $post->ID, '_camppayments_interm_bank_name', true ), 0, 35 );
				$row['43-interm-bank-address-1'] = substr( get_post_meta( $post->ID, '_camppayments_interm_bank_street_address', true ), 0, 35 );

				$row['44-interm-bank-address-2'] = '';
				$row['45-interm-bank-address-3'] = substr( sprintf( '%s %s %s',
					get_post_meta( $post->ID, '_camppayments_interm_bank_city', true ),
					get_post_meta( $post->ID, '_camppayments_interm_bank_state', true ),
					get_post_meta( $post->ID, '_camppayments_interm_bank_zip_code', true )
				), 0, 32 );

				$row['46-interm-bank-country'] = get_post_meta( $post->ID, '_camppayments_interm_bank_country_iso3166', true );

				$row['47-supl-id-type'] = 'ACCT';
				$row['48-supl-id-value'] = get_post_meta( $post->ID, '_camppayments_interm_bank_account', true );
			}

			// Because CSV is stupid:
			// print_r( $row );

			fputcsv( $report, array_values( $row ) );
			restore_current_blog();
		}

		// JPM Trailer
		fputcsv( $report, array( 'TRAILER', $count, $total ) );

		fclose( $report );
		$results = ob_get_clean();

		// JPM chokes on accents and non-latin characters.
		$results = remove_accents( $results );
		return $results;
	}

	/**
	 * Gather all the request details needed for a row in the export file
	 *
	 * @param stdClass $index
	 * @param array $args
	 *
	 * @return array
	 */
	protected static function get_report_row( $index, $args ) {
		switch_to_blog( $index->blog_id );

		$request = get_post( $index->post_id );

		$back_compat_statuses = array(
			'unpaid' => 'draft',
			'incomplete' => 'wcb-incomplete',
			'paid' => 'wcb-paid',
		);

		// Map old statuses to new statuses.
		if ( array_key_exists( $request->post_status, $back_compat_statuses ) ) {
			$request->post_status = $back_compat_statuses[ $request->post_status ];
		}

		if ( $args['status'] && $request->post_status != $args['status'] ) {
			return null;
		}

		$currency         = get_post_meta( $index->post_id, '_camppayments_currency',         true );
		$category         = get_post_meta( $index->post_id, '_camppayments_payment_category', true );
		$date_vendor_paid = get_post_meta( $index->post_id, '_camppayments_date_vendor_paid', true );

		if ( $date_vendor_paid ) {
			$date_vendor_paid = date( 'Y-m-d', $date_vendor_paid );
		}

		if ( 'null-select-one' === $currency ) {
			$currency = '';
		}

		if ( 'null' === $category ) {
			$category = '';
		}

		$row = array(
			get_wordcamp_name(),
			sprintf( '%d-%d', $index->blog_id, $index->post_id ),
			$request->post_title,
			$index->status,
			$date_vendor_paid,
			date( 'Y-m-d', $index->created ),
			date( 'Y-m-d', $index->due ),
			get_post_meta( $index->post_id, '_camppayments_payment_amount', true ),
			$currency,
			$category,
			get_post_meta( $index->post_id, '_camppayments_payment_method', true ),
			get_post_meta( $index->post_id, '_camppayments_vendor_name', true ),
			get_post_meta( $index->post_id, '_camppayments_vendor_contact_person', true ),
			get_post_meta( $index->post_id, '_camppayments_vendor_country', true ),
			WCP_Encryption::maybe_decrypt( get_post_meta( $index->post_id, '_camppayments_payable_to', true ) ),
			get_edit_post_link( $index->post_id ),
			get_post_meta( $index->post_id, '_camppayments_file_notes', true ),
		);

		restore_current_blog();

		return $row;
	}

	/**
	 * Render the Export tab
	 */
	protected static function render_export_tab() {
		$today      = date( 'Y-m-d' );
		$last_month = date( 'Y-m-d', strtotime( 'now - 1 month' ) );
		?>

		<script>
			/**
			 * Fallback to the jQueryUI datepicker if the browser doesn't support <input type="date">
			 */
			jQuery( document ).ready( function( $ ) {
				var browserTest = document.createElement( 'input' );
				browserTest.setAttribute( 'type', 'date' );

				if ( 'text' === browserTest.type ) {
					$( '#wcpn_export' ).find( 'input[type=date]' ).datepicker( {
						dateFormat : 'yy-mm-dd',
						changeMonth: true,
						changeYear : true
					} );
				}
			} );
		</script>

		<form id="wcpn_export" method="POST">
			<?php wp_nonce_field( 'export', 'wcpn_request_export' ); ?>

			<h2>Export Settings</h2>

			<table class="form-table">
				<tr>
					<th><label>Status</label></th>
					<td>
						<select name="wcpn_export_status">
							<option value="wcb-approved"><?php _e( 'Approved', 'wordcamporg' ); ?></option>
							<option value="wcb-paid"><?php _e( 'Paid', 'wordcamporg' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label>Date Range</label></th>
					<td>
						<input type="date" name="wcpn_export_start_date" class="medium-text" value="<?php echo esc_attr( $last_month ); ?>" /> to
						<input type="date" name="wcpn_export_end_date" class="medium-text" value="<?php echo esc_attr( $today ); ?>" />
					</td>
				</tr>
				<tr>
					<th><label>Format</label></th>
					<td>
						<select name="wcpn_export_type">
							<?php foreach ( self::get_export_types() as $key => $export_type ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $export_type['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Download Export' ); ?>
		</form>

		<?php
	}

	/**
	 * Renders the import tab.
	 */
	public static function render_import_tab() {
		?>
		<?php if ( isset( self::$import_results ) ) : ?>
		<h2>Import Results</h2>
		<pre><?php echo esc_html( print_r( self::$import_results, true ) ); ?></pre>
		<?php endif; ?>

		<p>Import payment results from JPM reports CSV.</p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'import', 'wcpn_request_import' ); ?>
			<label>Import File:</label>
			<input type="file" name="wcpn_import_file" />
			<?php submit_button( 'Import' ); ?>
		</form>
		<?php
	}

	/**
	 * Process a payments import, runs during init.
	 */
	public static function process_import_request() {
		if ( empty( $_POST['submit'] ) || 'import' != self::get_current_tab() ) {
			return;
		}

		if ( ! current_user_can( 'manage_network' ) || ! check_admin_referer( 'import', 'wcpn_request_import' ) ) {
			return;
		}

		if ( empty( $_FILES['wcpn_import_file'] ) ) {
			wp_die( 'Please select a file to import.' );
		}

		$file = $_FILES['wcpn_import_file'];
		if ( $file['type'] != 'text/csv' ) {
			wp_die( 'Please upload a text/csv file.' );
		}

		if ( $file['size'] < 1 ) {
			wp_die( 'Please upload a file that is not empty.' );
		}

		if ( $file['error'] ) {
			wp_die( 'Some other error has occurred. Sorry.' );
		}

		$handle = fopen( $file['tmp_name'], 'r' );
		$count = 0;
		$header = array();
		$results = array();

		while ( ( $line = fgetcsv( $handle ) ) !== false ) {
			// Skip first line.
			if ( ++$count == 1 ) {
				continue;
			}

			$entry = array(
				'type' => strtolower( $line[11] ),
				'status' => strtolower( $line[7] ),
				'amount' => round( floatval( $line[13] ), 2 ),
				'currency' => strtoupper( $line[14] ),
				'blog_id' => null,
				'post_id' => null,
				'processed' => false,
				'data' => null,
			);

			switch ( $entry['type'] ) {
				case 'wire':
					if ( ! empty( $line[44] ) && preg_match( '#^wcb-([0-9]+)-([0-9]+)$#', $line[44], $matches ) ) {
						$entry['blog_id'] = $matches[1];
						$entry['post_id'] = $matches[2];
					}
					break;
				case 'ach':
					if ( ! empty( $line[91] ) && preg_match( '#^([0-9]+)-([0-9]+)$#', $line[91], $matches ) ) {
						$entry['blog_id'] = $matches[1];
						$entry['post_id'] = $matches[2];
					}
					break;
			}

			if ( empty( $entry['blog_id'] ) || empty( $entry['post_id'] ) ) {
				$results[] = $entry;
				continue;
			}

			// Don't consume memory.
			wp_suspend_cache_addition( true );
			switch_to_blog( $entry['blog_id'] );

			$results[] = self::_import_process_entry( $entry );

			restore_current_blog();
			wp_suspend_cache_addition( false );
		}

		fclose( $handle );
		self::$import_results = $results;
	}

	/**
	 * Process a single import entry.
	 *
	 * Runs in a switch_to_blog() context.
	 *
	 * @param $entry Array
	 * @return Array
	 */
	private static function _import_process_entry( $entry ) {
		$post = get_post( $entry['post_id'] );
		if ( ! $post || $post->post_type != 'wcp_payment_request' ) {
			$entry['data'] = 'Post not found or post type mismatch';
			return $entry;
		}

		if ( $entry['currency'] != get_post_meta( $post->ID, '_camppayments_currency', true ) ) {
			$entry['data'] = 'Currency mismatch';
			return $entry;
		}

		$amount_orig = floatval( get_post_meta( $post->ID, '_camppayments_payment_amount', true ) );
		$amount_orig = round( $amount_orig, 2 );
		if ( (string) $entry['amount'] != (string) $amount_orig ) {
			$entry['data'] = 'Payment amount mismatch';
			return $entry;
		}

		// @todo Do some magic here.

		// All good.
		$entry['processed'] = true;
		return $entry;
	}

	/**
	 * Loads and initializes the list table object.
	 */
	public static function pre_render_dashboard() {
		require_once( __DIR__ . '/payment-requests-list-table.php' );

		self::$list_table = new Payment_Requests_List_Table();
	}

	/**
	 * Returns the current active tab in the UI.
	 */
	public static function get_current_tab() {
		$tab = 'overdue';
		$tabs = array(
			'drafts',
			'overdue',

			'pending-approval',
			'approved',
			'pending-payment',
			'paid',
			'cancelled-failed',
			'incomplete',

			'export',
			'import',
		);

		if ( isset( $_REQUEST['wcp-section'] ) && in_array( $_REQUEST['wcp-section'], $tabs ) ) {
			$tab = $_REQUEST['wcp-section'];
		}

		return $tab;
	}

	/**
	 * Renders available tabs.
	 */
	public static function render_dashboard_tabs() {
		$current_section = self::get_current_tab();
		$sections = array(
			'drafts'           => __( 'Drafts', 'wordcamporg' ),
			'overdue'          => __( 'Overdue', 'wordcamporg' ), // pending-approval + after due date
			'pending-approval' => __( 'Pending Approval', 'wordcamporg' ),
			'approved'         => __( 'Approved', 'wordcamporg' ),
			'pending-payment'  => __( 'Pending Payment', 'wordcamporg' ),
			'paid'             => __( 'Paid', 'wordcamporg' ),
			'cancelled-failed' => __( 'Cancelled/Failed', 'wordcamporg' ),
			'incomplete'       => __( 'Incomplete', 'wordcamporg' ),
			'export'           => __( 'Export', 'wordcamporg' ),
			'import'           => __( 'Import', 'wordcamporg' ),
		);

		foreach ( $sections as $section_key => $section_caption ) {
			$active = $current_section === $section_key ? 'nav-tab-active' : '';
			$url = add_query_arg( array(
				'wcp-section' => $section_key,
				'page' => 'wcp-dashboard',
			), network_admin_url( 'admin.php' ) );
			echo '<a class="nav-tab ' . $active . '" href="' . esc_url( $url ) . '">' . esc_html( $section_caption ) . '</a>';
		}
	}
}
