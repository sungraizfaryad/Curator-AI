<?php
/**
 * WP cron implementation of CURAI_Scheduler_Interface.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/automation/interface-curai-scheduler.php';

/**
 * Schedules Curator AI jobs via WordPress built-in cron.
 *
 * @since 1.0.0
 */
final class CURAI_WP_Cron_Scheduler implements CURAI_Scheduler_Interface {

	/**
	 * Enqueue a single ability execution via a WP cron single event.
	 *
	 * @since 1.0.0
	 * @param string $ability_id Ability slug (e.g. 'curator-ai/generate-meta-title').
	 * @param array  $input      Input array for the ability.
	 * @param int    $delay      Seconds to delay execution. 0 = next cron tick.
	 * @param int    $attempt    Retry attempt counter. 1 = first try.
	 * @return bool
	 */
	public function dispatch_ability( string $ability_id, array $input, int $delay = 0, int $attempt = 1 ): bool {
		$timestamp = time() + max( 0, $delay );

		return (bool) wp_schedule_single_event(
			$timestamp,
			'curai_job_run_ability',
			array( $ability_id, $input, $attempt )
		);
	}

	/**
	 * Enqueue an ability over a bulk item list, dispatching one cron event per chunk.
	 *
	 * @since 1.0.0
	 * @param string $ability_id      Ability slug.
	 * @param array  $items           Array of inputs to dispatch.
	 * @param int    $chunk_size      Items per scheduled action.
	 * @param int    $stagger_seconds Seconds between chunks.
	 * @return int Number of chunks scheduled.
	 */
	public function dispatch_bulk( string $ability_id, array $items, int $chunk_size = 25, int $stagger_seconds = 30 ): int {
		$chunks = array_chunk( $items, max( 1, $chunk_size ) );

		foreach ( $chunks as $i => $chunk ) {
			wp_schedule_single_event(
				time() + ( $i * $stagger_seconds ),
				'curai_job_bulk_chunk',
				array( $ability_id, $chunk )
			);
		}

		return count( $chunks );
	}

	/**
	 * Register a recurring hook on a WP cron interval.
	 *
	 * @since 1.0.0
	 * @param string $hook     Action hook name (must be registered separately).
	 * @param string $interval WP cron interval key (hourly, twicedaily, daily, weekly).
	 * @return bool True if scheduled or already scheduled.
	 */
	public function schedule_recurring( string $hook, string $interval ): bool {
		if ( wp_next_scheduled( $hook ) ) {
			return true;
		}

		return (bool) wp_schedule_event( time(), $interval, $hook );
	}

	/**
	 * Clear all single + recurring events for the given hook.
	 *
	 * @since 1.0.0
	 * @param string $hook Action hook name.
	 * @return void
	 */
	public function unschedule_all( string $hook ): void {
		wp_clear_scheduled_hook( $hook );
	}
}
