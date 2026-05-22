<?php
/**
 * Unit tests for CURAI_Yoast_SEO_Adapter.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/interface-curai-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-yoast-seo-adapter.php';

/**
 * Test suite for the Yoast SEO adapter.
 */
final class CURAI_Yoast_Adapter_Test extends TestCase {

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
	 * is_active() returns true when the WPSEO_VERSION constant is defined.
	 */
	public function test_is_active_returns_true_when_wpseo_version_defined(): void {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			define( 'WPSEO_VERSION', '21.0' );
		}

		$this->assertTrue( ( new CURAI_Yoast_SEO_Adapter() )->is_active() );
	}

	/**
	 * read_meta_title() fetches the Yoast-specific post meta key.
	 */
	public function test_read_meta_title_returns_yoast_meta_key_value(): void {
		Functions\expect( 'get_post_meta' )
			->once()
			->with( 42, '_yoast_wpseo_title', true )
			->andReturn( 'Yoast Title' );

		$adapter = new CURAI_Yoast_SEO_Adapter();
		$this->assertSame( 'Yoast Title', $adapter->read_meta_title( 42 ) );
	}

	/**
	 * write_meta_description() delegates to update_post_meta with the Yoast meta key.
	 */
	public function test_write_meta_description_persists_to_yoast_key(): void {
		Functions\expect( 'update_post_meta' )
			->once()
			->with( 42, '_yoast_wpseo_metadesc', 'New Desc' )
			->andReturn( true );

		$adapter = new CURAI_Yoast_SEO_Adapter();
		$this->assertTrue( $adapter->write_meta_description( 42, 'New Desc' ) );
	}

	/**
	 * read_focus_keyword() fetches the Yoast focus keyword meta key.
	 */
	public function test_read_focus_keyword_returns_yoast_focus_key_value(): void {
		Functions\expect( 'get_post_meta' )
			->once()
			->with( 42, '_yoast_wpseo_focuskw', true )
			->andReturn( 'wordpress plugin' );

		$adapter = new CURAI_Yoast_SEO_Adapter();
		$this->assertSame( 'wordpress plugin', $adapter->read_focus_keyword( 42 ) );
	}
}
