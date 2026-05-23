<?php
/**
 * Environment compatibility gate. Blocks plugin load if WP/PHP versions too low.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Environment compatibility gate — blocks plugin load if PHP or WordPress versions are too low.
 *
 * @since 1.0.0
 */
class CURAI_Environment_Check {

	/**
	 * Returns true only when both the PHP and WordPress version requirements are met.
	 *
	 * @since 1.0.0
	 * @return bool True if all requirements pass, false otherwise.
	 */
	public static function passes(): bool {
		return self::php_ok() && self::wp_ok();
	}

	/**
	 * Checks whether the current PHP version meets the minimum requirement.
	 *
	 * @since 1.0.0
	 * @return bool True if the PHP version is sufficient, false otherwise.
	 */
	public static function php_ok(): bool {
		return version_compare( PHP_VERSION, CURAI_MIN_PHP, '>=' );
	}

	/**
	 * Checks whether the current WordPress version meets the minimum requirement.
	 *
	 * @since 1.0.0
	 * @return bool True if the WordPress version is sufficient, false otherwise.
	 */
	public static function wp_ok(): bool {
		global $wp_version;
		return version_compare( $wp_version, CURAI_MIN_WP, '>=' );
	}

	/**
	 * Renders an admin error notice listing unmet PHP/WordPress version requirements.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_failure_notice(): void {
		global $wp_version;

		$messages = array();
		if ( ! self::php_ok() ) {
			$messages[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version. */
				esc_html__( 'Curator AI requires PHP %1$s or higher. You are running %2$s.', 'curator-ai-seo-site-care' ),
				esc_html( CURAI_MIN_PHP ),
				esc_html( PHP_VERSION )
			);
		}
		if ( ! self::wp_ok() ) {
			$messages[] = sprintf(
				/* translators: 1: required WP version, 2: current WP version. */
				esc_html__( 'Curator AI requires WordPress %1$s or higher. You are running %2$s.', 'curator-ai-seo-site-care' ),
				esc_html( CURAI_MIN_WP ),
				esc_html( $wp_version )
			);
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong><br>%s</p></div>',
			esc_html__( 'Curator AI is inactive.', 'curator-ai-seo-site-care' ),
			wp_kses_post( implode( '<br>', $messages ) )
		);
	}
}
