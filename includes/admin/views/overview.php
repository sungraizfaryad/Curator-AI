<?php
/**
 * Overview admin view.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$curai_ai_status      = CURAI_AI_Client_Detector::get_status();
$curai_active_adapter = CURAI_SEO_Adapter_Factory::get();

// Notice rendering.
$curai_notice_code = isset( $_GET['curai_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['curai_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $curai_notice_code ) {
	$curai_notice_map = array(
		'settings_saved'    => array( 'success', __( 'Settings saved.', 'curator-ai' ) ),
		'automation_saved'  => array( 'success', __( 'Automation rules saved.', 'curator-ai' ) ),
		'no_abilities_api'  => array( 'error', __( 'Abilities API unavailable — install/activate WordPress 7.0 AI Client.', 'curator-ai' ) ),
		'invalid_ability'   => array( 'error', __( 'Invalid ability requested.', 'curator-ai' ) ),
		'ability_not_found' => array( 'error', __( 'Ability not registered.', 'curator-ai' ) ),
	);
	if ( 0 === strpos( $curai_notice_code, 'bulk_ran_' ) ) {
		echo '<div class="curai-notice success">' . esc_html( sprintf( /* translators: %s: ability slug */ __( 'Ran %s.', 'curator-ai' ), substr( $curai_notice_code, 9 ) ) ) . '</div>';
	} elseif ( 0 === strpos( $curai_notice_code, 'bulk_error_' ) ) {
		echo '<div class="curai-notice error">' . esc_html( sprintf( /* translators: %s: error code */ __( 'Error: %s', 'curator-ai' ), substr( $curai_notice_code, 11 ) ) ) . '</div>';
	} elseif ( isset( $curai_notice_map[ $curai_notice_code ] ) ) {
		$curai_notice_class = $curai_notice_map[ $curai_notice_code ][0];
		$curai_notice_msg   = $curai_notice_map[ $curai_notice_code ][1];
		echo '<div class="curai-notice ' . esc_attr( $curai_notice_class ) . '">' . esc_html( $curai_notice_msg ) . '</div>';
	}
}

// Stats queries.
$curai_results_table = $wpdb->prefix . 'curai_audit_results';
$curai_jobs_table    = $wpdb->prefix . 'curai_jobs';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$curai_total_findings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$curai_results_table}`" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$curai_pending_jobs = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$curai_jobs_table}` WHERE status = 'pending'" );

$curai_usage_data   = get_option( 'curai_usage', array() );
$curai_usage_tokens = isset( $curai_usage_data['monthly_tokens'] ) ? (int) $curai_usage_data['monthly_tokens'] : 0;
$curai_usage_cost   = isset( $curai_usage_data['monthly_cost_usd'] ) ? number_format( (float) $curai_usage_data['monthly_cost_usd'], 4 ) : '0.0000';

// Recent findings.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$curai_recent_findings = $wpdb->get_results(
	"SELECT id, detected_at, audit_type, object_id, severity FROM `{$curai_results_table}` ORDER BY detected_at DESC LIMIT 10",
	ARRAY_A
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
if ( ! is_array( $curai_recent_findings ) ) {
	$curai_recent_findings = array();
}
?>
<div class="wrap curai-wrap">
	<h1><?php esc_html_e( 'Curator AI — Overview', 'curator-ai' ); ?></h1>

	<div class="curai-card">
		<h2><?php esc_html_e( 'System Status', 'curator-ai' ); ?></h2>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Function available:', 'curator-ai' ); ?></strong>
				<span class="<?php echo $curai_ai_status['available'] ? 'curai-status-ok' : 'curai-status-bad'; ?>">
					<?php echo $curai_ai_status['available'] ? esc_html__( 'Yes', 'curator-ai' ) : esc_html__( 'No', 'curator-ai' ); ?>
				</span>
			</li>
			<li>
				<strong><?php esc_html_e( 'AI plugin active:', 'curator-ai' ); ?></strong>
				<span class="<?php echo $curai_ai_status['plugin_active'] ? 'curai-status-ok' : 'curai-status-bad'; ?>">
					<?php echo $curai_ai_status['plugin_active'] ? esc_html__( 'Yes', 'curator-ai' ) : esc_html__( 'No', 'curator-ai' ); ?>
				</span>
			</li>
			<li>
				<strong><?php esc_html_e( 'Provider configured:', 'curator-ai' ); ?></strong>
				<span class="<?php echo $curai_ai_status['provider_configured'] ? 'curai-status-ok' : 'curai-status-bad'; ?>">
					<?php echo $curai_ai_status['provider_configured'] ? esc_html__( 'Yes', 'curator-ai' ) : esc_html__( 'No', 'curator-ai' ); ?>
				</span>
			</li>
			<li>
				<strong><?php esc_html_e( 'Active SEO adapter:', 'curator-ai' ); ?></strong>
				<?php echo esc_html( $curai_active_adapter->get_slug() ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Plugin version:', 'curator-ai' ); ?></strong>
				<?php echo esc_html( CURAI_VERSION ); ?>
			</li>
		</ul>
	</div>

	<div class="curai-card">
		<h2><?php esc_html_e( 'Statistics', 'curator-ai' ); ?></h2>
		<div class="curai-grid">
			<div class="curai-stat">
				<strong><?php echo esc_html( (string) $curai_total_findings ); ?></strong>
				<span><?php esc_html_e( 'Total audit findings', 'curator-ai' ); ?></span>
			</div>
			<div class="curai-stat">
				<strong><?php echo esc_html( (string) $curai_pending_jobs ); ?></strong>
				<span><?php esc_html_e( 'Pending jobs', 'curator-ai' ); ?></span>
			</div>
			<div class="curai-stat">
				<strong><?php echo esc_html( (string) $curai_usage_tokens ); ?></strong>
				<span><?php esc_html_e( 'Monthly tokens', 'curator-ai' ); ?></span>
			</div>
			<div class="curai-stat">
				<strong>$<?php echo esc_html( $curai_usage_cost ); ?></strong>
				<span><?php esc_html_e( 'Monthly cost (USD)', 'curator-ai' ); ?></span>
			</div>
		</div>
	</div>

	<div class="curai-card">
		<h2><?php esc_html_e( 'Recent Audit Findings', 'curator-ai' ); ?></h2>
		<?php if ( empty( $curai_recent_findings ) ) : ?>
			<p><?php esc_html_e( 'No audit results yet. Run a bulk audit to populate findings.', 'curator-ai' ); ?></p>
		<?php else : ?>
			<table class="curai-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Detected', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Type', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Object ID', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Severity', 'curator-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $curai_recent_findings as $curai_row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $curai_row['detected_at'] ); ?></td>
							<td><?php echo esc_html( (string) $curai_row['audit_type'] ); ?></td>
							<td><?php echo esc_html( (string) $curai_row['object_id'] ); ?></td>
							<td><?php echo esc_html( (string) $curai_row['severity'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
