<?php
/**
 * Main plugin class — singleton orchestrator.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/class-curai-activator.php';
require_once CURAI_PLUGIN_DIR . 'includes/class-curai-deactivator.php';
require_once CURAI_PLUGIN_DIR . 'includes/class-curai-i18n.php';
require_once CURAI_PLUGIN_DIR . 'includes/compat/class-curai-ai-client-detector.php';
require_once CURAI_PLUGIN_DIR . 'includes/admin/class-curai-admin.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-registrar.php';
require_once CURAI_PLUGIN_DIR . 'includes/automation/class-curai-automation.php';

/**
 * Main plugin orchestrator — singleton that bootstraps all Curator AI functionality.
 *
 * @since 1.0.0
 */
final class CURAI_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var CURAI_Plugin|null
	 */
	private static ?CURAI_Plugin $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @since 1.0.0
	 * @return CURAI_Plugin The single plugin instance.
	 */
	public static function instance(): CURAI_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use instance() to obtain the singleton.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Hooks all plugin components into WordPress and triggers a DB upgrade check.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( 'CURAI_I18n', 'load' ) );

		if ( is_admin() ) {
			CURAI_Admin::boot();
		}

		CURAI_Ability_Registrar::boot();
		CURAI_Automation::boot();

		$this->maybe_upgrade();
	}

	/**
	 * Runs activation tasks (create tables, set defaults, stamp DB version).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		CURAI_Activator::activate();
	}

	/**
	 * Runs deactivation tasks (clears transients and scheduled actions).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		CURAI_Deactivator::deactivate();
	}

	/**
	 * Runs database migrations when the stored DB version is below the current target.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_upgrade(): void {
		$current = get_option( 'curai_db_version', '0' );
		if ( version_compare( $current, CURAI_DB_VERSION, '<' ) ) {
			CURAI_Activator::create_tables();
			CURAI_Activator::stamp_db_version();
		}
	}
}
