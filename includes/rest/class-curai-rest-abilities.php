<?php
/**
 * REST endpoint: /curator-ai/v1/abilities
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the abilities REST routes under curator-ai/v1.
 *
 * Provides list, schema, and run endpoints for all registered Curator AI
 * abilities. Every route requires manage_options capability; the /run endpoint
 * additionally delegates to the individual ability's permission_callback.
 *
 * @since 1.0.0
 */
final class CURAI_REST_Abilities {

	/**
	 * Register all abilities routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		register_rest_route(
			'curator-ai/v1',
			'/abilities',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_abilities' ),
				'permission_callback' => array( __CLASS__, 'permission_read' ),
			)
		);

		register_rest_route(
			'curator-ai/v1',
			'/abilities/(?P<id>[a-zA-Z0-9_/-]+)/schema',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_schema' ),
				'permission_callback' => array( __CLASS__, 'permission_read' ),
				'args'                => array(
					'id' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'curator-ai/v1',
			'/abilities/(?P<id>[a-zA-Z0-9_/-]+)/run',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'run_ability' ),
				'permission_callback' => array( __CLASS__, 'permission_run' ),
			)
		);
	}

	/**
	 * Permission callback for read-only ability routes.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function permission_read(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Permission callback for the run route.
	 *
	 * Defers to the ability's own permission_callback when available, falling
	 * back to manage_options when the ability has no explicit callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The current REST request.
	 * @return bool
	 */
	public static function permission_run( WP_REST_Request $request ): bool {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			return current_user_can( 'manage_options' );
		}

		$id      = (string) $request->get_param( 'id' );
		$ability = wp_get_ability( $id );

		if ( null === $ability ) {
			return false;
		}

		if ( method_exists( $ability, 'get_permission_callback' ) ) {
			$cb = $ability->get_permission_callback();
			if ( is_callable( $cb ) ) {
				$body = $request->get_json_params();
				if ( empty( $body ) ) {
					$body = $request->get_body_params();
				}
				return (bool) call_user_func( $cb, (array) $body );
			}
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Return a list of all registered Curator AI abilities.
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	public static function list_abilities(): WP_REST_Response {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return rest_ensure_response( array() );
		}

		$abilities = wp_get_abilities();

		if ( ! is_array( $abilities ) ) {
			return rest_ensure_response( array() );
		}

		$result = array();

		foreach ( $abilities as $id => $ability ) {
			if ( strpos( (string) $id, 'curator-ai/' ) !== 0 ) {
				continue;
			}

			$label       = method_exists( $ability, 'get_label' ) ? $ability->get_label() : '';
			$description = method_exists( $ability, 'get_description' ) ? $ability->get_description() : '';
			$category    = method_exists( $ability, 'get_category' ) ? $ability->get_category() : '';

			$result[] = array(
				'id'          => $id,
				'label'       => $label,
				'description' => $description,
				'category'    => $category,
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Return the input/output schema for a single ability.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_schema( WP_REST_Request $request ) {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			return new WP_Error(
				'curai_no_abilities_api',
				__( 'Abilities API not available.', 'curator-ai-seo-site-care' ),
				array( 'status' => 503 )
			);
		}

		$id      = (string) $request->get_param( 'id' );
		$ability = wp_get_ability( $id );

		if ( null === $ability ) {
			return new WP_Error(
				'curai_ability_not_found',
				/* translators: %s: ability ID */
				sprintf( __( 'Ability %s not found.', 'curator-ai-seo-site-care' ), esc_html( $id ) ),
				array( 'status' => 404 )
			);
		}

		$input_schema  = array();
		$output_schema = array();

		if ( method_exists( $ability, 'get_input_schema' ) ) {
			try {
				$raw = $ability->get_input_schema();
				if ( is_array( $raw ) ) {
					$input_schema = $raw;
				}
			} catch ( \Throwable $e ) {
				$input_schema = array();
			}
		}

		if ( method_exists( $ability, 'get_output_schema' ) ) {
			try {
				$raw = $ability->get_output_schema();
				if ( is_array( $raw ) ) {
					$output_schema = $raw;
				}
			} catch ( \Throwable $e ) {
				$output_schema = array();
			}
		}

		return rest_ensure_response(
			array(
				'input'  => $input_schema,
				'output' => $output_schema,
			)
		);
	}

	/**
	 * Execute an ability and return its result.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function run_ability( WP_REST_Request $request ) {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			return new WP_Error(
				'curai_no_abilities_api',
				__( 'Abilities API not available.', 'curator-ai-seo-site-care' ),
				array( 'status' => 503 )
			);
		}

		$id      = (string) $request->get_param( 'id' );
		$ability = wp_get_ability( $id );

		if ( null === $ability ) {
			return new WP_Error(
				'curai_ability_not_found',
				/* translators: %s: ability ID */
				sprintf( __( 'Ability %s not found.', 'curator-ai-seo-site-care' ), esc_html( $id ) ),
				array( 'status' => 404 )
			);
		}

		$input = (array) $request->get_json_params();
		if ( empty( $input ) ) {
			$input = (array) $request->get_body_params();
		}

		$result = $ability->execute( $input );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
