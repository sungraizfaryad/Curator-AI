<?php
/**
 * Unit tests for CURAI_Ability_Audit_Stale.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-audit-stale.php';

final class CURAI_Ability_Audit_Stale_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $type ) => '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\when( 'sanitize_key' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_execute_returns_empty_when_no_posts(): void {
		$result = CURAI_Ability_Audit_Stale::execute( array( 'months' => 12 ) );

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['count'] );
		$this->assertSame( array(), $result['posts'] );
	}

	public function test_execute_classifies_posts_by_age(): void {
		$post                = new WP_Post();
		$post->ID            = 5;
		$post->post_title    = 'Old';
		$post->post_modified = '2024-01-01 00:00:00';

		Functions\when( 'get_posts' )->justReturn( array( $post ) );

		// Make CURAI_Audit_Store::upsert a no-op via a static wpdb stub.
		$GLOBALS['wpdb'] = new class {
			public string $prefix = 'wp_';
			public function prepare( string $sql, ...$args ): string { return $sql; }
			public function query( string $sql ): int { return 1; }
			public function insert( string $table, array $row ): int { return 1; }
		};

		$result = CURAI_Ability_Audit_Stale::execute( array( 'months' => 12 ) );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 5, $result['posts'][0]['id'] );
		$this->assertGreaterThan( 365, $result['posts'][0]['age_days'] );
	}
}
