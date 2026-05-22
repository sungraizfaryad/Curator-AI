<?php
/**
 * Ability: generate SEO meta description via AI.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/generate-meta-description`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Meta_Description {

	use CURAI_Ability_Helpers;

	/**
	 * Max tokens for a meta description.
	 *
	 * @since 1.0.0
	 */
	private const MAX_TOKENS = 96;

	/**
	 * Generate a meta description for the given post.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: post_id (int), focus_keyword (string, optional),
	 *                     max_length (int, default 155).
	 * @return array|WP_Error On success: `{ description: string, tokens_used: int }`.
	 */
	public static function execute( array $input ) {
		$post_id       = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$focus_keyword = isset( $input['focus_keyword'] ) ? (string) $input['focus_keyword'] : '';
		$max_length    = isset( $input['max_length'] ) ? (int) $input['max_length'] : 155;

		$post = self::load_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$budget = CURAI_Cost_Guard::check();
		if ( is_wp_error( $budget ) ) {
			return $budget;
		}

		$excerpt = self::build_plain_excerpt( $post );
		$prompt  = CURAI_Prompt_Builder::meta_description( $post->post_title, $excerpt, $focus_keyword, $max_length );

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

		$description = self::clamp_to_length( $result, $max_length );

		$tokens_used = (int) ceil( ( strlen( $prompt['user'] ) + strlen( $prompt['system'] ) + strlen( $description ) ) / 4 );
		CURAI_Cost_Guard::record_usage( $tokens_used, 0.0 );

		return array(
			'description' => $description,
			'tokens_used' => $tokens_used,
		);
	}

	/**
	 * Clamp a description to a hard length cap on a word boundary.
	 *
	 * @since 1.0.0
	 * @param string $value      Generated text.
	 * @param int    $max_length Hard max characters.
	 * @return string
	 */
	private static function clamp_to_length( string $value, int $max_length ): string {
		$value = trim( $value, " \t\n\r\0\x0B\"'" );
		if ( strlen( $value ) <= $max_length ) {
			return $value;
		}
		$truncated  = substr( $value, 0, $max_length );
		$last_space = strrpos( $truncated, ' ' );
		if ( false !== $last_space && $last_space > $max_length - 20 ) {
			$truncated = substr( $truncated, 0, $last_space );
		}
		return rtrim( $truncated );
	}
}
