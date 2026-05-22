<?php
/**
 * REST endpoint: /curator-ai/v1/audit
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/audit/class-curai-audit-store.php';

/**
 * Handles the audit REST routes under curator-ai/v1.
 *
 * Exposes stored audit results from the curai_audit_results table.
 *
 * @since 1.0.0
 */
final class CURAI_REST_Audit {

	/**
	 * Register all audit routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		register_rest_route(
			'curator-ai/v1',
			'/audit/results',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_results' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'type'  => array(
						'type'     => 'string',
						'required' => false,
						'default'  => '',
					),
					'limit' => array(
						'type'    => 'integer',
						'default' => 100,
					),
				),
			)
		);
	}

	/**
	 * Return audit results, optionally filtered by type.
	 *
	 * When a type parameter is provided only rows matching that audit_type are
	 * returned. When omitted, results for each known audit type are merged and
	 * returned together.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response
	 */
	public static function list_results( WP_REST_Request $request ): WP_REST_Response {
		$type  = (string) $request->get_param( 'type' );
		$limit = (int) $request->get_param( 'limit' );

		if ( '' !== $type ) {
			$rows = CURAI_Audit_Store::query_by_type( $type, $limit );
		} else {
			$known_types = array(
				'stale',
				'readability',
				'missing-meta-alt',
				'thin-content',
				'broken-links',
				'perf',
			);

			$rows = array();
			foreach ( $known_types as $audit_type ) {
				$type_rows = CURAI_Audit_Store::query_by_type( $audit_type, $limit );
				foreach ( $type_rows as $row ) {
					$rows[] = $row;
				}
			}
		}

		return rest_ensure_response( $rows );
	}
}
