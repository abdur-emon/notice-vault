<?php
/**
 * Upgrader Class
 *
 * Runs version-gated one-shot migrations for existing installations.
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
 * Upgrader Class
 *
 * Runs once per admin request, idempotent. Each migration sets a flag
 * inside the `wpnm_settings` option (the `migrations` sub-key) so it
 * never runs twice.
 *
 * @since 1.0.0
 */
class Upgrader {

	/**
	 * Option name housing settings and migration flags.
	 *
	 * @var string
	 */
	const OPTION = 'wpnm_settings';

	/**
	 * Run pending migrations. Called on `admin_init` (admin-only).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_upgrade() {
		$settings = get_option( self::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$migrations = isset( $settings['migrations'] ) && is_array( $settings['migrations'] )
			? $settings['migrations']
			: array();

		$ran_any = false;

		if ( empty( $migrations['settings_autoload_no'] ) ) {
			if ( self::flip_settings_autoload_to_no() ) {
				$migrations['settings_autoload_no'] = WPNM_VERSION;
				$ran_any                            = true;
			}
		}

		if ( $ran_any ) {
			$settings['migrations'] = $migrations;
			$settings['version']    = WPNM_VERSION;
			update_option( self::OPTION, $settings, false );
		}
	}

	/**
	 * Flip the `wpnm_settings` option from autoload=yes to autoload=no.
	 *
	 * Existing installs created by the original activator did not pass an
	 * autoload argument, so the option defaulted to autoload=yes and was
	 * loaded on every front-end request — contradicting the plugin's
	 * "admin-only, zero front-end overhead" promise.
	 *
	 * Uses WP 6.4+ `wp_set_option_autoload()` when available; falls back
	 * to a direct delete/add cycle on older cores.
	 *
	 * @since 1.0.0
	 * @return bool True if we did something (or the option is already correct).
	 */
	private static function flip_settings_autoload_to_no() {
		global $wpdb;

		$autoload = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::OPTION
			)
		);

		// Option doesn't exist yet, nothing to flip — mark as done so we don't keep checking.
		if ( null === $autoload ) {
			return true;
		}

		// Already not autoloaded — nothing to do.
		if ( ! in_array( $autoload, array( 'yes', 'on', 'auto', 'auto-on' ), true ) ) {
			return true;
		}

		// WP 6.4+ has a sanctioned API for this.
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( self::OPTION, false );
			return true;
		}

		// Older cores: read, delete, re-add with autoload=no.
		$value = get_option( self::OPTION );
		if ( false === $value ) {
			return false;
		}

		delete_option( self::OPTION );
		add_option( self::OPTION, $value, '', 'no' );
		return true;
	}
}
