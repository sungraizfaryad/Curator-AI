<?php
/**
 * Minimal stand-ins for WP_Error and WP_Post used in Curator AI unit tests.
 *
 * Loaded by individual test files when needed. Keeps tests independent of a
 * full WordPress test install.
 *
 * @package CuratorAI
 */

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for unit tests.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		public string $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		public string $message;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Minimal WP_Post stub for unit tests.
	 */
	class WP_Post {
		/**
		 * @var int
		 */
		public int $ID = 0;

		/**
		 * @var string
		 */
		public string $post_title = '';

		/**
		 * @var string
		 */
		public string $post_excerpt = '';

		/**
		 * @var string
		 */
		public string $post_content = '';
	}
}
