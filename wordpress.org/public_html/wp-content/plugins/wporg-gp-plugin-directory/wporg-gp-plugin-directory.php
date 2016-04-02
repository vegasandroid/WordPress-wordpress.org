<?php
/**
 * Plugin name: GlotPress: Plugin Directory Bridge
 * Description: Clears the content translation cache for plugins (readme/code) hosted on wordpress.org based on actions taken withing translate.wordpress.org.
 * Version:     1.1
 * Author:      WordPress.org
 * Author URI:  http://wordpress.org/
 * License:     GPLv2 or later
 */

class WPorg_GP_Plugin_Directory {
	public $master_project   = 'wp-plugins';
	public $i18n_cache_group = 'plugins-i18n';

	private $translation_edits_queue = array();

	public function __construct() {
		add_action( 'init', array( $this, 'add_global_cache_group' ) );
		add_action( 'gp_originals_imported', array( $this, 'originals_imported' ) );
		add_action( 'gp_translation_created', array( $this, 'queue_translation_for_cache_purge' ) );
		add_action( 'gp_translation_saved', array( $this, 'queue_translation_for_cache_purge' ) );

		// Cache purging is delayed until shutdown to prevent multiple purges for the same project.
		add_action( 'shutdown', array( $this, 'delete_plugin_i18n_cache_on_translation_edits' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->register_cli_commands();
		}
	}

	/**
	 * Registers CLI commands if WP-CLI is loaded.
	 */
	function register_cli_commands() {
		require_once __DIR__ . '/cli/import-plugin-translations.php';
		require_once __DIR__ . '/cli/set-plugin-project.php';
		require_once __DIR__ . '/cli/delete-plugin-project.php';

		WP_CLI::add_command( 'wporg-translate import-plugin-translations', 'WPorg_GP_CLI_Import_Plugin_Translations' );
		WP_CLI::add_command( 'wporg-translate set-plugin-project', 'WPorg_GP_CLI_Set_Plugin_Project' );
		WP_CLI::add_command( 'wporg-translate delete-plugin-project', 'WPorg_GP_CLI_Delete_Plugin_Project' );
	}

	/**
	 * Registers the global cache group wordpress.org/plugins/ uses for its display cache.
	 */
	public function add_global_cache_group() {
		wp_cache_add_global_groups( $this->i18n_cache_group );
	}

	/**
	 * Triggers a cache purge when a new originals were imported.
	 *
	 * @param int $project_id The project ID.
	 */
	public function originals_imported( $project_id ) {
		$project = GP::$project->get( $project_id );
		if ( empty( $project->path ) || ! $this->project_is_plugin( $project->path ) ) {
			return;
		}

		$this->delete_plugin_i18n_cache_keys_for_project( $project_id );
	}

	/**
	 * Adds a translation to a cache purge queue when a translation was created
	 * or updated.
	 *
	 * @param GP_Translation $translation Created/updated translation.
	 */
	public function queue_translation_for_cache_purge( $translation ) {
		if ( ! $this->project_is_plugin( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$this->translation_edits_queue[ $translation->original_id ][ $translation->translation_set_id ] = true;
	}

	/**
	 * Deletes the cache on a translation edits.
	 *
	 * @param GP_Translation $translation The edited translation.
	 */
	public function delete_plugin_i18n_cache_on_translation_edits() {
		if ( empty( $this->translation_edits_queue ) ) {
			return;
		}

		$purged = array();
		foreach ( $this->translation_edits_queue as $original_id => $set_ids ) {
			$original = GP::$original->get( $original_id );
			if ( ! $original ) {
				return;
			}

			foreach ( array_keys( $set_ids ) as $set_id ) {
				if ( in_array( "{$original->project_id}-{$set_id}", $purged ) ) {
					continue;
				}

				$translation_set = GP::$translation_set->get( $set_id );
				if ( ! $translation_set ) {
					return;
				}

				$this->delete_plugin_i18n_cache_keys_for_locale( $original->project_id, $translation_set->locale );
				$purged[] = "{$original->project_id}-{$set_id}";
			}
		}
	}

	/**
	 * Returns whether a project path belongs to the plugins project.
	 *
	 * @param string $path Path of a project.
	 *
	 * @return bool True if it's a plugin, false if not.
	 */
	private function project_is_plugin( $path ) {
		if ( empty( $path ) ) {
			return false;
		}

		$path = '/' . trim( $path, '/' ) . '/';
		if ( false === strpos( $path, "/{$this->master_project}/" ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Builds the cache prefix for a plugin.
	 *
	 * @param int $project_id The project ID.
	 *
	 * @return string The cache prefix, empty if project isn't a plugin.
	 */
	private function get_plugin_i18n_cache_prefix( $project_id ) {
		$project = GP::$project->get( $project_id );
		if ( empty( $project->path ) || !$this->project_is_plugin( $project->path ) ) {
			return '';
		}

		$project_dirs = explode( '/', trim( $project->path, '/' ) );
		if ( empty( $project_dirs ) || 3 !== count( $project_dirs ) || $project_dirs[0] !== $this->master_project ) {
			return '';
		}

		return "{$this->master_project}:{$project_dirs[1]}:{$project_dirs[2]}";
	}

	/**
	 * Deletes a set of known cache keys for a plugin.
	 *
	 * @param string $prefix Cache key prefix, such as 'plugin:livejournal-importer:readme-stable'.
	 * @param string $set    Set, such as 'original', 'fr', 'de'.
	 */
	private function delete_plugin_i18n_cache_keys_for( $prefix, $set ) {
		$suffixes = array(
			'translation_set_id', 'title', 'short_description', 'installation', 'description',
			'faq', 'screenshots', 'changelog', 'other_notes',
		);
		foreach ( $suffixes as $suffix ) {
			$cache_key = "{$prefix}:{$set}:{$suffix}";
			wp_cache_delete( $cache_key, $this->i18n_cache_group );
		}
	}

	/**
	 * Deletes the cached originals of a plugin.
	 *
	 * @param int $project_id The project ID.
	 */
	private function delete_plugin_i18n_cache_keys_for_project( $project_id ) {
		$prefix = $this->get_plugin_i18n_cache_prefix( (int) $project_id );
		if ( ! $prefix ) {
			return;
		}

		wp_cache_delete( "{$prefix}:originals", $this->i18n_cache_group );
		wp_cache_delete( "{$prefix}:branch_id", $this->i18n_cache_group );
		$this->delete_plugin_i18n_cache_keys_for( $prefix, 'original' );

		$translation_sets = (array) GP::$translation_set->by_project_id( $project_id );
		foreach ( $translation_sets as $translation_set ) {
			$this->delete_plugin_i18n_cache_keys_for( $prefix, $translation_set->locale );
		}
	}

	/**
	 * Deletes a cache keys of a locale for a plugin.
	 *
	 * @param int    $project_id The project ID.
	 * @param string $locale     GlotPress slug of a locale.
	 */
	private function delete_plugin_i18n_cache_keys_for_locale( $project_id, $locale ) {
		$prefix = $this->get_plugin_i18n_cache_prefix( (int) $project_id );
		if ( ! $prefix ) {
			return;
		}

		$this->delete_plugin_i18n_cache_keys_for( $prefix, $locale );
	}
}

function wporg_gp_plugin_directory() {
	global $wporg_gp_plugin_directory;

	if ( ! isset( $wporg_gp_plugin_directory ) ) {
		$wporg_gp_plugin_directory = new WPorg_GP_Plugin_Directory();
	}

	return $wporg_gp_plugin_directory;
}
add_action( 'plugins_loaded', 'wporg_gp_plugin_directory' );
