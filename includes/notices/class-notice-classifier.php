<?php
/**
 * Notice Classifier Class
 *
 * Classifies admin notices by type.
 *
 * @package Notice_Tracker
 * @subpackage Notices
 */

namespace Notice_Tracker\Notices;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice Classifier Class
 *
 * Detects notice types from HTML content.
 *
 * @since 1.0.0
 */
class Notice_Classifier {

	/**
	 * Get the canonical list of notice categories, filtered.
	 *
	 * Third-party code can register additional categories via the
	 * `wpnm_notice_types` filter. The returned array maps a type key
	 * (e.g. `success`) to a human label used by the settings UI.
	 *
	 * @since 1.0.0
	 * @return array<string,string>
	 */
	public static function get_types() {
		$types = array(
			'success' => __( 'Success Notices', 'notice-tracker' ),
			'error'   => __( 'Error Notices', 'notice-tracker' ),
			'warning' => __( 'Warning Notices', 'notice-tracker' ),
			'info'    => __( 'Info Notices', 'notice-tracker' ),
			'other'   => __( 'Non-standard Notices', 'notice-tracker' ),
			'system'  => __( 'WordPress System Notices', 'notice-tracker' ),
		);

		/**
		 * Filter the canonical list of notice categories.
		 *
		 * Use this to add custom buckets so that the settings UI exposes
		 * a configurable rule for them. Note that classify() will only
		 * return a custom type if the captured HTML happens to carry a
		 * matching CSS class (see contains_class()).
		 *
		 * @since 1.0.0
		 * @param array<string,string> $types Type-key => translated label.
		 */
		return (array) apply_filters( 'wpnm_notice_types', $types );
	}

	/**
	 * Classify notice type from HTML content.
	 *
	 * @since 1.0.0
	 * @param string $html Notice HTML content.
	 * @return string Notice type (success, error, warning, info, system, other).
	 */
	public static function classify( $html ) {
		// Check for WordPress notice classes.
		if ( self::contains_class( $html, 'notice-success' ) || self::contains_class( $html, 'updated' ) ) {
			return 'success';
		}

		if ( self::contains_class( $html, 'notice-error' ) || self::contains_class( $html, 'error' ) ) {
			return 'error';
		}

		if ( self::contains_class( $html, 'notice-warning' ) ) {
			return 'warning';
		}

		if ( self::contains_class( $html, 'notice-info' ) ) {
			return 'info';
		}

		// Check if it's a WordPress system notice.
		if ( self::is_system_notice( $html ) ) {
			return 'system';
		}

		// Default to 'other' for non-standard notices.
		return 'other';
	}

	/**
	 * Check if HTML contains a specific CSS class in a class attribute.
	 *
	 * Uses a regex to match only within class="..." attributes,
	 * preventing false positives from content text.
	 *
	 * @since 1.0.0
	 * @param string $html  HTML content.
	 * @param string $class Class name to check.
	 * @return bool
	 */
	private static function contains_class( $html, $class ) {
		// Match class name as a whole word within class attributes.
		$pattern = '/class\s*=\s*["\'][^"\']*\b' . preg_quote( $class, '/' ) . '\b[^"\']*["\']/i';
		return (bool) preg_match( $pattern, $html );
	}

	/**
	 * Check if notice is a WordPress system notice.
	 *
	 * Matches only against known core CSS classes so that any third-party
	 * notice text that happens to contain the word "WordPress" is not
	 * mis-classified as a system notice (which would deny the user the
	 * "Hide completely" option that other notice buckets get).
	 *
	 * @since 1.0.0
	 * @param string $html HTML content.
	 * @return bool
	 */
	private static function is_system_notice( $html ) {
		$system_classes = array(
			'update-nag',
			'update-message',
			'update-core-notice',
			'plugin-update-tr',
			'theme-update-message',
		);

		foreach ( $system_classes as $class ) {
			if ( self::contains_class( $html, $class ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract notice content (text only).
	 *
	 * @since 1.0.0
	 * @param string $html HTML content.
	 * @return string Plain text content.
	 */
	public static function extract_content( $html ) {
		// Remove script and style tags.
		$html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
		$html = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $html );

		// Strip HTML tags.
		$text = wp_strip_all_tags( $html );

		// Clean up whitespace.
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );

		return $text;
	}

	/**
	 * Get notice icon based on type.
	 *
	 * @since 1.0.0
	 * @param string $type Notice type.
	 * @return string Dashicon class.
	 */
	public static function get_icon( $type ) {
		$icons = array(
			'success' => 'dashicons-yes-alt',
			'error'   => 'dashicons-dismiss',
			'warning' => 'dashicons-warning',
			'info'    => 'dashicons-info',
			'system'  => 'dashicons-wordpress',
			'other'   => 'dashicons-bell',
		);

		return isset( $icons[ $type ] ) ? $icons[ $type ] : $icons['other'];
	}

	/**
	 * Get notice color based on type.
	 *
	 * @since 1.0.0
	 * @param string $type Notice type.
	 * @return string Color hex code.
	 */
	public static function get_color( $type ) {
		$colors = array(
			'success' => '#46b450',
			'error'   => '#dc3232',
			'warning' => '#ffb900',
			'info'    => '#00a0d2',
			'system'  => '#0073aa',
			'other'   => '#72777c',
		);

		return isset( $colors[ $type ] ) ? $colors[ $type ] : $colors['other'];
	}
}

