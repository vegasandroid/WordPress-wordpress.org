<?php
/**
 * Class providing navigation links.
 *
 * @package handbook
 */

class WPorg_Handbook_Navigation {

	/**
	 * Is the handbook table of contents produced by the handbook pages widget?
	 *
	 * @access private
	 * @var bool
	 */
	private static $using_pages_widget = false;

	/**
	 * Initializes handbook navigation.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'do_init' ), 100 );
	}

	/**
	 * Fires on 'init' action.
	 */
	public static function do_init() {
		// Note if the WPorg_Handbook_Pages_Widget widget is in use.
		if ( is_active_widget( false, false, WPorg_Handbook_Pages_Widget::get_widget_id_base(), true ) ) {
			self::$using_pages_widget = true;
		}
	}

	/**
	 * Outputs previous and next page navigation links (and wrapper markup).
	 *
	 * This function determines the method used for the handbook table of contents
	 * and outputs page navigation links accordingly.
	 *
	 * Recognizes use of either the WPorg_Handbook_Pages_Widget (as provided as
	 * part of this plugin) or a custom menu widget (by default, associated with
	 * the custom menu having the name "Table of Contents"). If both are present,
	 * the WPorg_Handbook_Pages_Widget is used.
	 *
	 * @param string $menu_name Optional. The name of the menu for the table of
	 *                          contents. Only applies if the handbook pages
	 *                          widget is not in use. Default 'Table of Contents'.
	 */
	public static function show_nav_links( $menu_name = 'Table of Contents' ) {
		$prev = $next = false;

		if ( self::$using_pages_widget ) {
			$adjacent = self::get_adjacent_posts_via_handbook_pages_widget();
		} else {
			$adjacent = self::get_adjacent_posts_via_menu( $menu_name );
		}

		if ( is_array( $adjacent ) ) {
			list( $prev, $next ) = $adjacent;
		}

		self::output_navigation( $prev, $next );
	}

	/**
	 * Outputs previous and/or next page navigation links according to the active
	 * handbook widget settings.
	 *
	 * @access protected
	 *
	 * @param int|WP_Post $post        Optional. The post object or ID. Default is
	 *                                 global post.
	 * @param string      $type        Optional. The type of adjacent post(s) to
	 *                                 return. One of 'prev', 'next', or 'both'.
	 *                                 Default 'both'.
	 * @param int|WP_Post $source_post Optional. The post requesting an adjacent
	 *                                 post, if not $post itself. Default ''.
	 * @return null|array {
	 *    The previous and next post.
	 *
	 *    @type false|object $prev Object containing 'title' and 'url' for previous post.
	 *    @type false|object $prev Object containing 'title' and 'url' for next post.
	 * }
	 */
	protected static function get_adjacent_posts_via_handbook_pages_widget( $post = '', $type = 'both', $source_post = '' ) {
		// Get current post.
		if ( ! $post = get_post( $post ) ) {
			return;
		}

		// Bail unless a handbook page.
		if ( ! wporg_is_handbook_post_type( get_post_type( $post ) ) ) {
			return;
		}

		// Determine which adjacent post(s) to find.
		if ( in_array( $type, array( 'prev', 'next' ) ) ) {
			if ( 'prev' === $type ) {
				$get_prev = true;
				$get_next = false;
			} else {
				$get_prev = false;
				$get_next = true;
			}
		} else {
			$get_prev = $get_next = true;
		}

		// Get settings for widget.
		$sort_column = 'menu_order';
		$exclude     = '';
		$widget_options = get_option( 'widget_' . WPorg_Handbook_Pages_Widget::get_widget_id_base() );
		foreach ( (array) $widget_options as $widget ) {
			if ( $widget && is_array( $widget ) ) {
				if ( ! empty( $widget['sortby']  ) ) { $sort_column = $widget['sortby'];  }
				if ( ! empty( $widget['exclude'] ) ) { $exclude     = $widget['exclude']; }
				break;
			}
		}

		// Cache key format is pages:{post type}:{sort column}(:{excluded})?.
		$cache_key = 'pages:' . get_post_type( $post ) . ':' . $sort_column;
		if ( $exclude ) {
			$cache_key .= ':' . str_replace( ' ', '', $exclude );
		}
		$cache_group = 'wporg_handbook:' . get_current_blog_id();

		$post_status = array( 'publish' );
		if ( current_user_can( get_post_type_object( get_post_type( $post ) )->cap->read_private_posts ) ) {
			$post_status[] = 'private';
		}

		// Get the hierarchically and menu_order ordered list of handbook pages.
		$handbook_pages = wp_cache_get( $cache_key, $cache_group );
		if ( false === $handbook_pages ) {
			if ( 'menu_order' === $sort_column ) {
				$sort_column = array( 'menu_order' => 'ASC', 'post_title' => 'ASC' );
			}

			$parent_id = wp_get_post_parent_id( $post );

			$handbook_pages = get_posts( array(
				'exclude'        => $exclude,
				'post_parent'    => $parent_id,
				'post_status'    => $post_status,
				'post_type'      => get_post_type( $post ),
				'orderby'        => $sort_column,
				'order'          => 'ASC',
				'posts_per_page' => -1,
			) );

			if ( $handbook_pages ) {
				wp_cache_add( $cache_key, $handbook_pages, $cache_group, 2 * MINUTE_IN_SECONDS );
			}
		}

		$prev = $next = false;

		// Determine the previous and next handbook pages.
		if ( $handbook_pages ) {
			$current_page  = wp_list_filter( $handbook_pages, array( 'ID' => $post->ID ) );
			$current_index = array_keys( $current_page );

			if ( false !== $current_index ) {
				$current_index = $current_index[0];
				$current_page  = $current_page[ $current_index ];

				// The previous post is the post's immediate sibling.
				// It's debatable if it should be the last leaf node of the previous
				// sibling's last child (since if you are on that leaf node, the next
				// post is the current post). That's what the custom menu-based
				// navigation does, but it is easier to do there than here.
				if ( $get_prev && array_key_exists( $current_index - 1, $handbook_pages ) ) {
					$prev = $handbook_pages[ $current_index - 1 ];
					$prev = (object) array(
						'url'   => get_the_permalink( $prev->ID ),
						'title' => get_the_title( $prev->ID ),
					);
				}

				// If no previous yet, then it's the parent, if there is one.
				if ( $get_prev && ! $prev && $parent_id ) {
					$prev = (object) array(
						'url'   => get_the_permalink( $parent_id ),
						'title' => get_the_title( $parent_id ),
					);
				}

				// The next post may be this post's first child.
				if ( $get_next && ! $source_post ) {
					$children = get_posts( array(
						'exclude'        => $exclude,
						'post_parent'    => $post->ID,
						'post_status'    => $post_status,
						'post_type'      => get_post_type( $post ),
						'orderby'        => $sort_column,
						'order'          => 'ASC',
						'posts_per_page' => 1,
					) );
					if ( $children ) {
						$next = (object) array(
							'url'   => get_the_permalink( $children[0]->ID ),
							'title' => get_the_title( $children[0]->ID ),
						);
					}
				}

				// If no next yet, get next sibling.
				if ( $get_next && ! $next && array_key_exists( $current_index + 1, $handbook_pages ) ) {
					$next = $handbook_pages[ $current_index + 1 ];
					$next = (object) array(
						'url'   => get_the_permalink( $next->ID ),
						'title' => get_the_title( $next->ID ),
					);
				}

				// If no next yet, recursively check for a next ancestor.
				if ( $get_next && ! $next && $parent_id ) {
					$parent_next = self::get_adjacent_posts_via_handbook_pages_widget( $parent_id, 'next', $post->ID );
					if ( is_array( $parent_next ) ) {
						$next = $parent_next[1];
					}
				}

			}
		}

		return array( $prev, $next );
	}

