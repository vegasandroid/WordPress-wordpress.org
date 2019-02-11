<?php

/**
 * WP.org Themes' functions and definitions.
 *
 * @package wporg-themes
 */

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function wporg_themes_setup() {
	global $themes_allowedtags;

	load_theme_textdomain( 'wporg-themes' );

	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
	) );

	// No need for canonical lookups
	remove_action( 'template_redirect', 'redirect_canonical' );
	remove_action( 'template_redirect', 'wp_old_slug_redirect' );

	add_action( 'template_redirect', 'wporg_themes_trailing_slashes' );

	add_theme_support( 'wp4-styles' );
}
add_action( 'after_setup_theme', 'wporg_themes_setup' );

/**
 * Handle the root-level redirect to trailing-slash'd uri which redirect_canonical() usually does.
 */
function wporg_themes_trailing_slashes() {
	if ( '/themes' === $_SERVER['REQUEST_URI'] ) {
		wp_safe_redirect( '/themes/', 301 );
		die();
	}

	if ( false !== stripos( $_SERVER['REQUEST_URI'], '/index.php' ) ) {
		$url = str_ireplace( '/index.php', '/', $_SERVER['REQUEST_URI'] );
		wp_safe_redirect( $url, 301 );
		die();
	}
}

/**
 * Enqueue scripts and styles.
 */
function wporg_themes_scripts() {
	$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$suffix       = $script_debug ? '' : '.min';

	// Concatenates core scripts when possible.
	if ( ! $script_debug ) {
		$GLOBALS['concatenate_scripts'] = true;
	}

	wp_enqueue_style( 'wporg-themes', get_theme_file_uri( '/css/style.css' ), [ 'dashicons', 'open-sans' ], '20190211' );
	wp_style_add_data( 'wporg-themes', 'rtl', 'replace' );

	if ( ! is_singular( 'page' ) ) {
		wp_enqueue_script( 'google-charts-loader', 'https://www.gstatic.com/charts/loader.js', array(), null, true );
		wp_enqueue_script( 'wporg-theme', get_template_directory_uri() . "/js/theme{$suffix}.js", array( 'wp-backbone' ), 21, true );

		wp_localize_script( 'wporg-theme', '_wpThemeSettings', array(
			'themes'   => false,
			'query'    => wporg_themes_get_themes_for_query(),
			'settings' => array(
				/* translators: %s: theme name */
				'title'        => __( '%s &#124; WordPress.org', 'wporg-themes' ),
				'isMobile'     => wp_is_mobile(),
				'postsPerPage' => 24,
				'path'         => trailingslashit( parse_url( home_url(), PHP_URL_PATH ) ),
				'locale'       => get_locale(),
				'favorites'    => array(
					'themes' => wporg_themes_get_user_favorites(),
					'user'   => wp_get_current_user()->user_login,
					'nonce'  => is_user_logged_in() ? wp_create_nonce( 'modify-theme-favorite' ) : false,
				),
			),
			'l10n' => array(
				'locale'            => str_replace( '_', '-', get_locale() ),
				'search'            => __( 'Search Themes', 'wporg-themes' ),
				'searchPlaceholder' => __( 'Search themes...', 'wporg-themes' ), // placeholder (no ellipsis)
				'error'             => __( 'An unexpected error occurred.', 'wporg-themes' ),

				// Downloads Graph
				'date'      => __( 'Date', 'wporg-themes' ),
				'downloads' => __( 'Downloads', 'wporg-themes' ),

				// Tags
				'tags' => wporg_themes_get_tag_translations(),

				// Active Installs
				'active_installs_less_than_10' => __( 'Less than 10', 'wporg-themes' ),
				'active_installs_1_million' => __( '1+ million', 'wporg-themes' ),
			),
		) );
	}

	// No emoji support needed.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );

	// No Jetpack styles needed.
	add_filter( 'jetpack_implode_frontend_css', '__return_false' );

	/*
	 * No Grofiles needed.
	 *
	 * Enqueued so that it's overridden in the global footer.
	 */
	wp_register_script( 'grofiles-cards', false );
	wp_enqueue_script( 'grofiles-cards' );
}
add_action( 'wp_enqueue_scripts', 'wporg_themes_scripts' );

// Disable mentions script in Theme Directory.
add_filter( 'jetpack_mentions_should_load_ui', '__return_false', 11 );

/**
 * Extend the default WordPress body classes.
 *
 * Adds body classes to
 * 1. denote singular themes.
 * 2. Identify IE8.
 * 3. denote if no themes were found.
 *
 * @param array $classes A list of existing body class values.
 * @return array The filtered body class list.
 */
function wporg_themes_body_class( $classes ) {

	if ( ! is_page() && get_query_var( 'name' ) && ! is_404() ) {
		$classes[] = 'modal-open';
	}

	if ( $GLOBALS['is_IE'] && false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 8' ) ) {
		$classes[] = 'ie8';
	}

	if ( empty( $GLOBALS['themes']['themes'] ) && ! is_singular( 'page' ) ) {
		$classes[] = 'no-results';
	}

	return $classes;
}
add_filter( 'body_class', 'wporg_themes_body_class' );

