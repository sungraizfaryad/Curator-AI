<?php
/**
 * Cost / usage guard. Tracks monthly tokens + USD spend, enforces cap.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enforces monthly AI spend cap and records usage.
 *
 * Storage: `curai_usage` option ({ month: 'YYYY-MM', tokens: int, cost_usd: float }).
 * Settings: `curai_settings` option (budget_cap_enabled, budget_cap_usd).
 *
 * @since 1.0.0
 */
class CURAI_Cost_Guard {

	/**
	 * Check whether another AI call is permitted under the monthly cap.
	 *
	 * @since 1.0.0
	 * @return true|WP_Error
	 */
	public static function check() {
		$settings = get_option( 'curai_settings', array() );
		if ( empty( $settings['budget_cap_enabled'] ) ) {
			return true;
		}

		$cap = isset( $settings['budget_cap_usd'] ) ? (float) $settings['budget_cap_usd'] : 0.0;
		if ( $cap <= 0.0 ) {
			return true;
		}

		$usage = self::get_current_usage();
		if ( (float) $usage['cost_usd'] >= $cap ) {
			return new WP_Error(
				'curai_budget_exceeded',
				sprintf(
					/* translators: 1: spent USD, 2: cap USD */
					__( 'Monthly AI budget exceeded: $%1$.2f of $%2$.2f used.', 'curator-ai-seo-site-care' ),
					(float) $usage['cost_usd'],
					$cap
				)
			);
		}

		return true;
	}

	/**
	 * Add tokens + cost to the current month's usage rollup.
	 *
	 * Resets the rollup if the stored month does not match the current month.
	 *
	 * @since 1.0.0
	 * @param int   $tokens Tokens to add.
	 * @param float $cost   USD cost to add.
	 * @return void
	 */
	public static function record_usage( int $tokens, float $cost ): void {
		$usage = self::get_current_usage();
		$now   = gmdate( 'Y-m' );

		if ( ( $usage['month'] ?? '' ) !== $now ) {
			$usage = array(
				'month'    => $now,
				'tokens'   => 0,
				'cost_usd' => 0.0,
			);
		}

		$usage['tokens']   = (int) $usage['tokens'] + max( 0, $tokens );
		$usage['cost_usd'] = (float) $usage['cost_usd'] + max( 0.0, $cost );

		update_option( 'curai_usage', $usage );
	}

	/**
	 * Read current month's usage rollup, defaulting if missing.
	 *
	 * @since 1.0.0
	 * @return array{ month: string, tokens: int, cost_usd: float }
	 */
	public static function get_current_usage(): array {
		$usage = get_option( 'curai_usage', array() );
		if ( ! is_array( $usage ) ) {
			$usage = array();
		}
		return array(
			'month'    => isset( $usage['month'] ) ? (string) $usage['month'] : gmdate( 'Y-m' ),
			'tokens'   => isset( $usage['tokens'] ) ? (int) $usage['tokens'] : 0,
			'cost_usd' => isset( $usage['cost_usd'] ) ? (float) $usage['cost_usd'] : 0.0,
		);
	}
}
