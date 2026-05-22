<?php
/**
 * Persists Curator AI audit findings to the curai_audit_results table.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for the `curai_audit_results` table.
 *
 * @since 1.0.0
 */
class CURAI_Audit_Store {

	/**
	 * Upsert an audit finding (one row per audit_type + object_id).
	 *
	 * @since 1.0.0
	 * @param string $audit_type   Audit type slug (e.g. 'stale', 'readability').
	 * @param int    $object_id    Object ID.
	 * @param string $object_type  Object type slug (e.g. 'post', 'attachment', 'url').
	 * @param int    $severity     0 ok, 1 info, 2 warn, 3 error.
	 * @param array  $data         Arbitrary payload (encoded as JSON).
	 * @return void
	 */
	public static function upsert( string $audit_type, int $object_id, string $object_type, int $severity, array $data ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'curai_audit_results';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Upsert by composite key; table name comes from $wpdb->prefix.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$table}` WHERE audit_type = %s AND object_id = %d",
				$audit_type,
				$object_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct insert into plugin-owned table.
		$wpdb->insert(
			$table,
			array(
				'audit_type'  => $audit_type,
				'object_id'   => $object_id,
				'object_type' => $object_type,
				'severity'    => $severity,
				'data'        => wp_json_encode( $data ),
				'detected_at' => current_time( 'mysql', true ),
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Query audit findings by audit_type.
	 *
	 * @since 1.0.0
	 * @param string $audit_type Audit type slug.
	 * @param int    $limit      Max rows to return.
	 * @return array<int, array<string, mixed>>
	 */
	public static function query_by_type( string $audit_type, int $limit = 500 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'curai_audit_results';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table, table name from $wpdb->prefix.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, audit_type, object_id, object_type, severity, data, detected_at, resolved_at FROM `{$table}` WHERE audit_type = %s ORDER BY detected_at DESC LIMIT %d",
				$audit_type,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Delete a finding for one object.
	 *
	 * @since 1.0.0
	 * @param string $audit_type Audit type slug.
	 * @param int    $object_id  Object ID.
	 * @return void
	 */
	public static function delete_for_object( string $audit_type, int $object_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'curai_audit_results';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table, table name from $wpdb->prefix.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$table}` WHERE audit_type = %s AND object_id = %d",
				$audit_type,
				$object_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
