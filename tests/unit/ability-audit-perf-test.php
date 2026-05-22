<?php
/**
 * Unit tests for CURAI_Ability_Audit_Perf.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-pagespeed-client.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-audit-perf.php';

final class CURAI_Ability_Audit_Perf_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $t ) => '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );
		Functions\when( 'home_url' )->justReturn( 'https://example.test/' );

		$GLOBALS['wpdb'] = new class {
			public string $prefix = 'wp_';
			public function prepare( string $sql, ...$args ): string { return $sql; }
			public function query( string $sql ): int { return 1; }
			public function insert( string $table, array $row ): int { return 1; }
		};
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_execute_returns_wp_error_when_url_invalid(): void {
		$result = CURAI_Ability_Audit_Perf::execute( array( 'url' => 'not-a-url' ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_invalid_url', $result->get_error_code() );
	}

	public function test_execute_returns_wp_error_when_pagespeed_fails(): void {
		Functions\when( 'get_option' )->justReturn( '' );
		Functions\when( 'wp_safe_remote_get' )->justReturn( new WP_Error( 'http_request_failed', 'down' ) );

		$result = CURAI_Ability_Audit_Perf::execute( array( 'url' => 'https://example.com', 'strategy' => 'mobile' ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_pagespeed_http_error', $result->get_error_code() );
	}
}
