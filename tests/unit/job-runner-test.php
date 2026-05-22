<?php
/**
 * Unit tests for CURAI_Job_Runner.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/interface-curai-scheduler.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-wp-cron-scheduler.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-job-runner.php';

/**
 * Test suite for CURAI_Job_Runner.
 */
final class CURAI_Job_Runner_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * When an ability is found, execute() is called and no retry is scheduled.
	 */
	public function test_run_ability_executes_when_ability_found(): void {
		$ability = Mockery::mock();
		$ability->shouldReceive( 'execute' )->once()->andReturn( array( 'ok' => true ) );

		Functions\when( 'wp_get_ability' )->justReturn( $ability );
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\expect( 'wp_schedule_single_event' )->never();

		CURAI_Job_Runner::run_ability( 'curator-ai/generate-meta-title', array( 'post_id' => 1 ) );

		$this->assertTrue( true ); // Mockery assertion is implicit via shouldReceive->once().
	}

	/**
	 * When execute() returns a WP_Error on attempt 1, a retry is scheduled at 30 s delay.
	 */
	public function test_run_ability_retries_on_wp_error_within_attempt_cap(): void {
		$ability = Mockery::mock();
		$ability->shouldReceive( 'execute' )->once()->andReturn( new WP_Error( 'fail', 'Test failure' ) );

		Functions\when( 'wp_get_ability' )->justReturn( $ability );
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );

		Functions\expect( 'wp_schedule_single_event' )
			->once()
			->with(
				Mockery::type( 'int' ),
				'curai_job_retry',
				Mockery::on( static fn ( $args ) => $args[2] === 2 )
			)
			->andReturn( true );

		CURAI_Job_Runner::run_ability( 'curator-ai/generate-meta-title', array( 'post_id' => 1 ), 1 );

		$this->addToAssertionCount( 1 ); // Mockery expectation verified in tearDown.
	}

	/**
	 * On the final attempt (3), no further retry event is scheduled.
	 */
	public function test_run_ability_does_not_retry_on_final_attempt(): void {
		$ability = Mockery::mock();
		$ability->shouldReceive( 'execute' )->once()->andReturn( new WP_Error( 'fail', 'Test failure' ) );

		Functions\when( 'wp_get_ability' )->justReturn( $ability );
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\expect( 'wp_schedule_single_event' )->never();

		CURAI_Job_Runner::run_ability( 'curator-ai/generate-meta-title', array( 'post_id' => 1 ), 3 );

		$this->addToAssertionCount( 1 ); // Mockery expectation verified in tearDown.
	}
}
