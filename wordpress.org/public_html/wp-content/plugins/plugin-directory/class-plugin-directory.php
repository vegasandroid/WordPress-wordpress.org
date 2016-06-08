<?php
namespace WordPressdotorg\Plugin_Directory;
use WordPressdotorg\Plugin_Directory\Admin\Customizations;

/**
 * The main Plugin Directory class, it handles most of the bootstrap and basic operations of the plugin.
 *
 * @package WordPressdotorg\Plugin_Directory
 */
class Plugin_Directory {

	/**
	 * Fetch the instance of the Plugin_Directory class.
	 */
	public static function instance() {
		static $instance = null;

		return ! is_null( $instance ) ? $instance : $instance = new Plugin_Directory();
	}

	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_filter( 'post_type_link', array( $this, 'package_link' ), 10, 2 );
		add_filter( 'term_link', array( $this, 'term_link' ), 10, 2 );
		add_filter( 'pre_insert_term', array( $this, 'pre_insert_term_prevent' ) );
		add_action( 'pre_get_posts', array( $this, 'use_plugins_in_query' ) );
		add_filter( 'rest_api_allowed_post_types', array( $this, 'filter_allowed_post_types' ) );
		add_filter( 'pre_update_option_jetpack_options', array( $this, 'filter_jetpack_options' ) );
		add_action( 'template_redirect', array( $this, 'redirect_hidden_plugins' ) );

		// Shim in postmeta support for data which doesn't yet live in postmeta
		add_filter( 'get_post_metadata', array( $this, 'filter_shim_postmeta' ), 10, 3 );

		add_filter( 'map_meta_cap', array( __NAMESPACE__ . '\Capabilities', 'map_meta_cap' ), 10, 4 );

		// Load the API routes
		add_action( 'rest_api_init', array( __NAMESPACE__ . '\API\Base', 'load_routes' ) );

		// Load all Admin-specific items.
		// Cannot be included on `admin_init` to allow access to menu hooks
		if ( defined( 'WP_ADMIN' ) && WP_ADMIN ) {
			Customizations::instance();

			add_action( 'wp_insert_post_data', array( __NAMESPACE__ . '\Admin\Status_Transitions', 'can_change_post_status' ), 10, 2 );
			add_action( 'transition_post_status', array( __NAMESPACE__ . '\Admin\Status_Transitions', 'instance' ) );
		}

