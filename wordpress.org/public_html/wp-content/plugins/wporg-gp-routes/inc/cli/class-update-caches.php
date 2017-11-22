<?php

namespace WordPressdotorg\GlotPress\Routes\CLI;

use GP;
use WP_CLI;
use WP_CLI_Command;

class Update_Caches extends WP_CLI_Command {

	private $cache_group = 'wporg-translate';

	private $running_all = false;

	/**
	 * Update all caches.
	 */
	public function all() {
		$this->running_all = true;
		$this->contributors_count();
		$this->translation_status();
		$this->existing_locales();
		$this->running_all = false;
	}

	/**
	 * Update contributors count per locale.
	 *
	 * @subcommand contributors-count
	 */
	public function contributors_count() {
		global $wpdb;

		if ( ! isset( $wpdb->user_translations_count ) ) {
			WP_CLI::error( 'The stats plugin seems not to be activated.' );
			return;
		}

		$locales   = GP::$translation_set->existing_locales();
		$db_counts = $wpdb->get_results(
			"SELECT `locale`, COUNT( DISTINCT user_id ) as `count` FROM {$wpdb->user_translations_count} WHERE `accepted` > 0 GROUP BY `locale`",
			OBJECT_K
		);

		if ( ! $db_counts || ! $locales ) {
			if ( $this->running_all ) {
				WP_CLI::warning( 'Retrieving contributors count failed.' );
				return;
			} else {
				WP_CLI::error( 'Retrieving contributors count failed.' );
			}
		}

		$counts = array();
		foreach ( $locales as $locale ) {
			if ( isset( $db_counts[ $locale ] ) ) {
				$counts[ $locale ] = (int) $db_counts[ $locale ]->count;
			} else {
				$counts[ $locale ] = 0;
			}
		}

		wp_cache_set( 'contributors-count', $counts, $this->cache_group );
		WP_CLI::success( 'Contributors count was updated.' );
	}

	/**
	 * Calculate the translation status of the WordPress project per locale.
	 *
	 * @subcommand translation-status
	 */
	public function translation_status() {
		global $wpdb;

		if ( ! isset( $wpdb->project_translation_status ) ) {
			if ( $this->running_all ) {
				WP_CLI::warning( 'Retrieving translation status failed: The stats plugin seems not to be activated.' );
				return;
			} else {
				WP_CLI::error( 'Retrieving translation status failed: The stats plugin seems not to be activated.' );
			}
		}

		$translation_status = $wpdb->get_results( $wpdb->prepare(
			"SELECT `locale`, `all` AS `all_count`, `waiting` AS `waiting_count`, `current` AS `current_count`, `fuzzy` AS `fuzzy_count`
			FROM {$wpdb->project_translation_status}
			WHERE `project_id` = %d AND `locale_slug` = %s",
			2, 'default' // 2 = wp/dev
		), OBJECT_K );

		if ( ! $translation_status ) {
			if ( $this->running_all ) {
				WP_CLI::warning( 'Retrieving translation status failed.' );
				return;
			} else {
				WP_CLI::error( 'Retrieving translation status failed.' );
			}
		}

		wp_cache_set( 'translation-status', $translation_status, $this->cache_group );
		WP_CLI::success( 'Translation status was updated.' );
	}

	/**
	 * Update cache for existing locales.
	 *
	 * @subcommand existing-locales
	 */
	public function existing_locales() {
		$existing_locales = GP::$translation_set->existing_locales();

		if ( ! $existing_locales ) {
			if ( $this->running_all ) {
				WP_CLI::warning( 'Retrieving existing locales failed.' );
				return;
			} else {
				WP_CLI::error( 'Retrieving existing locales failed.' );
			}
		}

		wp_cache_set( 'existing-locales', $existing_locales, $this->cache_group );
		WP_CLI::success( 'Existing locales were updated.' );
	}
}
