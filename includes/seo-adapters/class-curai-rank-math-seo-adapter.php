<?php
/**
 * Rank Math SEO adapter — reads and writes Rank Math meta keys.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/interface-curai-seo-adapter.php';

/**
 * Rank Math SEO adapter.
 *
 * Reads and writes SEO meta using the post meta keys maintained by the
 * Rank Math SEO plugin so that Curator AI data stays in sync with Rank Math.
 *
 * @since 1.0.0
 */
final class CURAI_Rank_Math_SEO_Adapter implements CURAI_SEO_Adapter_Interface {

	/**
	 * Post meta key for SEO title.
	 *
	 * @var string
	 */
	private const META_TITLE = 'rank_math_title';

	/**
	 * Post meta key for SEO description.
	 *
	 * @var string
	 */
	private const META_DESC = 'rank_math_description';

	/**
	 * Post meta key for focus keyword.
	 *
	 * @var string
	 */
	private const META_FOCUS = 'rank_math_focus_keyword';

	/**
	 * Returns true when the Rank Math SEO plugin is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_active(): bool {
		return class_exists( 'RankMath' );
	}

	/**
	 * Machine-readable identifier for this adapter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_slug(): string {
		return 'rank-math';
	}

	/**
	 * Human-readable label for this adapter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Rank Math', 'curator-ai' );
	}

	/**
	 * Read the SEO meta title stored by Rank Math for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_title( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_TITLE, true );
	}

	/**
	 * Read the SEO meta description stored by Rank Math for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_description( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_DESC, true );
	}

	/**
	 * Write a SEO meta title via the Rank Math meta key.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $title   Meta title to store.
	 * @return bool True on success, false on failure.
	 */
	public function write_meta_title( int $post_id, string $title ): bool {
		return (bool) update_post_meta( $post_id, self::META_TITLE, sanitize_text_field( $title ) );
	}

	/**
	 * Write a SEO meta description via the Rank Math meta key.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $desc    Meta description to store.
	 * @return bool True on success, false on failure.
	 */
	public function write_meta_description( int $post_id, string $desc ): bool {
		return (bool) update_post_meta( $post_id, self::META_DESC, sanitize_text_field( $desc ) );
	}

	/**
	 * Read the focus keyword stored by Rank Math for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_focus_keyword( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_FOCUS, true );
	}
}
