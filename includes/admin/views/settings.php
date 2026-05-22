<?php
/**
 * Settings admin view (stub).
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

$curai_settings_view = get_option( 'curai_settings', array() );
$curai_override      = get_option( 'curai_seo_adapter_override', 'auto' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Curator AI — Settings', 'curator-ai' ); ?></h1>
	<p><?php esc_html_e( 'Full settings form arrives in Phase 7. Stored values below for verification:', 'curator-ai' ); ?></p>
	<pre>
	<?php
	echo esc_html(
		wp_json_encode(
			array(
				'curai_settings'             => $curai_settings_view,
				'curai_seo_adapter_override' => $curai_override,
			),
			JSON_PRETTY_PRINT
		)
	);
	?>
	</pre>
</div>
