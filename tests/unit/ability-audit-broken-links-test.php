<?php
/**
 * Unit tests for CURAI_Ability_Audit_Broken_Links.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/trait-curai-ability-helpers.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-link-checker.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-audit-broken-links.php';

final class CURAI_Ability_Audit_Broken_Links_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $t ) => '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );

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

	public function test_execute_returns_wp_error_when_post_missing(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$result = CURAI_Ability_Audit_Broken_Links::execute( array( 'post_id' => 99 ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_post_not_found', $result->get_error_code() );
	}

	public function test_execute_returns_no_links_when_content_has_none(): void {
		$post               = new WP_Post();
		$post->ID           = 1;
		$post->post_content = '<p>No links here.</p>';
		Functions\when( 'get_post' )->justReturn( $post );

		$result = CURAI_Ability_Audit_Broken_Links::execute( array( 'post_id' => 1 ) );

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( 0, $result['broken_count'] );
		$this->assertSame( array(), $result['links'] );
	}

	public function test_execute_reports_broken_status(): void {
		$post               = new WP_Post();
		$post->ID           = 1;
		$post->post_content = '<a href="https://example.com/a">a</a><a href="https://example.com/b">b</a>';
		Functions\when( 'get_post' )->justReturn( $post );

		$counter = 0;
		Functions\when( 'wp_safe_remote_head' )->alias( static function () use ( &$counter ) {
			$counter++;
			return array( 'response' => array( 'code' => 1 === $counter ? 200 : 404 ) );
		} );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( static fn ( $r ) => $r['response']['code'] ?? 0 );

		$result = CURAI_Ability_Audit_Broken_Links::execute( array( 'post_id' => 1 ) );

		$this->assertSame( 2, $result['count'] );
		$this->assertSame( 1, $result['broken_count'] );
	}
}
