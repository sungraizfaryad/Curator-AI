<?php
/**
 * Plugin text domain handler.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin text domain registration.
 *
 * WordPress.org auto-loads translations for WP.org-hosted plugins since WP 4.6,
 * so no manual load_plugin_textdomain() call is needed. Class kept as a hook
 * surface for any future locale-related setup.
 *
 * @since 1.0.0
 */
class CURAI_I18n {

	/**
	 * Placeholder hook callback. Translations auto-load on WP.org-hosted plugins.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function load(): void {
		// Intentionally empty: WP 4.6+ auto-loads translations for WP.org-hosted plugins.
	}
}
