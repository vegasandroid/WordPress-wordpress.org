<?php

namespace WordPressdotorg\GlotPress\Plugin_Directory\Sync;

use GP;
use GP_Locales;
use GP_Translation;

class Translation_Sync {
	public $master_project = 'wp-plugins';

	private $queue = array();

	public $project_mapping = array(
		'dev'           => 'stable',
		'stable'        => 'dev',
		'dev-readme'    => 'stable-readme',
		'stable-readme' => 'dev-readme',
	);

	public function register_events() {
		add_action( 'gp_translation_created', array( $this, 'queue_translation_for_sync' ), 5 );
		add_action( 'gp_translation_saved', array( $this, 'queue_translation_for_sync' ), 5 );

		add_action( 'shutdown', array( $this, 'sync_translations' ) );
	}

	/**
	 * Adds a translation to a cache purge queue when a translation was created
	 * or updated.
	 *
	 * @param \GP_Translation $translation Created/updated translation.
	 */
	public function queue_translation_for_sync( $translation ) {
		global $wpdb;

		// Only propagate current translations without warnings.
		if ( 'current' !== $translation->status || ! empty( $translation->warnings ) ) {
			return;
		}

		$project = GP::$project->one(
			"SELECT p.* FROM {$wpdb->gp_projects} AS p JOIN {$wpdb->gp_originals} AS o ON o.project_id = p.id WHERE o.id = %d",
			$translation->original_id
		);

		if ( ! $project ) {
			return;
		}

		if ( ! $this->project_is_plugin( $project->path ) ) {
			return;
		}

		$this->queue[ $project->path ][ $translation->id ] = $translation;
	}

	/**
	 * Syncs translations between two plugin projects.
	 */
	public function sync_translations() {
		if ( empty( $this->queue ) ) {
			return;
		}

		// Avoid recursion.
		remove_action( 'gp_translation_created', array( $this, 'queue_translation_for_sync' ), 5 );
		remove_action( 'gp_translation_saved', array( $this, 'queue_translation_for_sync' ), 5 );

		foreach ( $this->queue as $project_path => $translations ) {
			$project = $this->get_dev_or_stable_project( $project_path );
			if ( ! $project ) {
				continue;
			}

			foreach ( $translations as $translation ) {
				$original = GP::$original->get( $translation->original_id );
				if ( ! $original ) {
					continue;
				}

				$translation_set = GP::$translation_set->get( $translation->translation_set_id );
				if ( ! $translation_set ) {
					continue;
				}

				$original_counterpart = GP::$original->by_project_id_and_entry(
					$project->id,
					$original,
					'+active'
				);

				if ( ! $original_counterpart ) {
					continue;
				}

				$translation_set_counterpart = GP::$translation_set->by_project_id_slug_and_locale(
					$project->id,
					$translation_set->slug,
					$translation_set->locale
				);

				if ( ! $translation_set_counterpart ) {
					continue;
				}

				$this->copy_translation_into_set( $translation, $translation_set_counterpart, $original_counterpart );
			}
		}
	}

	/**
	 * Duplicates a translation to another translation set.
	 *
	 * @param \GP_Translation     $translation         The translation which should be duplicated.
	 * @param \GP_Translation_Set $new_translation_set The new translation set.
	 * @param \GP_Original        $new_original        The new original.
	 * @return bool False on failure, true on success.
	 */
	private function copy_translation_into_set( $translation, $new_translation_set, $new_original ) {
		$locale = GP_Locales::by_slug( $new_translation_set->locale );
		$new_translation = array();

		for ( $i = 0; $i < $locale->nplurals; $i++ ) {
			$new_translation[] = $translation->{"translation_{$i}"};
		}

		// Check if the translation already exists.
		$existing_translations = GP::$translation->find( array(
			'translation_set_id' => $new_translation_set->id,
			'original_id'        => $new_original->id,
			'status'             => array( 'current', 'waiting' ),
		) );

		foreach ( $existing_translations as $_existing_translation ) {
			$existing_translation = array();
			for ( $i = 0; $i < $locale->nplurals; $i++ ) {
				$existing_translation[] = $_existing_translation->{"translation_{$i}"};
			}

			if ( $existing_translation === $new_translation ) {
				$_existing_translation->set_as_current();
				return true;
			}
		}

		$copy = new GP_Translation( $translation->fields() );
		$copy->original_id = $new_original->id;
		$copy->translation_set_id = $new_translation_set->id;
		$copy->status = 'current';

		$translation = GP::$translation->create( $copy );
		if ( ! $translation ) {
			return false;
		}

		$translation->set_as_current();

		return true;
	}

	/**
	 * Retrieves the counterpart of a plugin project.
	 *
	 * @param string $project_path The path of a plugin project.
	 * @return \GP_Project|null A project on success, null on failure.
	 */
	public function get_dev_or_stable_project( $project_path ) {
		static $project_cache;

		if ( null === $project_cache ) {
			$project_cache = array();
		}

		if ( isset( $project_cache[ $project_path ] ) ) {
			return $project_cache[ $project_path ];
		}

		$project = basename( $project_path );
		$counterpart = $this->project_mapping[ $project ];
		$new_project_path = preg_replace( "#/{$project}$#", "/$counterpart", $project_path, 1 );

		$project = GP::$project->by_path( $new_project_path );
		$project_cache[ $project_path ] = $project;

		return $project;
	}

	/**
	 * Returns whether a project path belongs to the plugins project.
	 *
	 * @param string $path Path of a project.
	 *
	 * @return bool True if it's a plugin, false if not.
	 */
	public function project_is_plugin( $path ) {
		if ( empty( $path ) ) {
			return false;
		}

		$path = '/' . trim( $path, '/' ) . '/';
		if ( false === strpos( $path, "/{$this->master_project}/" ) ) {
			return false;
		}

		return true;
	}
}
