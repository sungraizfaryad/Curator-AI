<?php
/**
 * CRUD layer for the curai_jobs table.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tracks background job state in the `{prefix}curai_jobs` table.
 *
 * All methods are static so callers never need to instantiate this class.
 *
 * @since 1.0.0
 */
final class CURAI_Job_Tracker {

	/**
	 * Create a new job record in pending state.
	 *
	 * @since 1.0.0
	 * @param string $job_type    Job type slug (e.g. 'weekly-audit').
	 * @param array  $args        Arbitrary job arguments (stored as JSON).
	 * @param int    $total_items Total item count for progress tracking.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public static function create( string $job_type, array $args, int $total_items = 0 ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'curai_jobs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct insert into plugin-owned table.
		$wpdb->insert(
			$table,
			array(
				'job_type'    => $job_type,
				'status'      => 'pending',
				'total_items' => $total_items,
				'args'        => wp_json_encode( $args ),
				'created_at'  => current_time( 'mysql', true ),
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return (int) $wpdb->insert_id;
	}

	/**
	 * Transition a job to running state and record its start time.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return bool True on success, false on failure.
	 */
	public static function mark_started( int $job_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'curai_jobs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update on plugin-owned table.
		$result = $wpdb->update(
			$table,
			array(
				'status'     => 'running',
				'started_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $job_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Increment completed and failed item counters for a job.
	 *
	 * Uses a direct SQL UPDATE with relative increments to avoid race conditions
	 * when multiple cron events process items concurrently.
	 *
	 * @since 1.0.0
	 * @param int $job_id         Job ID.
	 * @param int $completed_delta Number of newly completed items.
	 * @param int $failed_delta    Number of newly failed items.
	 * @return bool True on success, false on failure.
	 */
	public static function update_progress( int $job_id, int $completed_delta, int $failed_delta = 0 ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'curai_jobs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Relative-increment UPDATE; table name comes from $wpdb->prefix.
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET completed_items = completed_items + %d, failed_items = failed_items + %d WHERE id = %d",
				$completed_delta,
				$failed_delta,
				$job_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $result;
	}

	/**
	 * Transition a job to a terminal state and record its finish time.
	 *
	 * @since 1.0.0
	 * @param int    $job_id Job ID.
	 * @param string $status Terminal status ('completed', 'failed', 'cancelled').
	 * @return bool True on success, false on failure.
	 */
	public static function mark_complete( int $job_id, string $status = 'completed' ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'curai_jobs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update on plugin-owned table.
		$result = $wpdb->update(
			$table,
			array(
				'status'      => $status,
				'finished_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $job_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Retrieve a single job record by ID.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return array<string, mixed>|null Row as associative array, or null if not found.
	 */
	public static function get( int $job_id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'curai_jobs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Plugin-owned table, table name from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE id = %d",
				$job_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $row ) ? $row : null;
	}
}
