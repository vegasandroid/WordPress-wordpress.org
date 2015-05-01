<?php
/**
 * This script updates cached data for some heavy queries.
 */
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/glotpress/gp-load.php';

class WPorg_Update_Cached_Data extends GP_CLI {

	public $cache_group = 'wporg-translate';

	public function run() {
		$this->update_contributors_count();
		$this->update_translation_status();
	}

	/**
	 * Fetches contributors per locale.
	 */
	private function update_contributors_count() {
		global $gpdb;

		$results = $gpdb->get_results( "
			SELECT ts.locale, COUNT( DISTINCT( t.user_id ) ) AS count
			FROM $gpdb->translations t
				INNER JOIN $gpdb->translation_sets AS ts
					ON ts.id = t.translation_set_id
			WHERE
				( t.status = 'current' || t.status = 'old' )
				AND t.user_id IS NOT NULL
				AND t.user_id <> '0'
			GROUP BY ts.locale
		" );

		$counts = array();
		if ( $results ) {
			foreach ( $results as $result ) {
				$counts[ $result->locale ] = $result->count;
			}
		}

		wp_cache_set( 'contributors-count', $counts, $this->cache_group );
	}

	/**
	 * Calculates the translation status of the WordPress project per locale.
	 */
	private function update_translation_status() {
		global $gpdb;

		$locales = GP::$translation_set->existing_locales();
		$projects = GP::$project->many( "
			SELECT *
			FROM $gpdb->projects
			WHERE
				path LIKE 'wp/dev%'
				AND active = '1'
		" );
		$translation_status = array();
		foreach ( $projects as $project ) {
			foreach ( $locales as $locale ) {
				$set = GP::$translation_set->by_project_id_slug_and_locale(
					$project->id,
					'default',
					$locale
				);

				if ( ! $set ) {
					continue;
				}

				if ( ! isset( $translation_status[ $locale ] ) ) {
					$translation_status[ $locale ] = new StdClass;
					$translation_status[ $locale ]->waiting_count = $set->waiting_count();
					$translation_status[ $locale ]->current_count = $set->current_count();
					$translation_status[ $locale ]->fuzzy_count   = $set->fuzzy_count();
					$translation_status[ $locale ]->all_count     = $set->all_count();
				} else {
					$translation_status[ $locale ]->waiting_count += $set->waiting_count();
					$translation_status[ $locale ]->current_count += $set->current_count();
					$translation_status[ $locale ]->fuzzy_count   += $set->fuzzy_count();
					$translation_status[ $locale ]->all_count     += $set->all_count();
				}
			}
		}

		wp_cache_set( 'translation-status', $translation_status, $this->cache_group );
	}
}

$wporg_update_cached_data = new WPorg_Update_Cached_Data;
$wporg_update_cached_data->run();
