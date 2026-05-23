<?php
/**
 * Shared helpers for Curator AI ability classes.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Common ability operations: post loading, excerpt extraction.
 *
 * @since 1.0.0
 */
trait CURAI_Ability_Helpers {

	/**
	 * Load a post by ID or return a WP_Error.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return WP_Post|WP_Error
	 */
	protected static function load_post( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return new WP_Error(
				'curai_post_not_found',
				/* translators: %d: post ID */
				sprintf( __( 'Post %d not found.', 'curator-ai-seo-site-care' ), $post_id )
			);
		}
		return $post;
	}

	/**
	 * Build a plain-text excerpt suitable for prompt context.
	 *
	 * Uses the post excerpt when available, otherwise trims the post content.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post      Post object.
	 * @param int     $max_words Approximate word cap.
	 * @return string
	 */
	protected static function build_plain_excerpt( WP_Post $post, int $max_words = 80 ): string {
		$raw = '' !== $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		$raw = wp_strip_all_tags( strip_shortcodes( $raw ), true );
		return wp_trim_words( $raw, $max_words, '' );
	}
}
