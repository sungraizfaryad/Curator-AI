<?php
/**
 * Ability: HEAD-check external links inside a single post.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/audit-broken-links`.
 *
 * Phase 4 scope: per-post synchronous, capped at 50 links per call.
 * Full-site async sweep lands in Phase 6 with Action Scheduler.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Audit_Broken_Links {

	use CURAI_Ability_Helpers;

	/**
	 * Hard cap on links checked per execution to keep runtime predictable.
	 *
	 * @since 1.0.0
	 */
	private const MAX_LINKS = 50;

	/**
	 * Scan one post for broken external links.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: post_id (int).
	 * @return array|WP_Error On success: { post_id:int, count:int, broken_count:int, links: array<int, array{url:string,status:int,broken:bool,message:string}> }
	 */
	public static function execute( array $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		$post = self::load_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$urls = CURAI_Link_Checker::extract_urls( $post->post_content );
		if ( count( $urls ) > self::MAX_LINKS ) {
			$urls = array_slice( $urls, 0, self::MAX_LINKS );
		}

		$links        = array();
		$broken_count = 0;
		foreach ( $urls as $url ) {
			$check   = CURAI_Link_Checker::check_url( $url );
			$links[] = $check;
			if ( ! empty( $check['broken'] ) ) {
				++$broken_count;
			}
		}

		CURAI_Audit_Store::upsert(
			'broken_links',
			(int) $post->ID,
			'post',
			$broken_count > 0 ? 3 : 0,
			array(
				'count'        => count( $links ),
				'broken_count' => $broken_count,
				'links'        => $links,
			)
		);

		return array(
			'post_id'      => (int) $post->ID,
			'count'        => count( $links ),
			'broken_count' => $broken_count,
			'links'        => $links,
		);
	}
}
