<?php
/**
 * Notice Storage Class
 *
 * Handles storage and retrieval of notices.
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
 * Notice Storage Class
 *
 * Stores notices in WordPress options (transients).
 *
 * @since 1.0.0
 */
class Notice_Storage {

	/**
	 * Option name for storing notices.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Constructor.
	 *
	 * @param string $option_name Option name to store notices.
	 */
	public function __construct( $option_name = 'wpnm_notices' ) {
		$this->option_name = $option_name;
	}

	/**
	 * Maximum number of notices to store.
	 *
	 * @var int
	 */
	const MAX_NOTICES = 100;

	/**
	 * Store a notice.
	 *
	 * @since 1.0.0
	 * @param array $notice Notice data.
	 * @return bool|int Notice ID on success, false on failure.
	 */
	public function store( $notice ) {
		// Get current notices.
		$notices = $this->get_all();

		// Generate unique ID.
		$notice_id = $this->generate_id();

		// Add metadata.
		$notice['id']         = $notice_id;
		$notice['user_id']    = get_current_user_id();
		$notice['is_read']    = false;
		$notice['created_at'] = current_time( 'mysql' );
		$notice['expires_at'] = $this->get_expiration_date();

		// Add to notices array.
		$notices[ $notice_id ] = $notice;

		// Limit to max notices (FIFO).
		if ( count( $notices ) > $this->MAX_NOTICES ) {
			$notices = array_slice( $notices, -$this->MAX_NOTICES, null, true );
		}

		// Save to database.
		$saved = update_option( $this->option_name, $notices, false );

		// Clear notice count cache.
		delete_transient( 'wpnm_notice_count' );

		return $saved ? $notice_id : false;
	}

	/**
	 * Get all notices.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Notices array.
	 */
	public function get_all( $args = array() ) {
		$defaults = array(
			'type'    => '', // Filter by type.
			'is_read' => null, // Filter by read status.
			'limit'   => 0, // Limit results.
		);

		$args = wp_parse_args( $args, $defaults );

		// Get notices from database.
		$notices = get_option( $this->option_name, array() );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		// Filter out expired notices in-memory (no DB write on reads).
		$now     = current_time( 'mysql' );
		$notices = array_filter(
			$notices,
			function ( $notice ) use ( $now ) {
				return ! isset( $notice['expires_at'] ) || $notice['expires_at'] > $now;
			}
		);

		// Apply type filter.
		if ( ! empty( $args['type'] ) ) {
			$notices = array_filter(
				$notices,
				function ( $notice ) use ( $args ) {
					return isset( $notice['type'] ) && $notice['type'] === $args['type'];
				}
			);
		}

		// Apply read status filter.
		if ( null !== $args['is_read'] ) {
			$notices = array_filter(
				$notices,
				function ( $notice ) use ( $args ) {
					return isset( $notice['is_read'] ) && $notice['is_read'] === $args['is_read'];
				}
			);
		}

		// Apply user filter.
		$user_id = get_current_user_id();
		$notices = array_filter(
			$notices,
			function ( $notice ) use ( $user_id ) {
				return ! isset( $notice['user_id'] ) || (int) $notice['user_id'] === $user_id;
			}
		);

		// Sort by created_at (newest first).
		uasort(
			$notices,
			function ( $a, $b ) {
				$time_a = isset( $a['created_at'] ) ? strtotime( $a['created_at'] ) : 0;
				$time_b = isset( $b['created_at'] ) ? strtotime( $b['created_at'] ) : 0;
				return $time_b - $time_a;
			}
		);

		// Apply offset.
		if ( isset( $args['offset'] ) && $args['offset'] > 0 ) {
			$notices = array_slice( $notices, $args['offset'], null, true );
		}

		// Apply limit.
		if ( $args['limit'] > 0 ) {
			$notices = array_slice( $notices, 0, $args['limit'], true );
		}

		return $notices;
	}

