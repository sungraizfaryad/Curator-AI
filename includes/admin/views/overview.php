<?php
/**
 * Overview admin view.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

$curai_ai_status = CURAI_AI_Client_Detector::get_status();
?>
<div class="wrap curai-wrap">
	<h1><?php esc_html_e( 'Curator AI — Overview', 'curator-ai' ); ?></h1>

	<div class="curai-card">
		<h2><?php esc_html_e( 'WordPress AI Client status', 'curator-ai' ); ?></h2>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Function available:', 'curator-ai' ); ?></strong>
				<?php echo $curai_ai_status['available'] ? esc_html__( 'Yes', 'curator-ai' ) : esc_html__( 'No', 'curator-ai' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'AI plugin active:', 'curator-ai' ); ?></strong>
				<?php echo $curai_ai_status['plugin_active'] ? esc_html__( 'Yes', 'curator-ai' ) : esc_html__( 'No', 'curator-ai' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Provider configured:', 'curator-ai' ); ?></strong>
				<?php echo $curai_ai_status['provider_configured'] ? esc_html__( 'Yes', 'curator-ai' ) : esc_html__( 'No', 'curator-ai' ); ?>
			</li>
		</ul>
	</div>

	<div class="curai-card">
		<h2><?php esc_html_e( 'Phase 1 — Foundation only', 'curator-ai' ); ?></h2>
		<p><?php esc_html_e( 'Tables created. Settings stored. Future phases will add abilities, automation, and the Gutenberg sidebar.', 'curator-ai' ); ?></p>
	</div>
</div>
