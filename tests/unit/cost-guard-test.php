<?php
/**
 * Unit tests for CURAI_Cost_Guard.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/ai/class-curai-cost-guard.php';

final class CURAI_Cost_Guard_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_check_returns_true_when_budget_disabled(): void {
		Functions\when( 'get_option' )->alias( static function ( string $key, $default = false ) {
			if ( 'curai_settings' === $key ) {
				return array( 'budget_cap_enabled' => false, 'budget_cap_usd' => 0.0 );
			}
			if ( 'curai_usage' === $key ) {
				return array( 'month' => '2026-05', 'tokens' => 0, 'cost_usd' => 0.0 );
			}
			return $default;
		} );

		$this->assertTrue( CURAI_Cost_Guard::check() );
	}

	public function test_check_returns_wp_error_when_over_budget(): void {
		Functions\when( 'get_option' )->alias( static function ( string $key, $default = false ) {
			if ( 'curai_settings' === $key ) {
				return array( 'budget_cap_enabled' => true, 'budget_cap_usd' => 5.0 );
			}
			if ( 'curai_usage' === $key ) {
				return array( 'month' => gmdate( 'Y-m' ), 'tokens' => 1_000_000, 'cost_usd' => 10.0 );
			}
			return $default;
		} );

		$result = CURAI_Cost_Guard::check();
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_budget_exceeded', $result->get_error_code() );
	}

	public function test_record_usage_increments_tokens(): void {
		$stored = array( 'month' => gmdate( 'Y-m' ), 'tokens' => 100, 'cost_usd' => 0.5 );
		Functions\when( 'get_option' )->alias( static function ( string $key, $default = false ) use ( &$stored ) {
			if ( 'curai_usage' === $key ) {
				return $stored;
			}
			return $default;
		} );
		Functions\when( 'update_option' )->alias( static function ( string $key, $value ) use ( &$stored ) {
			if ( 'curai_usage' === $key ) {
				$stored = $value;
			}
			return true;
		} );

		CURAI_Cost_Guard::record_usage( 50, 0.25 );

		$this->assertSame( 150, $stored['tokens'] );
		$this->assertEqualsWithDelta( 0.75, $stored['cost_usd'], 0.001 );
	}

	public function test_record_usage_resets_when_month_changes(): void {
		$stored = array( 'month' => '2025-12', 'tokens' => 500, 'cost_usd' => 2.0 );
		Functions\when( 'get_option' )->alias( static function ( string $key, $default = false ) use ( &$stored ) {
			if ( 'curai_usage' === $key ) {
				return $stored;
			}
			return $default;
		} );
		Functions\when( 'update_option' )->alias( static function ( string $key, $value ) use ( &$stored ) {
			if ( 'curai_usage' === $key ) {
				$stored = $value;
			}
			return true;
		} );

		CURAI_Cost_Guard::record_usage( 10, 0.01 );

		$this->assertSame( gmdate( 'Y-m' ), $stored['month'] );
		$this->assertSame( 10, $stored['tokens'] );
		$this->assertEqualsWithDelta( 0.01, $stored['cost_usd'], 0.001 );
	}
}
