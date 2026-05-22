<?php
/**
 * Unit tests for CURAI_Job_Tracker.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-job-tracker.php';

final class CURAI_Job_Tracker_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'current_time' )->justReturn( '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_create_returns_inserted_id(): void {
		$wpdb = new class {
			public string $prefix    = 'wp_';
			public int    $insert_id = 99;
			public function insert( string $table, array $row ): int {
				return 1;
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		$id = CURAI_Job_Tracker::create( 'weekly-audit', array( 'email' => 'test@example.com' ), 10 );

		$this->assertSame( 99, $id );
	}

	public function test_mark_started_sets_status_running_and_started_at(): void {
		$captured = array();
		$wpdb     = new class( $captured ) {
			public string $prefix = 'wp_';
			public array  $captured;
			public function __construct( array &$captured ) {
				$this->captured =& $captured;
			}
			public function update( string $table, array $data, array $where ): int {
				$this->captured = array( 'table' => $table, 'data' => $data, 'where' => $where );
				return 1;
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		$result = CURAI_Job_Tracker::mark_started( 7 );

		$this->assertTrue( $result );
		$this->assertSame( 'running', $wpdb->captured['data']['status'] );
		$this->assertSame( '2026-05-22 12:00:00', $wpdb->captured['data']['started_at'] );
		$this->assertSame( array( 'id' => 7 ), $wpdb->captured['where'] );
	}

	public function test_update_progress_runs_prepared_sql(): void {
		$query_called = false;
		$wpdb         = new class( $query_called ) {
			public string $prefix = 'wp_';
			public bool   $query_called;
			public function __construct( bool &$query_called ) {
				$this->query_called =& $query_called;
			}
			public function prepare( string $sql, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', '%s' ), $sql ), $args );
			}
			public function query( string $sql ): int {
				$this->query_called = true;
				return 1;
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		$result = CURAI_Job_Tracker::update_progress( 5, 10, 2 );

		$this->assertTrue( $result );
		$this->assertTrue( $wpdb->query_called );
	}

	public function test_mark_complete_sets_status_and_finished_at(): void {
		$captured = array();
		$wpdb     = new class( $captured ) {
			public string $prefix = 'wp_';
			public array  $captured;
			public function __construct( array &$captured ) {
				$this->captured =& $captured;
			}
			public function update( string $table, array $data, array $where ): int {
				$this->captured = array( 'table' => $table, 'data' => $data, 'where' => $where );
				return 1;
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		$result = CURAI_Job_Tracker::mark_complete( 3, 'failed' );

		$this->assertTrue( $result );
		$this->assertSame( 'failed', $wpdb->captured['data']['status'] );
		$this->assertSame( '2026-05-22 12:00:00', $wpdb->captured['data']['finished_at'] );
		$this->assertSame( array( 'id' => 3 ), $wpdb->captured['where'] );
	}
}
