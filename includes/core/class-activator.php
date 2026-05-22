<?php
/**
 * Activator Class
 *
 * Handles plugin activation tasks.
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
				esc_html__( 'Notice Vault requires WordPress 5.0 or higher.', 'notice-vault' ),
				esc_html__( 'Plugin Activation Error', 'notice-vault' ),
				array( 'back_link' => true )
			);
		}

		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
			wp_die(
				esc_html__( 'Notice Vault requires PHP 7.2 or higher.', 'notice-vault' ),
				esc_html__( 'Plugin Activation Error', 'notice-vault' ),
				array( 'back_link' => true )
			);
		}

		// Create the notices custom table. Schema definition lives in Upgrader so
		// both fresh activation and the on-admin-init migrator share one source.
		Upgrader::ensure_table();

		// Seed default settings (autoload=no — this plugin is admin-only).
		self::set_default_options();

		// Schedule the daily cleanup cron immediately so it runs even on installs
		// that activate the plugin without ever visiting wp-admin afterward.
		if ( ! wp_next_scheduled( 'notice_vault_cleanup_notices' ) ) {
			wp_schedule_event( time(), 'daily', 'notice_vault_cleanup_notices' );
		}
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
			'version'          => NOTICE_VAULT_VERSION,
			// Mark schema migration as done so Upgrader doesn't re-run it on
			// the very next admin request.
			'migrations'       => array(
				'schema_v1'               => NOTICE_VAULT_VERSION,
				'notices_option_to_table' => NOTICE_VAULT_VERSION,
				'settings_autoload_no'    => NOTICE_VAULT_VERSION,
				'schema_html_column'      => NOTICE_VAULT_VERSION,
			),
		);

		// Defaults for each registered notice category. Uses the filtered list so
		// custom buckets registered via `notice_vault_notice_types` get sensible defaults too.
		foreach ( array_keys( \Notice_Vault\Notices\Notice_Classifier::get_types() ) as $type ) {
			$defaults[ 'notice_' . $type ] = 'popup';
		}

		// Only set if not already exists. autoload=no so the option is not
		// loaded on front-end requests — this plugin is admin-only.
		if ( ! get_option( 'notice_vault_settings' ) ) {
			add_option( 'notice_vault_settings', $defaults, '', 'no' );
		}
	}
}
