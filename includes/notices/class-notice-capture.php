<?php
/**
 * Notice Capture Class
 *
 * Captures admin notices using output buffering.
 *
 * @package Admin_Notice_Hub
 * @subpackage Notices
 */

namespace Admin_Notice_Hub\Notices;

use Admin_Notice_Hub\Permissions\Visibility_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice Capture Class
 *
 * Hooks into admin_notices and captures output.
 *
 * @since 1.0.0
 */
class Notice_Capture {

	/**
	 * Notice Storage instance.
	 *
	 * @var \Admin_Notice_Hub\Notices\Notice_Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param \Admin_Notice_Hub\Notices\Notice_Storage $storage Notice Storage instance.
	 */
	public function __construct( $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Whether capture is active.
	 *
	 * @var bool
	 */
	private $is_capturing = false;

	/**
	 * Cached settings to avoid repeated get_option calls.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Cached hash index for fast duplicate checks.
	 *
	 * @var array|null
	 */
	private $hash_index = null;

	/**
	 * Get cached settings.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = get_option( 'admin_notice_hub_settings', array() );
		}
		return $this->settings;
	}

	/**
	 * Start output buffering to capture notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function start_capture() {
		// Don't capture if user can't see notices.
		if ( ! $this->should_capture() ) {
			return;
		}

		$this->is_capturing = true;
		ob_start();
	}

	/**
	 * End output buffering and process captured notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function end_capture() {
		if ( ! $this->is_capturing ) {
			return;
		}

		// Get captured content.
		$content = ob_get_clean();

		// Process the captured notices.
		$this->process_notices( $content );

		$this->is_capturing = false;
	}

	/**
	 * Check if we should capture notices.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function should_capture() {
		// Don't capture on AJAX requests.
		if ( wp_doing_ajax() ) {
			return false;
		}

		// Don't capture on REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		// Use Visibility_Manager for user checks (single source of truth).
		if ( ! Visibility_Manager::can_see_notices() ) {
			return false;
		}

		return true;
	}

	/**
	 * Process captured notices.
	 *
	 * @since 1.0.0
	 * @param string $content Captured HTML content.
	 * @return void
	 */
	private function process_notices( $content ) {
		if ( empty( trim( $content ) ) ) {
			return;
		}

		// Split content into individual notices.
		$notices = $this->extract_notices( $content );

		// Build hash index once for all notices in this batch.
		$this->build_hash_index();

		foreach ( $notices as $notice_html ) {
			$this->process_single_notice( $notice_html );
		}

		// Reset hash index after batch.
		$this->hash_index = null;
	}

	/**
	 * Extract individual notices from the captured admin_notices buffer.
	 *
	 * Iterates every top-level element child of the wrapper, regardless of
	 * its CSS class. Standard WordPress notices (with `notice`/`updated`/
	 * `update-nag` classes) AND non-standard ones (plain divs, custom
	 * wrappers, etc.) are both returned.
	 *
	 * Why not filter by class: the readme advertises a "Non-standard Notices"
	 * category, which a third-party plugin emitting a `<div>` without a
	 * `notice` class is expected to hit. The old XPath-with-class-filter
	 * approach silently dropped those notices whenever they were emitted in
	 * the same buffer as a standard notice (because the class-matching
	 * branch returned >0 results and the whole-buffer fallback never ran).
	 *
	 * Classification of each captured chunk happens downstream in
	 * Notice_Classifier::classify(), which inspects the CSS classes and
	 * routes a class-less notice to the `other` bucket.
	 *
	 * @since 1.0.0
	 * @param string $html HTML content.
	 * @return array Array of notice HTML strings.
	 */
	private function extract_notices( $html ) {
		$notices = array();

		if ( '' === trim( $html ) ) {
			return $notices;
		}

		// Wrap in a known container so DOMDocument has a stable root we can
		// walk. We prepend an XML prolog with explicit UTF-8 encoding so
		// libxml does not silently fall back to ISO-8859-1 and mangle
		// accented characters in admin notice text. This is the modern
		// replacement for the deprecated `mb_convert_encoding(..., 'HTML-ENTITIES', ...)`
		// pattern.
		$doc             = new \DOMDocument();
		$internal_errors = libxml_use_internal_errors( true );
		$xml_prolog      = '<' . '?xml encoding="UTF-8"?' . '>';
		$wrapped         = $xml_prolog . '<div id="admin-notice-hub-wrapper">' . $html . '</div>';
		$doc->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $internal_errors );

		$wrapper = $doc->getElementById( 'admin-notice-hub-wrapper' );

		if ( $wrapper instanceof \DOMElement ) {
			foreach ( $wrapper->childNodes as $child ) {
				if ( XML_ELEMENT_NODE !== $child->nodeType ) {
					continue;
				}
				$serialized = $doc->saveHTML( $child );
				if ( ! is_string( $serialized ) || '' === trim( wp_strip_all_tags( $serialized ) ) ) {
					continue;
				}
				$notices[] = $serialized;
			}
		}

		// Fallback for buffers that contain only text or extremely malformed
		// markup (no element children at all) — treat the whole buffer as one
		// notice so we don't silently lose the content.
		if ( empty( $notices ) ) {
			$stripped = wp_strip_all_tags( $html );
			if ( '' !== trim( $stripped ) ) {
				$notices[] = $html;
			}
		}

		return $notices;
	}

	/**
	 * Process a single notice.
	 *
	 * @since 1.0.0
	 * @param string $notice_html Notice HTML.
	 * @return void
	 */
	private function process_single_notice( $notice_html ) {
		// Classify notice type.
		$type = Notice_Classifier::classify( $notice_html );

		$settings = $this->get_settings();
		$key      = 'notice_' . $type;

		// Check if we should handle this notice type.
		if ( ! isset( $settings[ $key ] ) ) {
			// Output the notice normally.
			echo wp_kses_post( $notice_html );
			return;
		}

		// Get action for this notice type.
		$action = $settings[ $key ];

		if ( 'nothing' === $action ) {
			// Do nothing - output normally.
			echo wp_kses_post( $notice_html );
			return;
		}

		if ( 'hide' === $action ) {
			// Hide completely - don't output, don't store.
			return;
		}

		if ( 'popup' === $action ) {
			// Store for popup display.
			$this->store_notice( $notice_html, $type );
			// Don't output to dashboard.
			return;
		}
	}

	/**
	 * Build hash index for fast duplicate checking.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function build_hash_index() {
		if ( null !== $this->hash_index ) {
			return;
		}

		$this->hash_index = array();
		$notices          = $this->storage->get_all();

		foreach ( $notices as $notice ) {
			if ( isset( $notice['hash'] ) ) {
				$this->hash_index[ $notice['hash'] ] = true;
			}
		}
	}

	/**
	 * Store notice.
	 *
	 * @since 1.0.0
	 * @param string $html Notice HTML.
	 * @param string $type Notice type.
	 * @return void
	 */
	private function store_notice( $html, $type ) {
		// Create notice hash to prevent duplicates.
		$hash = md5( $html );

		// Fast O(1) duplicate check using hash index.
		if ( isset( $this->hash_index[ $hash ] ) ) {
			return;
		}

		// Extract plain text content.
		$content = Notice_Classifier::extract_content( $html );

		// Prepare notice data.
		$notice = array(
			'type'    => $type,
			'content' => $content,
			'html'    => wp_kses_post( $html ),
			'hash'    => $hash,
		);

		// Store the notice.
		$this->storage->store( $notice );

		// Update hash index.
		$this->hash_index[ $hash ] = true;
	}
}

