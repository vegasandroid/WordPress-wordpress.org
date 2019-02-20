<?php

$core_tests_directory = getenv( 'WP_TESTS_DIR' );

if ( ! $core_tests_directory ) {
	echo "\nPlease set the WP_TESTS_DIR environment variable to the folder where WordPress' PHPUnit tests live --";
	echo "\ne.g., export WP_TESTS_DIR=/srv/www/wordpress-develop/tests/phpunit\n";

	return;
}

require_once( $core_tests_directory . '/includes/functions.php' );
require_once( dirname( dirname( $core_tests_directory ) ) . '/build/wp-admin/includes/plugin.php' );

/*
 * Load individual plugin bootstrappers
 *
 * There may eventually be cases where these conflict with one another (e.g., some need to run in context of
 * wp-admin while others need to run in front-end context), but it works for now. If they ever do conflict, then
 * that's probably a smell that we shouldn't be using PHPUnit for integration tests, though.
 *
 * If we don't want to migrate to Selenium etc, then another option might be using a PHPUnit listener to load the
 * bootstrap for a particular suite before the suite loads (see https://stackoverflow.com/a/30170762/450127). It's
 * not clear if that would properly isolate them from each other, and allow multiple independent contexts, though.
 */
require_once( __DIR__ . '/public_html/wp-content/plugins/wordcamp-organizer-reminders/tests/bootstrap.php' );
require_once( __DIR__ . '/public_html/wp-content/plugins/wordcamp-remote-css/tests/bootstrap.php' );

require_once( $core_tests_directory . '/includes/bootstrap.php' );