	/**
	 * Get a single notice by ID.
	 *
	 * @since 1.0.0
	 * @param int $notice_id Notice ID.
	 * @return array|false Notice data or false.
	 */
	public function get( $notice_id ) {
		$notices = $this->get_all();
		return isset( $notices[ $notice_id ] ) ? $notices[ $notice_id ] : false;
	}

	/**
	 * Mark notice as read.
	 *
	 * @since 1.0.0
	 * @param int $notice_id Notice ID.
	 * @return bool Success.
	 */
	public function mark_read( $notice_id ) {
		$notices = get_option( $this->option_name, array() );

		if ( ! isset( $notices[ $notice_id ] ) ) {
			return false;
		}

		if ( isset( $notices[ $notice_id ]['user_id'] ) && (int) $notices[ $notice_id ]['user_id'] !== get_current_user_id() ) {
			return false;
		}

		$notices[ $notice_id ]['is_read'] = true;

		delete_transient( 'wpnm_notice_count' );

		return update_option( $this->option_name, $notices, false );
	}

	/**
	 * Delete a notice.
	 *
	 * @since 1.0.0
	 * @param int $notice_id Notice ID.
	 * @return bool Success.
	 */
	public function delete( $notice_id ) {
		$notices = get_option( $this->option_name, array() );

		if ( ! isset( $notices[ $notice_id ] ) ) {
			return false;
		}

		if ( isset( $notices[ $notice_id ]['user_id'] ) && (int) $notices[ $notice_id ]['user_id'] !== get_current_user_id() ) {
			return false;
		}

		unset( $notices[ $notice_id ] );

		delete_transient( 'wpnm_notice_count' );

		return update_option( $this->option_name, $notices, false );
	}

	/**
	 * Get unread notice count.
	 *
	 * @since 1.0.0
	 * @return int Count.
	 */
	public function get_unread_count() {
		// Try to get from cache.
		$count = get_transient( 'wpnm_notice_count' );

		if ( false === $count ) {
			$notices = $this->get_all( array( 'is_read' => false ) );
			$count   = count( $notices );
			set_transient( 'wpnm_notice_count', $count, HOUR_IN_SECONDS );
		}

		return absint( $count );
	}

	/**
	 * Generate unique notice ID.
	 *
	 * @since 1.0.0
	 * @return string Unique ID.
	 */
	private function generate_id() {
		return 'notice_' . wp_generate_uuid4();
	}

	/**
	 * Get expiration date.
	 *
	 * @since 1.0.0
	 * @return string MySQL datetime.
	 */
	private function get_expiration_date() {
		$settings = get_option( 'wpnm_settings', array() );
		$days     = isset( $settings['auto_expire_days'] ) ? absint( $settings['auto_expire_days'] ) : 30;

		return gmdate( 'Y-m-d H:i:s', strtotime( "+{$days} days" ) );
	}

	/**
	 * Mark all notices as read.
	 *
	 * @since 1.0.0
	 * @return bool Success.
	 */
	public function mark_all_read() {
		$notices = get_option( $this->option_name, array() );

		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return false;
		}

		foreach ( $notices as $id => $notice ) {
			$notices[ $id ]['is_read'] = true;
		}

		delete_transient( 'wpnm_notice_count' );

		return update_option( $this->option_name, $notices, false );
	}

	/**
	 * Delete all notices.
	 *
	 * @since 1.0.0
	 * @return bool Success.
	 */
	public function delete_all() {
		delete_transient( 'wpnm_notice_count' );
		return update_option( $this->option_name, array(), false );
	}

	/**
	 * Clean expired notices (called by cron).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clean_expired() {
		$notices = get_option( $this->option_name, array() );

		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return;
		}

		$now     = current_time( 'mysql' );
		$cleaned = array_filter(
			$notices,
			function ( $notice ) use ( $now ) {
				return ! isset( $notice['expires_at'] ) || $notice['expires_at'] > $now;
			}
		);

		if ( count( $cleaned ) !== count( $notices ) ) {
			update_option( $this->option_name, $cleaned, false );
			delete_transient( 'wpnm_notice_count' );
		}
	}
}

