<?php
/**
 * This holds browser data in a separate file so we can bump version numbers
 * without cluttering the SVN.
 *
 * @package BrowseHappy
 */

/**
 * Returns current version numbers for all browsers.
 *
 * These are for major release branches, not full build numbers.
 * Firefox 3.6, 4, etc., not Chrome 11.0.696.65.
 *
 * @return array Associative array of browser names with their respective
 *               current (or somewhat current) version number.
 */
function get_browser_current_versions() {
	return array(
		'Chrome'            => '18', // Lowest version at the moment (mobile)
		'Firefox'           => '16',
		'Opera'             => '12.11',
		'Safari'            => '5',
		'Internet Explorer' => '9', // Left at 9 until Windows 7 adopts 10
	);
}

/**
 * Returns browser data for a given browser.
 *
 * @param string|false $browser The name of the browser. Default false.
 * @return false|array {
 *     Array of data about the browser. False if the browser is unknown.
 *
 *     @type string    $name        Name of the browser.
 *     @type string    $wikipedia   Wikipedia name for the browser.
 *     @type int|float $normalized  How should browser version number be
 *                                  normalized? 1 == just first number, else
 *                                  include second number (i.e. decimal value)
 *     @type string    $url         The home URL for the browser.
 *     @type string    $img_src     The non-HTTPs URL for the browser's logo image.
 *     @type string    $img_src_ssl The HTTPS URL for the browser's logo image.
 * }
 */
function browsehappy_api_get_browser_data( $browser = false ) {

	$http = 'http://s.wordpress.org/images/browsers/';
	$https = 'https://wordpress.org/images/browsers/';

	$data = array(
		'Internet Explorer' => (object) array(
			'name'        => 'Internet Explorer',
			'wikipedia'   => 'Internet_Explorer',
			'normalized'  => 1, // just first number
			'url'         => 'http://www.microsoft.com/windows/internet-explorer/',
			'img_src'     => $http . 'ie.png',
			'img_src_ssl' => $https . 'ie.png',
		),
		'Firefox' => (object) array(
			'name'        => 'Mozilla Firefox',
			'wikipedia'   => 'Firefox',
			'normalized'  => 1.5, // include second number if non-zero
			'url'         => 'http://www.firefox.com/',
			'img_src'     => $http . 'firefox.png',
			'img_src_ssl' => $https . 'firefox.png',
		),
		'Safari' => (object) array(
			'name'        => 'Safari',
			'wikipedia'   => 'Safari',
			'normalized'  => 1.5, // include second number if non-zero
			'url'         => 'http://www.apple.com/safari/',
			'img_src'     => $http . 'safari.png',
			'img_src_ssl' => $https . 'safari.png',
		),
		'Opera' => (object) array(
			'name'        => 'Opera',
			'wikipedia'   => 'Opera',
			'normalized'  => 2, // include second number
			'url'         => 'http://www.opera.com/',
			'img_src'     => $http . 'opera.png',
			'img_src_ssl' => $https . 'opera.png',
		),
		'Chrome' => (object) array(
			'name'        => 'Google Chrome',
			'wikipedia'   => 'Google_Chrome',
			'normalized'  => 1, // just first number
			'url'         => 'http://www.google.com/chrome',
			'img_src'     => $http . 'chrome.png',
			'img_src_ssl' => $https . 'chrome.png',
		),
	);

	if ( false === $browser ) {
		return $data;
	}

	if ( ! isset( $data[ $browser ] ) ) {
		return false;
	}

	return $data[ $browser ];
}
