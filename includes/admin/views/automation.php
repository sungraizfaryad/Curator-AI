<?php
/**
 * Automation admin view.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

// Notice rendering.
$curai_notice_code = isset( $_GET['curai_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['curai_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $curai_notice_code ) {
	$curai_notice_map = array(
		'settings_saved'    => array( 'success', __( 'Settings saved.', 'curator-ai-seo-site-care' ) ),
		'automation_saved'  => array( 'success', __( 'Automation rules saved.', 'curator-ai-seo-site-care' ) ),
		'no_abilities_api'  => array( 'error', __( 'Abilities API unavailable — install/activate WordPress 7.0 AI Client.', 'curator-ai-seo-site-care' ) ),
		'invalid_ability'   => array( 'error', __( 'Invalid ability requested.', 'curator-ai-seo-site-care' ) ),
		'ability_not_found' => array( 'error', __( 'Ability not registered.', 'curator-ai-seo-site-care' ) ),
	);
	if ( 0 === strpos( $curai_notice_code, 'bulk_ran_' ) ) {
		echo '<div class="curai-notice success">' . esc_html( sprintf( /* translators: %s: ability slug */ __( 'Ran %s.', 'curator-ai-seo-site-care' ), substr( $curai_notice_code, 9 ) ) ) . '</div>';
	} elseif ( 0 === strpos( $curai_notice_code, 'bulk_error_' ) ) {
		echo '<div class="curai-notice error">' . esc_html( sprintf( /* translators: %s: error code */ __( 'Error: %s', 'curator-ai-seo-site-care' ), substr( $curai_notice_code, 11 ) ) ) . '</div>';
	} elseif ( isset( $curai_notice_map[ $curai_notice_code ] ) ) {
		$curai_notice_class = $curai_notice_map[ $curai_notice_code ][0];
		$curai_notice_msg   = $curai_notice_map[ $curai_notice_code ][1];
		echo '<div class="curai-notice ' . esc_attr( $curai_notice_class ) . '">' . esc_html( $curai_notice_msg ) . '</div>';
	}
}

$curai_rules = CURAI_Rule_Engine::get_rules();

// Helper: get a rule sub-array safely.
$curai_get_rule = static function ( string $group, string $key ) use ( $curai_rules ): array {
	if ( isset( $curai_rules[ $group ][ $key ] ) && is_array( $curai_rules[ $group ][ $key ] ) ) {
		return $curai_rules[ $group ][ $key ];
	}
	return array();
};

$curai_ps_gmt   = $curai_get_rule( 'on_post_save', 'generate_meta_title' );
$curai_ps_gmd   = $curai_get_rule( 'on_post_save', 'generate_meta_description' );
$curai_ps_cr    = $curai_get_rule( 'on_post_save', 'check_readability' );
$curai_mu_gat   = $curai_get_rule( 'on_media_upload', 'generate_alt_text' );
$curai_sc_wa    = $curai_get_rule( 'scheduled', 'weekly_audit' );
$curai_sc_stale = $curai_get_rule( 'scheduled', 'stale_check' );
$curai_sc_bls   = $curai_get_rule( 'scheduled', 'broken_link_scan' );

