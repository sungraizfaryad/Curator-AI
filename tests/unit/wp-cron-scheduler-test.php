<?php
/**
 * Unit tests for CURAI_WP_Cron_Scheduler.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/interface-curai-scheduler.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-wp-cron-scheduler.php';

final class CURAI_WP_Cron_Scheduler_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_dispatch_ability_schedules_single_event(): void {
		Functions\expect( 'wp_schedule_single_event' )
			->once()
			->with( \Mockery::type( 'int' ), 'curai_job_run_ability', \Mockery::type( 'array' ) )
			->andReturn( true );

		$scheduler = new CURAI_WP_Cron_Scheduler();
		$this->assertTrue( $scheduler->dispatch_ability( 'curator-ai/generate-meta-title', array( 'post_id' => 5 ) ) );
	}

	public function test_dispatch_bulk_chunks_and_staggers(): void {
		$calls = 0;
		Functions\when( 'wp_schedule_single_event' )->alias( static function () use ( &$calls ) {
			$calls++;
			return true;
		} );

		$scheduler = new CURAI_WP_Cron_Scheduler();
		$chunks    = $scheduler->dispatch_bulk( 'curator-ai/audit-readability', range( 1, 60 ), 25, 30 );

		$this->assertSame( 3, $chunks );  // 25 + 25 + 10
		$this->assertSame( 3, $calls );
	}

	public function test_schedule_recurring_skips_when_already_scheduled(): void {
		Functions\expect( 'wp_next_scheduled' )->once()->with( 'curai_weekly_audit' )->andReturn( strtotime( '+1 day' ) );
		Functions\expect( 'wp_schedule_event' )->never();

		$scheduler = new CURAI_WP_Cron_Scheduler();
		$this->assertTrue( $scheduler->schedule_recurring( 'curai_weekly_audit', 'weekly' ) );
	}

	public function test_unschedule_all_clears_hook(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )->once()->with( 'curai_weekly_audit' );

		$scheduler = new CURAI_WP_Cron_Scheduler();
		$scheduler->unschedule_all( 'curai_weekly_audit' );
		$this->assertTrue( true ); // assert reached
	}
}
