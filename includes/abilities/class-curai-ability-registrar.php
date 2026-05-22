<?php
/**
 * Registers all Curator AI abilities with the Abilities API.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/abilities/trait-curai-ability-helpers.php';
require_once CURAI_PLUGIN_DIR . 'includes/ai/class-curai-ai-bridge.php';
require_once CURAI_PLUGIN_DIR . 'includes/ai/class-curai-prompt-builder.php';
require_once CURAI_PLUGIN_DIR . 'includes/ai/class-curai-cost-guard.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-meta-title.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-meta-description.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-alt-text.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-refresh-content.php';

/**
 * Hooks the Abilities API and registers every Curator AI category + ability.
 *
 * @since 1.0.0
 */
class CURAI_Ability_Registrar {

	/**
	 * Register the Abilities API hooks (categories first, then abilities).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_all' ) );
	}

	/**
	 * Register Curator AI ability categories.
	 *
	 * Categories must exist before any ability referencing them can register.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'seo',
			array(
				'label'       => __( 'SEO', 'curator-ai' ),
				'description' => __( 'SEO meta and alt text generation abilities.', 'curator-ai' ),
			)
		);

		wp_register_ability_category(
			'freshness',
			array(
				'label'       => __( 'Content Freshness', 'curator-ai' ),
				'description' => __( 'Detect stale content and refresh dates or rewrite outdated content.', 'curator-ai' ),
			)
		);

		wp_register_ability_category(
			'audit',
			array(
				'label'       => __( 'Site Audit', 'curator-ai' ),
				'description' => __( 'Readability, missing meta, broken links, thin content and performance audits.', 'curator-ai' ),
			)
		);
	}

	/**
	 * Register all Phase 2 abilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_all(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_meta_title();
		self::register_meta_description();
		self::register_alt_text();
		self::register_refresh_content();
	}

	/**
	 * Register `curator-ai/generate-meta-description`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_meta_description(): void {
		wp_register_ability(
			'curator-ai/generate-meta-description',
			array(
				'label'               => __( 'Generate Meta Description', 'curator-ai' ),
				'description'         => __( 'Generates an SEO-optimized meta description for a post via the configured AI provider.', 'curator-ai' ),
				'category'            => 'seo',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'focus_keyword' => array(
							'type'    => 'string',
							'default' => '',
						),
						'max_length'    => array(
							'type'    => 'integer',
							'default' => 155,
							'minimum' => 120,
							'maximum' => 160,
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'description' => array( 'type' => 'string' ),
						'tokens_used' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Meta_Description', 'execute' ),
				'permission_callback' => static function ( $input ) {
					$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/generate-alt-text`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_alt_text(): void {
		wp_register_ability(
			'curator-ai/generate-alt-text',
			array(
				'label'               => __( 'Generate Alt Text', 'curator-ai' ),
				'description'         => __( 'Generates accessible alt text for an image attachment via a vision-capable AI provider.', 'curator-ai' ),
				'category'            => 'seo',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'attachment_id'   => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'context_post_id' => array(
							'type'    => 'integer',
							'minimum' => 0,
							'default' => 0,
						),
					),
					'required'             => array( 'attachment_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'alt_text'    => array( 'type' => 'string' ),
						'tokens_used' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Alt_Text', 'execute' ),
				'permission_callback' => static function ( $input ) {
					$attachment_id = isset( $input['attachment_id'] ) ? (int) $input['attachment_id'] : 0;
					return $attachment_id > 0
						&& current_user_can( 'upload_files' )
						&& current_user_can( 'edit_post', $attachment_id );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/refresh-content`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_refresh_content(): void {
		wp_register_ability(
			'curator-ai/refresh-content',
			array(
				'label'               => __( 'Refresh Content', 'curator-ai' ),
				'description'         => __( 'Refreshes a post (date_only, context, or full rewrite) via the configured AI provider.', 'curator-ai' ),
				'category'            => 'freshness',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						'mode'    => array(
							'type'    => 'string',
							'enum'    => array( 'date_only', 'context', 'rewrite' ),
							'default' => 'date_only',
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'updated_content' => array( 'type' => 'string' ),
						'diff_summary'    => array( 'type' => 'string' ),
						'mode'            => array( 'type' => 'string' ),
						'tokens_used'     => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Refresh_Content', 'execute' ),
				'permission_callback' => static function ( $input ) {
					$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => true,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/generate-meta-title`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_meta_title(): void {
		wp_register_ability(
			'curator-ai/generate-meta-title',
			array(
				'label'               => __( 'Generate Meta Title', 'curator-ai' ),
				'description'         => __( 'Generates an SEO-optimized meta title for a post via the configured AI provider.', 'curator-ai' ),
				'category'            => 'seo',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array(
							'type'        => 'integer',
							'description' => 'Post ID.',
							'minimum'     => 1,
						),
						'focus_keyword' => array(
							'type'        => 'string',
							'description' => 'Optional focus keyword to include.',
							'default'     => '',
						),
						'max_length'    => array(
							'type'        => 'integer',
							'description' => 'Maximum characters for the generated title.',
							'default'     => 60,
							'minimum'     => 30,
							'maximum'     => 70,
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'title'       => array( 'type' => 'string' ),
						'tokens_used' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Meta_Title', 'execute' ),
				'permission_callback' => static function ( $input ) {
					$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}
}
