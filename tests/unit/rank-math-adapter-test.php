<?php
/**
 * Unit tests for CURAI_Rank_Math_SEO_Adapter.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/interface-curai-seo-adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/seo-adapters/class-curai-rank-math-seo-adapter.php';

/**
 * Test suite for the Rank Math SEO adapter.
 */
final class CURAI_Rank_Math_Adapter_Test extends TestCase {

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
	 * is_active() returns true when the RankMath class exists (stub loaded via wp-core-stubs.php).
	 */
	public function test_is_active_returns_true_when_rankmath_class_exists(): void {
		$this->assertTrue( ( new CURAI_Rank_Math_SEO_Adapter() )->is_active() );
	}

	/**
	 * read_meta_title() fetches the Rank Math-specific post meta key.
	 */
	public function test_read_meta_title_returns_rank_math_key_value(): void {
		Functions\expect( 'get_post_meta' )
			->once()
			->with( 7, 'rank_math_title', true )
			->andReturn( 'RM Title' );

		$adapter = new CURAI_Rank_Math_SEO_Adapter();
		$this->assertSame( 'RM Title', $adapter->read_meta_title( 7 ) );
	}

	/**
	 * write_meta_title() delegates to update_post_meta with the Rank Math meta key.
	 */
	public function test_write_meta_title_persists_to_rank_math_key(): void {
		Functions\expect( 'update_post_meta' )
			->once()
			->with( 7, 'rank_math_title', 'New Title' )
			->andReturn( true );

		$adapter = new CURAI_Rank_Math_SEO_Adapter();
		$this->assertTrue( $adapter->write_meta_title( 7, 'New Title' ) );
	}

	/**
	 * read_focus_keyword() fetches the Rank Math focus keyword meta key.
	 */
	public function test_read_focus_keyword_returns_rank_math_key_value(): void {
		Functions\expect( 'get_post_meta' )
			->once()
			->with( 7, 'rank_math_focus_keyword', true )
			->andReturn( 'seo plugin' );

		$adapter = new CURAI_Rank_Math_SEO_Adapter();
		$this->assertSame( 'seo plugin', $adapter->read_focus_keyword( 7 ) );
	}
}
