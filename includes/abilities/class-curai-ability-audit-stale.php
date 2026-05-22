<?php
/**
 * Ability: audit posts older than N months since last modification.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/audit-stale`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Audit_Stale {

	/**
	 * Find posts modified more than N months ago.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: months (int, default 12), post_types (array, default ['post']), limit (int, default 200).
	 * @return array{ posts: array<int, array{id:int,title:string,modified:string,age_days:int}>, count: int, months: int }
	 */
	public static function execute( array $input ): array {
		$months     = isset( $input['months'] ) ? max( 1, (int) $input['months'] ) : 12;
		$post_types = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_key', $input['post_types'] ) : array( 'post' );
		$limit      = isset( $input['limit'] ) ? max( 1, min( 1000, (int) $input['limit'] ) ) : 200;

		$cutoff_ts = strtotime( "-{$months} months", strtotime( current_time( 'mysql', true ) ) );
		$cutoff    = gmdate( 'Y-m-d H:i:s', (int) $cutoff_ts );

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'ASC',
				'date_query'     => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => $cutoff,
					),
				),
				'fields'         => '',
			)
		);

		$now_ts = (int) strtotime( current_time( 'mysql', true ) );
		$output = array();

		foreach ( (array) $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$modified_ts = (int) strtotime( $post->post_modified );
			$age_days    = max( 0, (int) floor( ( $now_ts - $modified_ts ) / DAY_IN_SECONDS ) );

			$row      = array(
				'id'       => (int) $post->ID,
				'title'    => (string) $post->post_title,
				'modified' => (string) $post->post_modified,
				'age_days' => $age_days,
			);
			$output[] = $row;

			CURAI_Audit_Store::upsert(
				'stale',
				(int) $post->ID,
				'post',
				$age_days > 365 ? 2 : 1,
				$row
			);
		}

		return array(
			'posts'  => $output,
			'count'  => count( $output ),
			'months' => $months,
		);
	}
}
