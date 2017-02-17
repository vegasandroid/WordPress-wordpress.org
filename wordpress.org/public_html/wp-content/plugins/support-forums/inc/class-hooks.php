<?php

namespace WordPressdotorg\Forums;

class Hooks {

	public function __construct() {
		// Basic behavior filters and actions.
		add_filter( 'bbp_get_forum_pagination_count', '__return_empty_string' );

		// Display-related filters and actions.
		add_filter( 'bbp_get_topic_admin_links', array( $this, 'get_admin_links' ), 10, 3 );
		add_filter( 'bbp_get_reply_admin_links', array( $this, 'get_admin_links' ), 10, 3 );

		// Gravatar suppression on lists of topics.
		add_filter( 'bbp_after_get_topic_author_link_parse_args', array( $this, 'get_author_link' ) );
		add_filter( 'bbp_after_get_reply_author_link_parse_args', array( $this, 'get_author_link' ) );

		// oEmbed.
		add_filter( 'oembed_discovery_links', array( $this, 'disable_oembed_discovery_links' ) );
		add_filter( 'oembed_response_data', array( $this, 'disable_oembed_response_data' ), 10, 2 );
		add_filter( 'embed_oembed_discover', '__return_false' );

		// Disable inline terms and mentions.
		add_action( 'plugins_loaded', array( $this, 'disable_inline_terms' ) );

		// Add notice to reply forms for privileged users in closed forums.
		add_action( 'bbp_template_notices', array( $this, 'closed_forum_notice_for_moderators' ), 1 );

		// Fix login url links
		add_filter( 'login_url', array( $this, 'fix_login_url' ), 10, 3 );

		// Limit no-replies view to certain number of days.
		add_filter( 'bbp_register_view_no_replies', array( $this, 'limit_no_replies_view' ) );

		// Add extra reply actions before Submit button in reply form.
		add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $this, 'add_extra_reply_actions' ) );

		// Process extra reply actions.
		add_action( 'bbp_new_reply',  array( $this, 'handle_extra_reply_actions' ), 10, 2 );
		add_action( 'bbp_edit_reply', array( $this, 'handle_extra_reply_actions' ), 10, 2 );
	}

	/**
	 * Remove "Trash" from admin links. Trashing a topic or reply will eventually
	 * permanently delete it when the trash is emptied. Better to mark it as
	 * pending or spam.
	 */
	public function get_admin_links( $retval, $r, $args ) {
		unset( $r['links']['trash'] );

		$links = implode( $r['sep'], array_filter( $r['links'] ) );
		$retval = $r['before'] . $links . $r['after'];

		return $retval;
	}

	/**
	 * Suppress Gravatars on lists of topics.
	 */
	public function get_author_link( $r ) {
		if ( ! bbp_is_single_topic() || bbp_is_topic_edit() ) {
			$r['type'] = 'name';
		}
		return $r;
	}

	/**
	 * Removes oEmbed discovery links for bbPress' post types.
	 *
	 * @param string $output HTML of the discovery links.
	 * @return string Empty string for bbPress' post types, HTML otherwise.
	 */
	public function disable_oembed_discovery_links( $output ) {
		$post_type = get_post_type();
		if ( $post_type && in_array( $post_type, [ bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() ] ) ) {
			return '';
		}

		return $output;
	}

	/**
	 * Prevents retrieving oEmbed data for bbPress' post types.
	 *
	 * @param array   $data The response data.
	 * @param WP_Post $post The post object.
	 * @return array|false False for bbPress' post types, array otherwise.
	 */
	public function disable_oembed_response_data( $data, $post ) {
		if ( in_array( $post->post_type, [ bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() ] ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Disable the inline terms and mentions, if they are enabled.
	 * Inline terms and mentions are for O2 and should not be running on the support forums.
	 * If this plugin is moved out of mu-plugins, this function can be removed as well.
	 *
	 * This fixes the post editing screens in the admin area on the support forums.
	 */
	public function disable_inline_terms() {
		remove_action( 'init', array( 'Jetpack_Inline_Terms', 'init' ) );
		remove_action( 'init', array( 'Jetpack_Mentions', 'init' ) );
	}

	/**
	 * For closed forums, adds a notice to privileged users indicating that
	 * though the reply form is available, the forum is closed.
	 *
	 * Otherwise, unless the topic itself is closed, there is no indication that
	 * the reply form is only available because of their privileged capabilities.
	 */
	public function closed_forum_notice_for_moderators() {
		if (
			is_single()
			&&
			bbp_current_user_can_access_create_reply_form()
			&&
			bbp_is_forum_closed( bbp_get_topic_forum_id() )
			&&
			! bbp_is_reply_edit()
		) {
			$err_msg = sprintf( esc_html__(
				'The forum &#8216;%s&#8217; is closed to new topics and replies, however your posting capabilities still allow you to do so.',
				'wporg-forums'),
				bbp_get_forum_title( bbp_get_topic_forum_id() )
			);

			bbp_add_error( 'bbp_forum_is_closed', $err_msg, 'message' );
		}
	}

	/**
	 * Adjust the login URL to point back to whatever part of the support forums we're
	 * currently looking at. This allows the redirect to come back to the same place
	 * instead of the main /support URL by default.
	 */
	public function fix_login_url( $login_url, $redirect, $force_reauth ) {
		// modify the redirect_to for the support forums to point to the current page
		if ( 0 === strpos($_SERVER['REQUEST_URI'], '/support' ) ) {
			// Note that this is not normal because of the code in /mu-plugins/wporg-sso/class-wporg-sso.php.
			// The login_url function there expects the redirect_to as the first parameter passed into it instead of the second
			// Since we're changing this with a filter on login_url, then we have to change the login_url to the
			// place we want to redirect instead, and then let the SSO plugin do the rest.
			//
			// If the SSO code gets fixed, this will need to be modified.
			//
			// parse_url is used here to remove any additional query args from the REQUEST_URI before redirection
			// The SSO code handles the urlencoding of the redirect_to parameter
			$url_parts = parse_url('https://wordpress.org'.$_SERVER['REQUEST_URI']);
			$constructed_url = $url_parts['scheme'] . '://' . $url_parts['host'] . (isset($url_parts['path'])?$url_parts['path']:'');
			$login_url = $constructed_url;
		}
		return $login_url;
	}

	/**
	 * Limits No Replies view to 21 days by default.
	 *
	 * @param array $args Array of query args for the view.
	 * @return array
	 */
	public function limit_no_replies_view( $args ) {
		$days = 21;

		if ( isset( $_GET['days'] ) ) {
			$days = (int) $_GET['days'];
		}

		$args['date_query'] = array(
			array(
				'after'  => sprintf( '%s days ago', $days ),
			),
		);

		return $args;
	}

	/**
	 * Add extra reply actions before Submit button in reply form.
	 */
	public function add_extra_reply_actions() {
		if ( class_exists( 'WordPressdotorg\Forums\Topic_Resolution\Plugin' ) ) :
			$topic_resolution_plugin = Topic_Resolution\Plugin::get_instance();

			if ( $topic_resolution_plugin->is_enabled_on_forum() && $topic_resolution_plugin->user_can_resolve( get_current_user_id(), bbp_get_topic_id() ) ) : ?>
				<p>
					<input name="bbp_reply_mark_resolved" id="bbp_reply_mark_resolved" type="checkbox" value="yes" />
					<label for="bbp_reply_mark_resolved"><?php esc_html_e( 'Reply and mark as resolved', 'wporg-forums' ); ?></label>
				</p>
				<?php
			endif;
		endif;

		if ( current_user_can( 'moderate', bbp_get_topic_id() ) ) : ?>
			<p>
				<input name="bbp_reply_close_topic" id="bbp_reply_close_topic" type="checkbox" value="yes" />
				<label for="bbp_reply_close_topic"><?php esc_html_e( 'Reply and close the topic', 'wporg-forums' ); ?></label>
			</p>
			<?php
		endif;
	}

	/**
	 * Process extra reply actions.
	 *
	 * @param int $reply_id Reply ID.
	 * @param int $topic_id Topic ID.
	 */
	public function handle_extra_reply_actions( $reply_id, $topic_id ) {
		// Handle "Reply and mark as resolved" checkbox
		if ( isset( $_POST['bbp_reply_mark_resolved'] ) && 'yes' === $_POST['bbp_reply_mark_resolved'] ) {
			if ( class_exists( 'WordPressdotorg\Forums\Topic_Resolution\Plugin' ) ) {
				$topic_resolution_plugin = Topic_Resolution\Plugin::get_instance();

				$plugin_enabled   = $topic_resolution_plugin->is_enabled_on_forum( bbp_get_topic_forum_id( $topic_id ) );
				$user_can_resolve = $topic_resolution_plugin->user_can_resolve( get_current_user_id(), $topic_id );

				if ( $plugin_enabled && $user_can_resolve ) {
					$topic_resolution_plugin->set_topic_resolution( array(
						'id'         => $topic_id,
						'resolution' => 'yes',
					) );
				}
			}
		}

		// Handle "Reply and close the topic" checkbox
		if ( isset( $_POST['bbp_reply_close_topic'] ) && 'yes' === $_POST['bbp_reply_close_topic'] ) {
			if ( current_user_can( 'moderate', $topic_id ) && bbp_is_topic_open( $topic_id ) ) {
				bbp_close_topic( $topic_id );
			}
		}
	}

}
