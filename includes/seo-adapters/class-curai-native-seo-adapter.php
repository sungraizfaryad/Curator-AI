<?php
/**
 * Native fallback SEO adapter — stores meta in own post meta keys when no SEO plugin is active.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/interface-curai-seo-adapter.php';

/**
 * Native fallback SEO adapter.
 *
 * Stores SEO meta in plugin-owned post meta keys so Curator AI works even
 * when no third-party SEO plugin is present.
 *
 * @since 1.0.0
 */
final class CURAI_Native_SEO_Adapter implements CURAI_SEO_Adapter_Interface {

	/**
	 * Post meta key for SEO title.
	 *
	 * @var string
	 */
	private const META_TITLE = '_curai_meta_title';

	/**
	 * Post meta key for SEO description.
	 *
	 * @var string
	 */
	private const META_DESC = '_curai_meta_desc';

	/**
	 * Always returns true — this adapter is the unconditional fallback.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Machine-readable identifier for this adapter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_slug(): string {
		return 'native';
	}

	/**
	 * Human-readable label for this adapter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Native (Curator AI)', 'curator-ai' );
	}

	/**
	 * Read the stored SEO meta title for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_title( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_TITLE, true );
	}

	/**
	 * Read the stored SEO meta description for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_description( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_DESC, true );
	}

	/**
	 * Write a SEO meta title for a post.
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
	 * Write a SEO meta description for a post.
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
	 * Focus keywords are not stored by the native adapter — returns empty string.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Always empty.
	 */
	public function read_focus_keyword( int $post_id ): string {
		return '';
	}
}