		register_activation_hook( PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Set up the Plugin Directory.
	 */
	public function init() {
		load_plugin_textdomain( 'wporg-plugins' );

		wp_cache_add_global_groups( 'wporg-plugins' );

		register_post_type( 'plugin', array(
			'labels'          => array(
				'name'               => __( 'Plugins',                   'wporg-plugins' ),
				'singular_name'      => __( 'Plugin',                    'wporg-plugins' ),
				'menu_name'          => __( 'My Plugins',                'wporg-plugins' ),
				'add_new'            => __( 'Add New',                   'wporg-plugins' ),
				'add_new_item'       => __( 'Add New Plugin',            'wporg-plugins' ),
				'new_item'           => __( 'New Plugin',                'wporg-plugins' ),
				'view_item'          => __( 'View Plugin',               'wporg-plugins' ),
				'search_items'       => __( 'Search Plugins',            'wporg-plugins' ),
				'not_found'          => __( 'No plugins found',          'wporg-plugins' ),
				'not_found_in_trash' => __( 'No plugins found in Trash', 'wporg-plugins' ),

				// Context only available in admin, not in toolbar.
				'edit_item'          => is_admin() ? __( 'Editing Plugin: %s', 'wporg-plugins' ) : __( 'Edit Plugin', 'wporg-plugins' ),
			),
			'description'     => __( 'A Repo Plugin', 'wporg-plugins' ),
			'supports'        => array( 'comments' ),
			'public'          => true,
			'show_ui'         => true,
			'has_archive'     => true,
			'rewrite'         => false,
			'menu_icon'       => 'dashicons-admin-plugins',
			'capabilities'    => array(
				'edit_post'          => 'plugin_edit',
				'read_post'          => 'read',
				'edit_posts'         => 'plugin_dashboard_access',
				'edit_others_posts'  => 'plugin_edit_others',
				'publish_posts'      => 'plugin_approve',
				'read_private_posts' => 'do_not_allow',
				'delete_posts'       => 'do_not_allow',
				'create_posts'       => 'do_not_allow',
			)
		) );

		register_taxonomy( 'plugin_section', 'plugin', array(
			'hierarchical'      => true,
			'query_var'         => 'plugin_section',
			'rewrite'           => false,
			'public'            => true,
			'show_ui'           => current_user_can( 'plugin_set_section' ),
			'show_admin_column' => current_user_can( 'plugin_set_section' ),
			'meta_box_cb'       => 'post_categories_meta_box',
			'capabilities'      => array(
				'assign_terms' => 'plugin_set_section',
			),
			'labels'            => array(
				'name'          => __( 'Plugin Sections',  'wporg-plugins' ),
			),
		) );

		register_taxonomy( 'plugin_category', 'plugin', array(
			'hierarchical'      => true, /* for tax_input[] handling on post saves. */
			'query_var'         => 'plugin_category',
			'rewrite'           => array(
				'hierarchical' => false,
				'slug'         => 'category',
				'with_front'   => false,
				'ep_mask'      => EP_TAGS,
			),
			'labels'            => array(
				'name'          => __( 'Plugin Categories',  'wporg-plugins' ),
				'singular_name' => __( 'Plugin Category',   'wporg-plugins' ),
				'edit_item'     => __( 'Edit Category',     'wporg-plugins' ),
				'update_item'   => __( 'Update Category',   'wporg-plugins' ),
				'add_new_item'  => __( 'Add New Category',  'wporg-plugins' ),
				'new_item_name' => __( 'New Category Name', 'wporg-plugins' ),
				'search_items'  => __( 'Search Categories',  'wporg-plugins' ),
			),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'meta_box_cb'       => array( __NAMESPACE__ . '\Admin\Metabox\Plugin_Categories', 'display' ),
			'capabilities'      => array(
				'assign_terms' => 'plugin_set_category'
			)
		) );

		register_taxonomy( 'plugin_built_for', 'plugin', array(
			'hierarchical'      => true, /* for tax_input[] handling on post saves. */
			'query_var'         => 'plugin_built_for',
			'rewrite'           => false,
			'labels'            => array(
				'name'          => __( 'Built For',  'wporg-plugins' ),
			),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => false,
			//'meta_box_cb'       => array( __NAMESPACE__ . '\Admin\Metabox\Plugin_Categories', 'display' ),
			'capabilities'      => array(
				'assign_terms' => 'plugin_set_category'
			)
		) );

		register_taxonomy( 'plugin_business_model', 'plugin', array(
			'hierarchical'      => true, /* for tax_input[] handling on post saves. */
			'query_var'         => 'plugin_business_model',
			'rewrite'           => false,
			'labels'            => array(
				'name'          => __( 'Business Model',  'wporg-plugins' ),
			),
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => false,
			//'meta_box_cb'       => array( __NAMESPACE__ . '\Admin\Metabox\Plugin_Categories', 'display' ),
			'capabilities'      => array(
				'assign_terms' => 'plugin_set_category'
			)
		) );

		register_post_status( 'pending', array(
			'label'                     => _x( 'Pending', 'plugin status', 'wporg-plugins' ),
			'public'                    => false,
			'show_in_admin_status_list' => current_user_can( 'plugin_review' ),
			'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'wporg-plugins' ),
		) );
		register_post_status( 'disabled', array(
			'label'                     => _x( 'Disabled', 'plugin status', 'wporg-plugins' ),
			'public'                    => false,
			'show_in_admin_status_list' => current_user_can( 'plugin_disable' ),
			'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'wporg-plugins' ),
		) );
		register_post_status( 'approved', array(
			'label'                     => _x( 'Approved', 'plugin status', 'wporg-plugins' ),
			'public'                    => false,
			'show_in_admin_status_list' => current_user_can( 'plugin_approve' ),
			'label_count'               => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'wporg-plugins' ),
		) );
		register_post_status( 'closed', array(
			'label'                     => _x( 'Closed', 'plugin status', 'wporg-plugins' ),
			'public'                    => false,
			'show_in_admin_status_list' => current_user_can( 'plugin_close' ),
			'label_count'               => _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>', 'wporg-plugins' ),
		) );
		register_post_status( 'rejected', array(
			'label'                     => _x( 'Rejected', 'plugin status', 'wporg-plugins' ),
			'public'                    => false,
			'show_in_admin_status_list' => current_user_can( 'plugin_reject' ),
			'label_count'               => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'wporg-plugins' ),
		) );

