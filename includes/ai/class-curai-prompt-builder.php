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
}
