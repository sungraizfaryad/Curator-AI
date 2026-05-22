<?php
/**
 * Cron callbacks that actually execute scheduled abilities.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/automation/interface-curai-scheduler.php';
require_once CURAI_PLUGIN_DIR . 'includes/automation/class-curai-wp-cron-scheduler.php';

/**
 * Executes scheduled ability jobs triggered by WP cron events.
 *
 * @since 1.0.0
 */
final class CURAI_Job_Runner {

	/**
	 * Register cron action callbacks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'curai_job_run_ability', array( __CLASS__, 'run_ability' ), 10, 3 );
		add_action( 'curai_job_bulk_chunk', array( __CLASS__, 'run_bulk_chunk' ), 10, 2 );
		add_action( 'curai_job_retry', array( __CLASS__, 'run_ability' ), 10, 3 );
	}

	/**
	 * Execute a single ability, retrying on failure up to 3 attempts.
	 *
	 * @since 1.0.0
	 * @param string $ability_id Ability slug (e.g. 'curator-ai/generate-meta-title').
	 * @param array  $input      Input array for the ability.
	 * @param int    $attempt    Retry attempt counter. 1 = first try.
	 * @return void
	 */
	public static function run_ability( string $ability_id, array $input, int $attempt = 1 ): void {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			return;
		}

		$ability = wp_get_ability( $ability_id );

		if ( null === $ability ) {
			return;
		}

		$result = $ability->execute( $input );

		if ( is_wp_error( $result ) ) {
			if ( $attempt < 3 ) {
				wp_schedule_single_event(
					time() + self::backoff_seconds( $attempt ),
					'curai_job_retry',
					array( $ability_id, $input, $attempt + 1 )
				);
			}
		}
	}

	/**
	 * Execute an ability for each item in a bulk chunk.
	 *
	 * @since 1.0.0
	 * @param string $ability_id Ability slug.
	 * @param array  $items      Array of input arrays to process.
	 * @return void
	 */
	public static function run_bulk_chunk( string $ability_id, array $items ): void {
		foreach ( $items as $item ) {
			self::run_ability( $ability_id, $item, 1 );
		}
	}

	/**
	 * Return the delay in seconds for a given retry attempt number.
	 *
	 * @since 1.0.0
	 * @param int $attempt Attempt number (1-based).
	 * @return int Delay in seconds.
	 */
	private static function backoff_seconds( int $attempt ): int {
		return match ( $attempt ) {
			1       => 30,
			2       => 300,
			default => 1800,
		};
	}

	/**
	 * Return the scheduler instance.
	 *
	 * Phase 8 may inject this via a filter.
	 *
	 * @since 1.0.0
	 * @return CURAI_Scheduler_Interface
	 */
	private static function get_scheduler(): CURAI_Scheduler_Interface {
		return new CURAI_WP_Cron_Scheduler();
	}
}
