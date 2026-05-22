<?php
/**
 * Uninstall Script
 *
 * Runs when the plugin is uninstalled (deleted).
 *
 * @package Notice_Vault
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options.
 *
 * `notice_vault_settings`  — settings + migration flags.
 * `notice_vault_notices`   — legacy v0.x option (kept here for installs that never
 *                    completed the option-to-table migration before uninstall).
 */
function notice_vault_delete_options() {
	delete_option( 'notice_vault_settings' );
	delete_option( 'notice_vault_notices' );
}

/**
 * Drop the custom notices table.
 */
function notice_vault_drop_table() {
	global $wpdb;
	$table = $wpdb->prefix . 'notice_vault_notices';
	// $table is built from $wpdb->prefix + a constant; uninstall is a one-shot teardown so caching is irrelevant.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

/**
 * Delete plugin transients.
 *
 * Per-user unread-count transients have the form `notice_vault_notice_count_<user_id>`,
 * so we sweep them all from the options table in one go (transients live in
 * `_transient_` / `_transient_timeout_` prefixed options).
 */
function notice_vault_delete_transients() {
	global $wpdb;

	delete_transient( 'notice_vault_activation_redirect' );
	delete_transient( 'notice_vault_notice_count' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_notice_vault_notice_count_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_notice_vault_notice_count_' ) . '%'
		)
	);
}

/**
 * Clear scheduled events.
 */
function notice_vault_clear_scheduled_events() {
	$timestamp = wp_next_scheduled( 'notice_vault_cleanup_notices' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'notice_vault_cleanup_notices' );
	}
}

// Run cleanup.
notice_vault_delete_options();
notice_vault_drop_table();
notice_vault_delete_transients();
notice_vault_clear_scheduled_events();
