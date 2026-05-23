<?php
/**
 * Plugin Name:       Curator AI — SEO & Site Care
 * Description:       AI-powered site care: SEO meta + alt text generation, content freshness, site audits. Uses WordPress 7.0 AI Client + Abilities API.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Requires Plugins:  ai
 * Author:            Sungraiz
 * Author URI:        https://sungraizfaryad.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       curator-ai-seo-site-care
 * Domain Path:       /languages
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

define( 'CURAI_VERSION', '1.0.0' );
define( 'CURAI_PLUGIN_FILE', __FILE__ );
define( 'CURAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CURAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CURAI_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'CURAI_DB_VERSION', '1.0.0' );
define( 'CURAI_MIN_PHP', '8.1' );
define( 'CURAI_MIN_WP', '7.0' );

require_once CURAI_PLUGIN_DIR . 'includes/compat/class-curai-environment-check.php';

if ( ! CURAI_Environment_Check::passes() ) {
	add_action( 'admin_notices', array( 'CURAI_Environment_Check', 'render_failure_notice' ) );
	return;
}

require_once CURAI_PLUGIN_DIR . 'includes/class-curai-plugin.php';

register_activation_hook( __FILE__, array( 'CURAI_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CURAI_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( CURAI_Plugin::instance(), 'init' ), 5 );
