<?php
/**
 * Unit tests for CURAI_PageSpeed_Client.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-pagespeed-client.php';

final class CURAI_PageSpeed_Client_Test extends TestCase {

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

	public function test_build_url_includes_strategy_and_key(): void {
		$url = CURAI_PageSpeed_Client::build_url( 'https://example.com', 'mobile', 'KEY123' );
		$this->assertStringContainsString( 'pagespeedonline/v5/runPagespeed', $url );
		$this->assertStringContainsString( 'strategy=mobile', $url );
		$this->assertStringContainsString( 'url=https%3A%2F%2Fexample.com', $url );
		$this->assertStringContainsString( 'key=KEY123', $url );
	}

	public function test_build_url_omits_key_when_empty(): void {
		$url = CURAI_PageSpeed_Client::build_url( 'https://example.com', 'desktop', '' );
		$this->assertStringNotContainsString( 'key=', $url );
		$this->assertStringContainsString( 'strategy=desktop', $url );
	}

	public function test_audit_returns_wp_error_on_http_failure(): void {
		Functions\when( 'wp_safe_remote_get' )->justReturn( new WP_Error( 'http_request_failed', 'down' ) );
		Functions\when( 'get_option' )->justReturn( '' );

		$result = CURAI_PageSpeed_Client::audit( 'https://example.com', 'mobile' );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_pagespeed_http_error', $result->get_error_code() );
	}
}
