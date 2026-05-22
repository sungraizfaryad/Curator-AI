<?php
/**
 * Unit tests for CURAI_Ability_Refresh_Content.
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
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-refresh-content.php';

final class CURAI_Ability_Refresh_Content_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );
		Functions\when( 'strip_shortcodes' )->returnArg( 1 );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $type ) => '2026-05-22 12:00:00' );
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

	public function test_execute_returns_wp_error_when_post_missing(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$result = CURAI_Ability_Refresh_Content::execute(
			array( 'post_id' => 999, 'mode' => 'date_only' )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_post_not_found', $result->get_error_code() );
	}

	public function test_execute_returns_wp_error_when_mode_invalid(): void {
		$post     = new WP_Post();
		$post->ID = 5;
		Functions\when( 'get_post' )->justReturn( $post );

		$result = CURAI_Ability_Refresh_Content::execute(
			array( 'post_id' => 5, 'mode' => 'bogus' )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_invalid_mode', $result->get_error_code() );
	}

	public function test_execute_date_only_does_not_call_ai(): void {
		$post              = new WP_Post();
		$post->ID          = 5;
		$post->post_title  = 'Old';
		$post->post_content = '<p>Body</p>';
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'wp_update_post' )->justReturn( 5 );

		$result = CURAI_Ability_Refresh_Content::execute(
			array( 'post_id' => 5, 'mode' => 'date_only' )
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'date_only', $result['mode'] );
		$this->assertSame( 0, $result['tokens_used'] );
		$this->assertSame( '<p>Body</p>', $result['updated_content'] );
	}

	public function test_execute_context_mode_returns_wp_error_when_ai_unavailable(): void {
		$post              = new WP_Post();
		$post->ID          = 5;
		$post->post_title  = 'Title';
		$post->post_content = '<p>Body</p>';
		Functions\when( 'get_post' )->justReturn( $post );

		$result = CURAI_Ability_Refresh_Content::execute(
			array( 'post_id' => 5, 'mode' => 'context' )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_ai_unavailable', $result->get_error_code() );
	}
}
