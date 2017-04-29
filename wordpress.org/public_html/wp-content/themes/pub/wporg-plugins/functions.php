<?php
/**
 * Plugin Directory functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPressdotorg\Plugin_Directory\Theme
 */

namespace WordPressdotorg\Plugin_Directory\Theme;
use WordPressdotorg\Plugin_Directory\Plugin_Directory;
use WordPressdotorg\Plugin_Directory\Template;

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function setup() {

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Don't include Adjacent Posts functionality
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function content_width() {
	$GLOBALS['content_width'] = apply_filters( 'wporg_plugins_content_width', 640 );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\content_width', 0 );

/**
 * Enqueue scripts and styles.
 */
function scripts() {
	$suffix = is_rtl() ? '-rtl' : '';
	wp_enqueue_style( 'wporg-plugins-style', get_template_directory_uri() . "/css/style{$suffix}.css", array(), '20170420b' );

	wp_enqueue_script( 'wporg-plugins-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );
	wp_enqueue_script( 'wporg-plugins-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );

	if ( is_singular( 'plugin' ) ) {
		wp_enqueue_script( 'wporg-plugins-accordion', get_template_directory_uri() . '/js/section-accordion.js', array(), '20161121', true );
		wp_enqueue_script( 'wporg-plugins-faq-accordion', get_template_directory_uri() . '/js/section-faq.js', array(), '20170404', true );
	}

	if ( ! is_404() ) {
		wp_enqueue_script( 'wporg-plugins-locale-banner', get_template_directory_uri() . '/js/locale-banner.js', array(), '20160622', true );
		wp_localize_script( 'wporg-plugins-locale-banner', 'wporgLocaleBanner', array(
			'apiURL'        => rest_url( '/plugins/v1/locale-banner' ),
			'currentPlugin' => is_singular( 'plugin' ) ? get_queried_object()->post_name : '',
		) );
	}

	if ( get_query_var( 'plugin_advanced' ) ) {
		wp_enqueue_script( 'google-charts-loader', 'https://www.gstatic.com/charts/loader.js', array(), false, true );
		wp_enqueue_script( 'wporg-plugins-stats', get_template_directory_uri() . '/js/stats.js', array( 'jquery', 'google-charts-loader' ), '20170328', true );

		wp_localize_script( 'wporg-plugins-stats', 'pluginStats', array(
			'slug' => is_singular( 'plugin' ) ? get_queried_object()->post_name : '',
			'l10n' => array(
				'date'      => __( 'Date', 'wporg-plugins' ),
				'downloads' => __( 'Downloads', 'wporg-plugins' ),
				'noData'    => __( 'No data yet', 'wporg-plugins' ),
				'today'     => __( 'Today', 'wporg-plugins' ),
				'yesterday' => __( 'Yesterday', 'wporg-plugins' ),
				'last_week' => __( 'Last Week', 'wporg-plugins' ),
				'all_time'  => __( 'All Time', 'wporg-plugins' ),
			),
		) );
	}

	// React is currently only used on detail pages
	if ( is_single() ) {
		wp_enqueue_script( 'wporg-plugins-client', get_template_directory_uri() . '/js/theme.js', array(), '20170420', true );
		wp_localize_script( 'wporg-plugins-client', 'pluginDirectory', array(
			'endpoint' => untrailingslashit( rest_url() ), // 'https://wordpress.org/plugins-wp/wp-json',
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'base'     => get_blog_details()->path,
			'userId'   => get_current_user_id(),
		) );
		wp_localize_script( 'wporg-plugins-client', 'localeData', array(
			'' => array(
				'Plural-Forms' => _x( 'nplurals=2; plural=n != 1;', 'plural forms', 'wporg-plugins' ),
				'Language'     => _x( 'en', 'language (fr, fr_CA)', 'wporg-plugins' ),
				'localeSlug'   => _x( 'en', 'locale slug', 'wporg-plugins' ) ,
			),
		) );
	}

}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\scripts' );

/**
 * Don't split plugin content in the front-end.
 */
function content() {
	remove_filter( 'the_content', array( Plugin_Directory::instance(), 'filter_post_content_to_correct_page' ), 1 );
}
add_action( 'template_redirect', __NAMESPACE__ . '\content' );

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param \WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
}
add_action( 'customize_register', __NAMESPACE__ . '\customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function customize_preview_js() {
	wp_enqueue_script( 'wporg_plugins_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init',  __NAMESPACE__ . '\customize_preview_js' );


function custom_body_class( $classes ) {
	$classes[] = 'no-js';
	return $classes;
}
add_filter( 'body_class', __NAMESPACE__ . '\custom_body_class' );

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
function document_title( $title ) {
	if ( is_front_page() ) {
		$title['title'] = __( 'WordPress Plugins', 'wporg-plugins' );
	} else {
		$title['site'] = __( 'WordPress Plugins', 'wporg-plugins' );
	}

	return $title;
}
add_filter( 'document_title_parts', __NAMESPACE__ . '\document_title' );

/**
 * Set the separator for the document title.
 *
 * @return string Document title separator.
 */
function document_title_separator() {
	return ( is_feed() ) ? '&#8212;' : '&mdash;';
}
add_filter( 'document_title_separator', __NAMESPACE__ . '\document_title_separator' );

/**
 * Shorten excerpt length on index pages, so plugins cards are all the same height.
 *
 * @param string $excerpt The excerpt.
 * @return string
 */
function excerpt_length( $excerpt ) {
	if ( is_home() || is_archive() ) {
		/*
		 * translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		 * Do not translate into your own language.
		 */
		if ( strpos( _x( 'words', 'Word count type. Do not translate!', 'wporg-plugins' ), 'characters' ) === 0 ) {
			// Use the default limit of 55 characters for East Asian locales.
			$excerpt = wp_trim_words( $excerpt );
		} else {
			// Limit the excerpt to 15 words for other locales.
			$excerpt = wp_trim_words( $excerpt, 15 );
		}
	}

	return $excerpt;
}
add_filter( 'get_the_excerpt', __NAMESPACE__ . '\excerpt_length' );

/**
 * Adds meta tags for richer social media integrations.
 */
function social_meta_data() {
	if ( ! is_singular( 'plugin' ) ) {
		return;
	}

	$banner  = Template::get_plugin_banner();
	$banner['banner_2x'] = $banner['banner_2x'] ? $banner['banner'] : false;
	$icon = Template::get_plugin_icon();

	printf( '<meta property="og:title" content="%s" />' . "\n", the_title_attribute( array( 'echo' => false ) ) );
	printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( strip_tags( get_the_excerpt() ) ) );
	printf( '<meta property="og:site_name" content="WordPress.org" />' . "\n" );
	printf( '<meta property="og:type" content="website" />' . "\n" );
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( get_permalink() ) );
	printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
	printf( '<meta name="twitter:site" content="@WordPress">' . "\n" );

	if ( $banner['banner_2x'] ) {
		printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $banner['banner_2x'] ) );
	}
	if ( isset( $banner['banner'] ) ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $banner['banner'] ) );
	}
	if ( ! $icon['generated'] && ( $icon['icon_2x'] || $icon['icon'] ) ) {
		printf( '<meta name="thumbnail" content="%s" />' . "\n", esc_url( $icon['icon_2x'] ?: $icon['icon'] ) );
	}
}
add_action( 'wp_head', __NAMESPACE__ . '\social_meta_data' );

