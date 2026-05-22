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

	public function test_meta_description_includes_post_title_and_max_length(): void {
		$prompt = CURAI_Prompt_Builder::meta_description( 'Title', 'Excerpt', '', 155 );

		$this->assertStringContainsString( 'Title', $prompt['user'] );
		$this->assertStringContainsString( '155', $prompt['user'] );
		$this->assertStringContainsString( 'meta description', strtolower( $prompt['system'] ) );
	}

	public function test_alt_text_system_avoids_image_of_prefix(): void {
		$prompt = CURAI_Prompt_Builder::alt_text();

		$this->assertStringContainsString( 'accessibility', strtolower( $prompt['system'] ) );
		$this->assertStringContainsString( 'image of', strtolower( $prompt['system'] ) );
	}

	public function test_alt_text_user_includes_context_when_provided(): void {
		$prompt = CURAI_Prompt_Builder::alt_text( 'WordPress Hosting Guide' );

		$this->assertStringContainsString( 'WordPress Hosting Guide', $prompt['user'] );
	}

	public function test_refresh_content_context_mode_preserves_voice(): void {
		$prompt = CURAI_Prompt_Builder::refresh_content( 'Title', '<p>Old content</p>', 'context' );

		$this->assertStringContainsString( 'Title', $prompt['user'] );
		$this->assertStringContainsString( '<p>Old content</p>', $prompt['user'] );
		$this->assertStringContainsString( 'original voice', strtolower( $prompt['system'] ) );
	}

	public function test_refresh_content_rewrite_mode_does_full_rewrite(): void {
		$prompt = CURAI_Prompt_Builder::refresh_content( 'Title', '<p>Old</p>', 'rewrite' );

		$this->assertStringContainsString( 'Fully rewrite', $prompt['system'] );
	}
}
