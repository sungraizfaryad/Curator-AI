<?php
/**
 * Evaluates automation rules stored in the curai_automation_rules option.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static utility for reading and evaluating Curator AI automation rules.
 *
 * Intentionally stateless — every method reads from the WP options cache so
 * callers never need to manage state themselves.
 *
 * @since 1.0.0
 */
final class CURAI_Rule_Engine {

	/**
	 * Return the full automation rules array from the WP options table.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_rules(): array {
		$rules = get_option( 'curai_automation_rules', array() );

		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Return a rule sub-array by dot-separated path (e.g. 'on_post_save.generate_meta_title').
	 *
	 * @since 1.0.0
	 * @param string $path Dot-separated path into the rules array.
	 * @return array<string, mixed> Rule array, or empty array if the path does not exist.
	 */
	public static function get_rule( string $path ): array {
		$rules  = self::get_rules();
		$keys   = explode( '.', $path );
		$cursor = $rules;

		foreach ( $keys as $key ) {
			if ( ! is_array( $cursor ) || ! array_key_exists( $key, $cursor ) ) {
				return array();
			}
			$cursor = $cursor[ $key ];
		}

		return is_array( $cursor ) ? $cursor : array();
	}

	/**
	 * Return true if the rule at the given path has its enabled flag set.
	 *
	 * @since 1.0.0
	 * @param string $path Dot-separated path into the rules array.
	 * @return bool
	 */
	public static function is_enabled( string $path ): bool {
		$rule = self::get_rule( $path );

		return ! empty( $rule['enabled'] );
	}

	/**
	 * Determine whether an on_post_save rule should fire for a given post.
	 *
	 * NOTE: skip_if_exists is intentionally NOT evaluated here — the listener
	 * must handle that check because it requires reading the current stored value.
	 *
	 * @since 1.0.0
	 * @param string $rule_key  Key under the 'on_post_save' group (e.g. 'generate_meta_title').
	 * @param int    $post_id   Post ID (reserved for future per-post checks).
	 * @param string $post_type The post's post_type slug.
	 * @return bool
	 */
	public static function should_fire_post_save( string $rule_key, int $post_id, string $post_type ): bool {
		$rule = self::get_rule( 'on_post_save.' . $rule_key );

		if ( empty( $rule['enabled'] ) ) {
			return false;
		}

		if ( isset( $rule['post_types'] ) && ! in_array( $post_type, $rule['post_types'], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether the on_media_upload / generate_alt_text rule should fire
	 * for a given attachment.
	 *
	 * NOTE: skip_if_exists is intentionally NOT evaluated here — the listener
	 * handles that check after reading the existing alt text value.
	 *
	 * @since 1.0.0
	 * @param int $attachment_id Attachment post ID.
	 * @return bool
	 */
	public static function should_fire_media_upload( int $attachment_id ): bool {
		$rule = self::get_rule( 'on_media_upload.generate_alt_text' );

		if ( empty( $rule['enabled'] ) ) {
			return false;
		}

		if ( isset( $rule['max_size_mb'] ) ) {
			$file      = get_attached_file( $attachment_id );
			$max_bytes = (float) $rule['max_size_mb'] * 1024 * 1024;

			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- filesize() may return false on missing files; we treat that as 0 bytes (pass-through).
			$file_size = $file ? (int) @filesize( $file ) : 0;

			if ( $file_size > $max_bytes ) {
				return false;
			}
		}

		return true;
	}
}