		// Add the browse/* views.
		// TODO: browse/favorites/$user
		add_rewrite_tag( '%browse%', '(featured|popular|beta|new|favorites)' );
		add_permastruct( 'browse', 'browse/%browse%' );

		// If changing capabilities around, uncomment this.
		//Capabilities::add_roles();

		// When this plugin is used in the context of a Rosetta site, handle it gracefully
		if ( 'wordpress.org' != $_SERVER['HTTP_HOST'] && defined( 'WPORG_PLUGIN_DIRECTORY_BLOGID' ) ) {
			add_filter( 'option_home',    array( $this, 'rosetta_network_localize_url' ) );
			add_filter( 'option_siteurl', array( $this, 'rosetta_network_localize_url' ) );
		}

		if ( 'en_US' != get_locale() ) {
			add_filter( 'get_term', array( __NAMESPACE__ . '\i18n', 'translate_term' ) );
			add_filter( 'the_content', array( $this, 'translate_post_content' ), 1, 2 );
			add_filter( 'the_title', array( $this, 'translate_post_title' ), 1, 2 );
			add_filter( 'get_the_excerpt', array( $this, 'translate_post_excerpt' ), 1 );
		}

		// Instantiate our copy of the Jetpack_Search class.
		if ( class_exists( 'Jetpack' ) && ! class_exists( 'Jetpack_Search' ) ) {
			require_once( __DIR__ . '/libs/site-search/jetpack-search.php' );
			\Jetpack_Search::instance();
		}
	}

	/**
	 * Register the Shortcodes used within the content.
	 */
	public function register_shortcodes() {
		add_shortcode( 'wporg-plugins-developers',  array( __NAMESPACE__ . '\Shortcodes\Developers',  'display' ) );
		add_shortcode( 'wporg-plugin-upload',       array( __NAMESPACE__ . '\Shortcodes\Upload',      'display' ) );
		add_shortcode( 'wporg-plugins-screenshots', array( __NAMESPACE__ . '\Shortcodes\Screenshots', 'display' ) );
		add_shortcode( 'wporg-plugins-stats',       array( __NAMESPACE__ . '\Shortcodes\Stats',       'display' ) );
	}

	public function register_widgets() {
		register_widget( __NAMESPACE__ . '\Widgets\Meta' );
		register_widget( __NAMESPACE__ . '\Widgets\Ratings' );
		register_widget( __NAMESPACE__ . '\Widgets\Support' );
	}

	/**
	 * Upon plugin activation, set up the current site for acting
	 * as the plugin directory.
	 *
	 * Setting up the site requires setting up the theme and proper
	 * rewrite permastructs.
	 */
	public function activate() {

		/**
		 * @var \WP_Rewrite $wp_rewrite WordPress rewrite component.
		 */
		global $wp_rewrite;

		// Setup the environment.
		$this->init();

		// %postname% is required.
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		// /tags/ & /category/ shouldn't conflict
		$wp_rewrite->set_tag_base( '/post-tags' );
		$wp_rewrite->set_category_base( '/post-categories' );

		// Add our custom capabilitie and roles.
		Capabilities::add_roles();

		// We require the WordPress.org Ratings plugin also be active.
		if ( ! is_plugin_active( 'wporg-ratings/wporg-ratings.php' ) ) {
			activate_plugin( 'wporg-ratings/wporg-ratings.php' );
		}

		/**
		 * Enable the WordPress.org Plugin Repo Theme.
		 *
		 * @var \WP_Theme $theme
		 */
		foreach ( wp_get_themes() as $theme ) {
			if ( $theme->get( 'Name' ) === 'WordPress.org Plugins' ) {
				switch_theme( $theme->get_stylesheet() );
				break;
			}
		}

		flush_rewrite_rules();

		do_action( 'wporg_plugins_activation' );
	}

	/**
	 * Clean up options & rewrite rules after plugin deactivation.
	 */
	public function deactivate() {
		flush_rewrite_rules();

		do_action( 'wporg_plugins_deactivation' );
	}

	/**
	 * Filter the URLs to use the current localized domain name, rather than WordPress.org.
	 *
	 * The Plugin Directory is available at multiple URLs (internationalised domains), this method allows
	 * for the one blog (a single blog_id) to be presented at multiple URLs yet have correct localised links.
	 *
	 * This method works in conjunction with a filter in sunrise.php, duplicated here for transparency:
	 *
	 * // Make the Plugin Directory available at /plugins/ on all rosetta sites.
	 * function wporg_plugins_on_rosetta_domains( $site, $domain, $path, $segments ) {
	 *     // All non-rosetta networks define DOMAIN_CURRENT_SITE in wp-config.php
	 *     if ( ! defined( 'DOMAIN_CURRENT_SITE' ) && 'wordpress.org' != $domain && '/plugins/' == substr( $path . '/', 0, 9 ) ) {
	 *          $site = get_blog_details( WPORG_PLUGIN_DIRECTORY_BLOGID, false );
	 *          if ( $site ) {
	 *              $site = clone $site;
	 *              // 6 = The Rosetta network, this causes the site to be loaded as part of the Rosetta network
	 *              $site->site_id = 6;
	 *              return $site;
	 *          }
	 *     }
	 *
	 *     return $site;
	 * }
	 * add_filter( 'pre_get_site_by_path', 'wporg_plugins_on_rosetta_domains', 10, 4 );
	 *
	 * @param string $url The URL to be localized.
	 * @return string
	 */
	public function rosetta_network_localize_url( $url ) {
		static $localized_url = null;

		if ( is_null( $localized_url ) ) {
			$localized_url = 'https://' . preg_replace( '![^a-z.-]+!', '', $_SERVER['HTTP_HOST'] );
		}

		return preg_replace( '!^[https]+://wordpress\.org!i', $localized_url, $url );
	}

	/**
	 * Filter the permalink for the Plugins to be /plugin-name/.
	 *
	 * @param string   $link The generated permalink.
	 * @param \WP_Post $post The Plugin post object.
	 * @return string
	 */
	public function package_link( $link, $post ) {
		if ( 'plugin' !== $post->post_type ) {
			return $link;
		}

		return trailingslashit( home_url( $post->post_name ) );
	}

	/**
	 * Filter the permalink for terms to be more useful.
	 *
	 * @param string   $termlink The generated term link.
	 * @param \WP_Term $term     The term the link is for.
	 */
	public function term_link( $termlink, $term ) {
		if ( 'plugin_business_model' == $term->taxonomy ) {
			return false;
		}
		if ( 'plugin_built_for' == $term->taxonomy ) {
			// Term slug = Post Slug = /%postname%/
			return trailingslashit( home_url( $term->slug ) );
		}

		return $termlink;
	}

	/**
	 * Checks if the current users is a super admin before allowing terms to be added.
	 *
	 * @param string $term The term to add or update.
	 * @return string|\WP_Error The term to add or update or WP_Error on failure.
	 */
	public function pre_insert_term_prevent( $term ) {
		if ( ! is_super_admin() ) {
			$term = new \WP_Error( 'not-allowed', __( 'You are not allowed to add terms.', 'wporg-plugins' ) );
		}

		return $term;
	}

	/**
	 * @param \WP_Query $wp_query The WordPress Query object.
	 */
	public function use_plugins_in_query( $wp_query ) {
		if ( is_admin() || ! $wp_query->is_main_query() ) {
			return;
		}

		if ( empty( $wp_query->query_vars['pagename'] ) && ( empty( $wp_query->query_vars['post_type'] ) || 'post' == $wp_query->query_vars['post_type'] ) ) {
			$wp_query->query_vars['post_type']   = array( 'plugin' );
			$wp_query->query_vars['post_status'] = array( 'publish' );
		}

		if ( empty( $wp_query->query ) ) {
			$wp_query->query_vars['browse'] = 'featured';
		}

		switch ( get_query_var( 'browse' ) ) {
			case 'beta':
				$wp_query->query_vars['plugin_section'] = 'beta';
				break;

			case 'featured':
				$wp_query->query_vars['plugin_section'] = 'featured';
				break;

			case 'favorites':
				$favorites_user = get_current_user_id();
				if ( !empty( $wp_query->query_vars['favorites_user'] ) ) {
					$favorites_user = $wp_query->query_vars['favorites_user'];
				} elseif ( !empty( $_GET['favorites_user'] ) ) {
					$favorites_user = $_GET['favorites_user'];
				}
				if ( ! is_numeric( $favorites_user ) ) {
					$favorites_user = get_user_by( 'slug', $favorites_user );
					if ( $favorites_user ) {
						$favorites_user = $favorites_user->ID;
					}
				}

				if ( $favorites_user ) {
					$wp_query->query_vars['post_name__in'] = get_user_meta( $favorites_user, 'plugin_favorites', true );
				}
				if ( ! $favorites_user || ! $wp_query->query_vars['post_name__in'] ) {
					$wp_query->query_vars['p'] = -1;
				}
				break;

			case 'popular':
				$wp_query->query_vars['orderby'] = 'meta_value_num';
				$wp_query->query_vars['meta_key'] = 'active_installs';
				break;
		}

	}

	/**
	 * Returns the requested page's content, translated.
	 *
	 * @param string $content
	 * @return string
	 */
	public function translate_post_content( $content, $section = null ) {
		if ( is_null( $section ) ) {
			return $content;
		}
		return Plugin_i18n::instance()->translate( $section, $content );
	}

	/**
	 * Returns the requested page's content, translated.
	 *
	 * @param string $content
	 * @return string
	 */
	public function translate_post_title( $title, $post_id ) {
		if ( $post_id === get_post()->ID ) {
			return Plugin_i18n::instance()->translate( 'title', $title );
		}
		return $title;
	}

	/**
	 * Returns the requested page's excerpt, translated.
	 *
	 * @param string $content
	 * @return string
	 */
	public function translate_post_excerpt( $excerpt ) {
		return Plugin_i18n::instance()->translate( 'excerpt', $excerpt );
	}

	/**
	 * Filter for rest_api_allowed_post_types to enable JP syncing of the CPT
	 *
	 * @param array $allowed_post_types
	 * @return array
	 */
	public function filter_allowed_post_types( $allowed_post_types ) {
		$allowed_post_types[] = 'plugin';
		return $allowed_post_types;
	}

	/**
	 * Filter for pre_update_option_jetpack_options to ensure CPT posts are seen as public and searchable by TP
	 *
	 * @param mixed $new_value
	 * @return mixed
	 */
	public function filter_jetpack_options( $new_value ) {
		if ( is_array( $new_value ) && array_key_exists( 'public', $new_value ) )
			$new_value['public'] = 1;

		return $new_value;
	}

	/**
	 * Redirects Committers and Admins to a plugin's edit page if it's disabled or closed.
	 */
	public function redirect_hidden_plugins() {
		if ( ! is_404() ) {
			return;
		}

		$post = self::get_plugin_post( get_query_var( 'name', false ) );

		if ( $post instanceof \WP_Post && in_array( $post->post_status, array( 'disabled', 'closed' ), true ) && current_user_can( 'edit_post', $post ) ) {
			wp_safe_redirect( add_query_arg( array( 'post' => $post->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
		}
	}

	/**
	 * Shim in some postmeta values which get retrieved from other locations temporarily.
	 *
	 * @param null|array|string $value     The value get_metadata() should return - a single metadata value,
	 *                                     or an array of values.
	 * @param int               $object_id Object ID.
	 * @param string            $meta_key  Meta key.
	 * @return array
	 */
	public function filter_shim_postmeta( $value, $object_id, $meta_key ) {
		switch ( $meta_key ) {
			case 'downloads':
				$post = get_post( $object_id );
				$count = Template::get_downloads_count( $post );

				return array( $count );
				break;
			case 'rating':
				$post = get_post( $object_id );
				// The WordPress.org global ratings functions
				if ( ! function_exists( 'wporg_get_rating_avg' ) ) {
					break;
				}
				$rating = wporg_get_rating_avg( 'plugin', $post->post_name );

				return array( $rating );
				break;
			case 'ratings':
				$post = get_post( $object_id );
				if ( ! function_exists( 'wporg_get_rating_counts' ) ) {
					break;
				}
				$ratings = wporg_get_rating_counts( 'plugin', $post->post_name );

				return array( $ratings );
				break;
			case false:
				// In the event $meta_key is false, the caller wants all meta fields, so we'll append our custom ones here too.
				remove_filter( 'get_post_metadata', array( $this, 'filter_shim_postmeta' ) );

				// Fetch the existing ones from the database
				$value = get_metadata( 'post', $object_id, $meta_key );

				// Re-attach ourselves for next time!
				add_filter( 'get_post_metadata', array( $this, 'filter_shim_postmeta' ), 10, 3 );

				$custom_meta_fields = array( 'downloads', 'rating', 'ratings' );
				$custom_meta_fields = apply_filters( 'wporg_plugins_custom_meta_fields', $custom_meta_fields, $object_id );

				foreach ( $custom_meta_fields as $key ) {
					// When WordPress calls `get_post_meta( $post_id, false )` it expects an array of maybe_serialize()'d data
					$shimed_data = $this->filter_shim_postmeta( false, $object_id, $key );
					if ( $shimed_data ) {
						$value[ $key ][0] = (string) maybe_serialize( $shimed_data[0] );
					}
				}

				break;
		}
		return $value;
	}

	/**
	 * Returns an array of pages based on section comments in the content.
	 *
	 * @param string $content
	 * @return array
	 */
	public function split_post_content_into_pages( $content ) {
		$_pages        = preg_split( "#<!--section=(.+?)-->#", $content, - 1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$content_pages = array(
			'screenshots' => '[wporg-plugins-screenshots]',
			'stats'       => '[wporg-plugins-stats]',
			'developers'  => '[wporg-plugins-developers]',
		);

		for ( $i = 0; $i < count( $_pages ); $i += 2 ) {

			// Don't overwrite existing tabs.
			if ( ! isset( $content_pages[ $_pages[ $i ] ] ) ) {
				$content_pages[ $_pages[ $i ] ] = $_pages[ $i + 1 ];
			}
		}

		return $content_pages;
	}

	/**
	 * Retrieve the WP_Post object representing a given plugin.
	 *
	 * @param $plugin_slug string|\WP_Post The slug of the plugin to retrieve.
	 * @return \WP_Post|bool
	 */
	static public function get_plugin_post( $plugin_slug ) {
		global $post;
		if ( $plugin_slug instanceof \WP_Post ) {
			return $plugin_slug;
		}
		// Use the global $post object when it matches to avoid hitting the database.
		if ( !empty( $post ) && 'plugin' == $post->post_type && $plugin_slug == $post->post_name ) {
			return $post;
		}

		$plugin_slug = sanitize_title_for_query( $plugin_slug );

		if ( false !== ( $post_id = wp_cache_get( $plugin_slug, 'plugin-slugs' ) ) && ( $post = get_post( $post_id ) ) ) {
			// We have a $post.
		} else {
			// get_post_by_slug();
			$posts = get_posts( array(
				'post_type'   => 'plugin',
				'name'        => $plugin_slug,
				'post_status' => array( 'publish', 'pending', 'disabled', 'closed', 'draft', 'approved' ),
			) );
			if ( ! $posts ) {
				$post = false;
				wp_cache_add( 0, $plugin_slug, 'plugin-slugs' );
			} else {
				$post = reset( $posts );
				wp_cache_add( $post->ID, $plugin_slug, 'plugin-slugs' );
			}
		}

		return $post;
	}

	/**
	 * Create a new post entry for a given plugin slug.
	 *
	 * @param array $plugin_info {
	 *     Array of initial plugin post data, all fields are optional.
	 *
	 *     @type string $title       The title of the plugin.
	 *     @type string $slug        The slug of the plugin.
	 *     @type string $status      The status of the plugin ( 'publish', 'pending', 'disabled', 'closed' ).
	 *     @type int    $author      The ID of the plugin author.
	 *     @type string $description The short description of the plugin.
	 *     @type string $content     The long description of the plugin.
	 *     @type array  $tags        The tags associated with the plugin.
	 *     @type array  $tags        The meta information of the plugin.
	 * }
	 * @return \WP_Post|\WP_Error
	 */
	static public function create_plugin_post( array $plugin_info ) {
		$title   = ! empty( $plugin_info['title'] )       ? $plugin_info['title']       : '';
		$slug    = ! empty( $plugin_info['slug'] )        ? $plugin_info['slug']        : sanitize_title( $title );
		$status  = ! empty( $plugin_info['status'] )      ? $plugin_info['status']      : 'draft';
		$author  = ! empty( $plugin_info['author'] )      ? $plugin_info['author']      : 0;
		$desc    = ! empty( $plugin_info['description'] ) ? $plugin_info['description'] : '';
		$content = ! empty( $plugin_info['content'] )     ? $plugin_info['content']     : '';
		$meta    = ! empty( $plugin_info['meta'] )        ? $plugin_info['meta']        : array();

		$post_date         = ! empty( $plugin_info['post_date'] )         ? $plugin_info['post_date']         : '';
		$post_date_gmt     = ! empty( $plugin_info['post_date_gmt'] )     ? $plugin_info['post_date_gmt']     : '';
		$post_modified     = ! empty( $plugin_info['post_modified'] )     ? $plugin_info['post_modified']     : '';
		$post_modified_gmt = ! empty( $plugin_info['post_modified_gmt'] ) ? $plugin_info['post_modified_gmt'] : '';

		$id = wp_insert_post( array(
			'post_type'    => 'plugin',
			'post_status'  => $status,
			'post_name'    => $slug,
			'post_title'   => $title ?: $slug,
			'post_author'  => $author,
			'post_content' => $content,
			'post_excerpt' => $desc,
			'meta_input'   => $meta,
			'post_date'         => $post_date,
			'post_date_gmt'     => $post_date_gmt,
			'post_modified'     => $post_modified,
			'post_modified_gmt' => $post_modified_gmt,
		), true );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		wp_cache_set( $id, $slug, 'plugin-slugs' );

		return get_post( $id );
	}
}
