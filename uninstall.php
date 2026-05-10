<?php
/**
 * Uninstall Script
 *
 * Runs when the plugin is uninstalled (deleted).
 *
 * @package Notice_Tracker
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options.
 */
function wpnm_delete_options() {
	delete_option( 'wpnm_settings' );
	delete_option( 'wpnm_notices' );
}

/**
 * Delete plugin transients.
 */
function wpnm_delete_transients() {
	delete_transient( 'wpnm_activation_redirect' );
	delete_transient( 'wpnm_notice_count' );
}



/**
 * Clear scheduled events.
 */
function wpnm_clear_scheduled_events() {
	$timestamp = wp_next_scheduled( 'wpnm_cleanup_notices' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wpnm_cleanup_notices' );
	}
}

// Run cleanup.
wpnm_delete_options();
wpnm_delete_transients();
wpnm_clear_scheduled_events();

