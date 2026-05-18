<?php
/**
 * Activator Class
 *
 * Handles plugin activation tasks.
 *
 * @package Notice_Tracker
 * @subpackage Core
 */

namespace Notice_Tracker\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator Class
 *
 * Runs on plugin activation.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate() {
		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			wp_die(
				esc_html__( 'Notice Tracker requires WordPress 5.0 or higher.', 'notice-tracker' ),
				esc_html__( 'Plugin Activation Error', 'notice-tracker' ),
				array( 'back_link' => true )
			);
		}

		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
			wp_die(
				esc_html__( 'Notice Tracker requires PHP 7.2 or higher.', 'notice-tracker' ),
				esc_html__( 'Plugin Activation Error', 'notice-tracker' ),
				array( 'back_link' => true )
			);
		}

		// Set default options.
		self::set_default_options();

		// Create custom table if needed (optional - we'll use options for now).
		// self::create_table();
	}

	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_default_options() {
		$defaults = array(
			'popup_style'      => 'slide-right', // slide-right, modal, panel.
			'visibility_mode'  => 'show-all',    // show-all, hide-all, hide-selected, show-selected.
			'visibility_users' => array(),
			'auto_expire_days' => 30,
			'version'          => WPNM_VERSION,
		);

		// Defaults for each registered notice category. Uses the filtered list so
		// custom buckets registered via `wpnm_notice_types` get sensible defaults too.
		foreach ( array_keys( \Notice_Tracker\Notices\Notice_Classifier::get_types() ) as $type ) {
			$defaults[ 'notice_' . $type ] = 'popup';
		}

		// Only set if not already exists. autoload=no so the option is not
		// loaded on front-end requests — this plugin is admin-only.
		if ( ! get_option( 'wpnm_settings' ) ) {
			add_option( 'wpnm_settings', $defaults, '', 'no' );
		}
	}

	/**
	 * Create custom database table (optional).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'wpnm_notices';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			notice_type varchar(20) NOT NULL,
			notice_content text NOT NULL,
			notice_hash varchar(32) NOT NULL,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY notice_type (notice_type),
			KEY is_read (is_read),
			KEY expires_at (expires_at),
			KEY notice_hash (notice_hash)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

