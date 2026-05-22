<?php
/**
 * Unit tests for CURAI_Link_Checker.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-link-checker.php';

final class CURAI_Link_Checker_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_extract_urls_from_html_returns_external_hrefs(): void {
		$html  = '<p>Visit <a href="https://example.com">site</a> and <a href="/internal">internal</a>.</p>';
		$urls = CURAI_Link_Checker::extract_urls( $html );

		$this->assertContains( 'https://example.com', $urls );
		$this->assertNotContains( '/internal', $urls );
	}

	public function test_check_url_returns_status_on_success(): void {
		Functions\when( 'wp_safe_remote_head' )->justReturn( array( 'response' => array( 'code' => 200 ) ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( static fn ( $r ) => $r['response']['code'] ?? 0 );

		$result = CURAI_Link_Checker::check_url( 'https://example.com' );
		$this->assertSame( 200, $result['status'] );
		$this->assertSame( 'https://example.com', $result['url'] );
		$this->assertFalse( $result['broken'] );
	}

	public function test_check_url_marks_broken_for_404(): void {
		Functions\when( 'wp_safe_remote_head' )->justReturn( array( 'response' => array( 'code' => 404 ) ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( static fn ( $r ) => $r['response']['code'] ?? 0 );

		$result = CURAI_Link_Checker::check_url( 'https://example.com/missing' );
		$this->assertSame( 404, $result['status'] );
		$this->assertTrue( $result['broken'] );
	}
}
