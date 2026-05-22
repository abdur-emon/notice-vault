<?php
/**
 * Deactivator Class
 *
 * Handles plugin deactivation tasks.
 *
 * @package Notice_Vault
 * @subpackage Core
 */

namespace Notice_Vault\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivator Class
 *
 * Runs on plugin deactivation.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate() {
		// Clear scheduled events.
		self::clear_scheduled_events();

		// Clear transients.
		self::clear_transients();

		// Note: We don't delete options or database tables on deactivation.
		// This preserves user settings if they reactivate.
		// Use uninstall.php for complete cleanup.
	}

	/**
	 * Clear scheduled cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_scheduled_events() {
		// Clear notice cleanup cron.
		$timestamp = wp_next_scheduled( 'notice_vault_cleanup_notices' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'notice_vault_cleanup_notices' );
		}
	}

	/**
	 * Clear plugin transients.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function clear_transients() {
		delete_transient( 'notice_vault_activation_redirect' );
		delete_transient( 'notice_vault_notice_count' );
	}
}

