<?php
/**
 * Settings admin view (stub).
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'curai_settings', array() );
$override = get_option( 'curai_seo_adapter_override', 'auto' );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Curator AI — Settings', 'curator-ai' ); ?></h1>
    <p><?php esc_html_e( 'Full settings form arrives in Phase 7. Stored values below for verification:', 'curator-ai' ); ?></p>
    <pre><?php echo esc_html( wp_json_encode( array( 'curai_settings' => $settings, 'curai_seo_adapter_override' => $override ), JSON_PRETTY_PRINT ) ); ?></pre>
</div>
