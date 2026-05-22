<?php
/**
 * Loads plugin text domain.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles loading of the plugin text domain for translations.
 *
 * @since 1.0.0
 */
class CURAI_I18n {

	/**
	 * Loads the plugin text domain from the languages directory.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function load(): void {
		load_plugin_textdomain(
			'curator-ai',
			false,
			dirname( CURAI_PLUGIN_BASE ) . '/languages/'
		);
	}
}
