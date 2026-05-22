<?php
/**
 * Unit tests for CURAI_Environment_Check.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/compat/class-curai-environment-check.php';

final class CURAI_Environment_Check_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_php_ok_returns_true_when_php_meets_minimum(): void {
        // PHP_VERSION is the actual runtime — assume >= 8.1 (matches plugin requirement).
        $this->assertTrue( CURAI_Environment_Check::php_ok() );
    }

    public function test_wp_ok_returns_true_when_wp_meets_minimum(): void {
        global $wp_version;
        $wp_version = '7.0.1';
        $this->assertTrue( CURAI_Environment_Check::wp_ok() );
    }

    public function test_wp_ok_returns_false_when_wp_below_minimum(): void {
        global $wp_version;
        $wp_version = '6.9.4';
        $this->assertFalse( CURAI_Environment_Check::wp_ok() );
    }

    public function test_passes_returns_false_when_any_check_fails(): void {
        global $wp_version;
        $wp_version = '6.0';
        $this->assertFalse( CURAI_Environment_Check::passes() );
    }
}
