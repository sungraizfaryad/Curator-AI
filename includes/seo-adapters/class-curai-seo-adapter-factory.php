<?php
/**
 * Factory that resolves the active SEO adapter at runtime.
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/interface-curai-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-yoast-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-rank-math-seo-adapter.php';
require_once CURAI_PLUGIN_DIR . 'includes/seo-adapters/class-curai-native-seo-adapter.php';

/**
 * Factory that resolves the active SEO adapter at runtime.
 *
 * Supports an admin override via the `curai_seo_adapter_override` option and
 * a filter (`curai_seo_adapters`) so third-party code can register custom adapters.
 *
 * @since 1.0.0
 */
final class CURAI_SEO_Adapter_Factory {

	/**
	 * Runtime cache of the resolved adapter.
	 *
	 * @since 1.0.0
	 * @var CURAI_SEO_Adapter_Interface|null
	 */
	private static ?CURAI_SEO_Adapter_Interface $cached = null;

	/**
	 * Resolve and return the active SEO adapter.
	 *
	 * Resolution order:
	 * 1. Return cached instance if already resolved.
	 * 2. Build default adapter list and pass through the `curai_seo_adapters` filter.
	 * 3. Sanitize filter results — keep only objects implementing the interface.
	 * 4. If `curai_seo_adapter_override` option is set to a known slug, use that adapter.
	 * 5. Otherwise, auto-detect: return the first adapter whose `is_active()` returns true.
	 * 6. Hard fallback: return a fresh Native adapter if nothing matched (should never occur).
	 *
	 * @since 1.0.0
	 * @return CURAI_SEO_Adapter_Interface
	 */
	public static function get(): CURAI_SEO_Adapter_Interface {
		if ( null !== self::$cached ) {
			return self::$cached;
		}

		$adapters = self::build_adapter_list();

		$override = (string) get_option( 'curai_seo_adapter_override', 'auto' );

		if ( 'auto' !== $override ) {
			foreach ( $adapters as $adapter ) {
				if ( $adapter->get_slug() === $override ) {
					self::$cached = $adapter;
					return self::$cached;
				}
			}
			// Slug not found — fall through to auto detection.
		}

		foreach ( $adapters as $adapter ) {
			if ( $adapter->is_active() ) {
				self::$cached = $adapter;
				return self::$cached;
			}
		}

		// Hard fallback — should never be reached because Native is always active.
		self::$cached = new CURAI_Native_SEO_Adapter();
		return self::$cached;
	}

	/**
	 * Return all available adapters after applying the filter.
	 *
	 * Intentionally not cached so that admin requests (Phase 7a settings dropdown)
	 * always reflect any adapter registered via the filter on that specific request.
	 *
	 * @since 1.0.0
	 * @return CURAI_SEO_Adapter_Interface[]
	 */
	public static function all(): array {
		return self::build_adapter_list();
	}

	/**
	 * Clear the resolved-adapter cache.
	 *
	 * Test-only helper — call in setUp/tearDown so each test starts with a clean slate.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function reset(): void {
		self::$cached = null;
	}

	/**
	 * Build, filter, and sanitize the adapter list.
	 *
	 * Applies the `curai_seo_adapters` filter and removes any items that do not
	 * implement CURAI_SEO_Adapter_Interface.
	 *
	 * @since 1.0.0
	 * @return CURAI_SEO_Adapter_Interface[]
	 */
	private static function build_adapter_list(): array {
		$adapters = array(
			new CURAI_Yoast_SEO_Adapter(),
			new CURAI_Rank_Math_SEO_Adapter(),
			new CURAI_Native_SEO_Adapter(),
		);

		$adapters = apply_filters( 'curai_seo_adapters', $adapters );

		// Defensive: remove anything that slipped through without implementing the interface.
		$adapters = array_values(
			array_filter(
				$adapters,
				static function ( $item ) {
					return $item instanceof CURAI_SEO_Adapter_Interface;
				}
			)
		);

		return $adapters;
	}
}
