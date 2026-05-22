<?php
/**
 * Extracts URLs from HTML and checks their reachability.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * HEAD-checks links against a remote host using the WP HTTP API.
 *
 * @since 1.0.0
 */
class CURAI_Link_Checker {

	/**
	 * Pull absolute (http/https) hrefs out of an HTML blob.
	 *
	 * @since 1.0.0
	 * @param string $html Source HTML.
	 * @return array<int, string> List of unique URLs.
	 */
	public static function extract_urls( string $html ): array {
		preg_match_all( '/href=["\'](https?:\/\/[^"\'\s>]+)["\']/i', $html, $matches );
		$urls = isset( $matches[1] ) ? array_values( array_unique( $matches[1] ) ) : array();
		return $urls;
	}

	/**
	 * HEAD-check a single URL and return a normalized result.
	 *
	 * @since 1.0.0
	 * @param string $url Absolute URL.
	 * @return array{ url: string, status: int, broken: bool, message: string }
	 */
	public static function check_url( string $url ): array {
		$response = wp_safe_remote_head(
			$url,
			array(
				'timeout'     => 6,
				'redirection' => 3,
				'user-agent'  => 'CuratorAI/1.0 (+https://wordpress.org)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'url'     => $url,
				'status'  => 0,
				'broken'  => true,
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return array(
			'url'     => $url,
			'status'  => $code,
			'broken'  => $code >= 400 || $code <= 0,
			'message' => '',
		);
	}
}
