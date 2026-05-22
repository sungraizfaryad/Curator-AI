<?php
/**
 * Contract for SEO plugin adapters used by Curator AI.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO adapter contract — implemented by Yoast, Rank Math, Native, and community extensions.
 *
 * @since 1.0.0
 */
interface CURAI_SEO_Adapter_Interface {

	/**
	 * Whether this adapter's backing SEO plugin is active (or always true for the native fallback).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_active(): bool;

	/**
	 * Machine-readable identifier for this adapter (e.g. 'native', 'yoast').
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Human-readable label for this adapter shown in the UI.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Read the SEO meta title for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_title( int $post_id ): string;

	/**
	 * Read the SEO meta description for a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_meta_description( int $post_id ): string;

	/**
	 * Write a SEO meta title for a post.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $title   Meta title to store.
	 * @return bool True on success, false on failure.
	 */
	public function write_meta_title( int $post_id, string $title ): bool;

	/**
	 * Write a SEO meta description for a post.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $desc    Meta description to store.
	 * @return bool True on success, false on failure.
	 */
	public function write_meta_description( int $post_id, string $desc ): bool;

	/**
	 * Read the focus keyword for a post (empty string if not supported).
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function read_focus_keyword( int $post_id ): string;
}
