<?php

/*
Plugin Name: WP15 - Locales
Description: Manage front-end locale switching.
Version:     0.1
Author:      WordPress Meta Team
Author URI:  https://make.wordpress.org/meta
*/

namespace WP15\Locales;
defined( 'WPINC' ) or die();

use GP_Locales;

require_once trailingslashit( dirname( __FILE__ ) ) . 'locale-detection/locale-detection.php';

/**
 * Register style and script assets for later enqueueing.
 */
function register_assets() {
	// Locale switcher script.
	wp_register_script(
		'locale-switcher',
		WP_CONTENT_URL . '/mu-plugins/assets/locale-switcher.js',
		array( 'jquery', 'select2' ),
		1,
		true
	);

	wp_localize_script(
		'locale-switcher',
		'WP15LocaleSwitcher',
		array(
			'locale' => get_locale(),
			'dir'    => is_rtl() ? 'rtl' : 'ltr',
		)
	);
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_assets' );

/**
 * Retreives all avaiable locales with their native names.
 *
 * See https://meta.trac.wordpress.org/browser/sites/trunk/wordpress.org/public_html/wp-content/themes/pub/wporg-login/functions.php#L150
 *
 * @return array Locales with their native names.
 */
function get_locales() {
	wp_cache_add_global_groups( [ 'locale-associations' ] );

	$wp_locales = wp_cache_get( 'locale-list', 'locale-associations' );
	if ( false === $wp_locales ) {
		$wp_locales = (array) $GLOBALS['wpdb']->get_col( 'SELECT locale FROM wporg_locales' );
		wp_cache_set( 'locale-list', $wp_locales, 'locale-associations' );
	}

	$wp_locales[] = 'en_US';

	require_once trailingslashit( dirname( __FILE__ ) ) . 'locales/locales.php';

	$locales = [];

	foreach ( $wp_locales as $locale ) {
		$gp_locale = GP_Locales::by_field( 'wp_locale', $locale );
		if ( ! $gp_locale ) {
			continue;
		}

		$locales[ $locale ] = $gp_locale->native_name;
	}

	natsort( $locales );

	return $locales;
}

/**
 * Prints markup for a simple language switcher.
 *
 * See https://meta.trac.wordpress.org/browser/sites/trunk/wordpress.org/public_html/wp-content/themes/pub/wporg-login/functions.php#L184
 */
function locale_switcher() {
	$current_locale = get_locale();

	?>
	<div class="wp15-locale-switcher-container">
		<form id="wp15-locale-switcher-form" action="" method="GET">
			<label for="wp15-locale-switcher">
				<span aria-hidden="true" class="dashicons dashicons-translation"></span>
				<span class="screen-reader-text"><?php _e( 'Select the language:', 'wp15' ); ?></span>
			</label>
			<select id="wp15-locale-switcher" name="locale">
				<?php
				foreach ( get_locales() as $locale => $locale_name ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $locale ),
						selected( $locale, $current_locale, false ),
						esc_html( $locale_name )
					);
				}
				?>
			</select>
		</form>
		<?php //todo Add blurb about submitting missing translations? ?>
	</div>
	<?php

	wp_enqueue_script( 'locale-switcher' );
}
