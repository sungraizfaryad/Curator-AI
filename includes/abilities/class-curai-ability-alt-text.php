<?php
/**
 * Ability: generate alt text for an attachment via vision-capable AI.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/generate-alt-text`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Alt_Text {

	/**
	 * Max tokens for alt text.
	 *
	 * @since 1.0.0
	 */
	private const MAX_TOKENS = 80;

	/**
	 * Hard cap on alt text length per accessibility guidelines.
	 *
	 * @since 1.0.0
	 */
	private const MAX_ALT_LENGTH = 125;

	/**
	 * Generate alt text from an image attachment.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: attachment_id (int), context_post_id (int, optional).
	 * @return array|WP_Error On success: `{ alt_text: string, tokens_used: int }`.
	 */
	public static function execute( array $input ) {
		$attachment_id   = isset( $input['attachment_id'] ) ? (int) $input['attachment_id'] : 0;
		$context_post_id = isset( $input['context_post_id'] ) ? (int) $input['context_post_id'] : 0;

		$attachment = get_post( $attachment_id );
		if ( ! $attachment instanceof WP_Post ) {
			return new WP_Error(
				'curai_attachment_not_found',
				/* translators: %d: attachment ID */
				sprintf( __( 'Attachment %d not found.', 'curator-ai-seo-site-care' ), $attachment_id )
			);
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error(
				'curai_attachment_not_image',
				/* translators: %s: mime type */
				sprintf( __( 'Attachment is not an image (mime: %s).', 'curator-ai-seo-site-care' ), (string) get_post_mime_type( $attachment_id ) )
			);
		}

		$budget = CURAI_Cost_Guard::check();
		if ( is_wp_error( $budget ) ) {
			return $budget;
		}

		$image_url = wp_get_attachment_url( $attachment_id );
		$mime      = (string) get_post_mime_type( $attachment_id );

		$context_title = '';
		if ( $context_post_id > 0 ) {
			$context_post = get_post( $context_post_id );
			if ( $context_post instanceof WP_Post ) {
				$context_title = (string) $context_post->post_title;
			}
		}

		$prompt = CURAI_Prompt_Builder::alt_text( $context_title );

		$result = CURAI_AI_Bridge::generate_text_with_image(
			$prompt['user'],
			$prompt['system'],
			(string) $image_url,
			$mime,
			array(
				'max_tokens'  => self::MAX_TOKENS,
				'temperature' => 0.5,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$alt = self::clean( $result );

		$tokens_used = (int) ceil( ( strlen( $prompt['user'] ) + strlen( $prompt['system'] ) + strlen( $alt ) ) / 4 );
		CURAI_Cost_Guard::record_usage( $tokens_used, 0.0 );

		return array(
			'alt_text'    => $alt,
			'tokens_used' => $tokens_used,
		);
	}

	/**
	 * Strip surrounding quotes and clamp to MAX_ALT_LENGTH.
	 *
	 * @since 1.0.0
	 * @param string $value Raw AI output.
	 * @return string
	 */
	private static function clean( string $value ): string {
		$value = trim( $value, " \t\n\r\0\x0B\"'" );
		if ( strlen( $value ) <= self::MAX_ALT_LENGTH ) {
			return $value;
		}
		$truncated  = substr( $value, 0, self::MAX_ALT_LENGTH );
		$last_space = strrpos( $truncated, ' ' );
		if ( false !== $last_space && $last_space > self::MAX_ALT_LENGTH - 20 ) {
			$truncated = substr( $truncated, 0, $last_space );
		}
		return rtrim( $truncated );
	}
}
