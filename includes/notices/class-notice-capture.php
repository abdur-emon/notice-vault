<?php
/**
 * Notice Capture Class
 *
 * Captures admin notices using output buffering.
 *
 * @package Notice_Tracker
 * @subpackage Notices
 */

namespace Notice_Tracker\Notices;

use Notice_Tracker\Permissions\Visibility_Manager;

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
			$this->settings = get_option( 'wpnm_settings', array() );
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
	 * Extract individual notices from HTML using DOM parsing.
	 *
	 * Handles nested divs correctly unlike regex-only approach.
	 *
	 * @since 1.0.0
	 * @param string $html HTML content.
	 * @return array Array of notice HTML strings.
	 */
	private function extract_notices( $html ) {
		$notices = array();

		// Use DOMDocument for reliable nested HTML parsing.
		$doc = new \DOMDocument();

		// Suppress warnings for malformed HTML.
		$internal_errors = libxml_use_internal_errors( true );
		$doc->loadHTML( '<div id="wpnm-wrapper">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $internal_errors );

		$xpath = new \DOMXPath( $doc );

		// Find top-level divs with 'notice' or 'updated' or 'error' or 'update-nag' class.
		$nodes = $xpath->query( '//div[@id="wpnm-wrapper"]/*[contains(concat(" ", normalize-space(@class), " "), " notice ") or contains(concat(" ", normalize-space(@class), " "), " updated ") or contains(concat(" ", normalize-space(@class), " "), " update-nag ")]' );

		if ( $nodes && $nodes->length > 0 ) {
			foreach ( $nodes as $node ) {
				$notices[] = $doc->saveHTML( $node );
			}
		}

		// If DOM didn't find notices, output content as-is (non-standard markup).
		if ( empty( $notices ) && ! empty( trim( $html ) ) ) {
			// Check if there's any meaningful HTML content.
			$stripped = wp_strip_all_tags( $html );
			if ( ! empty( trim( $stripped ) ) ) {
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
		$notices          = Notice_Storage::get_all();

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
		Notice_Storage::store( $notice );

		// Update hash index.
		$this->hash_index[ $hash ] = true;
	}
}

