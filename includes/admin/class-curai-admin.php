<?php
/**
 * Admin menu registration + asset enqueue stub.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus, enqueues assets, and wires admin notices for the plugin.
 *
 * @since 1.0.0
 */
class CURAI_Admin {

	/**
	 * Registers WordPress hooks for the admin area.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( 'CURAI_AI_Client_Detector', 'render_missing_notice' ) );
	}

	/**
	 * Registers the top-level Curator AI menu and all its submenus in the WordPress admin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_menus(): void {
		add_menu_page(
			esc_html__( 'Curator AI', 'curator-ai-seo-site-care' ),
			esc_html__( 'Curator AI', 'curator-ai-seo-site-care' ),
			'manage_options',
			'curator-ai-seo-site-care',
			array( __CLASS__, 'render_overview' ),
			'dashicons-superhero',
			58
		);

		add_submenu_page(
			'curator-ai-seo-site-care',
			esc_html__( 'Overview', 'curator-ai-seo-site-care' ),
			esc_html__( 'Overview', 'curator-ai-seo-site-care' ),
			'manage_options',
			'curator-ai-seo-site-care',
			array( __CLASS__, 'render_overview' )
		);

		add_submenu_page(
			'curator-ai-seo-site-care',
			esc_html__( 'Automation', 'curator-ai-seo-site-care' ),
			esc_html__( 'Automation', 'curator-ai-seo-site-care' ),
			'manage_options',
			'curator-ai-automation',
			array( __CLASS__, 'render_automation' )
		);

		add_submenu_page(
			'curator-ai-seo-site-care',
			esc_html__( 'Audit Reports', 'curator-ai-seo-site-care' ),
			esc_html__( 'Audit Reports', 'curator-ai-seo-site-care' ),
			'manage_options',
			'curator-ai-audit',
			array( __CLASS__, 'render_audit' )
		);

		add_submenu_page(
			'curator-ai-seo-site-care',
			esc_html__( 'Bulk Operations', 'curator-ai-seo-site-care' ),
			esc_html__( 'Bulk Operations', 'curator-ai-seo-site-care' ),
			'manage_options',
			'curator-ai-bulk',
			array( __CLASS__, 'render_bulk' )
		);

		add_submenu_page(
			'curator-ai-seo-site-care',
			esc_html__( 'Settings', 'curator-ai-seo-site-care' ),
			esc_html__( 'Settings', 'curator-ai-seo-site-care' ),
			'manage_options',
			'curator-ai-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Enqueues CSS and JS assets on Curator AI admin pages.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( ! is_string( $hook ) || false === strpos( $hook, 'curator-ai-seo-site-care' ) ) {
			return;
		}
		wp_enqueue_style(
			'curai-admin',
			CURAI_PLUGIN_URL . 'assets/admin/admin.css',
			array(),
			CURAI_VERSION
		);
	}

	/**
	 * Renders the Overview admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_overview(): void {
		require_once CURAI_PLUGIN_DIR . 'includes/admin/views/overview.php';
	}

	/**
	 * Renders the Automation admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_automation(): void {
		require_once CURAI_PLUGIN_DIR . 'includes/admin/views/automation.php';
	}

	/**
	 * Renders the Audit Reports admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_audit(): void {
		require_once CURAI_PLUGIN_DIR . 'includes/admin/views/audit.php';
	}

	/**
	 * Renders the Bulk Operations admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_bulk(): void {
		require_once CURAI_PLUGIN_DIR . 'includes/admin/views/bulk.php';
	}

	/**
	 * Renders the Settings admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_settings(): void {
		require_once CURAI_PLUGIN_DIR . 'includes/admin/views/settings.php';
	}
}
