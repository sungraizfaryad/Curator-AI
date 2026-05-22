<?php
/**
 * Ability: generate SEO meta title via AI.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/generate-meta-title`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Meta_Title {

	use CURAI_Ability_Helpers;

	/**
	 * Max tokens to request from the model for a meta title.
	 *
	 * Title is short; small cap keeps latency and cost low.
	 *
	 * @since 1.0.0
	 */
	private const MAX_TOKENS = 64;

	/**
	 * Generate a meta title for the given post.
	 *
	 * @since 1.0.0
	 * @param array $input Validated input. Keys: post_id (int), focus_keyword (string, optional),
	 *                     max_length (int, default 60).
	 * @return array|WP_Error On success: `{ title: string, tokens_used: int }`. On failure: WP_Error.
	 */
	public static function execute( array $input ) {
		$post_id       = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$focus_keyword = isset( $input['focus_keyword'] ) ? (string) $input['focus_keyword'] : '';
		$max_length    = isset( $input['max_length'] ) ? (int) $input['max_length'] : 60;

		$post = self::load_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$budget = CURAI_Cost_Guard::check();
		if ( is_wp_error( $budget ) ) {
			return $budget;
		}

		$excerpt = self::build_plain_excerpt( $post );
		$prompt  = CURAI_Prompt_Builder::meta_title( $post->post_title, $excerpt, $focus_keyword, $max_length );

		$result = CURAI_AI_Bridge::generate_text(
			$prompt['user'],
			$prompt['system'],
			array(
				'max_tokens'  => self::MAX_TOKENS,
				'temperature' => 0.7,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$title = self::trim_to_length( $result, $max_length );

		// Approximate token usage: ~4 chars per token (OpenAI heuristic).
		$tokens_used = (int) ceil( ( strlen( $prompt['user'] ) + strlen( $prompt['system'] ) + strlen( $title ) ) / 4 );
		CURAI_Cost_Guard::record_usage( $tokens_used, 0.0 );

		return array(
			'title'       => $title,
			'tokens_used' => $tokens_used,
		);
	}

	/**
	 * Hard-cap the generated title at the requested length, preserving word boundaries.
	 *
	 * @since 1.0.0
	 * @param string $value      Generated title.
	 * @param int    $max_length Max characters allowed.
	 * @return string
	 */
	private static function trim_to_length( string $value, int $max_length ): string {
		$value = trim( $value, " \t\n\r\0\x0B\"'" );
		if ( strlen( $value ) <= $max_length ) {
			return $value;
		}
		$truncated  = substr( $value, 0, $max_length );
		$last_space = strrpos( $truncated, ' ' );
		if ( false !== $last_space && $last_space > $max_length - 12 ) {
			$truncated = substr( $truncated, 0, $last_space );
		}
		return rtrim( $truncated );
	}
}
