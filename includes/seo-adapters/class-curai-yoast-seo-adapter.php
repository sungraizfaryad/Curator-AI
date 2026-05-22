<?php
/**
 * Yoast SEO adapter — reads and writes Yoast meta keys.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/interface-curai-seo-adapter.php';

/**
 * Yoast SEO adapter.
 *
 * Reads and writes SEO meta using the post meta keys maintained by the
 * Yoast SEO plugin so that Curator AI data stays in sync with Yoast.
 *
 * @since 1.0.0
 */
final class CURAI_Yoast_SEO_Adapter implements CURAI_SEO_Adapter_Interface {

	/**
	 * Post meta key for SEO title.
	 *
	 * @var string
	 */
	private const META_TITLE = '_yoast_wpseo_title';

	/**
	 * Post meta key for SEO description.
	 *
	 * @var string
	 */
	private const META_DESC = '_yoast_wpseo_metadesc';

	/**
	 * Post meta key for focus keyword.
	 *
	 * @var string
	 */
	private const META_FOCUS = '_yoast_wpseo_focuskw';

	/**
	 * Returns true when the Yoast SEO plugin is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Machine-readable identifier for this adapter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_slug(): string {
		return 'yoast';
	}

	/**
	 * Human-readable label for this adapter.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Yoast SEO', 'curator-ai' );
	}

	/**
	 * Read the SEO meta title stored by Yoast for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_title( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_TITLE, true );
	}

	/**
	 * Read the SEO meta description stored by Yoast for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_description( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_DESC, true );
	}

	/**
	 * Write a SEO meta title via the Yoast meta key.
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
	 * Write a SEO meta description via the Yoast meta key.
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
	 * Read the focus keyword stored by Yoast for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_focus_keyword( int $post_id ): string {
		return (string) get_post_meta( $post_id, self::META_FOCUS, true );
	}
}
