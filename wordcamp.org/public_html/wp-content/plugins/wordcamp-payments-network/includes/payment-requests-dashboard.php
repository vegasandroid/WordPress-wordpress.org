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
		add_action( 'network_admin_menu', array( __CLASS__, 'network_admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'upgrade' ) );
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
				if ( 'import' == self::get_current_tab() ) {
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
