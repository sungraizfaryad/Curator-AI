<?php
/**
 * Ability: flag posts under a word-count threshold.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/audit-thin-content`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Audit_Thin_Content {

	/**
	 * Find published posts with fewer than $min_words plain-text words.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: min_words (int, default 300), post_types (array, default ['post']), limit (int, default 200).
	 * @return array{ posts: array<int, array{id:int,title:string,word_count:int}>, count: int, min_words: int }
	 */
	public static function execute( array $input ): array {
		$min_words  = isset( $input['min_words'] ) ? max( 50, (int) $input['min_words'] ) : 300;
		$post_types = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_key', $input['post_types'] ) : array( 'post' );
		$limit      = isset( $input['limit'] ) ? max( 1, min( 1000, (int) $input['limit'] ) ) : 200;

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
			)
		);

		$output = array();
		foreach ( (array) $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$plain      = wp_strip_all_tags( strip_shortcodes( $post->post_content ), true );
			$word_count = str_word_count( $plain );

			if ( $word_count >= $min_words ) {
				continue;
			}

			$row      = array(
				'id'         => (int) $post->ID,
				'title'      => (string) $post->post_title,
				'word_count' => (int) $word_count,
			);
			$output[] = $row;

			CURAI_Audit_Store::upsert(
				'thin',
				(int) $post->ID,
				'post',
				$word_count < 100 ? 3 : 2,
				$row
			);
		}

		return array(
			'posts'     => $output,
			'count'     => count( $output ),
			'min_words' => $min_words,
		);
	}
}
