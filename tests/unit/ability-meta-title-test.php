<?php
/**
 * Unit tests for CURAI_Ability_Meta_Title.
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
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-meta-title.php';

final class CURAI_Ability_Meta_Title_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );
		Functions\when( 'strip_shortcodes' )->returnArg( 1 );
		Functions\when( 'wp_trim_words' )->returnArg( 1 );
		Functions\when( 'get_option' )->alias( static function ( string $key, $default = false ) {
			if ( 'curai_settings' === $key ) {
				return array( 'budget_cap_enabled' => false );
			}
			if ( 'curai_usage' === $key ) {
				return array(
					'month'    => gmdate( 'Y-m' ),
					'tokens'   => 0,
					'cost_usd' => 0.0,
				);
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

		$result = CURAI_Ability_Meta_Title::execute(
			array(
				'post_id'    => 999,
				'max_length' => 60,
			)
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_post_not_found', $result->get_error_code() );
	}

	public function test_execute_returns_wp_error_when_ai_unavailable(): void {
		$post               = new WP_Post();
		$post->ID           = 5;
		$post->post_title   = 'Title';
		$post->post_excerpt = 'Excerpt';
		Functions\when( 'get_post' )->justReturn( $post );

		// wp_ai_client_prompt not defined → bridge returns curai_ai_unavailable.
		$result = CURAI_Ability_Meta_Title::execute(
			array(
				'post_id'    => 5,
				'max_length' => 60,
			)
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'curai_ai_unavailable', $result->get_error_code() );
	}
}
