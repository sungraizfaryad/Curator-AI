<?php
/**
 * Unit tests for CURAI_Automation.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/interface-curai-scheduler.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-wp-cron-scheduler.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-rule-engine.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-job-runner.php';
require_once dirname( __DIR__, 2 ) . '/includes/automation/class-curai-automation.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/interface-curai-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-yoast-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-rank-math-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-native-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-seo-adapter-factory.php';

/**
 * Fake scheduler that records dispatch_ability calls.
 */
final class CURAI_Recording_Scheduler implements CURAI_Scheduler_Interface {

	/** @var array<int, array<string, mixed>> */
	public array $dispatches = array();

	/** @inheritDoc */
	public function dispatch_ability( string $ability_id, array $input, int $delay = 0, int $attempt = 1 ): bool {
		$this->dispatches[] = compact( 'ability_id', 'input', 'delay', 'attempt' );
		return true;
	}

	/** @inheritDoc */
	public function dispatch_bulk( string $ability_id, array $items, int $chunk_size = 25, int $stagger_seconds = 30 ): int {
		return 0;
	}

	/** @inheritDoc */
	public function schedule_recurring( string $hook, string $interval ): bool {
		return true;
	}

	/** @inheritDoc */
	public function unschedule_all( string $hook ): void {}
}

/**
 * Fake SEO adapter that returns empty strings for all read methods.
 */
final class CURAI_Test_Fake_Adapter implements CURAI_SEO_Adapter_Interface {

	/** @inheritDoc */
	public function is_active(): bool {
		return true;
	}

	/** @inheritDoc */
	public function get_slug(): string {
		return 'fake';
	}

	/** @inheritDoc */
	public function get_label(): string {
		return 'Fake';
	}

	/** @inheritDoc */
	public function read_meta_title( int $post_id ): string {
		return '';
	}

	/** @inheritDoc */
	public function read_meta_description( int $post_id ): string {
		return '';
	}

	/** @inheritDoc */
	public function write_meta_title( int $post_id, string $title ): bool {
		return true;
	}

	/** @inheritDoc */
	public function write_meta_description( int $post_id, string $desc ): bool {
		return true;
	}

	/** @inheritDoc */
	public function read_focus_keyword( int $post_id ): string {
		return '';
	}
}

/**
 * Test suite for CURAI_Automation.
 */
final class CURAI_Automation_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		CURAI_SEO_Adapter_Factory::reset();
	}

	protected function tearDown(): void {
		CURAI_SEO_Adapter_Factory::reset();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * on_save_post dispatches meta-title ability when rule is enabled and no existing value.
	 */
	public function test_on_save_post_dispatches_meta_title_when_rule_enabled(): void {
		$rules = array(
			'on_post_save' => array(
				'generate_meta_title' => array(
					'enabled'        => true,
					'post_types'     => array( 'post' ),
					'skip_if_exists' => false,
				),
				'generate_meta_description' => array(
					'enabled' => false,
				),
				'check_readability' => array(
					'enabled' => false,
				),
			),
		);

		$recorder = new CURAI_Recording_Scheduler();
		$adapter  = new CURAI_Test_Fake_Adapter();

		Functions\when( 'get_option' )->alias(
			static function ( $key, $default = false ) use ( $rules ) {
				if ( 'curai_automation_rules' === $key ) {
					return $rules;
				}
				if ( 'curai_seo_adapter_override' === $key ) {
					return 'fake';
				}
				return $default;
			}
		);

		Functions\when( 'apply_filters' )->alias(
			static function ( $hook, $value ) use ( $recorder, $adapter ) {
				if ( 'curai_scheduler' === $hook ) {
					return $recorder;
				}
				if ( 'curai_seo_adapters' === $hook ) {
					return array( $adapter );
				}
				return $value;
			}
		);

		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );
		Functions\when( 'add_action' )->justReturn( true );

		$post              = new WP_Post();
		$post->ID          = 10;
		$post->post_status = 'publish';
		$post->post_type   = 'post';

		CURAI_Automation::on_save_post( 10, $post, false );

		$ability_ids = array_column( $recorder->dispatches, 'ability_id' );
		$this->assertContains( 'curator-ai/generate-meta-title', $ability_ids );
	}

	/**
	 * on_save_post dispatches nothing when all rules are disabled.
	 */
	public function test_on_save_post_skips_when_rule_disabled(): void {
		$rules = array(
			'on_post_save' => array(
				'generate_meta_title'       => array( 'enabled' => false ),
				'generate_meta_description' => array( 'enabled' => false ),
				'check_readability'         => array( 'enabled' => false ),
			),
		);

		$recorder = new CURAI_Recording_Scheduler();

		Functions\when( 'get_option' )->alias(
			static function ( $key, $default = false ) use ( $rules ) {
				if ( 'curai_automation_rules' === $key ) {
					return $rules;
				}
				if ( 'curai_seo_adapter_override' === $key ) {
					return 'auto';
				}
				return $default;
			}
		);

		Functions\when( 'apply_filters' )->alias(
			static function ( $hook, $value ) use ( $recorder ) {
				if ( 'curai_scheduler' === $hook ) {
					return $recorder;
				}
				if ( 'curai_seo_adapters' === $hook ) {
					return array( new CURAI_Test_Fake_Adapter() );
				}
				return $value;
			}
		);

		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );
		Functions\when( 'add_action' )->justReturn( true );

		$post              = new WP_Post();
		$post->ID          = 11;
		$post->post_status = 'publish';
		$post->post_type   = 'post';

		CURAI_Automation::on_save_post( 11, $post, true );

		$this->assertEmpty( $recorder->dispatches );
	}

	/**
	 * on_add_attachment dispatches alt-text ability when rule is enabled and no existing alt.
	 */
	public function test_on_add_attachment_dispatches_alt_text_when_rule_enabled_and_no_existing_alt(): void {
		$rules = array(
			'on_media_upload' => array(
				'generate_alt_text' => array(
					'enabled'        => true,
					'skip_if_exists' => true,
				),
			),
		);

		$recorder = new CURAI_Recording_Scheduler();

		Functions\when( 'get_option' )->alias(
			static function ( $key, $default = false ) use ( $rules ) {
				if ( 'curai_automation_rules' === $key ) {
					return $rules;
				}
				return $default;
			}
		);

		Functions\when( 'get_post_meta' )->alias(
			static function ( $post_id, $key, $single ) {
				if ( '_wp_attachment_image_alt' === $key ) {
					return '';
				}
				return '';
			}
		);

		Functions\when( 'apply_filters' )->alias(
			static function ( $hook, $value ) use ( $recorder ) {
				if ( 'curai_scheduler' === $hook ) {
					return $recorder;
				}
				return $value;
			}
		);

		CURAI_Automation::on_add_attachment( 99 );

		$ability_ids = array_column( $recorder->dispatches, 'ability_id' );
		$this->assertContains( 'curator-ai/generate-alt-text', $ability_ids );
	}
}
