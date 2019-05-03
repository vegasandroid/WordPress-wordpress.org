<?php
namespace WordPressdotorg\Plugin_Directory;

// This script should only be called in a CLI environment.
if ( 'cli' != php_sapi_name() ) {
	die();
}

ob_start();

$opts = getopt( '', array( 'url:', 'abspath:', 'plugin:', 'changed-tags:', 'async', 'create' ) );

// Guess the default parameters:
if ( empty( $opts ) && $argc == 2 ) {
	$opts['plugin'] = $argv[1];
	$argv[1]        = '--plugin ' . $argv[1];
}
if ( empty( $opts['url'] ) ) {
	$opts['url'] = 'https://wordpress.org/plugins/';
}
if ( empty( $opts['abspath'] ) && false !== strpos( __DIR__, 'wp-content' ) ) {
	$opts['abspath'] = substr( __DIR__, 0, strpos( __DIR__, 'wp-content' ) );
}

if ( empty( $opts['changed-tags'] ) ) {
	$opts['changed-tags'] = array( 'trunk' );
} else {
	$opts['changed-tags'] = explode( ',', $opts['changed-tags'] );
}

$opts['async']  = isset( $opts['async'] );
$opts['create'] = isset( $opts['create'] );

foreach ( array( 'url', 'abspath', 'plugin' ) as $opt ) {
	if ( empty( $opts[ $opt ] ) ) {
		fwrite( STDERR, "Missing Parameter: $opt\n" );
		fwrite( STDERR, "Usage: php {$argv[0]} --plugin hello-dolly --abspath /home/example/public_html --url https://wordpress.org/plugins/\n" );
		fwrite( STDERR, "Optional: --async to queue a job to import, --create to create a Post if none exist.\n" );
		fwrite( STDERR, "--url and --abspath will be guessed if possible.\n" );
		die();
	}
}

// Bootstrap WordPress
$_SERVER['HTTP_HOST']   = parse_url( $opts['url'], PHP_URL_HOST );
$_SERVER['REQUEST_URI'] = parse_url( $opts['url'], PHP_URL_PATH );

require rtrim( $opts['abspath'], '/' ) . '/wp-load.php';

if ( ! class_exists( '\WordPressdotorg\Plugin_Directory\Plugin_Directory' ) ) {
	fwrite( STDERR, "Error! This site doesn't have the Plugin Directory plugin enabled.\n" );
	if ( defined( 'WPORG_PLUGIN_DIRECTORY_BLOGID' ) ) {
		fwrite( STDERR, "Run the following command instead:\n" );
		fwrite( STDERR, "\tphp " . implode( ' ', $argv ) . ' --url ' . get_site_url( WPORG_PLUGIN_DIRECTORY_BLOGID, '/' ) . "\n" );
	}
	die();
}

$plugin_slug  = $opts['plugin'];
$changed_tags = $opts['changed-tags'];
$start_time   = microtime( 1 );

// If the create flag is set, check if the post exists first:
if ( $opts['create'] && ! Plugin_Directory::get_plugin_post( $plugin_slug ) ) {

	$create_result = Plugin_Directory::create_plugin_post( array(
		'post_name' => $plugin_slug,
	) );

	if ( is_wp_error( $create_result ) ) {
		echo "Failed. {$plugin_slug} post was not be found, and failed to be created.\n";
		fwrite( STDERR, "[{$plugin_slug}] Plugin Import Failed: " . $create_result->get_error_message() . "\n" );
		exit( 1 );
	}
}

// If async, queue it to be parsed instead.
if ( $opts['async'] ) {
	Jobs\Plugin_Import::queue( $plugin_slug, array( 'tags_touched' => $changed_tags ) );
	echo "Queueing Import for $plugin_slug... OK\n";
	die();
}

echo "Processing Import for $plugin_slug... ";
try {
	$importer = new CLI\Import();
	$importer->import_from_svn( $plugin_slug, $changed_tags );
	echo 'OK. Took ' . round( microtime( 1 ) - $start_time, 2 ) . "s\n";
} catch ( \Exception $e ) {
	echo 'Failed. Took ' . round( microtime( 1 ) - $start_time, 2 ) . "s\n";

	fwrite( STDERR, "[{$plugin_slug}] Plugin Import Failed: " . $e->getMessage() . "\n" );
	exit( 1 );
}
