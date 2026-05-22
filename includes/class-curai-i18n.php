<?php
/**
 * Loads plugin text domain.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

class CURAI_I18n {

    public static function load(): void {
        load_plugin_textdomain(
            'curator-ai',
            false,
            dirname( CURAI_PLUGIN_BASE ) . '/languages/'
        );
    }
}