/**
 * Append an optimized site name.
 *
 * @param array $title {
 *     The document title parts.
 *
 *     @type string $title   Title of the viewed page.
 *     @type string $page    Optional. Page number if paginated.
 *     @type string $tagline Optional. Site description when on home page.
 *     @type string $site    Optional. Site title when not on home page.
 * }
 * @return array Filtered title parts.
 */
function wporg_themes_document_title( $title ) {
	if ( is_front_page() ) {
		$title['title']   = __( 'WordPress Themes', 'wporg-themes' );
		$title['tagline'] = __( 'WordPress.org', 'wporg-themes' );
	} else if ( is_category() || is_tag() ) {
		/* translators: Category or tag name */
		$title['title'] = sprintf( __( 'WordPress Themes: %s Free', 'wporg-themes' ), single_term_title( '', false ) );
	}

	if ( ! is_front_page() ) {
		$title['site'] = __( 'WordPress.org', 'wporg-themes' );
	}

	return $title;
}
add_filter( 'document_title_parts', 'wporg_themes_document_title' );

/**
 * Set the separator for the document title.
 *
 * @return string Document title separator.
 */
add_filter( 'document_title_separator', function() {
	return '&#124;';
} );

/**
 * Adds meta description for front page.
 *
 * @param array $tags Array that consists of meta name and meta content pairs.
 */
function wporg_themes_meta_tags( $tags ) {
	if ( is_front_page() ) {
		$tags['description'] = __( 'Find the perfect theme for your WordPress website. Choose from thousands of stunning designs with a wide variety of features and customization options.', 'wporg-themes' );
	}

	return $tags;
}
add_filter( 'jetpack_seo_meta_tags', 'wporg_themes_meta_tags' );

/**
 * Overrides feeds to use a custom RSS2 feed which contains the current requests themes.
 */
function wporg_themes_custom_feed() {
	if ( ! is_feed() ) {
		return;
	}
	if ( 'repopackage' != get_query_var( 'post_type' ) ) {
		return;
	}

	include __DIR__ . '/rss.php';
	die();
}
add_filter( 'template_redirect', 'wporg_themes_custom_feed' );

/**
 * Include view templates in the footer.
 */
function wporg_themes_view_templates() {
	if ( ! is_singular( 'page' ) ) {
		get_template_part( 'view-templates/theme' );
		get_template_part( 'view-templates/theme-preview' );
		get_template_part( 'view-templates/theme-single' );
	}
}
add_action( 'wp_footer', 'wporg_themes_view_templates' );

/**
 * This is a copy of get_theme_feature_list(), but with the wporg-themes text domain
 *
 * @param string $include Optional. Type of list: 'active', 'deprecated' or 'all'. Default 'active'.
 * @return array List of features.
 */
