<?php
/**
 * Unit tests for CURAI_Prompt_Builder.
 *
 * @package CuratorAI
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/ai/class-curai-prompt-builder.php';

final class CURAI_Prompt_Builder_Test extends TestCase {

	public function test_meta_title_prompt_includes_post_title_and_excerpt(): void {
		$prompt = CURAI_Prompt_Builder::meta_title(
			'My Post Title',
			'My excerpt here.',
			'',
			60
		);

		$this->assertIsArray( $prompt );
		$this->assertArrayHasKey( 'user', $prompt );
		$this->assertArrayHasKey( 'system', $prompt );
		$this->assertStringContainsString( 'My Post Title', $prompt['user'] );
		$this->assertStringContainsString( 'My excerpt here.', $prompt['user'] );
		$this->assertStringContainsString( '60', $prompt['user'] );
	}

	public function test_meta_title_prompt_includes_focus_keyword_when_provided(): void {
		$prompt = CURAI_Prompt_Builder::meta_title( 'Title', 'Excerpt', 'wordpress hosting', 60 );

		$this->assertStringContainsString( 'wordpress hosting', $prompt['user'] );
	}

	public function test_meta_title_system_instruction_is_seo_focused(): void {
		$prompt = CURAI_Prompt_Builder::meta_title( 'Title', 'Excerpt', '', 60 );

		$this->assertStringContainsString( 'SEO', $prompt['system'] );
		$this->assertStringContainsString( 'no quotes', strtolower( $prompt['system'] ) );
	}
}
