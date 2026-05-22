<?php
/**
 * Unit tests for CURAI_Native_SEO_Adapter.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/interface-curai-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-native-seo-adapter.php';

/**
 * Test suite for the native SEO adapter.
 */
final class CURAI_Native_Adapter_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( '__' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * The native adapter is always active regardless of any external plugin state.
	 */
	public function test_is_active_always_returns_true(): void {
		$this->assertTrue( ( new CURAI_Native_SEO_Adapter() )->is_active() );
	}

	/**
	 * Read meta title returns whatever get_post_meta returns, cast to string.
	 */
	public function test_read_meta_title_returns_stored_value(): void {
		Functions\when( 'get_post_meta' )
			->alias(
				static function ( int $post_id, string $key, bool $single ) {
					if ( '_curai_meta_title' === $key ) {
						return 'Stored Title';
					}
					return '';
				}
			);

		$adapter = new CURAI_Native_SEO_Adapter();
		$this->assertSame( 'Stored Title', $adapter->read_meta_title( 1 ) );
	}

	/**
	 * Read meta title returns empty string when no value is stored.
	 */
	public function test_read_meta_title_returns_empty_string_when_not_set(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$adapter = new CURAI_Native_SEO_Adapter();
		$this->assertSame( '', $adapter->read_meta_title( 99 ) );
	}

	/**
	 * Write meta title delegates to update_post_meta and returns bool; a subsequent
	 * read returns the written value.
	 */
	public function test_write_meta_title_persists_and_read_returns_it(): void {
		$stored = array();

		Functions\when( 'update_post_meta' )
			->alias(
				static function ( int $post_id, string $key, string $value ) use ( &$stored ) {
					$stored[ $key ] = $value;
					return true;
				}
			);

		Functions\when( 'get_post_meta' )
			->alias(
				static function ( int $post_id, string $key, bool $single ) use ( &$stored ) {
					return $stored[ $key ] ?? '';
				}
			);

		$adapter = new CURAI_Native_SEO_Adapter();
		$written = $adapter->write_meta_title( 1, 'My SEO Title' );

		$this->assertTrue( $written );
		$this->assertSame( 'My SEO Title', $adapter->read_meta_title( 1 ) );
	}
}
