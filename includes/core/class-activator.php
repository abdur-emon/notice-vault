<?php
/**
 * Activator Class
 *
 * Handles plugin activation tasks.
 *
 * @package Admin_Notice_Hub
 * @subpackage Core
 */

namespace Admin_Notice_Hub\Core;

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
				esc_html__( 'Admin Notice Hub requires WordPress 5.0 or higher.', 'admin-notice-hub' ),
				esc_html__( 'Plugin Activation Error', 'admin-notice-hub' ),
				array( 'back_link' => true )
			);
		}

		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
			wp_die(
				esc_html__( 'Admin Notice Hub requires PHP 7.2 or higher.', 'admin-notice-hub' ),
				esc_html__( 'Plugin Activation Error', 'admin-notice-hub' ),
				array( 'back_link' => true )
			);
		}

		// Create the notices custom table. Schema definition lives in Upgrader so
		// both fresh activation and the on-admin-init migrator share one source.
		require_once ADMIN_NOTICE_HUB_PLUGIN_DIR . 'includes/core/class-upgrader.php';
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
			'version'          => ADMIN_NOTICE_HUB_VERSION,
			// Mark schema migration as done so Upgrader doesn't re-run it on
			// the very next admin request.
			'migrations'       => array(
				'schema_v1'               => ADMIN_NOTICE_HUB_VERSION,
				'notices_option_to_table' => ADMIN_NOTICE_HUB_VERSION,
				'settings_autoload_no'    => ADMIN_NOTICE_HUB_VERSION,
			),
		);

		// Defaults for each registered notice category. Uses the filtered list so
		// custom buckets registered via `admin_notice_hub_notice_types` get sensible defaults too.
		foreach ( array_keys( \Admin_Notice_Hub\Notices\Notice_Classifier::get_types() ) as $type ) {
			$defaults[ 'notice_' . $type ] = 'popup';
		}

		// Only set if not already exists. autoload=no so the option is not
		// loaded on front-end requests — this plugin is admin-only.
		if ( ! get_option( 'admin_notice_hub_settings' ) ) {
			add_option( 'admin_notice_hub_settings', $defaults, '', 'no' );
		}
	}
}