function wporg_themes_get_feature_list( $include = 'active' ) {
	$features = array();

	if ( 'active' === $include || 'all' === $include ) {
		$features = array(
			__( 'Layout', 'wporg-themes' )   => array(
				'grid-layout'   => __( 'Grid Layout', 'wporg-themes' ),
				'one-column'    => __( 'One Column', 'wporg-themes' ),
				'two-columns'   => __( 'Two Columns', 'wporg-themes' ),
				'three-columns' => __( 'Three Columns', 'wporg-themes' ),
				'four-columns'  => __( 'Four Columns', 'wporg-themes' ),
				'left-sidebar'  => __( 'Left Sidebar', 'wporg-themes' ),
				'right-sidebar' => __( 'Right Sidebar', 'wporg-themes' ),
				'wide-blocks'   => __( 'Wide Blocks', 'wporg-themes' ),
			),
			__( 'Features', 'wporg-themes' ) => array(
				'accessibility-ready'   => __( 'Accessibility Ready', 'wporg-themes' ),
				'block-styles'          => __( 'Block Editor Styles', 'wporg-themes' ),
				'buddypress'            => __( 'BuddyPress', 'wporg-themes' ),
				'custom-background'     => __( 'Custom Background', 'wporg-themes' ),
				'custom-colors'         => __( 'Custom Colors', 'wporg-themes' ),
				'custom-header'         => __( 'Custom Header', 'wporg-themes' ),
				'custom-logo'           => __( 'Custom Logo', 'wporg-themes' ),
				'custom-menu'           => __( 'Custom Menu', 'wporg-themes' ),
				'editor-style'          => __( 'Editor Style', 'wporg-themes' ),
				'featured-image-header' => __( 'Featured Image Header', 'wporg-themes' ),
				'featured-images'       => __( 'Featured Images', 'wporg-themes' ),
				'flexible-header'       => __( 'Flexible Header', 'wporg-themes' ),
				'footer-widgets'        => __( 'Footer Widgets', 'wporg-themes' ),
				'front-page-post-form'  => __( 'Front Page Posting', 'wporg-themes' ),
				'full-width-template'   => __( 'Full Width Template', 'wporg-themes' ),
				'microformats'          => __( 'Microformats', 'wporg-themes' ),
				'post-formats'          => __( 'Post Formats', 'wporg-themes' ),
				'rtl-language-support'  => __( 'RTL Language Support', 'wporg-themes' ),
				'sticky-post'           => __( 'Sticky Post', 'wporg-themes' ),
				'theme-options'         => __( 'Theme Options', 'wporg-themes' ),
				'threaded-comments'     => __( 'Threaded Comments', 'wporg-themes' ),
				'translation-ready'     => __( 'Translation Ready', 'wporg-themes' ),
			),
			__( 'Subject', 'wporg-themes' )  => array(
				'blog'           => __( 'Blog', 'wporg-themes' ),
				'e-commerce'     => __( 'E-Commerce', 'wporg-themes' ),
				'education'      => __( 'Education', 'wporg-themes' ),
				'entertainment'  => __( 'Entertainment', 'wporg-themes' ),
				'food-and-drink' => __( 'Food & Drink', 'wporg-themes' ),
				'holiday'        => __( 'Holiday', 'wporg-themes' ),
				'news'           => __( 'News', 'wporg-themes' ),
				'photography'    => __( 'Photography', 'wporg-themes' ),
				'portfolio'      => __( 'Portfolio', 'wporg-themes' ),
			),
		);
	}

	if ( 'deprecated' === $include || 'all' === $include ) {
		$features[ __( 'Colors', 'wporg-themes' ) ] = array(
			'black'  => __( 'Black', 'wporg-themes' ),
			'blue'   => __( 'Blue', 'wporg-themes' ),
			'brown'  => __( 'Brown', 'wporg-themes' ),
			'gray'   => __( 'Gray', 'wporg-themes' ),
			'green'  => __( 'Green', 'wporg-themes' ),
			'orange' => __( 'Orange', 'wporg-themes' ),
			'pink'   => __( 'Pink', 'wporg-themes' ),
			'purple' => __( 'Purple', 'wporg-themes' ),
			'red'    => __( 'Red', 'wporg-themes' ),
			'silver' => __( 'Silver', 'wporg-themes' ),
			'tan'    => __( 'Tan', 'wporg-themes' ),
			'white'  => __( 'White', 'wporg-themes' ),
			'yellow' => __( 'Yellow', 'wporg-themes' ),
			'dark'   => __( 'Dark', 'wporg-themes' ),
			'light'  => __( 'Light', 'wporg-themes' ),
		);

		if ( 'deprecated' === $include ) {
			// Initialize arrays.
			$features[ __( 'Layout', 'wporg-themes' ) ]   = array();
			$features[ __( 'Features', 'wporg-themes' ) ] = array();
			$features[ __( 'Subject', 'wporg-themes' ) ]  = array();
		}

		$features[ __( 'Layout', 'wporg-themes' ) ] = array_merge( $features[ __( 'Layout', 'wporg-themes' ) ], array(
			'fixed-layout'      => __( 'Fixed Layout', 'wporg-themes' ),
			'fluid-layout'      => __( 'Fluid Layout', 'wporg-themes' ),
			'responsive-layout' => __( 'Responsive Layout', 'wporg-themes' ),
		) );

		$features[ __( 'Features', 'wporg-themes' ) ] = array_merge( $features[ __( 'Features', 'wporg-themes' ) ], array(
			'blavatar' => __( 'Blavatar', 'wporg-themes' ),
		) );

		$features[ __( 'Subject', 'wporg-themes' ) ] = array_merge( $features[ __( 'Subject', 'wporg-themes' ) ], array(
			'photoblogging' => __( 'Photoblogging', 'wporg-themes' ),
			'seasonal'      => __( 'Seasonal', 'wporg-themes' ),
		) );
	}

	return $features;
}

/**
 * Returns an array of [ tag_slug => translated_tag_name] tags for translation within JS
 *
 * @return array List of features.
 */
function wporg_themes_get_tag_translations() {
	$translations = array();
	foreach ( wporg_themes_get_feature_list( 'all' ) as $group => $tags ) {
		$translations = array_merge( $translations, $tags );
	}
	return $translations;
}

/**
 * Override the embed template with our own
 */
function wporg_themes_embed_template( $template ) {
	$theme_embed_template = locate_template( 'embed.php' );
	if ( $theme_embed_template ) {
		return $theme_embed_template;
	}
	return $template;
}
add_filter( 'embed_template', 'wporg_themes_embed_template' );

include_once WP_CONTENT_DIR . '/plugins/jetpack/modules/seo-tools/jetpack-seo.php';
include_once WP_CONTENT_DIR . '/plugins/jetpack/modules/seo-tools/jetpack-seo-posts.php';
include_once WP_CONTENT_DIR . '/plugins/jetpack/modules/seo-tools/jetpack-seo-titles.php';
include_once WP_CONTENT_DIR . '/plugins/jetpack/modules/seo-tools/jetpack-seo-utils.php';

if ( class_exists( 'Jetpack_SEO' ) ) {
	new Jetpack_SEO;
}
