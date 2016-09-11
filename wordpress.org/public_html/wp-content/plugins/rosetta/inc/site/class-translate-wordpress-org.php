<?php
namespace WordPressdotorg\Rosetta\Site;

use WP_Site;

class Translate_WordPress_Org implements Site {

	/**
	 * Tests whether this site manager is eligible for a site.
	 *
	 * @param WP_Site $site The site object.
	 *
	 * @return bool True if site is eligible, false otherwise.
	 */
	public static function test( WP_Site $site ) {
		if ( 'translate.wordpress.org' === $site->domain ) {
			return true;
		}

		return false;
	}

	/**
	 * Registers actions and filters.
	 */
	public function register_events() {
		// TODO: Implement register_events() method.
	}
}
