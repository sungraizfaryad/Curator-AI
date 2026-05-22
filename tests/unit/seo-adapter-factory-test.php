<?php
/**
 * Unit tests for CURAI_SEO_Adapter_Factory.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/interface-curai-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-yoast-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-rank-math-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-native-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-seo-adapter-factory.php';

/**
 * Test suite for CURAI_SEO_Adapter_Factory.
 */
final class CURAI_SEO_Adapter_Factory_Test extends TestCase {

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
	 * Auto-detection returns the native adapter when it is the only active one.
	 */
	public function test_auto_returns_native_when_no_other_adapter_active(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				if ( 'curai_seo_adapters' === $hook ) {
					return array(
						new CURAI_Test_Inactive_Adapter( 'yoast' ),
						new CURAI_Test_Inactive_Adapter( 'rank-math' ),
						new CURAI_Test_Active_Adapter( 'native' ),
					);
				}
				return $value;
			}
		);
		Functions\when( 'get_option' )->justReturn( 'auto' );

		$adapter = CURAI_SEO_Adapter_Factory::get();
		$this->assertSame( 'native', $adapter->get_slug() );
	}

	/**
	 * Auto-detection picks the first active adapter when multiple adapters are active.
	 */
	public function test_auto_picks_first_active_when_multiple_active(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				if ( 'curai_seo_adapters' === $hook ) {
					return array(
						new CURAI_Test_Active_Adapter( 'yoast' ),
						new CURAI_Test_Active_Adapter( 'rank-math' ),
						new CURAI_Test_Active_Adapter( 'native' ),
					);
				}
				return $value;
			}
		);
		Functions\when( 'get_option' )->justReturn( 'auto' );

		$adapter = CURAI_SEO_Adapter_Factory::get();
		$this->assertSame( 'yoast', $adapter->get_slug() );
	}

	/**
	 * An admin override forces a specific adapter slug regardless of detection order.
	 */
	public function test_override_forces_specific_slug(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				if ( 'curai_seo_adapters' === $hook ) {
					return array(
						new CURAI_Test_Active_Adapter( 'yoast' ),
						new CURAI_Test_Active_Adapter( 'rank-math' ),
						new CURAI_Test_Active_Adapter( 'native' ),
					);
				}
				return $value;
			}
		);
		Functions\when( 'get_option' )->justReturn( 'rank-math' );

		$adapter = CURAI_SEO_Adapter_Factory::get();
		$this->assertSame( 'rank-math', $adapter->get_slug() );
	}

	/**
	 * An override with an unknown slug falls back to auto-detection.
	 */
	public function test_override_falls_back_to_auto_when_slug_unknown(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				if ( 'curai_seo_adapters' === $hook ) {
					return array(
						new CURAI_Test_Active_Adapter( 'yoast' ),
						new CURAI_Test_Active_Adapter( 'rank-math' ),
						new CURAI_Test_Active_Adapter( 'native' ),
					);
				}
				return $value;
			}
		);
		Functions\when( 'get_option' )->justReturn( 'nonexistent' );

		$adapter = CURAI_SEO_Adapter_Factory::get();
		$this->assertSame( 'yoast', $adapter->get_slug() );
	}

	/**
	 * The curai_seo_adapters filter can append a custom adapter that wins auto-detection.
	 */
	public function test_filter_can_append_custom_adapter(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				if ( 'curai_seo_adapters' === $hook ) {
					return array(
						new CURAI_Test_Inactive_Adapter( 'yoast' ),
						new CURAI_Test_Inactive_Adapter( 'rank-math' ),
						new CURAI_Test_Inactive_Adapter( 'native' ),
						new CURAI_Test_Active_Adapter( 'custom' ),
					);
				}
				return $value;
			}
		);
		Functions\when( 'get_option' )->justReturn( 'auto' );

		$adapter = CURAI_SEO_Adapter_Factory::get();
		$this->assertSame( 'custom', $adapter->get_slug() );
	}

	/**
	 * Repeated calls to get() return the exact same cached instance.
	 */
	public function test_cache_returns_same_instance_on_repeat_call(): void {
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				if ( 'curai_seo_adapters' === $hook ) {
					return array(
						new CURAI_Test_Active_Adapter( 'native' ),
					);
				}
				return $value;
			}
		);
		Functions\when( 'get_option' )->justReturn( 'auto' );

		$first  = CURAI_SEO_Adapter_Factory::get();
		$second = CURAI_SEO_Adapter_Factory::get();
		$this->assertSame( $first, $second );
	}
}

/**
 * Test fixture: adapter whose is_active() always returns true.
 */
final class CURAI_Test_Active_Adapter implements CURAI_SEO_Adapter_Interface {

	/**
	 * @param string $slug Adapter slug.
	 */
	public function __construct( private string $slug ) {}

	/** @inheritDoc */
	public function is_active(): bool {
		return true;
	}

	/** @inheritDoc */
	public function get_slug(): string {
		return $this->slug;
	}

	/** @inheritDoc */
	public function get_label(): string {
		return $this->slug;
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
 * Test fixture: adapter whose is_active() always returns false.
 */
final class CURAI_Test_Inactive_Adapter implements CURAI_SEO_Adapter_Interface {

	/**
	 * @param string $slug Adapter slug.
	 */
	public function __construct( private string $slug ) {}

	/** @inheritDoc */
	public function is_active(): bool {
		return false;
	}

	/** @inheritDoc */
	public function get_slug(): string {
		return $this->slug;
	}

	/** @inheritDoc */
	public function get_label(): string {
		return $this->slug;
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
		return false;
	}

	/** @inheritDoc */
	public function write_meta_description( int $post_id, string $desc ): bool {
		return false;
	}

	/** @inheritDoc */
	public function read_focus_keyword( int $post_id ): string {
		return '';
	}
}