/**
 * Adds hreflang link attributes to plugin pages.
 *
 * @link https://support.google.com/webmasters/answer/189077?hl=en Use hreflang for language and regional URLs.
 * @link https://sites.google.com/site/webmasterhelpforum/en/faq-internationalisation FAQ: Internationalisation.
 */
function hreflang_link_attributes() {
	if ( is_404() ) {
		return;
	}

	wp_cache_add_global_groups( array( 'locale-associations' ) );

	if ( false === ( $sites = wp_cache_get( 'local-sites', 'locale-associations' ) ) ) {
		global $wpdb;

		$sites = $wpdb->get_results( 'SELECT locale, subdomain FROM locales', OBJECT_K );
		if ( ! $sites ) {
			return;
		}

		require_once GLOTPRESS_LOCALES_PATH;

		foreach ( $sites as $site ) {
			$gp_locale = \GP_Locales::by_field( 'wp_locale', $site->locale );
			if ( ! $gp_locale ) {
				unset( $sites[ $site->locale ] );
				continue;
			}

			$hreflang = false;

			// Note that Google only supports ISO 639-1 codes.
			if ( isset( $gp_locale->lang_code_iso_639_1 ) && isset( $gp_locale->country_code ) ) {
				$hreflang = $gp_locale->lang_code_iso_639_1 . '-' . $gp_locale->country_code;
			} elseif ( isset( $gp_locale->lang_code_iso_639_1 ) ) {
				$hreflang = $gp_locale->lang_code_iso_639_1;
			} elseif ( isset( $gp_locale->lang_code_iso_639_2 ) ) {
				$hreflang = $gp_locale->lang_code_iso_639_2;
			} elseif ( isset( $gp_locale->lang_code_iso_639_3 ) ) {
				$hreflang = $gp_locale->lang_code_iso_639_3;
			}

			if ( $hreflang && 'art' !== $hreflang ) {
				$sites[ $site->locale ]->hreflang = strtolower( $hreflang );
			} else {
				unset( $sites[ $site->locale ] );
			}
		}

		// Add en_US to the list of sites.
		$sites['en_US'] = (object) array(
			'locale'    => 'en_US',
			'hreflang'  => 'en',
			'subdomain' => ''
		);

		uasort( $sites, function( $a, $b ) {
			return strcasecmp( $a->hreflang, $b->hreflang );
		} );

		wp_cache_set( 'local-sites', $sites, 'locale-associations' );
	}

	foreach ( $sites as $site ) {
		$url = sprintf(
			'https://%swordpress.org%s',
			$site->subdomain ? "{$site->subdomain}." : '',
			$_SERVER[ 'REQUEST_URI' ]
		);

		printf(
			'<link rel="alternate" href="%s" hreflang="%s" />' . "\n",
			esc_url( $url ),
			esc_attr( $site->hreflang )
		);
	}
}
add_action( 'wp_head', __NAMESPACE__ . '\hreflang_link_attributes' );

/**
 * Bold archive terms are made here.
 *
 * @param string $term The archive term to bold.
 * @return string
 */
function strong_archive_title( $term ) {
	return '<strong>' . $term . '</strong>';
}
add_action( 'wp_head', function() {
	add_filter( 'post_type_archive_title', __NAMESPACE__ . '\strong_archive_title' );
	add_filter( 'single_term_title',       __NAMESPACE__ . '\strong_archive_title' );
	add_filter( 'single_cat_title',        __NAMESPACE__ . '\strong_archive_title' );
	add_filter( 'single_tag_title',        __NAMESPACE__ . '\strong_archive_title' );
	add_filter( 'get_the_date',            __NAMESPACE__ . '\strong_archive_title' );
} );

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';
