<?php
/**
 * Thin client for the Google PageSpeed Insights v5 API.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs PageSpeed Insights audits and returns normalized Core Web Vitals.
 *
 * Anonymous calls allowed at modest rate. A user-supplied API key in the
 * `curai_pagespeed_api_key` option raises the quota.
 *
 * @since 1.0.0
 */
class CURAI_PageSpeed_Client {

	/**
	 * Endpoint URL.
	 *
	 * @since 1.0.0
	 */
	private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

	/**
	 * Audit a URL and return a flattened metric set.
	 *
	 * @since 1.0.0
	 * @param string $url      Public URL to audit.
	 * @param string $strategy 'mobile' or 'desktop'.
	 * @return array|WP_Error
	 */
	public static function audit( string $url, string $strategy = 'mobile' ) {
		$key      = (string) get_option( 'curai_pagespeed_api_key', '' );
		$endpoint = self::build_url( $url, $strategy, $key );

		$response = wp_safe_remote_get(
			$endpoint,
			array(
				'timeout'    => 30,
				'user-agent' => 'CuratorAI/1.0 (+https://wordpress.org)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'curai_pagespeed_http_error',
				$response->get_error_message()
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( 200 !== $code ) {
			return new WP_Error(
				'curai_pagespeed_bad_status',
				sprintf( 'PageSpeed Insights returned HTTP %d.', $code )
			);
		}

		if ( ! is_array( $json ) || ! isset( $json['lighthouseResult'] ) ) {
			return new WP_Error(
				'curai_pagespeed_invalid_response',
				'PageSpeed Insights returned an unexpected payload.'
			);
		}

		return self::flatten( $json );
	}

	/**
	 * Build the PageSpeed Insights query URL.
	 *
	 * @since 1.0.0
	 * @param string $url      Page URL.
	 * @param string $strategy 'mobile' or 'desktop'.
	 * @param string $api_key  Optional Google API key.
	 * @return string
	 */
	public static function build_url( string $url, string $strategy, string $api_key ): string {
		$args = array(
			'url'      => $url,
			'strategy' => 'desktop' === $strategy ? 'desktop' : 'mobile',
			'category' => 'performance',
		);
		if ( '' !== $api_key ) {
			$args['key'] = $api_key;
		}
		return self::ENDPOINT . '?' . http_build_query( $args );
	}

	/**
	 * Flatten the Lighthouse response into the audit ability output shape.
	 *
	 * @since 1.0.0
	 * @param array $json Decoded API response.
	 * @return array{ lcp: float, cls: float, inp: float, fcp: float, ttfb: float, score: int, opportunities: array }
	 */
	private static function flatten( array $json ): array {
		$lighthouse = $json['lighthouseResult'] ?? array();
		$audits     = $lighthouse['audits'] ?? array();
		$categories = $lighthouse['categories']['performance'] ?? array();

		$opportunities = array();
		foreach ( $audits as $key => $audit ) {
			if ( isset( $audit['details']['type'] ) && 'opportunity' === $audit['details']['type'] ) {
				$opportunities[] = array(
					'id'      => $key,
					'title'   => (string) ( $audit['title'] ?? '' ),
					'savings' => (int) ( $audit['details']['overallSavingsMs'] ?? 0 ),
				);
			}
		}

		return array(
			'lcp'           => (float) ( $audits['largest-contentful-paint']['numericValue'] ?? 0 ) / 1000,
			'cls'           => (float) ( $audits['cumulative-layout-shift']['numericValue'] ?? 0 ),
			'inp'           => (float) ( $audits['interaction-to-next-paint']['numericValue'] ?? 0 ),
			'fcp'           => (float) ( $audits['first-contentful-paint']['numericValue'] ?? 0 ) / 1000,
			'ttfb'          => (float) ( $audits['server-response-time']['numericValue'] ?? 0 ) / 1000,
			'score'         => (int) round( (float) ( $categories['score'] ?? 0 ) * 100 ),
			'opportunities' => $opportunities,
		);
	}
}
