<?php
namespace WordPressdotorg\Plugin_Directory\Admin;
use \WordPressdotorg\Plugin_Directory;

/**
 * All functionality related to the Administration interface.
 *
 * @package WordPressdotorg\Plugin_Directory\Admin
 */
class Customizations {

	/**
	 * Fetch the instance of the Customizations class.
	 */
	public static function instance() {
		static $instance = null;

		return ! is_null( $instance ) ? $instance : $instance = new Customizations();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Admin Metaboxes
		add_action( 'add_meta_boxes', array( $this, 'register_admin_metaboxes' ), 10, 1 );
		add_action( 'do_meta_boxes', array( $this, 'replace_title_global' ) );

		add_action( 'save_post_plugin', array( $this, 'save_plugin_post' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_replyto-comment', array( $this, 'save_custom_comment' ), 0 );

		add_filter( 'postbox_classes_plugin_internal-notes', array( $this, 'postbox_classes' ) );
	}

	/**
	 * Adds the plugin name into the post editing title.
	 *
	 * @global string $title The wp-admin title variable.
	 *
	 * @param string $post_type The post type of the current page
	 * @return void.
	 */
	public function replace_title_global( $post_type ) {
		global $title;

		if ( 'plugin' === $post_type ) {
			$title = sprintf( $title, get_the_title() ); // esc_html() on output
		}
	}

	/**
	 * Enqueue JS and CSS assets needed for any wp-admin screens.
	 *
	 * @param string $hook_suffix The hook suffix of the current screen.
	 * @return void.
	 */
	public function enqueue_assets( $hook_suffix ) {
		global $post_type;

		if ( 'post.php' == $hook_suffix && 'plugin' == $post_type ) {
			wp_enqueue_style( 'plugin-admin-edit-css', plugins_url( 'css/edit-form.css', Plugin_Directory\PLUGIN_FILE ), array( 'edit' ), 1 );
			wp_enqueue_script( 'plugin-admin-edit-js', plugins_url( 'js/edit-form.js', Plugin_Directory\PLUGIN_FILE ), array( 'wp-util' ), 1 );
		}
	}

	/**
	 * Register the Admin metaboxes for the plugin management screens.
	 *
	 * @param string $post_type The post type of the current screen.
	 * @return void.
	 */
	public function register_admin_metaboxes( $post_type ) {
		if ( 'plugin' != $post_type ) {
			return;
		}

		add_meta_box(
			'plugin-committers',
			__( 'Plugin Committers', 'wporg-plugins' ),
			array( __NAMESPACE__ . '\Metabox\Committers', 'display' ),
			'plugin', 'side'
		);

		add_meta_box(
			'internal-notes',
			__( 'Internal Notes', 'wporg-plugins' ),
			array( __NAMESPACE__ . '\Metabox\Internal_Notes', 'display' ),
			'plugin', 'normal', 'high'
		);

		add_meta_box(
			'plugin-review',
			__( 'Plugin Review Tools', 'wporg-plugins' ),
			array( __NAMESPACE__ . '\Metabox\Review_Tools', 'display' ),
			'plugin', 'normal', 'high'
		);

		add_meta_box(
			'plugin-fields',
			__( 'Plugin Meta', 'wporg-plugins' ),
			array( __NAMESPACE__ . '\Metabox\Custom_Fields', 'display' ),
			'plugin', 'normal', 'low'
		);

		// Replace the publish box
		add_meta_box(
			'submitdiv',
			__( 'Plugin Controls', 'wporg-plugins' ),
			array( __NAMESPACE__ . '\Metabox\Controls', 'display' ),
			'plugin', 'side', 'high'
		);

		// Remove the Slug metabox
		add_meta_box( 'slugdiv', false, false, false );
	}

	/**
	 * Hook into the save process for the plugin post_type to save extra metadata.
	 *
	 * Currently saves the tested_with value.
	 *
	 * @param int      $post_id The post_id being updated.
	 * @param \WP_Post $post    The WP_Post object being updated.
	 */
	public function save_plugin_post( $post_id, $post ) {
		// Save meta information
		if ( isset( $_POST['tested_with'] ) && isset( $_POST['hidden_tested_with'] ) && $_POST['tested_with'] != $_POST['hidden_tested_with'] ) {
			update_post_meta( $post_id, 'tested', wp_slash( wp_unslash( $_POST['tested_with'] ) ) );
		}
	}

	/**
	 * Filters the postbox classes for custom comment meta boxes.
	 *
	 * @param array $classes An array of postbox classes.
	 * @return array
	 */
	public function postbox_classes( $classes ) {
		$classes[] = 'comments-meta-box';

		return array_filter( $classes );
	}

	/**
	 * Saves a comment that is not built-in.
	 *
	 * We pretty much have to replicate all of `wp_ajax_replyto_comment()` to be able to comment on draft posts.
	 */
	public function save_custom_comment() {
		$comment_post_ID = (int) $_POST['comment_post_ID'];
		$post            = get_post( $comment_post_ID );

		if ( 'plugin' !== $post->post_type ) {
			return;
		}
		remove_action( 'wp_ajax_replyto-comment', 'wp_ajax_replyto_comment', 1 );

		global $wp_list_table;
		if ( empty( $action ) ) {
			$action = 'replyto-comment';
		}

		check_ajax_referer( $action, '_ajax_nonce-replyto-comment' );

		if ( ! $post ) {
			wp_die( - 1 );
		}

		if ( ! current_user_can( 'edit_post', $comment_post_ID ) ) {
			wp_die( - 1 );
		}

		if ( empty( $post->post_status ) ) {
			wp_die( 1 );
		}

		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			wp_die( __( 'Sorry, you must be logged in to reply to a comment.' ) );
		}

		$user_ID              = $user->ID;
		$comment_author       = wp_slash( $user->display_name );
		$comment_author_email = wp_slash( $user->user_email );
		$comment_author_url   = wp_slash( $user->user_url );
		$comment_content      = trim( $_POST['content'] );
		$comment_type         = isset( $_POST['comment_type'] ) ? trim( $_POST['comment_type'] ) : '';

		if ( current_user_can( 'unfiltered_html' ) ) {
			if ( ! isset( $_POST['_wp_unfiltered_html_comment'] ) ) {
				$_POST['_wp_unfiltered_html_comment'] = '';
			}

			if ( wp_create_nonce( 'unfiltered-html-comment' ) != $_POST['_wp_unfiltered_html_comment'] ) {
				kses_remove_filters(); // start with a clean slate
				kses_init_filters(); // set up the filters
			}
		}

		if ( '' == $comment_content ) {
			wp_die( __( 'ERROR: please type a comment.' ) );
		}

		$comment_parent = 0;
		if ( isset( $_POST['comment_ID'] ) ) {
			$comment_parent = absint( $_POST['comment_ID'] );
		}
		$comment_auto_approved = false;
		$comment_data          = compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID' );

		// Automatically approve parent comment.
		if ( ! empty( $_POST['approve_parent'] ) ) {
			$parent = get_comment( $comment_parent );

			if ( $parent && $parent->comment_approved === '0' && $parent->comment_post_ID == $comment_post_ID ) {
				if ( ! current_user_can( 'edit_comment', $parent->comment_ID ) ) {
					wp_die( - 1 );
				}

				if ( wp_set_comment_status( $parent, 'approve' ) ) {
					$comment_auto_approved = true;
				}
			}
		}

		$comment_id = wp_new_comment( $comment_data );
		$comment    = get_comment( $comment_id );
		if ( ! $comment ) {
			wp_die( 1 );
		}

		$position = ( isset( $_POST['position'] ) && (int) $_POST['position'] ) ? (int) $_POST['position'] : '-1';

		ob_start();
		if ( isset( $_REQUEST['mode'] ) && 'dashboard' == $_REQUEST['mode'] ) {
			require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );
			_wp_dashboard_recent_comments_row( $comment );
		} else {
			if ( isset( $_REQUEST['mode'] ) && 'single' == $_REQUEST['mode'] ) {
				$wp_list_table = _get_list_table( 'WP_Post_Comments_List_Table', array( 'screen' => 'edit-comments' ) );
			} else {
				$wp_list_table = _get_list_table( 'WP_Comments_List_Table', array( 'screen' => 'edit-comments' ) );
			}
			$wp_list_table->single_row( $comment );
		}
		$comment_list_item = ob_get_clean();

		$response = array(
			'what'     => 'comment',
			'id'       => $comment->comment_ID,
			'data'     => $comment_list_item,
			'position' => $position
		);

		$counts                   = wp_count_comments();
		$response['supplemental'] = array(
			'in_moderation'        => $counts->moderated,
			'i18n_comments_text'   => sprintf(
				_n( '%s Comment', '%s Comments', $counts->approved ),
				number_format_i18n( $counts->approved )
			),
			'i18n_moderation_text' => sprintf(
				_nx( '%s in moderation', '%s in moderation', $counts->moderated, 'comments' ),
				number_format_i18n( $counts->moderated )
			)
		);

		if ( $comment_auto_approved ) {
			$response['supplemental']['parent_approved'] = $parent->comment_ID;
			$response['supplemental']['parent_post_id']  = $parent->comment_post_ID;
		}

		$x = new \WP_Ajax_Response();
		$x->add( $response );
		$x->send();
	}
}
