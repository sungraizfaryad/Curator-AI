<?php
/**
 * REST endpoint: /curator-ai/v1/status
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/compat/class-curai-ai-client-detector.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/interface-curai-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-yoast-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-rank-math-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-native-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-seo-adapter-factory.php';

/**
 * Handles the status REST route under curator-ai/v1.
 *
 * Returns a snapshot of plugin health: AI client state, active SEO adapter,
 * token usage for the current billing month, and the running plugin version.
 *
 * @since 1.0.0
 */
final class CURAI_REST_Status {

	/**
	 * Register the status route.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		register_rest_route(
			'curator-ai/v1',
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'read' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Return plugin status as a JSON object.
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	public static function read(): WP_REST_Response {
		$ai_status = CURAI_AI_Client_Detector::get_status();
		$adapter   = CURAI_SEO_Adapter_Factory::get();
		$usage     = get_option(
			'curai_usage',
			array(
				'month'    => gmdate( 'Y-m' ),
				'tokens'   => 0,
				'cost_usd' => 0.0,
			)
		);

		$adapter_slug  = method_exists( $adapter, 'get_slug' ) ? $adapter->get_slug() : '';
		$adapter_label = method_exists( $adapter, 'get_label' ) ? $adapter->get_label() : '';

		return rest_ensure_response(
			array(
				'ai_client'      => $ai_status,
				'adapter'        => array(
					'slug'  => $adapter_slug,
					'label' => $adapter_label,
				),
				'usage'          => $usage,
				'plugin_version' => CURAI_VERSION,
			)
		);
	}
}
