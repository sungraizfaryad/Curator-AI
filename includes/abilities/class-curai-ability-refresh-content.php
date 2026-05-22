<?php
/**
 * Ability: refresh post content (date_only / context / rewrite).
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/refresh-content`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Refresh_Content {

	use CURAI_Ability_Helpers;

	/**
	 * Max tokens for content generation. Set high since output can be long.
	 *
	 * @since 1.0.0
	 */
	private const MAX_TOKENS = 4096;

	/**
	 * Allowed modes.
	 *
	 * @since 1.0.0
	 */
	private const MODES = array( 'date_only', 'context', 'rewrite' );

	/**
	 * Refresh content for the given post.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: post_id (int), mode (string: date_only|context|rewrite).
	 * @return array|WP_Error On success: { updated_content, diff_summary, mode, tokens_used }.
	 */
	public static function execute( array $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$mode    = isset( $input['mode'] ) ? (string) $input['mode'] : 'date_only';

		$post = self::load_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! in_array( $mode, self::MODES, true ) ) {
			return new WP_Error(
				'curai_invalid_mode',
				/* translators: %s: mode value */
				sprintf( __( 'Invalid refresh mode: %s. Use date_only, context, or rewrite.', 'curator-ai' ), $mode )
			);
		}

		if ( 'date_only' === $mode ) {
			return self::refresh_date_only( $post );
		}

		$budget = CURAI_Cost_Guard::check();
		if ( is_wp_error( $budget ) ) {
			return $budget;
		}

		$prompt = CURAI_Prompt_Builder::refresh_content( $post->post_title, $post->post_content, $mode );

		$result = CURAI_AI_Bridge::generate_text(
			$prompt['user'],
			$prompt['system'],
			array(
				'max_tokens'  => self::MAX_TOKENS,
				'temperature' => 'rewrite' === $mode ? 0.7 : 0.4,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$updated     = trim( $result );
		$tokens_used = (int) ceil( ( strlen( $prompt['user'] ) + strlen( $prompt['system'] ) + strlen( $updated ) ) / 4 );
		CURAI_Cost_Guard::record_usage( $tokens_used, 0.0 );

		return array(
			'updated_content' => $updated,
			'diff_summary'    => self::diff_summary( $post->post_content, $updated ),
			'mode'            => $mode,
			'tokens_used'     => $tokens_used,
		);
	}

	/**
	 * Bump post_modified intent without AI. Caller persists via wp_update_post.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Source post.
	 * @return array
	 */
	private static function refresh_date_only( WP_Post $post ): array {
		return array(
			'updated_content' => $post->post_content,
			'diff_summary'    => __( 'Modified date refreshed to current time. Content unchanged.', 'curator-ai' ),
			'mode'            => 'date_only',
			'tokens_used'     => 0,
		);
	}

	/**
	 * Human-readable summary of how content changed.
	 *
	 * @since 1.0.0
	 * @param string $original Source content.
	 * @param string $updated  Generated content.
	 * @return string
	 */
	private static function diff_summary( string $original, string $updated ): string {
		$delta = strlen( $updated ) - strlen( $original );
		if ( 0 === $delta ) {
			return __( 'Content rewritten with same length.', 'curator-ai' );
		}
		return sprintf(
			/* translators: %d: signed delta in characters */
			__( 'Content updated (%+d characters).', 'curator-ai' ),
			$delta
		);
	}
}
