<?php
/**
 * Activation handler: creates tables, sets defaults.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activation handler: creates custom tables and stores default plugin options.
 *
 * @since 1.0.0
 */
class CURAI_Activator {

	/**
	 * Runs all activation steps: create tables, set defaults, stamp DB version.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		self::stamp_db_version();
	}

	/**
	 * Creates (or updates) the plugin's custom database tables using dbDelta.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		$audit_sql = "CREATE TABLE {$prefix}curai_audit_results (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_type      VARCHAR(40)     NOT NULL,
            object_id       BIGINT UNSIGNED NOT NULL,
            object_type     VARCHAR(20)     NOT NULL,
            severity        TINYINT         NOT NULL,
            data            LONGTEXT        NULL,
            detected_at     DATETIME        NOT NULL,
            resolved_at     DATETIME        NULL,
            PRIMARY KEY  (id),
            KEY audit_type_obj (audit_type, object_id),
            KEY severity (severity),
            KEY detected_at (detected_at)
        ) {$charset_collate};";

		$history_sql = "CREATE TABLE {$prefix}curai_history (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ability         VARCHAR(80)     NOT NULL,
            object_id       BIGINT UNSIGNED NOT NULL,
            user_id         BIGINT UNSIGNED NOT NULL,
            previous_value  LONGTEXT        NULL,
            new_value       LONGTEXT        NULL,
            applied         TINYINT(1)      NOT NULL DEFAULT 0,
            tokens_used     INT             NOT NULL DEFAULT 0,
            model           VARCHAR(80)     NULL,
            created_at      DATETIME        NOT NULL,
            PRIMARY KEY  (id),
            KEY ability_obj (ability, object_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

		$jobs_sql = "CREATE TABLE {$prefix}curai_jobs (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_type        VARCHAR(40)     NOT NULL,
            status          VARCHAR(20)     NOT NULL,
            total_items     INT             NOT NULL DEFAULT 0,
            completed_items INT             NOT NULL DEFAULT 0,
            failed_items    INT             NOT NULL DEFAULT 0,
            args            LONGTEXT        NULL,
            started_at      DATETIME        NULL,
            finished_at     DATETIME        NULL,
            created_at      DATETIME        NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY job_type (job_type)
        ) {$charset_collate};";

		dbDelta( $audit_sql );
		dbDelta( $history_sql );
		dbDelta( $jobs_sql );
	}

	/**
	 * Stores default plugin options when they do not already exist in the database.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function set_default_options(): void {
		if ( false === get_option( 'curai_settings', false ) ) {
			add_option( 'curai_settings', self::default_settings() );
		}
		if ( false === get_option( 'curai_automation_rules', false ) ) {
			add_option( 'curai_automation_rules', self::default_automation_rules() );
		}
		if ( false === get_option( 'curai_usage', false ) ) {
			add_option(
				'curai_usage',
				array(
					'month'    => gmdate( 'Y-m' ),
					'tokens'   => 0,
					'cost_usd' => 0.0,
				)
			);
		}
		if ( false === get_option( 'curai_seo_adapter_override', false ) ) {
			add_option( 'curai_seo_adapter_override', 'auto' );
		}
	}

	/**
	 * Returns the default plugin settings array.
	 *
	 * @since 1.0.0
	 * @return array Default values for the curai_settings option.
	 */
	public static function default_settings(): array {
		return array(
			'model_default'      => 'gpt-4o-mini',
			'budget_cap_usd'     => 0.0,
			'budget_cap_enabled' => false,
			'pagespeed_api_key'  => '',
		);
	}

	/**
	 * Returns the default automation rules array used on first activation.
	 *
	 * @since 1.0.0
	 * @return array Default values for the curai_automation_rules option.
	 */
	public static function default_automation_rules(): array {
		return array(
			'on_post_save'    => array(
				'generate_meta_title'       => array(
					'enabled'        => false,
					'post_types'     => array( 'post', 'page' ),
					'skip_if_exists' => true,
				),
				'generate_meta_description' => array(
					'enabled'        => false,
					'post_types'     => array( 'post', 'page' ),
					'skip_if_exists' => true,
				),
				'check_readability'         => array(
					'enabled'    => false,
					'post_types' => array( 'post' ),
				),
			),
			'on_media_upload' => array(
				'generate_alt_text' => array(
					'enabled'        => false,
					'skip_if_exists' => true,
					'max_size_mb'    => 5,
				),
			),
			'scheduled'       => array(
				'weekly_audit'     => array(
					'enabled' => false,
					'day'     => 'monday',
					'email'   => '',
				),
				'stale_check'      => array(
					'enabled'         => false,
					'interval_months' => 12,
					'notify'          => false,
				),
				'broken_link_scan' => array(
					'enabled'       => false,
					'interval_days' => 7,
				),
			),
		);
	}

	/**
	 * Writes the current DB schema version to the curai_db_version option.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function stamp_db_version(): void {
		update_option( 'curai_db_version', CURAI_DB_VERSION );
	}
}
