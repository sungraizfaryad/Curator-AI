<?php
/**
 * Unit tests for CURAI_Ability_Audit_Thin_Content.
 *
 * @package CuratorAI
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/stubs/wp-core-stubs.php';
require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-audit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/abilities/class-curai-ability-audit-thin-content.php';

final class CURAI_Ability_Audit_Thin_Content_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'is_wp_error' )->alias( static fn ( $thing ) => $thing instanceof WP_Error );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn ( $s, $rl = true ) => is_string( $s ) ? strip_tags( $s ) : '' );
		Functions\when( 'strip_shortcodes' )->returnArg( 1 );
		Functions\when( 'current_time' )->alias( static fn ( $t ) => '2026-05-22 12:00:00' );
		Functions\when( 'wp_json_encode' )->alias( static fn ( $v ) => json_encode( $v ) );
		Functions\when( 'sanitize_key' )->returnArg( 1 );
		$GLOBALS['wpdb'] = new class {
			public string $prefix = 'wp_';
			public function prepare( string $sql, ...$args ): string { return $sql; }
			public function query( string $sql ): int { return 1; }
			public function insert( string $table, array $row ): int { return 1; }
		};
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_execute_returns_empty_when_no_thin_posts(): void {
		Functions\when( 'get_posts' )->justReturn( array() );

		$result = CURAI_Ability_Audit_Thin_Content::execute( array( 'min_words' => 300 ) );

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( array(), $result['posts'] );
	}

	public function test_execute_flags_thin_posts(): void {
		$post               = new WP_Post();
		$post->ID           = 7;
		$post->post_title   = 'Short';
		$post->post_content = 'Only a few words here.';
		Functions\when( 'get_posts' )->justReturn( array( $post ) );

		$result = CURAI_Ability_Audit_Thin_Content::execute( array( 'min_words' => 300 ) );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 7, $result['posts'][0]['id'] );
		$this->assertLessThan( 300, $result['posts'][0]['word_count'] );
	}
}
