<?php
/**
 * Unit tests for CURAI_Audit_Store.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';

final class CURAI_Audit_Store_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $type ) => '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_upsert_deletes_existing_then_inserts(): void {
		$captured = array(
			'delete' => null,
			'insert' => null,
		);
		$wpdb = new class( $captured ) {
			public string $prefix = 'wp_';
			public array $captured;
			public function __construct( array &$captured ) {
				$this->captured =& $captured;
			}
			public function prepare( string $sql, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( "'%d'", "'%s'" ), $sql ), $args );
			}
			public function query( string $sql ): int {
				$this->captured['delete'] = $sql;
				return 1;
			}
			public function insert( string $table, array $row ): int {
				$this->captured['insert'] = array( 'table' => $table, 'row' => $row );
				return 1;
			}
			public function get_results( string $sql, $output = null ) { return array(); }
		};
		$GLOBALS['wpdb'] = $wpdb;

		CURAI_Audit_Store::upsert( 'stale', 42, 'post', 2, array( 'age_days' => 400 ) );

		$this->assertNotNull( $wpdb->captured['delete'] );
		$this->assertStringContainsString( "DELETE FROM `wp_curai_audit_results`", $wpdb->captured['delete'] );
		$this->assertStringContainsString( "'stale'", $wpdb->captured['delete'] );
		$this->assertStringContainsString( "'42'", $wpdb->captured['delete'] );
		$this->assertSame( 'wp_curai_audit_results', $wpdb->captured['insert']['table'] );
		$this->assertSame( 'stale', $wpdb->captured['insert']['row']['audit_type'] );
		$this->assertSame( 42, $wpdb->captured['insert']['row']['object_id'] );
		$this->assertSame( 2, $wpdb->captured['insert']['row']['severity'] );
		$this->assertStringContainsString( '"age_days":400', $wpdb->captured['insert']['row']['data'] );
	}

	public function test_query_by_type_returns_results(): void {
		$wpdb = new class {
			public string $prefix = 'wp_';
			public function prepare( string $sql, ...$args ): string { return $sql; }
			public function get_results( string $sql, $output = null ): array {
				return array(
					array( 'id' => 1, 'audit_type' => 'stale', 'object_id' => 5, 'severity' => 2, 'data' => '{"age_days":400}', 'detected_at' => '2026-05-22 12:00:00' ),
				);
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		$rows = CURAI_Audit_Store::query_by_type( 'stale', 100 );
		$this->assertCount( 1, $rows );
		$this->assertSame( 5, (int) $rows[0]['object_id'] );
	}

	public function test_delete_for_object_removes_row(): void {
		$captured = null;
		$wpdb = new class( $captured ) {
			public string $prefix = 'wp_';
			public ?string $captured;
			public function __construct( ?string &$captured ) {
				$this->captured =& $captured;
			}
			public function prepare( string $sql, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( "'%d'", "'%s'" ), $sql ), $args );
			}
			public function query( string $sql ): int {
				$this->captured = $sql;
				return 1;
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		CURAI_Audit_Store::delete_for_object( 'stale', 5 );

		$this->assertStringContainsString( 'DELETE', $wpdb->captured );
		$this->assertStringContainsString( "'5'", $wpdb->captured );
	}
}
