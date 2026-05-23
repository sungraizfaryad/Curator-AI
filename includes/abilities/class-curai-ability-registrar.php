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
require_once CURAI_PLUGIN_DIR . 'includes/audit/class-curai-audit-store.php';
require_once CURAI_PLUGIN_DIR . 'includes/audit/class-curai-readability-calc.php';
require_once CURAI_PLUGIN_DIR . 'includes/audit/class-curai-link-checker.php';
require_once CURAI_PLUGIN_DIR . 'includes/audit/class-curai-pagespeed-client.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/interface-curai-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-native-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-yoast-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-rank-math-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-seo-adapter-factory.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-meta-title.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-meta-description.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-alt-text.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-refresh-content.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-audit-stale.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-audit-readability.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-audit-missing-meta-alt.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-audit-thin-content.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-audit-broken-links.php';
require_once CURAI_PLUGIN_DIR . 'includes/abilities/class-curai-ability-audit-perf.php';

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
				'label'       => __( 'SEO', 'curator-ai-seo-site-care' ),
				'description' => __( 'SEO meta and alt text generation abilities.', 'curator-ai-seo-site-care' ),
			)
		);

		wp_register_ability_category(
			'freshness',
			array(
				'label'       => __( 'Content Freshness', 'curator-ai-seo-site-care' ),
				'description' => __( 'Detect stale content and refresh dates or rewrite outdated content.', 'curator-ai-seo-site-care' ),
			)
		);

		wp_register_ability_category(
			'audit',
			array(
				'label'       => __( 'Site Audit', 'curator-ai-seo-site-care' ),
				'description' => __( 'Readability, missing meta, broken links, thin content and performance audits.', 'curator-ai-seo-site-care' ),
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
		self::register_audit_stale();
		self::register_audit_readability();
		self::register_audit_missing_meta_alt();
		self::register_audit_thin_content();
		self::register_audit_broken_links();
		self::register_audit_perf();
	}

	/**
	 * Register `curator-ai/audit-stale`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_audit_stale(): void {
		wp_register_ability(
			'curator-ai/audit-stale',
			array(
				'label'               => __( 'Audit Stale Posts', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Finds published posts older than N months since last modification.', 'curator-ai-seo-site-care' ),
				'category'            => 'audit',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'months'     => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 12,
						),
						'post_types' => array(
							'type'    => 'array',
							'items'   => array( 'type' => 'string' ),
							'default' => array( 'post' ),
						),
						'limit'      => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 1000,
							'default' => 200,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'posts'  => array( 'type' => 'array' ),
						'count'  => array( 'type' => 'integer' ),
						'months' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Audit_Stale', 'execute' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => true,
						'readonly'    => true,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/audit-readability`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_audit_readability(): void {
		wp_register_ability(
			'curator-ai/audit-readability',
			array(
				'label'               => __( 'Audit Readability', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Computes Flesch-Kincaid readability metrics for a single post.', 'curator-ai-seo-site-care' ),
				'category'            => 'audit',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'flesch_kincaid'   => array( 'type' => 'number' ),
						'grade'            => array( 'type' => 'number' ),
						'sentences'        => array( 'type' => 'integer' ),
						'words'            => array( 'type' => 'integer' ),
						'syllables'        => array( 'type' => 'integer' ),
						'avg_sentence_len' => array( 'type' => 'number' ),
						'passive_ratio'    => array( 'type' => 'number' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Audit_Readability', 'execute' ),
				'permission_callback' => static function ( $input ) {
					$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => true,
						'readonly'    => true,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/audit-missing-meta-alt`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_audit_missing_meta_alt(): void {
		wp_register_ability(
			'curator-ai/audit-missing-meta-alt',
			array(
				'label'               => __( 'Audit Missing Meta & Alt', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Finds posts missing meta title/description and images missing alt text.', 'curator-ai-seo-site-care' ),
				'category'            => 'audit',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_types' => array(
							'type'    => 'array',
							'items'   => array( 'type' => 'string' ),
							'default' => array( 'post', 'page' ),
						),
						'limit'      => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 5000,
							'default' => 500,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'missing_meta_title' => array( 'type' => 'array' ),
						'missing_meta_desc'  => array( 'type' => 'array' ),
						'missing_alt'        => array( 'type' => 'array' ),
						'counts'             => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Audit_Missing_Meta_Alt', 'execute' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => true,
						'readonly'    => true,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/audit-thin-content`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_audit_thin_content(): void {
		wp_register_ability(
			'curator-ai/audit-thin-content',
			array(
				'label'               => __( 'Audit Thin Content', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Flags posts with fewer than N words of plain-text content.', 'curator-ai-seo-site-care' ),
				'category'            => 'audit',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'min_words'  => array(
							'type'    => 'integer',
							'minimum' => 50,
							'default' => 300,
						),
						'post_types' => array(
							'type'    => 'array',
							'items'   => array( 'type' => 'string' ),
							'default' => array( 'post' ),
						),
						'limit'      => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 1000,
							'default' => 200,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'posts'     => array( 'type' => 'array' ),
						'count'     => array( 'type' => 'integer' ),
						'min_words' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Audit_Thin_Content', 'execute' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => true,
						'readonly'    => true,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/audit-broken-links`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_audit_broken_links(): void {
		wp_register_ability(
			'curator-ai/audit-broken-links',
			array(
				'label'               => __( 'Audit Broken Links', 'curator-ai-seo-site-care' ),
				'description'         => __( 'HEAD-checks external links inside a single post (full-site async in Phase 6).', 'curator-ai-seo-site-care' ),
				'category'            => 'audit',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'      => array( 'type' => 'integer' ),
						'count'        => array( 'type' => 'integer' ),
						'broken_count' => array( 'type' => 'integer' ),
						'links'        => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Audit_Broken_Links', 'execute' ),
				'permission_callback' => static function ( $input ) {
					$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => true,
						'readonly'    => true,
					),
				),
			)
		);
	}

	/**
	 * Register `curator-ai/audit-perf`.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_audit_perf(): void {
		wp_register_ability(
			'curator-ai/audit-perf',
			array(
				'label'               => __( 'Audit Performance', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Runs Google PageSpeed Insights on a URL and returns Core Web Vitals.', 'curator-ai-seo-site-care' ),
				'category'            => 'audit',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'url'      => array(
							'type'   => 'string',
							'format' => 'uri',
						),
						'strategy' => array(
							'type'    => 'string',
							'enum'    => array( 'mobile', 'desktop' ),
							'default' => 'mobile',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'url'           => array( 'type' => 'string' ),
						'strategy'      => array( 'type' => 'string' ),
						'lcp'           => array( 'type' => 'number' ),
						'cls'           => array( 'type' => 'number' ),
						'inp'           => array( 'type' => 'number' ),
						'fcp'           => array( 'type' => 'number' ),
						'ttfb'          => array( 'type' => 'number' ),
						'score'         => array( 'type' => 'integer' ),
						'opportunities' => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( 'CURAI_Ability_Audit_Perf', 'execute' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'destructive' => false,
						'idempotent'  => true,
						'readonly'    => true,
					),
				),
			)
		);
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
				'label'               => __( 'Generate Meta Description', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Generates an SEO-optimized meta description for a post via the configured AI provider.', 'curator-ai-seo-site-care' ),
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
				'label'               => __( 'Generate Alt Text', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Generates accessible alt text for an image attachment via a vision-capable AI provider.', 'curator-ai-seo-site-care' ),
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
				'label'               => __( 'Refresh Content', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Refreshes a post (date_only, context, or full rewrite) via the configured AI provider.', 'curator-ai-seo-site-care' ),
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
				'label'               => __( 'Generate Meta Title', 'curator-ai-seo-site-care' ),
				'description'         => __( 'Generates an SEO-optimized meta title for a post via the configured AI provider.', 'curator-ai-seo-site-care' ),
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
