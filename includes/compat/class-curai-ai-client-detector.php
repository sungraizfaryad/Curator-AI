<?php
/**
 * Detects WordPress 7.0 AI Client availability.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects whether the WordPress AI Client plugin is available and configured.
 *
 * @since 1.0.0
 */
class CURAI_AI_Client_Detector {

	/**
	 * Checks whether the WordPress AI Client API function exists.
	 *
	 * @since 1.0.0
	 * @return bool True if the AI client is available, false otherwise.
	 */
	public static function is_available(): bool {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Returns a status array summarising AI client availability.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type bool $available           Whether the AI client function exists.
	 *     @type bool $plugin_active       Whether the AI plugin is active.
	 *     @type bool $provider_configured Whether a chat-completion provider is configured.
	 * }
	 */
	public static function get_status(): array {
		return array(
			'available'           => self::is_available(),
			'plugin_active'       => self::wp_ai_plugin_active(),
			'provider_configured' => self::has_provider_configured(),
		);
	}

	/**
	 * Checks whether the WordPress AI plugin (ai/ai.php) is active.
	 *
	 * @since 1.0.0
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public static function wp_ai_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'ai/ai.php' );
	}

	/**
	 * Checks whether a chat-completion provider is configured in the AI client.
	 *
	 * @since 1.0.0
	 * @return bool True if a provider is configured, false otherwise.
	 */
	public static function has_provider_configured(): bool {
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
	 * Renders an admin notice when the AI client is missing or unconfigured.
	 *
	 * Only displays on Curator AI admin screens.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_missing_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false === strpos( (string) $screen->id, 'curator-ai-seo-site-care' ) ) {
			return;
		}

		$status = self::get_status();
		if ( $status['available'] && $status['plugin_active'] && $status['provider_configured'] ) {
			return;
		}

		if ( ! $status['plugin_active'] ) {
			$install_url = wp_nonce_url(
				self_admin_url( 'update.php?action=install-plugin&plugin=ai' ),
				'install-plugin_ai'
			);
			$message     = sprintf(
				/* translators: %s: install plugin URL. */
				__( 'Curator AI requires the official WordPress <strong>AI</strong> plugin. <a href="%s">Install it now</a>. Audit features still work; AI generation is disabled.', 'curator-ai-seo-site-care' ),
				esc_url( $install_url )
			);
		} elseif ( ! $status['provider_configured'] ) {
			$settings_url = self_admin_url( 'options-connectors.php' );
			$message      = sprintf(
				/* translators: %s: connectors settings URL. */
				__( 'Curator AI: AI plugin is active but no provider is configured. <a href="%s">Add a provider in Settings → Connectors</a>. Audit features still work; AI generation is disabled.', 'curator-ai-seo-site-care' ),
				esc_url( $settings_url )
			);
		} else {
			$message = __( 'Curator AI: AI client is not available. Audit features still work; AI generation is disabled.', 'curator-ai-seo-site-care' );
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses(
				$message,
				array(
					'a'      => array( 'href' => array() ),
					'strong' => array(),
				)
			)
		);
	}
}
