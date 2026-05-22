<?php
/**
 * Ability: per-post readability score.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/audit-readability`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Audit_Readability {

	use CURAI_Ability_Helpers;

	/**
	 * Compute readability metrics for the given post.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: post_id (int).
	 * @return array|WP_Error
	 */
	public static function execute( array $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		$post = self::load_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$plain = wp_strip_all_tags( strip_shortcodes( $post->post_content ), true );
		$stats = CURAI_Readability_Calc::score( $plain );

		$severity = self::severity_from_grade( $stats['grade'] );

		CURAI_Audit_Store::upsert( 'readability', (int) $post->ID, 'post', $severity, $stats );

		return $stats;
	}

	/**
	 * Map a Flesch-Kincaid grade to a coarse severity bucket.
	 *
	 * Grade <= 8: info. Grade 9-12: warn. Grade > 12: error.
	 *
	 * @since 1.0.0
	 * @param float $grade Flesch-Kincaid grade level.
	 * @return int
	 */
	private static function severity_from_grade( float $grade ): int {
		if ( $grade <= 8.0 ) {
			return 1;
		}
		if ( $grade <= 12.0 ) {
			return 2;
		}
		return 3;
	}
}
