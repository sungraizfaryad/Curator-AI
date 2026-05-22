<?php
/**
 * Bulk Operations admin view.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

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

$curai_bulk_abilities = array(
	array(
		'id'          => 'curator-ai/audit-stale',
		'slug'        => 'audit-stale',
		'label'       => __( 'Stale Content Audit', 'curator-ai' ),
		'description' => __( 'Flags posts and pages that have not been updated within the configured threshold. Helps identify content that may need a refresh.', 'curator-ai' ),
	),
	array(
		'id'          => 'curator-ai/audit-readability',
		'slug'        => 'audit-readability',
		'label'       => __( 'Readability Audit', 'curator-ai' ),
		'description' => __( 'Checks Flesch–Kincaid readability scores across published posts and pages. Surfaces content that may be too complex for your target audience.', 'curator-ai' ),
	),
	array(
		'id'          => 'curator-ai/audit-missing-meta-alt',
		'slug'        => 'audit-missing-meta-alt',
		'label'       => __( 'Missing Meta & Alt Text Audit', 'curator-ai' ),
		'description' => __( 'Scans for posts missing SEO meta titles or descriptions, and images missing alt text. Essential for basic on-page SEO.', 'curator-ai' ),
	),
	array(
		'id'          => 'curator-ai/audit-thin-content',
		'slug'        => 'audit-thin-content',
		'label'       => __( 'Thin Content Audit', 'curator-ai' ),
		'description' => __( 'Identifies posts with low word counts that may be considered thin by search engines and hurt rankings.', 'curator-ai' ),
	),
	array(
		'id'          => 'curator-ai/audit-broken-links',
		'slug'        => 'audit-broken-links',
		'label'       => __( 'Broken Link Scan', 'curator-ai' ),
		'description' => __( 'Crawls internal and external links in post content to find 4xx/5xx responses. Broken links harm user experience and crawlability.', 'curator-ai' ),
	),
	array(
		'id'          => 'curator-ai/audit-perf',
		'slug'        => 'audit-perf',
		'label'       => __( 'Performance Audit', 'curator-ai' ),
		'description' => __( 'Checks page weight, image sizes, and other front-end performance signals across the site. Surfaces pages that may score poorly in Core Web Vitals.', 'curator-ai' ),
	),
);
?>
<div class="wrap curai-wrap">
	<h1><?php esc_html_e( 'Curator AI — Bulk Operations', 'curator-ai' ); ?></h1>

	<div class="curai-grid">
		<?php foreach ( $curai_bulk_abilities as $curai_ability ) : ?>
			<div class="curai-card">
				<h2><?php echo esc_html( $curai_ability['label'] ); ?></h2>
				<p><?php echo esc_html( $curai_ability['description'] ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="curai_run_bulk_audit">
					<input type="hidden" name="ability" value="<?php echo esc_attr( $curai_ability['id'] ); ?>">
					<?php wp_nonce_field( 'curai_run_bulk_audit' ); ?>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Run now', 'curator-ai' ); ?></button>
				</form>
				<?php if ( ( 'bulk_ran_' . $curai_ability['slug'] ) === $curai_notice_code ) : ?>
					<p class="curai-status-ok" style="margin-top:0.5em;">
						<?php esc_html_e( 'Completed successfully.', 'curator-ai' ); ?>
					</p>
				<?php elseif ( ( 'bulk_error_' . $curai_ability['slug'] ) === $curai_notice_code ) : ?>
					<p class="curai-status-bad" style="margin-top:0.5em;">
						<?php esc_html_e( 'Error during last run.', 'curator-ai' ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