	/**
	 * Outputs previous and/or next page navigation links using the
	 * specified menu to inform navigation ordering.
	 *
	 * @access protected
	 *
	 * @param string $menu_name The name of the menu to use for nav ordering.
	 * @param int|WP_Post $post Optional. The post object or ID. Default is global
	 *                          post.
	 * @return null|array {
	 *    The previous and next post.
	 *
	 *    @type false|object $prev Object containing 'title' and 'url' for previous post.
	 *    @type false|object $prev Object containing 'title' and 'url' for next post.
	 * }
	 */
	protected static function get_adjacent_posts_via_menu( $menu_name, $post = '' ) {
		// Get current post.
		if ( ! $post = get_post( $post ) ) {
			return;
		}

		// Get the items for the specified menu.
		if ( ! $menu_items = wp_get_nav_menu_items( $menu_name ) ) {
			return;
		}

		// Get ids for all menu objects.
		$menu_ids = wp_list_pluck( $menu_items, 'object_id' );

		// Index of current post in menu. Return if not in menu.
		$i = array_search( $post->ID, $menu_ids );
		if ( false === $i ) {
			return;
		}

		// Find the previous post (note: previous menu item may not be a post).
		$previous = null;
		for ( $n = $i-1; $n >= 0; $n-- ) {
			if ( isset( $menu_items[ $n ] ) && is_a( $menu_items[ $n ], 'WP_Post' ) ) {
				$previous = $menu_items[ $n ];
				break;
			}
		}

		// Find the next post (note: next menu item may not be a post).
		$next = null;
		$max = count( $menu_items );
		for ( $n = $i+1; $n < $max; $n++ ) {
			if ( isset( $menu_items[ $n ] ) && is_a( $menu_items[ $n ], 'WP_Post' ) ) {
				$next = $menu_items[ $n ];
				break;
			}
		}

		return array( $previous, $next );
	}

	/**
	 * Outputs navigation markup for the specified previous and/or next pages.
	 *
	 * @access protected
	 *
	 * @param object $previous Object with the 'url' and 'title' attribute for the
	 *                         previous page.
	 * @param object $next     Object with the 'url' and 'title' attribute for the
	 *                         next page.
	 */
	protected static function output_navigation( $previous, $next ) {
		if ( ! $previous && ! $next ) {
			return;
		}

		?>

		<nav class="handbook-navigation" role="navigation">
			<h1 class="screen-reader-text"><?php _e( 'Handbook navigation', 'wporg' ); ?></h1>
			<div class="nav-links">

			<?php
			if ( $previous ) {
				printf( '<a href="%s" rel="previous"><span class="meta-nav">&larr;</span> %s</a>',
					esc_url( $previous->url ),
					esc_html( $previous->title )
				);
			}

			if ( $next ) {
				printf( '<a href="%s" rel="next">%s <span class="meta-nav">&rarr;</span></a>',
					esc_url( $next->url ),
					esc_html( $next->title )
				);
			}
			?>

			</div>
			<!-- .nav-links -->
		</nav><!-- .navigation -->
	<?php
	}

}

WPorg_Handbook_Navigation::init();
