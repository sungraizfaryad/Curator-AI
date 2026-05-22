<?php
/**
 * Deactivation handler: clears transients + scheduled actions.
 * Does NOT drop tables or options (preserved for reactivation).
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

class CURAI_Deactivator {

    public static function deactivate(): void {
        self::clear_transients();
        self::clear_scheduled_actions();
    }

    public static function clear_transients(): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Bulk transient cleanup on deactivation.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_curai_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_curai_' ) . '%'
            )
        );
    }

    public static function clear_scheduled_actions(): void {
        // Standard cron events (none registered in Phase 1 — placeholder for later phases).
        $hooks = array(
            'curai_job_run_ability',
            'curai_job_bulk_chunk',
            'curai_job_weekly_audit',
        );
        foreach ( $hooks as $hook ) {
            wp_clear_scheduled_hook( $hook );
        }

        // Action Scheduler hooks (cleaned if AS already active).
        if ( function_exists( 'as_unschedule_all_actions' ) ) {
            foreach ( $hooks as $hook ) {
                as_unschedule_all_actions( $hook, array(), 'curator-ai' );
            }
        }
    }
}
