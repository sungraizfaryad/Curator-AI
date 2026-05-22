<?php
/**
 * Wraps WordPress 7.0 AI Client for Curator AI ability calls.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bridge between Curator AI abilities and the WP 7.0 AI Client fluent builder.
 *
 * Generation returns string on success, WP_Error on any failure. Never throws.
 *
 * @since 1.0.0
 */
class CURAI_AI_Bridge {

	/**
	 * Whether the underlying WP AI Client function is loadable.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Whether the AI Client has a provider configured for text generation.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function supports_text_generation(): bool {
		if ( ! self::is_available() ) {
			return false;
		}
		try {
			return (bool) wp_ai_client_prompt()->is_supported_for_text_generation();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Generate text via the configured AI provider.
	 *
	 * @since 1.0.0
	 * @param string $user_prompt        User-facing prompt text.
	 * @param string $system_instruction System role instruction.
	 * @param array  $options            Optional. Keys: max_tokens (int, default 256),
	 *                                   temperature (float, default 0.7).
	 * @return string|WP_Error Generated text or WP_Error with a `curai_ai_*` code.
	 */
	public static function generate_text( string $user_prompt, string $system_instruction = '', array $options = array() ) {
		if ( ! self::is_available() ) {
			return new WP_Error(
				'curai_ai_unavailable',
				__( 'WordPress AI Client is not available in this WordPress version.', 'curator-ai' )
			);
		}

		$max_tokens  = isset( $options['max_tokens'] ) ? (int) $options['max_tokens'] : 256;
		$temperature = isset( $options['temperature'] ) ? (float) $options['temperature'] : 0.7;

		try {
			$builder = wp_ai_client_prompt( $user_prompt );
			if ( '' !== $system_instruction ) {
				$builder = $builder->using_system_instruction( $system_instruction );
			}
			$result = $builder
				->using_max_tokens( $max_tokens )
				->using_temperature( $temperature )
				->generate_text();
		} catch ( \Throwable $e ) {
			return new WP_Error( 'curai_ai_exception', $e->getMessage() );
		}

		if ( is_wp_error( $result ) ) {
			return self::wrap_error( $result );
		}

		if ( ! is_string( $result ) ) {
			return new WP_Error(
				'curai_ai_unexpected_response',
				__( 'AI Client returned an unexpected response type.', 'curator-ai' )
			);
		}

		return trim( $result );
	}

	/**
	 * Normalize a core WP_Error into a Curator AI-coded WP_Error.
	 *
	 * @since 1.0.0
	 * @param WP_Error $error Original error from WP AI Client.
	 * @return WP_Error
	 */
	private static function wrap_error( WP_Error $error ): WP_Error {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();

		return new WP_Error( 'curai_ai_' . $code, $message );
	}
}
