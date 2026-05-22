<?php
/**
 * Detects WordPress 7.0 AI Client availability.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

class CURAI_AI_Client_Detector {

    public static function is_available(): bool {
        return function_exists( 'wp_ai_client_prompt' );
    }

    public static function get_status(): array {
        return array(
            'available'         => self::is_available(),
            'plugin_active'     => self::wp_ai_plugin_active(),
            'provider_configured' => self::has_provider_configured(),
        );
    }

    public static function wp_ai_plugin_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active( 'ai/ai.php' );
    }

    public static function has_provider_configured(): bool {
        if ( ! self::is_available() ) {
            return false;
        }
        if ( function_exists( 'wp_ai_client_is_supported_chat_completion' ) ) {
            return (bool) wp_ai_client_is_supported_chat_completion();
        }
        return false;
    }

    public static function render_missing_notice(): void {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && false === strpos( (string) $screen->id, 'curator-ai' ) ) {
            return;
        }

        $message = esc_html__( 'Curator AI needs the WordPress AI Client plugin active and a provider configured. Audit features still work; AI generation is disabled.', 'curator-ai' );
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            wp_kses_post( $message )
        );
    }
}
