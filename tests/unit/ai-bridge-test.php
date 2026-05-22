<?php
/**
 * Unit tests for CURAI_AI_Bridge.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/ai/class-curai-ai-bridge.php';

final class CURAI_AI_Bridge_Test extends TestCase {

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

	public function test_is_available_returns_false_when_function_missing(): void {
		// wp_ai_client_prompt is not defined in the unit test env.
		$this->assertFalse( CURAI_AI_Bridge::is_available() );
	}

	public function test_generate_text_returns_wp_error_when_unavailable(): void {
		$result = CURAI_AI_Bridge::generate_text( 'hello', 'system prompt' );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_ai_unavailable', $result->get_error_code() );
	}
}
