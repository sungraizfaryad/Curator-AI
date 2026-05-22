<?php
/**
 * Audit Reports admin view.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

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

$curai_audit_tabs = array(
	''             => __( 'All', 'curator-ai' ),
	'stale'        => __( 'Stale', 'curator-ai' ),
	'readability'  => __( 'Readability', 'curator-ai' ),
	'missing-meta' => __( 'Missing Meta', 'curator-ai' ),
	'thin-content' => __( 'Thin Content', 'curator-ai' ),
	'broken-links' => __( 'Broken Links', 'curator-ai' ),
	'performance'  => __( 'Performance', 'curator-ai' ),
);

$curai_audit_abilities = array(
	'stale'        => 'curator-ai/audit-stale',
	'readability'  => 'curator-ai/audit-readability',
	'missing-meta' => 'curator-ai/audit-missing-meta-alt',
	'thin-content' => 'curator-ai/audit-thin-content',
	'broken-links' => 'curator-ai/audit-broken-links',
	'performance'  => 'curator-ai/audit-perf',
);

$curai_active_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! array_key_exists( $curai_active_type, $curai_audit_tabs ) ) {
	$curai_active_type = '';
}

$curai_results_table = $wpdb->prefix . 'curai_audit_results';

if ( '' === $curai_active_type ) {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$curai_audit_rows = $wpdb->get_results(
		"SELECT id, audit_type, object_id, severity, data, detected_at, resolved_at FROM `{$curai_results_table}` ORDER BY detected_at DESC LIMIT 200",
		ARRAY_A
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
} else {
	$curai_audit_rows = CURAI_Audit_Store::query_by_type( $curai_active_type, 200 );
}

if ( ! is_array( $curai_audit_rows ) ) {
	$curai_audit_rows = array();
}

$curai_current_url = add_query_arg( array( 'page' => 'curator-ai-audit' ), admin_url( 'admin.php' ) );
?>
<div class="wrap curai-wrap">
	<h1><?php esc_html_e( 'Curator AI — Audit Reports', 'curator-ai' ); ?></h1>

	<div class="curai-tabs">
		<?php foreach ( $curai_audit_tabs as $curai_tab_type => $curai_tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'type', $curai_tab_type, $curai_current_url ) ); ?>"
				class="<?php echo ( $curai_active_type === $curai_tab_type ) ? 'current' : ''; ?>">
				<?php echo esc_html( $curai_tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<?php if ( '' !== $curai_active_type && isset( $curai_audit_abilities[ $curai_active_type ] ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1em;">
			<input type="hidden" name="action" value="curai_run_bulk_audit">
			<input type="hidden" name="ability" value="<?php echo esc_attr( $curai_audit_abilities[ $curai_active_type ] ); ?>">
			<?php wp_nonce_field( 'curai_run_bulk_audit' ); ?>
			<button type="submit" class="button button-secondary">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: audit type label */
						__( 'Run %s audit now', 'curator-ai' ),
						$curai_audit_tabs[ $curai_active_type ]
					)
				);
				?>
			</button>
		</form>
	<?php endif; ?>

	<div class="curai-card">
		<?php if ( empty( $curai_audit_rows ) ) : ?>
			<p><?php esc_html_e( 'No audit results yet. Run a bulk audit to populate findings.', 'curator-ai' ); ?></p>
		<?php else : ?>
			<table class="curai-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Detected', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Type', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Object ID', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Severity', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Resolved', 'curator-ai' ); ?></th>
						<th><?php esc_html_e( 'Data preview', 'curator-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $curai_audit_rows as $curai_audit_row ) : ?>
						<?php
						$curai_data_decoded = json_decode( isset( $curai_audit_row['data'] ) ? (string) $curai_audit_row['data'] : '', true );
						$curai_data_preview = is_array( $curai_data_decoded )
							? substr( wp_json_encode( $curai_data_decoded ), 0, 80 )
							: '';
						?>
						<tr>
							<td><?php echo esc_html( (string) $curai_audit_row['detected_at'] ); ?></td>
							<td><?php echo esc_html( (string) $curai_audit_row['audit_type'] ); ?></td>
							<td><?php echo esc_html( (string) $curai_audit_row['object_id'] ); ?></td>
							<td><?php echo esc_html( (string) $curai_audit_row['severity'] ); ?></td>
							<td>
								<?php if ( ! empty( $curai_audit_row['resolved_at'] ) ) : ?>
									<span class="curai-status-ok"><?php esc_html_e( 'Yes', 'curator-ai' ); ?></span>
								<?php else : ?>
									<span class="curai-status-bad"><?php esc_html_e( 'No', 'curator-ai' ); ?></span>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $curai_data_preview ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
