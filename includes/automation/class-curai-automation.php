<?php
/**
 * WordPress action listeners that dispatch ability runs based on automation rules.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/automation/class-curai-rule-engine.php';
require_once CURAI_PLUGIN_DIR . 'includes/automation/interface-curai-scheduler.php';
require_once CURAI_PLUGIN_DIR . 'includes/automation/class-curai-wp-cron-scheduler.php';
require_once CURAI_PLUGIN_DIR . 'includes/automation/class-curai-job-runner.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-seo-adapter-factory.php';

/**
 * Listens to WordPress action hooks and dispatches ability runs via automation rules.
 *
 * @since 1.0.0
 */
final class CURAI_Automation {

	/**
	 * Register WordPress action listeners and boot the job runner.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 3 );
		add_action( 'add_attachment', array( __CLASS__, 'on_add_attachment' ), 20, 1 );
		CURAI_Job_Runner::boot();
	}

	/**
	 * Handle post save events and dispatch configured ability runs.
	 *
	 * $update is part of the save_post hook signature; suppressed unused-param warning below.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
	public static function on_save_post( int $post_id, WP_Post $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( 'trash' === $post->post_status ) {
			return;
		}

		$scheduler = self::get_scheduler();
		$adapter   = CURAI_SEO_Adapter_Factory::get();
		$post_type = $post->post_type;

		if ( CURAI_Rule_Engine::should_fire_post_save( 'generate_meta_title', $post_id, $post_type ) ) {
			$rule           = CURAI_Rule_Engine::get_rule( 'on_post_save.generate_meta_title' );
			$skip_if_exists = ! empty( $rule['skip_if_exists'] );
			$existing       = $adapter->read_meta_title( $post_id );
			if ( ! $skip_if_exists || '' === $existing ) {
				$scheduler->dispatch_ability( 'curator-ai/generate-meta-title', array( 'post_id' => $post_id ) );
			}
		}

		if ( CURAI_Rule_Engine::should_fire_post_save( 'generate_meta_description', $post_id, $post_type ) ) {
			$rule           = CURAI_Rule_Engine::get_rule( 'on_post_save.generate_meta_description' );
			$skip_if_exists = ! empty( $rule['skip_if_exists'] );
			$existing       = $adapter->read_meta_description( $post_id );
			if ( ! $skip_if_exists || '' === $existing ) {
				$scheduler->dispatch_ability( 'curator-ai/generate-meta-description', array( 'post_id' => $post_id ) );
			}
		}

		if ( CURAI_Rule_Engine::should_fire_post_save( 'check_readability', $post_id, $post_type ) ) {
			$scheduler->dispatch_ability( 'curator-ai/audit-readability', array( 'post_id' => $post_id ) );
		}
	}

	/**
	 * Handle attachment upload events and dispatch alt-text generation if configured.
	 *
	 * @since 1.0.0
	 * @param int $attachment_id Attachment post ID.
	 * @return void
	 */
	public static function on_add_attachment( int $attachment_id ): void {
		if ( ! CURAI_Rule_Engine::should_fire_media_upload( $attachment_id ) ) {
			return;
		}

		$rule           = CURAI_Rule_Engine::get_rule( 'on_media_upload.generate_alt_text' );
		$skip_if_exists = ! empty( $rule['skip_if_exists'] );
		$existing       = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		if ( $skip_if_exists && '' !== $existing ) {
			return;
		}

		self::get_scheduler()->dispatch_ability( 'curator-ai/generate-alt-text', array( 'attachment_id' => $attachment_id ) );
	}

	/**
	 * Return the scheduler instance, allowing override via filter.
	 *
	 * @since 1.0.0
	 * @return CURAI_Scheduler_Interface
	 */
	private static function get_scheduler(): CURAI_Scheduler_Interface {
		$scheduler = apply_filters( 'curai_scheduler', new CURAI_WP_Cron_Scheduler() );
		return $scheduler instanceof CURAI_Scheduler_Interface ? $scheduler : new CURAI_WP_Cron_Scheduler();
	}
}
