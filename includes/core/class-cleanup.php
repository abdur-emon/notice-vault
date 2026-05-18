<?php
/**
 * Cleanup Class
 *
 * Handles scheduled cleanup tasks.
 *
 * @package Notice_Tracker
 * @subpackage Core
 */

namespace Notice_Tracker\Core;

use Notice_Tracker\Notices\Notice_Storage;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cleanup Class
 *
 * Manages cleanup cron jobs.
 *
 * @since 1.0.0
 */
class Cleanup {

	/**
	 * Initialize cleanup.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		// Schedule cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'wpnm_cleanup_notices' ) ) {
			wp_schedule_event( time(), 'daily', 'wpnm_cleanup_notices' );
		}

		// Hook cleanup function.
		add_action( 'wpnm_cleanup_notices', array( __CLASS__, 'cleanup_expired_notices' ) );
	}

	/**
	 * Cleanup expired notices.
	 *
	 * Delegates to Notice_Storage for single source of truth. Also calls
	 * Upgrader::maybe_upgrade() first so the cron is safe to run even if
	 * it fires before any admin page has loaded after a plugin update
	 * (e.g. wp.org auto-update). Each migration is gated by a flag,
	 * so subsequent cron runs are no-ops.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function cleanup_expired_notices() {
		Upgrader::maybe_upgrade();
		( new Notice_Storage() )->clean_expired();
	}

	/**
	 * Clear all notices.
	 *
	 * @since 1.0.0
	 * @return bool Success.
	 */
	public static function clear_all_notices() {
		return ( new Notice_Storage() )->delete_all();
	}
}

