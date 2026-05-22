<?php
/**
 * Unit tests for CURAI_AI_Client_Detector.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/compat/class-curai-ai-client-detector.php';

final class CURAI_AI_Client_Detector_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_is_available_returns_false_when_function_missing(): void {
        // wp_ai_client_prompt is not defined in test environment.
        $this->assertFalse( CURAI_AI_Client_Detector::is_available() );
    }

    public function test_has_provider_configured_returns_false_when_client_unavailable(): void {
        $this->assertFalse( CURAI_AI_Client_Detector::has_provider_configured() );
    }

    public function test_get_status_returns_three_keys(): void {
        Functions\when( 'is_plugin_active' )->justReturn( false );
        $status = CURAI_AI_Client_Detector::get_status();
        $this->assertArrayHasKey( 'available', $status );
        $this->assertArrayHasKey( 'plugin_active', $status );
        $this->assertArrayHasKey( 'provider_configured', $status );
    }
}
