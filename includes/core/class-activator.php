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

		// Create the notices custom table. Schema definition lives in Upgrader so
		// both fresh activation and the on-admin-init migrator share one source.
		require_once WPNM_PLUGIN_DIR . 'includes/core/class-upgrader.php';
		Upgrader::ensure_table();

		// Seed default settings (autoload=no — this plugin is admin-only).
		self::set_default_options();
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
			// Mark schema migration as done so Upgrader doesn't re-run it on
			// the very next admin request.
			'migrations'       => array(
				'schema_v1'               => WPNM_VERSION,
				'notices_option_to_table' => WPNM_VERSION,
				'settings_autoload_no'    => WPNM_VERSION,
			),
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
}
