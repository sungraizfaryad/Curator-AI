<?php
/**
 * Settings admin view.
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

$curai_settings_view = get_option( 'curai_settings', array() );
if ( ! is_array( $curai_settings_view ) ) {
	$curai_settings_view = array();
}
$curai_override       = get_option( 'curai_seo_adapter_override', 'auto' );
$curai_active_adapter = CURAI_SEO_Adapter_Factory::get();
$curai_adapter_label  = $curai_active_adapter->get_label();
?>
<div class="wrap curai-wrap">
	<h1><?php esc_html_e( 'Curator AI — Settings', 'curator-ai-seo-site-care' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="curai_save_settings">
		<?php wp_nonce_field( 'curai_save_settings' ); ?>

		<div class="curai-card">
			<h2><?php esc_html_e( 'General', 'curator-ai-seo-site-care' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="curai_model_default"><?php esc_html_e( 'Default model', 'curator-ai-seo-site-care' ); ?></label>
					</th>
					<td>
						<input type="text" id="curai_model_default" class="regular-text"
							name="curai_settings[model_default]"
							value="<?php echo esc_attr( isset( $curai_settings_view['model_default'] ) ? (string) $curai_settings_view['model_default'] : '' ); ?>">
						<p class="description"><?php esc_html_e( 'AI model identifier passed to the WordPress AI Client. Leave blank to use the provider default.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="curai_budget_cap_enabled"><?php esc_html_e( 'Monthly budget cap', 'curator-ai-seo-site-care' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="curai_budget_cap_enabled"
								name="curai_settings[budget_cap_enabled]" value="1"
								<?php checked( ! empty( $curai_settings_view['budget_cap_enabled'] ) ); ?>>
							<?php esc_html_e( 'Enable monthly budget cap', 'curator-ai-seo-site-care' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="curai_budget_cap_usd"><?php esc_html_e( 'Budget cap (USD)', 'curator-ai-seo-site-care' ); ?></label>
					</th>
					<td>
						<input type="number" id="curai_budget_cap_usd" step="0.01" min="0"
							name="curai_settings[budget_cap_usd]"
							value="<?php echo esc_attr( isset( $curai_settings_view['budget_cap_usd'] ) ? (string) $curai_settings_view['budget_cap_usd'] : '5.00' ); ?>">
						<p class="description"><?php esc_html_e( 'Stop AI generation when monthly spend exceeds this amount.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="curai_pagespeed_api_key"><?php esc_html_e( 'PageSpeed API key', 'curator-ai-seo-site-care' ); ?></label>
					</th>
					<td>
						<input type="password" id="curai_pagespeed_api_key" class="regular-text"
							name="curai_settings[pagespeed_api_key]"
							value="<?php echo esc_attr( isset( $curai_settings_view['pagespeed_api_key'] ) ? (string) $curai_settings_view['pagespeed_api_key'] : '' ); ?>">
						<p class="description"><?php esc_html_e( 'Google PageSpeed Insights API key for performance audits.', 'curator-ai-seo-site-care' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="curai-card">
			<h2><?php esc_html_e( 'SEO Adapter', 'curator-ai-seo-site-care' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="curai_seo_adapter_override"><?php esc_html_e( 'Adapter override', 'curator-ai-seo-site-care' ); ?></label>
					</th>
					<td>
						<select id="curai_seo_adapter_override" name="curai_seo_adapter_override">
							<option value="auto" <?php selected( $curai_override, 'auto' ); ?>><?php esc_html_e( 'Auto-detect', 'curator-ai-seo-site-care' ); ?></option>
							<option value="yoast" <?php selected( $curai_override, 'yoast' ); ?>><?php esc_html_e( 'Yoast SEO', 'curator-ai-seo-site-care' ); ?></option>
							<option value="rank-math" <?php selected( $curai_override, 'rank-math' ); ?>><?php esc_html_e( 'Rank Math', 'curator-ai-seo-site-care' ); ?></option>
							<option value="native" <?php selected( $curai_override, 'native' ); ?>><?php esc_html_e( 'Native (WordPress core)', 'curator-ai-seo-site-care' ); ?></option>
						</select>
						<p class="description">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: active adapter label */
									__( 'Currently active: %s', 'curator-ai-seo-site-care' ),
									$curai_adapter_label
								)
							);
							?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'curator-ai-seo-site-care' ); ?></button>
		</p>
	</form>
</div>
