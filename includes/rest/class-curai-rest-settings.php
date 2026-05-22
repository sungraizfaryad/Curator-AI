<?php
/**
 * REST endpoint: /curator-ai/v1/settings
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the settings REST routes under curator-ai/v1.
 *
 * Exposes a GET/POST endpoint for reading and updating the plugin's stored
 * options: curai_settings, curai_seo_adapter_override, and
 * curai_automation_rules.
 *
 * @since 1.0.0
 */
final class CURAI_REST_Settings {

	/**
	 * Valid adapter override slugs.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private static array $valid_adapters = array( 'auto', 'yoast', 'rank-math', 'native' );

	/**
	 * Register the settings routes (GET + POST on the same URL).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		register_rest_route(
			'curator-ai/v1',
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'read' ),
					'permission_callback' => static function () {
						return current_user_can( 'manage_options' );
					},
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'write' ),
					'permission_callback' => static function () {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);
	}

	/**
	 * Return current plugin settings as a JSON object.
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	public static function read(): WP_REST_Response {
		$settings = get_option( 'curai_settings', array() );

		$pagespeed_key_set = '' !== (string) ( is_array( $settings ) ? ( $settings['pagespeed_api_key'] ?? '' ) : '' );

		return rest_ensure_response(
			array(
				'settings'          => is_array( $settings ) ? $settings : array(),
				'adapter_override'  => get_option( 'curai_seo_adapter_override', 'auto' ),
				'automation_rules'  => get_option( 'curai_automation_rules', array() ),
				'pagespeed_key_set' => $pagespeed_key_set,
			)
		);
	}

	/**
	 * Update one or more settings groups from a JSON request body.
	 *
	 * Accepted body keys: settings (object), adapter_override (string),
	 * automation_rules (object). Unknown keys are silently ignored.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response
	 */
	public static function write( WP_REST_Request $request ): WP_REST_Response {
		$body = (array) $request->get_json_params();

		if ( isset( $body['settings'] ) && is_array( $body['settings'] ) ) {
			$existing = get_option( 'curai_settings', array() );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}
			$clean = self::sanitize_settings_array( $body['settings'] );
			update_option( 'curai_settings', array_merge( $existing, $clean ) );
		}

		if ( isset( $body['adapter_override'] ) ) {
			$override = sanitize_text_field( (string) $body['adapter_override'] );
			if ( in_array( $override, self::$valid_adapters, true ) ) {
				update_option( 'curai_seo_adapter_override', $override );
			}
		}

		if ( isset( $body['automation_rules'] ) && is_array( $body['automation_rules'] ) ) {
			update_option( 'curai_automation_rules', $body['automation_rules'] );
		}

		return self::read();
	}

	/**
	 * Sanitize a flat or nested settings array.
	 *
	 * Strings are passed through sanitize_text_field, integers cast to int,
	 * booleans cast to bool. Nested arrays are recursed.
	 *
	 * @since 1.0.0
	 * @param array $raw Raw settings values.
	 * @return array Sanitized settings array.
	 */
	private static function sanitize_settings_array( array $raw ): array {
		$clean = array();

		foreach ( $raw as $key => $value ) {
			$key = sanitize_key( (string) $key );

			if ( is_bool( $value ) ) {
				$clean[ $key ] = (bool) $value;
			} elseif ( is_int( $value ) ) {
				$clean[ $key ] = (int) $value;
			} elseif ( is_float( $value ) ) {
				$clean[ $key ] = (float) $value;
			} elseif ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_settings_array( $value );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $clean;
	}
}
