<?php
/**
 * Uninstall Script
 *
 * Runs when the plugin is uninstalled (deleted).
 *
 * @package Quietboard_Notice_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options.
 *
 * `wpnm_settings`  — settings + migration flags.
 * `wpnm_notices`   — legacy v0.x option (kept here for installs that never
 *                    completed the option-to-table migration before uninstall).
 */
function wpnm_delete_options() {
	delete_option( 'wpnm_settings' );
	delete_option( 'wpnm_notices' );
}

/**
 * Drop the custom notices table.
 */
function wpnm_drop_table() {
	global $wpdb;
	$table = $wpdb->prefix . 'wpnm_notices';
	// $table is built from $wpdb->prefix + a constant; uninstall is a one-shot teardown so caching is irrelevant.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

/**
 * Delete plugin transients.
 *
 * Per-user unread-count transients have the form `wpnm_notice_count_<user_id>`,
 * so we sweep them all from the options table in one go (transients live in
 * `_transient_` / `_transient_timeout_` prefixed options).
 */
function wpnm_delete_transients() {
	global $wpdb;

	delete_transient( 'wpnm_activation_redirect' );
	delete_transient( 'wpnm_notice_count' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_wpnm_notice_count_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_wpnm_notice_count_' ) . '%'
		)
	);
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
wpnm_drop_table();
wpnm_delete_transients();
wpnm_clear_scheduled_events();
