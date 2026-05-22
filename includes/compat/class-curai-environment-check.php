<?php
/**
 * Environment compatibility gate. Blocks plugin load if WP/PHP versions too low.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

class CURAI_Environment_Check {

    public static function passes(): bool {
        return self::php_ok() && self::wp_ok();
    }

    public static function php_ok(): bool {
        return version_compare( PHP_VERSION, CURAI_MIN_PHP, '>=' );
    }

    public static function wp_ok(): bool {
        global $wp_version;
        return version_compare( $wp_version, CURAI_MIN_WP, '>=' );
    }

    public static function render_failure_notice(): void {
        global $wp_version;

        $messages = array();
        if ( ! self::php_ok() ) {
            $messages[] = sprintf(
                /* translators: 1: required PHP version, 2: current PHP version. */
                esc_html__( 'Curator AI requires PHP %1$s or higher. You are running %2$s.', 'curator-ai' ),
                esc_html( CURAI_MIN_PHP ),
                esc_html( PHP_VERSION )
            );
        }
        if ( ! self::wp_ok() ) {
            $messages[] = sprintf(
                /* translators: 1: required WP version, 2: current WP version. */
                esc_html__( 'Curator AI requires WordPress %1$s or higher. You are running %2$s.', 'curator-ai' ),
                esc_html( CURAI_MIN_WP ),
                esc_html( $wp_version )
            );
        }

        printf(
            '<div class="notice notice-error"><p><strong>%s</strong><br>%s</p></div>',
            esc_html__( 'Curator AI is inactive.', 'curator-ai' ),
            wp_kses_post( implode( '<br>', $messages ) )
        );
    }
}
