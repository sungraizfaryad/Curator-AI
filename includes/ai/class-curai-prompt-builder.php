<?php
/**
 * Builds prompt + system instruction pairs for each Curator AI ability.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Centralized prompt templates.
 *
 * @since 1.0.0
 */
class CURAI_Prompt_Builder {

	/**
	 * Build the meta-title prompt.
	 *
	 * @since 1.0.0
	 * @param string $post_title    Original post title.
	 * @param string $excerpt       Plain-text excerpt or content snippet.
	 * @param string $focus_keyword Optional focus keyword.
	 * @param int    $max_length    Maximum characters for the generated title.
	 * @return array{user: string, system: string}
	 */
	public static function meta_title( string $post_title, string $excerpt, string $focus_keyword, int $max_length ): array {
		$system = sprintf(
			'You are an SEO expert. Write a single meta title under %d characters. Reply with the title only, no quotes, no markdown, no explanation.',
			$max_length
		);

		$user  = "Original post title: {$post_title}\n";
		$user .= "Excerpt: {$excerpt}\n";
		if ( '' !== $focus_keyword ) {
			$user .= "Focus keyword (must appear): {$focus_keyword}\n";
		}
		$user .= "Maximum characters: {$max_length}.";

		return array(
			'user'   => $user,
			'system' => $system,
		);
	}

	/**
	 * Build the meta-description prompt.
	 *
	 * @since 1.0.0
	 * @param string $post_title    Post title.
	 * @param string $excerpt       Plain-text excerpt.
	 * @param string $focus_keyword Optional focus keyword.
	 * @param int    $max_length    Max characters (typical 155).
	 * @return array{user: string, system: string}
	 */
	public static function meta_description( string $post_title, string $excerpt, string $focus_keyword, int $max_length ): array {
		$system = sprintf(
			'You are an SEO expert. Write a single meta description between 120 and %d characters that summarizes the post and invites a click. Reply with the description only, no quotes, no markdown, no explanation.',
			$max_length
		);

		$user  = "Post title: {$post_title}\n";
		$user .= "Excerpt: {$excerpt}\n";
		if ( '' !== $focus_keyword ) {
			$user .= "Focus keyword (must appear naturally): {$focus_keyword}\n";
		}
		$user .= "Maximum characters: {$max_length}.";

		return array(
			'user'   => $user,
			'system' => $system,
		);
	}

	/**
	 * Build the alt-text prompt.
	 *
	 * @since 1.0.0
	 * @param string $context_post_title Optional surrounding post title for SEO context.
	 * @return array{user: string, system: string}
	 */
	public static function alt_text( string $context_post_title = '' ): array {
		$system = 'You are an accessibility and SEO expert. Describe the provided image in one sentence under 125 characters. Be concrete and specific. Do not start with "Image of" or "Picture of". Reply with the alt text only, no quotes.';

		$user = 'Generate alt text for this image.';
		if ( '' !== $context_post_title ) {
			$user .= " The image appears in a post titled: {$context_post_title}.";
		}

		return array(
			'user'   => $user,
			'system' => $system,
		);
	}

	/**
	 * Build the refresh-content prompt for the `context` or `rewrite` mode.
	 *
	 * @since 1.0.0
	 * @param string $post_title   Post title (preserved).
	 * @param string $post_content Current post content (HTML allowed).
	 * @param string $mode         Either 'context' or 'rewrite'.
	 * @return array{user: string, system: string}
	 */
	public static function refresh_content( string $post_title, string $post_content, string $mode ): array {
		if ( 'rewrite' === $mode ) {
			$system = 'You are a senior content editor. Fully rewrite the provided post content while preserving its meaning, structure, and HTML formatting. Use current 2026 references and statistics. Reply with the rewritten content only, no commentary, no quotes.';
			$user   = "Title: {$post_title}\n\nCurrent content (HTML):\n{$post_content}";
		} else {
			$system = 'You are a senior content editor. Refresh the provided post content for currency (update outdated dates, references, statistics for 2026) while preserving HTML structure and the original voice. Do not add new sections. Reply with the refreshed content only, no commentary, no quotes.';
			$user   = "Title: {$post_title}\n\nCurrent content (HTML):\n{$post_content}";
		}

		return array(
			'user'   => $user,
			'system' => $system,
		);
	}
}
