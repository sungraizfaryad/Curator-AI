<?php
/**
 * Unit tests for CURAI_Ability_Audit_Readability.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/trait-curai-ability-helpers.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-readability-calc.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-audit-readability.php';

final class CURAI_Ability_Audit_Readability_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn ( $s, $rl = true ) => is_string( $s ) ? strip_tags( $s ) : '' );
		Functions\when( 'strip_shortcodes' )->returnArg( 1 );
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

		$result = CURAI_Ability_Audit_Readability::execute( array( 'post_id' => 999 ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_post_not_found', $result->get_error_code() );
	}

	public function test_execute_returns_readability_stats(): void {
		$post               = new WP_Post();
		$post->ID           = 5;
		$post->post_title   = 'T';
		$post->post_content = 'The cat sat on the mat. The dog ran in the park. They were friends.';
		Functions\when( 'get_post' )->justReturn( $post );

		$result = CURAI_Ability_Audit_Readability::execute( array( 'post_id' => 5 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'flesch_kincaid', $result );
		$this->assertArrayHasKey( 'grade', $result );
		$this->assertArrayHasKey( 'sentences', $result );
		$this->assertArrayHasKey( 'words', $result );
		$this->assertGreaterThan( 0, $result['sentences'] );
	}
}
