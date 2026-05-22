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

final class CURAI_Plugin {

    private static ?CURAI_Plugin $instance = null;

    public static function instance(): CURAI_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        add_action( 'init', array( 'CURAI_I18n', 'load' ) );

        if ( is_admin() ) {
            CURAI_Admin::boot();
        }

        $this->maybe_upgrade();
    }

    public static function activate(): void {
        CURAI_Activator::activate();
    }

    public static function deactivate(): void {
        CURAI_Deactivator::deactivate();
    }

    private function maybe_upgrade(): void {
        $current = get_option( 'curai_db_version', '0' );
        if ( version_compare( $current, CURAI_DB_VERSION, '<' ) ) {
            CURAI_Activator::create_tables();
            CURAI_Activator::stamp_db_version();
        }
    }
}
