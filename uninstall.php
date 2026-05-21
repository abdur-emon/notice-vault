<?php
/**
 * Uninstall Script
 *
 * Runs when the plugin is uninstalled (deleted).
 *
 * @package Admin_Notice_Hub
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options.
 *
 * `admin_notice_hub_settings`  — settings + migration flags.
 * `admin_notice_hub_notices`   — legacy v0.x option (kept here for installs that never
 *                    completed the option-to-table migration before uninstall).
 */
function admin_notice_hub_delete_options() {
	delete_option( 'admin_notice_hub_settings' );
	delete_option( 'admin_notice_hub_notices' );
}

/**
 * Drop the custom notices table.
 */
function admin_notice_hub_drop_table() {
	global $wpdb;
	$table = $wpdb->prefix . 'admin_notice_hub_notices';
	// $table is built from $wpdb->prefix + a constant; uninstall is a one-shot teardown so caching is irrelevant.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

/**
 * Delete plugin transients.
 *
 * Per-user unread-count transients have the form `admin_notice_hub_notice_count_<user_id>`,
 * so we sweep them all from the options table in one go (transients live in
 * `_transient_` / `_transient_timeout_` prefixed options).
 */
function admin_notice_hub_delete_transients() {
	global $wpdb;

	delete_transient( 'admin_notice_hub_activation_redirect' );
	delete_transient( 'admin_notice_hub_notice_count' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_admin_notice_hub_notice_count_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_admin_notice_hub_notice_count_' ) . '%'
		)
	);
}

/**
 * Clear scheduled events.
 */
function admin_notice_hub_clear_scheduled_events() {
	$timestamp = wp_next_scheduled( 'admin_notice_hub_cleanup_notices' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'admin_notice_hub_cleanup_notices' );
	}
}

// Run cleanup.
admin_notice_hub_delete_options();
admin_notice_hub_drop_table();
admin_notice_hub_delete_transients();
admin_notice_hub_clear_scheduled_events();
