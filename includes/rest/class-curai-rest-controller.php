<?php
/**
 * Registers all Curator AI REST routes under curator-ai/v1.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/rest/class-curai-rest-abilities.php';
require_once CURAI_PLUGIN_DIR . 'includes/rest/class-curai-rest-audit.php';
require_once CURAI_PLUGIN_DIR . 'includes/rest/class-curai-rest-settings.php';
require_once CURAI_PLUGIN_DIR . 'includes/rest/class-curai-rest-status.php';

/**
 * Bootstraps all Curator AI REST endpoint classes.
 *
 * Call CURAI_REST_Controller::boot() during plugin init to register every
 * endpoint under the curator-ai/v1 namespace when rest_api_init fires.
 *
 * @since 1.0.0
 */
final class CURAI_REST_Controller {

	/**
	 * Attach the rest_api_init hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Delegates route registration to each endpoint class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_routes(): void {
		CURAI_REST_Abilities::register();
		CURAI_REST_Audit::register();
		CURAI_REST_Settings::register();
		CURAI_REST_Status::register();
	}
}
