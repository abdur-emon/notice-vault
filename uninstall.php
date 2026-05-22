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
 * Per-blog teardown. Drops the notices table, the settings option, and the
 * legacy v0.x option (in case the option-to-table migration never ran on
 * this blog). Safe to call from inside switch_to_blog().
 */
function notice_vault_uninstall_current_site() {
	global $wpdb;

	delete_option( 'notice_vault_settings' );
	delete_option( 'notice_vault_notices' );

	$table = $wpdb->prefix . 'notice_vault_notices';
	// $table is built from $wpdb->prefix + a constant; uninstall is a one-shot teardown.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

	// Per-user unread-count transients have the form `notice_vault_notice_count_<user_id>`.
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
 * Unschedule the daily cleanup cron. Cron events are stored as a sitemeta-style
 * option but `wp_next_scheduled`/`wp_unschedule_event` are switch_to_blog-aware,
 * so it's safe to call this per-site.
 */
function notice_vault_clear_scheduled_events() {
	$timestamp = wp_next_scheduled( 'notice_vault_cleanup_notices' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'notice_vault_cleanup_notices' );
	}
}

// On multisite, uninstall fires once for the whole network — but Notice Vault
// stores per-site tables (and is documented as per-site activation). Iterate
// every blog so subsite tables don't orphan.
if ( is_multisite() ) {
	$notice_vault_sites = function_exists( 'get_sites' )
		? get_sites( array( 'fields' => 'ids', 'number' => 0 ) )
		: array( get_current_blog_id() );

	foreach ( $notice_vault_sites as $notice_vault_site_id ) {
		switch_to_blog( (int) $notice_vault_site_id );
		notice_vault_uninstall_current_site();
		notice_vault_clear_scheduled_events();
		restore_current_blog();
	}
} else {
	notice_vault_uninstall_current_site();
	notice_vault_clear_scheduled_events();
}
