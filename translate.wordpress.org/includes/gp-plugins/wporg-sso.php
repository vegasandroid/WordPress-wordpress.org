<?php
/**
 * Loads SSO GlotPress plugin from linked external
 * 
 * @author stephdau
 */

$sso_plugin_path = __DIR__ . '/wporg-sso/gp-plugin.php';

if ( file_exists( $sso_plugin_path ) ) {
	require_once( $sso_plugin_path );
}