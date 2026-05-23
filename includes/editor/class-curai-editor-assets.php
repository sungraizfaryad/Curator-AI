<?php
/**
 * Enqueues the Block Editor sidebar bundle.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block Editor sidebar asset enqueuer.
 *
 * @since 1.0.0
 */
final class CURAI_Editor_Assets {

	/**
	 * Registers WordPress hooks for the block editor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueues the sidebar script, styles, and script translations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue(): void {
		wp_enqueue_script(
			'curai-sidebar',
			CURAI_PLUGIN_URL . 'assets/editor/sidebar.js',
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-editor',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-api-fetch',
				'wp-i18n',
			),
			CURAI_VERSION,
			true
		);

		wp_enqueue_style(
			'curai-sidebar',
			CURAI_PLUGIN_URL . 'assets/editor/sidebar.css',
			array(),
			CURAI_VERSION
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'curai-sidebar', 'curator-ai-seo-site-care' );
		}
	}
}