$curai_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
?>
<div class="wrap curai-wrap">
	<h1><?php esc_html_e( 'Curator AI — Automation Rules', 'curator-ai-seo-site-care' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="curai_save_automation">
		<?php wp_nonce_field( 'curai_save_automation' ); ?>

		<div class="curai-card">
			<h2><?php esc_html_e( 'On Post Save', 'curator-ai-seo-site-care' ); ?></h2>

			<h3><?php esc_html_e( 'Generate Meta Title', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_post_save][generate_meta_title][enabled]" value="1"
								<?php checked( ! empty( $curai_ps_gmt['enabled'] ) ); ?>>
							<?php esc_html_e( 'Run when a post is saved', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post types', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="text" class="regular-text"
							name="curai_automation_rules[on_post_save][generate_meta_title][post_types]"
							value="<?php echo esc_attr( isset( $curai_ps_gmt['post_types'] ) ? implode( ', ', (array) $curai_ps_gmt['post_types'] ) : '' ); ?>"
							placeholder="<?php esc_attr_e( 'post, page', 'curator-ai-seo-site-care' ); ?>">
						<p class="description"><?php esc_html_e( 'Comma-separated post type slugs. Leave blank for all.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Skip if exists', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_post_save][generate_meta_title][skip_if_exists]" value="1"
								<?php checked( ! empty( $curai_ps_gmt['skip_if_exists'] ) ); ?>>
							<?php esc_html_e( 'Skip if a meta title already exists', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Generate Meta Description', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_post_save][generate_meta_description][enabled]" value="1"
								<?php checked( ! empty( $curai_ps_gmd['enabled'] ) ); ?>>
							<?php esc_html_e( 'Run when a post is saved', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post types', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="text" class="regular-text"
							name="curai_automation_rules[on_post_save][generate_meta_description][post_types]"
							value="<?php echo esc_attr( isset( $curai_ps_gmd['post_types'] ) ? implode( ', ', (array) $curai_ps_gmd['post_types'] ) : '' ); ?>"
							placeholder="<?php esc_attr_e( 'post, page', 'curator-ai-seo-site-care' ); ?>">
						<p class="description"><?php esc_html_e( 'Comma-separated post type slugs. Leave blank for all.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Skip if exists', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_post_save][generate_meta_description][skip_if_exists]" value="1"
								<?php checked( ! empty( $curai_ps_gmd['skip_if_exists'] ) ); ?>>
							<?php esc_html_e( 'Skip if a meta description already exists', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Check Readability', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_post_save][check_readability][enabled]" value="1"
								<?php checked( ! empty( $curai_ps_cr['enabled'] ) ); ?>>
							<?php esc_html_e( 'Run when a post is saved', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Post types', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="text" class="regular-text"
							name="curai_automation_rules[on_post_save][check_readability][post_types]"
							value="<?php echo esc_attr( isset( $curai_ps_cr['post_types'] ) ? implode( ', ', (array) $curai_ps_cr['post_types'] ) : '' ); ?>"
							placeholder="<?php esc_attr_e( 'post, page', 'curator-ai-seo-site-care' ); ?>">
						<p class="description"><?php esc_html_e( 'Comma-separated post type slugs. Leave blank for all.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="curai-card">
			<h2><?php esc_html_e( 'On Media Upload', 'curator-ai-seo-site-care' ); ?></h2>

			<h3><?php esc_html_e( 'Generate Alt Text', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_media_upload][generate_alt_text][enabled]" value="1"
								<?php checked( ! empty( $curai_mu_gat['enabled'] ) ); ?>>
							<?php esc_html_e( 'Run when media is uploaded', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Skip if exists', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[on_media_upload][generate_alt_text][skip_if_exists]" value="1"
								<?php checked( ! empty( $curai_mu_gat['skip_if_exists'] ) ); ?>>
							<?php esc_html_e( 'Skip if alt text already exists', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Max file size (MB)', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="number" min="0" step="1"
							name="curai_automation_rules[on_media_upload][generate_alt_text][max_size_mb]"
							value="<?php echo esc_attr( isset( $curai_mu_gat['max_size_mb'] ) ? (string) (int) $curai_mu_gat['max_size_mb'] : '5' ); ?>">
						<p class="description"><?php esc_html_e( 'Skip images larger than this size. 0 = no limit.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="curai-card">
			<h2><?php esc_html_e( 'Scheduled', 'curator-ai-seo-site-care' ); ?></h2>

			<h3><?php esc_html_e( 'Weekly Audit', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[scheduled][weekly_audit][enabled]" value="1"
								<?php checked( ! empty( $curai_sc_wa['enabled'] ) ); ?>>
							<?php esc_html_e( 'Run a full audit weekly', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Day of week', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<select name="curai_automation_rules[scheduled][weekly_audit][day]">
							<?php foreach ( $curai_days as $curai_day ) : ?>
								<option value="<?php echo esc_attr( $curai_day ); ?>"
									<?php selected( isset( $curai_sc_wa['day'] ) ? $curai_sc_wa['day'] : 'monday', $curai_day ); ?>>
									<?php echo esc_html( ucfirst( $curai_day ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Report email', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="email" class="regular-text"
							name="curai_automation_rules[scheduled][weekly_audit][email]"
							value="<?php echo esc_attr( isset( $curai_sc_wa['email'] ) ? $curai_sc_wa['email'] : '' ); ?>">
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Stale Content Check', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[scheduled][stale_check][enabled]" value="1"
								<?php checked( ! empty( $curai_sc_stale['enabled'] ) ); ?>>
							<?php esc_html_e( 'Flag posts that have not been updated recently', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Interval (months)', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="number" min="1" step="1"
							name="curai_automation_rules[scheduled][stale_check][interval_months]"
							value="<?php echo esc_attr( isset( $curai_sc_stale['interval_months'] ) ? (string) (int) $curai_sc_stale['interval_months'] : '6' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notify', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[scheduled][stale_check][notify]" value="1"
								<?php checked( ! empty( $curai_sc_stale['notify'] ) ); ?>>
							<?php esc_html_e( 'Send an email when stale content is found', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Broken Link Scan', 'curator-ai-seo-site-care' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="curai_automation_rules[scheduled][broken_link_scan][enabled]" value="1"
								<?php checked( ! empty( $curai_sc_bls['enabled'] ) ); ?>>
							<?php esc_html_e( 'Periodically scan for broken links', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Interval (days)', 'curator-ai-seo-site-care' ); ?></th>
					<td>
						<input type="number" min="1" step="1"
							name="curai_automation_rules[scheduled][broken_link_scan][interval_days]"
							value="<?php echo esc_attr( isset( $curai_sc_bls['interval_days'] ) ? (string) (int) $curai_sc_bls['interval_days'] : '7' ); ?>">
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save automation rules', 'curator-ai-seo-site-care' ); ?></button>
		</p>
	</form>
</div>
