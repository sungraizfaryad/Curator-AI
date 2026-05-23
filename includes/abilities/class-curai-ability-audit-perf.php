<?php
/**
 * Ability: PageSpeed Insights performance audit.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Execute callback for `curator-ai/audit-perf`.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Audit_Perf {

	/**
	 * Run a PageSpeed Insights audit.
	 *
	 * @since 1.0.0
	 * @param array $input Keys: url (string, default home_url), strategy (string, 'mobile'|'desktop', default 'mobile').
	 * @return array|WP_Error
	 */
	public static function execute( array $input ) {
		$url      = isset( $input['url'] ) && is_string( $input['url'] ) ? trim( $input['url'] ) : (string) home_url( '/' );
		$strategy = isset( $input['strategy'] ) && 'desktop' === $input['strategy'] ? 'desktop' : 'mobile';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || ! preg_match( '#^https?://#i', $url ) ) {
			return new WP_Error(
				'curai_invalid_url',
				/* translators: %s: URL value */
				sprintf( __( 'Invalid URL for performance audit: %s', 'curator-ai-seo-site-care' ), $url )
			);
		}

		$result = CURAI_PageSpeed_Client::audit( $url, $strategy );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$severity = self::severity_from_score( (int) ( $result['score'] ?? 0 ) );

		CURAI_Audit_Store::upsert(
			'perf',
			0,
			'url',
			$severity,
			array_merge(
				array(
					'url'      => $url,
					'strategy' => $strategy,
				),
				$result
			)
		);

		return array_merge(
			array(
				'url'      => $url,
				'strategy' => $strategy,
			),
			$result
		);
	}

	/**
	 * Map a Lighthouse 0-100 performance score to severity.
	 *
	 * @since 1.0.0
	 * @param int $score Score 0-100.
	 * @return int
	 */
	private static function severity_from_score( int $score ): int {
		if ( $score >= 90 ) {
			return 0;
		}
		if ( $score >= 50 ) {
			return 2;
		}
		return 3;
	}
}
