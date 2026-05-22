<?php
/**
 * Unit tests for CURAI_Ability_Audit_Missing_Meta_Alt.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-audit-missing-meta-alt.php';

final class CURAI_Ability_Audit_Missing_Meta_Alt_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $t ) => '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );
		Functions\when( 'sanitize_key' )->returnArg( 1 );

		$GLOBALS['wpdb'] = new class {
			public string $prefix  = 'wp_';
			public string $posts   = 'wp_posts';
			public string $postmeta = 'wp_postmeta';
			public array $next_results = array();
			public function prepare( string $sql, ...$args ): string { return $sql; }
			public function query( string $sql ): int { return 1; }
			public function insert( string $table, array $row ): int { return 1; }
			public function get_col( string $sql ): array {
				$rs                 = $this->next_results;
				$this->next_results = array();
				return $rs;
			}
		};
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_execute_returns_counts_for_empty_site(): void {
		$result = CURAI_Ability_Audit_Missing_Meta_Alt::execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'missing_meta_title', $result );
		$this->assertArrayHasKey( 'missing_meta_desc', $result );
		$this->assertArrayHasKey( 'missing_alt', $result );
		$this->assertArrayHasKey( 'counts', $result );
		$this->assertSame( 0, $result['counts']['missing_meta_title'] );
		$this->assertSame( 0, $result['counts']['missing_meta_desc'] );
		$this->assertSame( 0, $result['counts']['missing_alt'] );
	}

	public function test_execute_returns_ids_when_wpdb_returns_them(): void {
		$GLOBALS['wpdb']->next_results = array( '12', '34', '56' );
		$result = CURAI_Ability_Audit_Missing_Meta_Alt::execute( array( 'limit' => 10 ) );

		$this->assertArrayHasKey( 'missing_meta_title', $result );
		$this->assertGreaterThan( 0, count( $result['missing_meta_title'] ) );
	}
}
