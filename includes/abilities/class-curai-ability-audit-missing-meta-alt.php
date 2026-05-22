<?php
/**
 * Ability: detect posts missing meta title/description and images missing alt.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/audit-missing-meta-alt`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Audit_Missing_Meta_Alt {

	/**
	 * Meta keys to test for "meta title" coverage. A post is considered to have
	 * a meta title if ANY of these is non-empty.
	 *
	 * @since 1.0.0
	 */
	private const META_TITLE_KEYS = array( '_yoast_wpseo_title', 'rank_math_title', '_curai_meta_title' );

	/**
	 * Meta keys to test for "meta description" coverage.
	 *
	 * @since 1.0.0
	 */
	private const META_DESC_KEYS = array( '_yoast_wpseo_metadesc', 'rank_math_description', '_curai_meta_desc' );

	/**
	 * Find posts missing all known meta-title keys, posts missing all known
	 * meta-description keys, and attachments with empty _wp_attachment_image_alt.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: post_types (array, default ['post','page']), limit (int, default 500).
	 * @return array {
	 *     missing_meta_title: int[],
	 *     missing_meta_desc:  int[],
	 *     missing_alt:        int[],
	 *     counts: array{ missing_meta_title:int, missing_meta_desc:int, missing_alt:int }
	 * }
	 */
	public static function execute( array $input ): array {
		$post_types = isset( $input['post_types'] ) && is_array( $input['post_types'] )
			? array_map( 'sanitize_key', $input['post_types'] )
			: array( 'post', 'page' );
		$limit      = isset( $input['limit'] ) ? max( 1, min( 5000, (int) $input['limit'] ) ) : 500;

		$missing_title = self::find_missing_meta( self::META_TITLE_KEYS, $post_types, $limit );
		$missing_desc  = self::find_missing_meta( self::META_DESC_KEYS, $post_types, $limit );
		$missing_alt   = self::find_attachments_missing_alt( $limit );

		CURAI_Audit_Store::upsert(
			'missing_meta_alt',
			0,
			'site',
			( count( $missing_title ) + count( $missing_desc ) + count( $missing_alt ) ) > 0 ? 2 : 0,
			array(
				'missing_meta_title' => count( $missing_title ),
				'missing_meta_desc'  => count( $missing_desc ),
				'missing_alt'        => count( $missing_alt ),
			)
		);

		return array(
			'missing_meta_title' => $missing_title,
			'missing_meta_desc'  => $missing_desc,
			'missing_alt'        => $missing_alt,
			'counts'             => array(
				'missing_meta_title' => count( $missing_title ),
				'missing_meta_desc'  => count( $missing_desc ),
				'missing_alt'        => count( $missing_alt ),
			),
		);
	}

	/**
	 * Find published posts of the given types that lack ALL of the given meta keys.
	 *
	 * Uses a single SQL with NOT EXISTS over the meta keys.
	 *
	 * @since 1.0.0
	 * @param array $meta_keys  Meta keys to check against.
	 * @param array $post_types Post types to scan.
	 * @param int   $limit      Max IDs to return.
	 * @return int[] Post IDs.
	 */
	private static function find_missing_meta( array $meta_keys, array $post_types, int $limit ): array {
		global $wpdb;

		if ( empty( $meta_keys ) || empty( $post_types ) ) {
			return array();
		}

		$key_placeholders  = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		$sql = "SELECT p.ID FROM {$wpdb->posts} p
			WHERE p.post_status = 'publish'
			AND p.post_type IN ({$type_placeholders})
			AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key IN ({$key_placeholders})
				AND pm.meta_value <> ''
			)
			ORDER BY p.ID DESC
			LIMIT %d";

		$args = array_merge( $post_types, $meta_keys, array( $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Built-in tables, prepared, dynamic IN clauses validated.
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$args ) );

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Find attachment IDs (image mime) with empty _wp_attachment_image_alt.
	 *
	 * @since 1.0.0
	 * @param int $limit Max IDs to return.
	 * @return int[]
	 */
	private static function find_attachments_missing_alt( int $limit ): array {
		global $wpdb;

		$sql = "SELECT p.ID FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_attachment_image_alt'
			WHERE p.post_type = 'attachment'
			AND p.post_mime_type LIKE 'image/%%'
			AND ( pm.meta_value IS NULL OR pm.meta_value = '' )
			ORDER BY p.ID DESC
			LIMIT %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Built-in tables, prepared.
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $limit ) );

		return array_map( 'intval', (array) $ids );
	}
}
