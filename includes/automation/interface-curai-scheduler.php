<?php
/**
 * Contract for background job schedulers.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Scheduler interface — implemented by the WP cron adapter and any future queue backends.
 *
 * @since 1.0.0
 */
interface CURAI_Scheduler_Interface {

	/**
	 * Enqueue a single ability execution. Returns true if scheduled.
	 *
	 * @since 1.0.0
	 * @param string $ability_id Ability slug (e.g. 'curator-ai/generate-meta-title').
	 * @param array  $input      Input array for the ability.
	 * @param int    $delay      Seconds to delay execution. 0 = next cron tick.
	 * @param int    $attempt    Retry attempt counter. 1 = first try.
	 * @return bool
	 */
	public function dispatch_ability( string $ability_id, array $input, int $delay = 0, int $attempt = 1 ): bool;

	/**
	 * Enqueue an ability over a bulk item list, chunked.
	 *
	 * @since 1.0.0
	 * @param string $ability_id      Ability slug.
	 * @param array  $items           Array of inputs to dispatch.
	 * @param int    $chunk_size      Items per scheduled action.
	 * @param int    $stagger_seconds Seconds between chunks.
	 * @return int Number of chunks scheduled.
	 */
	public function dispatch_bulk( string $ability_id, array $items, int $chunk_size = 25, int $stagger_seconds = 30 ): int;

	/**
	 * Register a recurring hook on a WP cron interval.
	 *
	 * @since 1.0.0
	 * @param string $hook     Action hook name (must be registered separately).
	 * @param string $interval WP cron interval key (hourly, twicedaily, daily, weekly).
	 * @return bool True if scheduled or already scheduled.
	 */
	public function schedule_recurring( string $hook, string $interval ): bool;

	/**
	 * Clear all single + recurring events for the given hook.
	 *
	 * @since 1.0.0
	 * @param string $hook Action hook name.
	 * @return void
	 */
	public function unschedule_all( string $hook ): void;
}
