<?php
/**
 * WP.org login' functions and definitions.
 *
 * @package wporg-login
 */



/**
 * Registers support for various WordPress features.
 */
function wporg_login_setup() {
	load_theme_textdomain( 'wporg-login' );
}
add_action( 'after_setup_theme', 'wporg_login_setup' );



/**
 * Extend the default WordPress body classes.
 *
 * @param array $classes A list of existing body class values.
 * @return array The filtered body class list.
 */
function wporg_login_body_class( $classes ) {
//	$classes[] = 'wporg-responsive';
	$classes[] = 'wporg-login';
	return $classes;
}
add_filter( 'body_class', 'wporg_login_body_class' );



/**
 * Remove the toolbar.
 */
function wporg_login_init() {
	show_admin_bar( false );
}
add_action( 'init', 'wporg_login_init' );
