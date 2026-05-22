<?php
/**
 * Unit tests for CURAI_Rule_Engine.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-rule-engine.php';

final class CURAI_Rule_Engine_Test extends TestCase {

	/** @var array<string, mixed> Full rules fixture reused across tests. */
	private array $full_rules = array(
		'on_post_save' => array(
			'generate_meta_title' => array(
				'enabled'        => true,
				'post_types'     => array( 'post', 'page' ),
				'skip_if_exists' => true,
			),
			'check_readability'   => array(
				'enabled'    => true,
				'post_types' => array( 'post' ),
			),
		),
		'on_media_upload' => array(
			'generate_alt_text' => array(
				'enabled'        => false,
				'skip_if_exists' => true,
				'max_size_mb'    => 5,
			),
		),
		'scheduled' => array(
			'weekly_audit' => array( 'enabled' => false ),
		),
	);

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_rule_returns_empty_array_for_missing_path(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$this->assertSame( array(), CURAI_Rule_Engine::get_rule( 'on_post_save.foo' ) );
	}

	public function test_is_enabled_true_when_rule_enabled_flag_set(): void {
		Functions\when( 'get_option' )->justReturn( $this->full_rules );

		$this->assertTrue( CURAI_Rule_Engine::is_enabled( 'on_post_save.generate_meta_title' ) );
	}

	public function test_should_fire_post_save_returns_false_when_post_type_not_in_list(): void {
		Functions\when( 'get_option' )->justReturn( $this->full_rules );

		// check_readability is limited to 'post'; passing 'page' must return false.
		$this->assertFalse( CURAI_Rule_Engine::should_fire_post_save( 'check_readability', 1, 'page' ) );
	}

	public function test_should_fire_post_save_returns_true_for_matching_post_type(): void {
		Functions\when( 'get_option' )->justReturn( $this->full_rules );

		$this->assertTrue( CURAI_Rule_Engine::should_fire_post_save( 'check_readability', 1, 'post' ) );
	}

	public function test_should_fire_media_upload_false_when_disabled(): void {
		$rules = $this->full_rules;
		// generate_alt_text is already disabled in the fixture; confirm false.
		Functions\when( 'get_option' )->justReturn( $rules );

		$this->assertFalse( CURAI_Rule_Engine::should_fire_media_upload( 42 ) );
	}
}
