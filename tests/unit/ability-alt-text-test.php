<?php
/**
 * Unit tests for CURAI_Ability_Alt_Text.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/trait-curai-ability-helpers.php';
require_once dirname( __DIR__, 2 ) . '/includes/ai/class-curai-ai-bridge.php';
require_once dirname( __DIR__, 2 ) . '/includes/ai/class-curai-prompt-builder.php';
require_once dirname( __DIR__, 2 ) . '/includes/ai/class-curai-cost-guard.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-alt-text.php';

final class CURAI_Ability_Alt_Text_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'get_option' )->alias( static function ( string $key, $default = false ) {
			if ( 'curai_settings' === $key ) {
				return array( 'budget_cap_enabled' => false );
			}
			if ( 'curai_usage' === $key ) {
				return array( 'month' => gmdate( 'Y-m' ), 'tokens' => 0, 'cost_usd' => 0.0 );
			}
			return $default;
		} );
		Functions\when( 'update_option' )->justReturn( true );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_execute_returns_wp_error_when_attachment_missing(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$result = CURAI_Ability_Alt_Text::execute( array( 'attachment_id' => 999 ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_attachment_not_found', $result->get_error_code() );
	}

	public function test_execute_returns_wp_error_when_not_an_image(): void {
		$attachment       = new WP_Post();
		$attachment->ID   = 5;
		Functions\when( 'get_post' )->justReturn( $attachment );
		Functions\when( 'wp_attachment_is_image' )->justReturn( false );
		Functions\when( 'get_post_mime_type' )->justReturn( 'application/pdf' );

		$result = CURAI_Ability_Alt_Text::execute( array( 'attachment_id' => 5 ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_attachment_not_image', $result->get_error_code() );
	}

	public function test_execute_returns_wp_error_when_ai_unavailable(): void {
		$attachment       = new WP_Post();
		$attachment->ID   = 5;
		Functions\when( 'get_post' )->justReturn( $attachment );
		Functions\when( 'wp_attachment_is_image' )->justReturn( true );
		Functions\when( 'get_post_mime_type' )->justReturn( 'image/jpeg' );
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.test/image.jpg' );

		$result = CURAI_Ability_Alt_Text::execute( array( 'attachment_id' => 5 ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_ai_unavailable', $result->get_error_code() );
	}
}
