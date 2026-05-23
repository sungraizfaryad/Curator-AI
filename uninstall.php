<?php
/**
 * Uninstall cleanup: drops all Curator AI tables, options, post meta, transients, scheduled actions.
 *
 * @package CuratorAI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$curai_uninstall_site = static function () use ( $wpdb ) {
	$tables = array(
		$wpdb->prefix . 'curai_audit_results',
		$wpdb->prefix . 'curai_history',
		$wpdb->prefix . 'curai_jobs',
	);
	foreach ( $tables as $table ) {
		$safe_table = esc_sql( $table );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name from $wpdb->prefix, escaped with esc_sql(); DROP TABLE required for uninstall.
		$wpdb->query( "DROP TABLE IF EXISTS `{$safe_table}`" );
	}

	$options = array(
		'curai_settings',
		'curai_automation_rules',
		'curai_db_version',
		'curai_usage',
		'curai_pagespeed_api_key',
		'curai_seo_adapter_override',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
		delete_site_option( $option );
	}

	// Transients.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk uninstall cleanup, no caching needed.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_curai_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_curai_' ) . '%'
		)
	);

	// Post meta and attachment meta.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk uninstall cleanup, no caching needed.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			$wpdb->esc_like( '_curai_' ) . '%'
		)
	);

	// Scheduled cron events.
	$hooks = array(
		'curai_job_run_ability',
		'curai_job_bulk_chunk',
		'curai_job_weekly_audit',
	);
	foreach ( $hooks as $hook ) {
		wp_clear_scheduled_hook( $hook );
	}
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		foreach ( $hooks as $hook ) {
			as_unschedule_all_actions( $hook, array(), 'curator-ai-seo-site-care' );
		}
	}
};

if ( is_multisite() ) {
	$curai_site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $curai_site_ids as $curai_site_id ) {
		switch_to_blog( (int) $curai_site_id );
		$curai_uninstall_site();
		restore_current_blog();
	}
} else {
	$curai_uninstall_site();
}
