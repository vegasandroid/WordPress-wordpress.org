<?php
namespace WordPressdotorg\Rosetta\User\Role;

class General_Translation_Editor implements Role {

	/**
	 * Retrieves the name of this role.
	 *
	 * @return string The name of this role.
	 */
	public static function get_name() {
		return 'general_translation_editor';
	}

	/**
	 * Retrieves the display name of this role.
	 *
	 * @param bool $translated Whether the name should be translated or not.
	 * @return string. The display name.
	 */
	public static function get_display_name( $translated = false ) {
		return $translated ? __( 'General Translation Editor', 'rosetta' ) : 'General Translation Editor';
	}

	/**
	 * Retrieves the capabilities of this role.
	 *
	 * @return array Array of capabilities.
	 */
	public static function get_capabilities() {
		return [];
	}

	/**
	 * Retrieves the dynamic capabilities for this role.
	 *
	 * @return array Array of dynamic capabilities.
	 */
	public static function get_dynamic_capabilities() {
		return [
			// Core.
			'read'                       => true,

			// Custom.
			'manage_translation_editors' => true,
		];
	}

	/**
	 * Whether this role is an additional role.
	 *
	 * @return bool True if role is additional, false if not.
	 */
	public static function is_additional_role() {
		return true;
	}

	/**
	 * Whether this role is an editable role.
	 *
	 * @see get_editable_roles()
	 *
	 * @return bool True if role is editable, false if not.
	 */
	public static function is_editable_role() {
		return false;
	}
}
