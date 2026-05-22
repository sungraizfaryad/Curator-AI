<?php
/**
 * PHPUnit bootstrap — uses Brain Monkey to mock WordPress functions.
 *
 * @package CuratorAI
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'CURAI_VERSION' ) ) {
    define( 'CURAI_VERSION', '1.0.0' );
}
if ( ! defined( 'CURAI_PLUGIN_DIR' ) ) {
    define( 'CURAI_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'CURAI_PLUGIN_URL' ) ) {
    define( 'CURAI_PLUGIN_URL', 'https://example.test/wp-content/plugins/curator-ai/' );
}
if ( ! defined( 'CURAI_PLUGIN_BASE' ) ) {
    define( 'CURAI_PLUGIN_BASE', 'curator-ai/curator-ai.php' );
}
if ( ! defined( 'CURAI_PLUGIN_FILE' ) ) {
    define( 'CURAI_PLUGIN_FILE', dirname( __DIR__ ) . '/curator-ai.php' );
}
if ( ! defined( 'CURAI_DB_VERSION' ) ) {
    define( 'CURAI_DB_VERSION', '1.0.0' );
}
if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'CURAI_MIN_PHP' ) ) {
    define( 'CURAI_MIN_PHP', '8.1' );
}
if ( ! defined( 'CURAI_MIN_WP' ) ) {
    define( 'CURAI_MIN_WP', '7.0' );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 86400 );
}
