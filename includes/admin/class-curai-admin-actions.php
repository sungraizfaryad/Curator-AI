<?php
/**
 * Handles nonce-protected admin_post_* form submissions.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Processes Curator AI admin form submissions via admin_post_* hooks.
 *
 * Each handler verifies capability, checks the request nonce, sanitizes POST
 * data, persists the changes, and redirects back to the referring page with a
 * curai_notice query-string parameter.
 *
 * @since 1.0.0
 */
final class CURAI_Admin_Actions {

	/**
	 * Register admin_post_* action hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'admin_post_curai_save_automation', array( __CLASS__, 'handle_save_automation' ) );
		add_action( 'admin_post_curai_save_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_curai_run_bulk_audit', array( __CLASS__, 'handle_run_bulk_audit' ) );
	}

	/**
	 * Handle the curai_save_automation form submission.
	 *
	 * Reads curai_automation_rules from $_POST, sanitizes each known key, merges
	 * with stored rules, and persists the result.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function handle_save_automation(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'curator-ai-seo-site-care' ) );
		}

		check_admin_referer( 'curai_save_automation' );

		$existing = get_option( 'curai_automation_rules', array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via check_admin_referer above.
		$raw = isset( $_POST['curai_automation_rules'] ) && is_array( $_POST['curai_automation_rules'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized inside sanitize_automation_rules().
			? wp_unslash( $_POST['curai_automation_rules'] )
			: array();

		$clean = self::sanitize_automation_rules( (array) $raw, $existing );

		update_option( 'curai_automation_rules', $clean );

		$referer  = wp_get_referer();
		$redirect = add_query_arg(
			'curai_notice',
			'automation_saved',
			$referer ? $referer : admin_url( 'admin.php?page=curator-ai-automation' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle the curai_save_settings form submission.
	 *
	 * Reads curai_settings and curai_seo_adapter_override from $_POST, sanitizes,
	 * and persists them.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'curator-ai-seo-site-care' ) );
		}

		check_admin_referer( 'curai_save_settings' );

		// Settings sub-array.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via check_admin_referer above.
		if ( isset( $_POST['curai_settings'] ) && is_array( $_POST['curai_settings'] ) ) {
			$existing = get_option( 'curai_settings', array() );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized inside sanitize_flat_settings().
			$raw_settings = (array) wp_unslash( $_POST['curai_settings'] );
			$clean        = self::sanitize_flat_settings( $raw_settings );
			update_option( 'curai_settings', array_merge( $existing, $clean ) );
		}

		// Adapter override.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via check_admin_referer above.
		if ( isset( $_POST['curai_seo_adapter_override'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
			$override = sanitize_text_field( wp_unslash( (string) $_POST['curai_seo_adapter_override'] ) );
			$valid    = array( 'auto', 'yoast', 'rank-math', 'native' );
			if ( in_array( $override, $valid, true ) ) {
				update_option( 'curai_seo_adapter_override', $override );
			}
		}

		$referer  = wp_get_referer();
		$redirect = add_query_arg(
			'curai_notice',
			'settings_saved',
			$referer ? $referer : admin_url( 'admin.php?page=curator-ai-settings' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle the curai_run_bulk_audit form submission.
	 *
	 * Reads the ability ID from $_POST, validates it against the allowed list,
	 * resolves the ability object, executes it synchronously, and redirects.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function handle_run_bulk_audit(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'curator-ai-seo-site-care' ) );
		}

		check_admin_referer( 'curai_run_bulk_audit' );

		$fallback_url = admin_url( 'admin.php?page=curator-ai-bulk' );

		$referer = wp_get_referer();

		if ( ! function_exists( 'wp_get_ability' ) ) {
			wp_safe_redirect( add_query_arg( 'curai_notice', 'no_abilities_api', $referer ? $referer : $fallback_url ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via check_admin_referer above.
		$ability_id = isset( $_POST['ability'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
			? sanitize_text_field( wp_unslash( (string) $_POST['ability'] ) )
			: '';

		$allowed = array(
			'curator-ai/audit-stale',
			'curator-ai/audit-readability',
			'curator-ai/audit-missing-meta-alt',
			'curator-ai/audit-thin-content',
			'curator-ai/audit-broken-links',
			'curator-ai/audit-perf',
		);

		if ( ! in_array( $ability_id, $allowed, true ) ) {
			wp_safe_redirect( add_query_arg( 'curai_notice', 'invalid_ability', $referer ? $referer : $fallback_url ) );
			exit;
		}

		$ability = wp_get_ability( $ability_id );

		if ( null === $ability ) {
			wp_safe_redirect( add_query_arg( 'curai_notice', 'ability_not_found', $referer ? $referer : $fallback_url ) );
			exit;
		}

		$result = $ability->execute( array() );

		if ( is_wp_error( $result ) ) {
			$notice = 'bulk_error_' . $result->get_error_code();
		} else {
			$notice = 'bulk_ran_' . str_replace( 'curator-ai/', '', $ability_id );
		}

		wp_safe_redirect( add_query_arg( 'curai_notice', $notice, $referer ? $referer : $fallback_url ) );
		exit;
	}

	/**
	 * Sanitize automation rules from raw POST input.
	 *
	 * Merges the incoming raw array into $existing, sanitizing per known key.
	 *
	 * @since 1.0.0
	 * @param array $raw      Raw, unslashed POST array.
	 * @param array $existing Existing stored rules to merge into.
	 * @return array Sanitized merged automation rules.
	 */
	private static function sanitize_automation_rules( array $raw, array $existing ): array {
		$clean = $existing;

		$top_level_keys = array( 'on_post_save', 'on_media_upload', 'scheduled' );

		foreach ( $top_level_keys as $group ) {
			if ( ! isset( $raw[ $group ] ) || ! is_array( $raw[ $group ] ) ) {
				continue;
			}

			if ( ! isset( $clean[ $group ] ) || ! is_array( $clean[ $group ] ) ) {
				$clean[ $group ] = array();
			}

			foreach ( $raw[ $group ] as $rule_key => $rule_data ) {
				$rule_key = sanitize_key( (string) $rule_key );

				if ( ! isset( $clean[ $group ][ $rule_key ] ) || ! is_array( $clean[ $group ][ $rule_key ] ) ) {
					$clean[ $group ][ $rule_key ] = array();
				}

				$rule_data = is_array( $rule_data ) ? $rule_data : array();

				if ( array_key_exists( 'enabled', $rule_data ) ) {
					$clean[ $group ][ $rule_key ]['enabled'] = (bool) $rule_data['enabled'];
				}

				if ( isset( $rule_data['post_types'] ) && is_array( $rule_data['post_types'] ) ) {
					$clean[ $group ][ $rule_key ]['post_types'] = array_values(
						array_filter(
							array_map(
								'sanitize_text_field',
								$rule_data['post_types']
							)
						)
					);
				}

				if ( array_key_exists( 'skip_if_exists', $rule_data ) ) {
					$clean[ $group ][ $rule_key ]['skip_if_exists'] = (bool) $rule_data['skip_if_exists'];
				}

				if ( isset( $rule_data['max_size_mb'] ) ) {
					$clean[ $group ][ $rule_key ]['max_size_mb'] = (int) $rule_data['max_size_mb'];
				}

				if ( isset( $rule_data['interval_months'] ) ) {
					$clean[ $group ][ $rule_key ]['interval_months'] = (int) $rule_data['interval_months'];
				}

				if ( isset( $rule_data['interval_days'] ) ) {
					$clean[ $group ][ $rule_key ]['interval_days'] = (int) $rule_data['interval_days'];
				}

				if ( isset( $rule_data['day'] ) ) {
					$valid_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
					$day        = sanitize_text_field( (string) $rule_data['day'] );
					if ( in_array( $day, $valid_days, true ) ) {
						$clean[ $group ][ $rule_key ]['day'] = $day;
					}
				}

				if ( isset( $rule_data['email'] ) ) {
					$email = sanitize_email( (string) $rule_data['email'] );
					if ( '' !== $email ) {
						$clean[ $group ][ $rule_key ]['email'] = $email;
					}
				}
			}
		}

		return $clean;
	}

	/**
	 * Sanitize a flat (one-level) settings key→value array.
	 *
	 * Strings → sanitize_text_field, integers → (int), booleans → (bool).
	 * Unknown keys are silently kept as sanitized text.
	 *
	 * @since 1.0.0
	 * @param array $raw Raw settings array.
	 * @return array Sanitized settings array.
	 */
	private static function sanitize_flat_settings( array $raw ): array {
		$clean = array();

		foreach ( $raw as $key => $value ) {
			$key = sanitize_key( (string) $key );

			if ( is_bool( $value ) ) {
				$clean[ $key ] = (bool) $value;
			} elseif ( is_int( $value ) ) {
				$clean[ $key ] = (int) $value;
			} elseif ( is_float( $value ) ) {
				$clean[ $key ] = (float) $value;
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $clean;
	}
}
