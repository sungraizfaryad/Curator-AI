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
