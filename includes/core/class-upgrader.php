<?php
/**
 * Upgrader Class
 *
 * Owns the plugin's database schema and version-gated one-shot
 * migrations. Both Activator (on plugin activation) and Plugin
 * (on every admin request, idempotently) call into this class.
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
 * Upgrader Class
 *
 * Migration flags are stored inside the `notice_vault_settings` option under the
 * `migrations` sub-key. Each flag holds the plugin version that ran it,
 * so the same migration never executes twice.
 *
 * @since 1.0.0
 */
class Upgrader {

	/**
	 * Plugin settings option name.
	 *
	 * @var string
	 */
	const OPTION = 'notice_vault_settings';

	/**
	 * Legacy option that held all notices before the table existed.
	 * Removed by migrate_notices_option_to_table().
	 *
	 * @var string
	 */
	const LEGACY_NOTICES_OPTION = 'notice_vault_notices';

	/**
	 * Notices table base name (without prefix).
	 *
	 * @var string
	 */
	const NOTICES_TABLE_BASENAME = 'notice_vault_notices';

	/**
	 * Fully-qualified notices table name for the current blog.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function notices_table() {
		global $wpdb;
		return $wpdb->prefix . self::NOTICES_TABLE_BASENAME;
	}

	/**
	 * Create or update the notices table.
	 *
	 * Called from Activator on plugin activation and from maybe_upgrade()
	 * on existing installs. dbDelta is idempotent.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function ensure_table() {
		global $wpdb;

		$table_name      = self::notices_table();
		$charset_collate = $wpdb->get_charset_collate();

		// IMPORTANT: dbDelta is picky — two spaces between PRIMARY KEY and the
		// column list, indexes on their own lines, no leading whitespace on the
		// CREATE TABLE line.
		// `html` is nullable so dbDelta can ALTER existing tables in place without
		// failing on legacy rows. New rows always write a sanitized HTML payload.
		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			notice_id VARCHAR(64) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			notice_type VARCHAR(20) NOT NULL,
			content TEXT NOT NULL,
			html TEXT NULL DEFAULT NULL,
			hash VARCHAR(32) NOT NULL,
			is_read TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			expires_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY notice_id (notice_id),
			KEY user_read (user_id, is_read),
			KEY user_hash (user_id, hash),
			KEY expires_at (expires_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Run pending migrations. Called synchronously from Plugin::__construct()
	 * when in admin context (so the table exists before Notice_Storage is
	 * touched). Idempotent — each migration is gated by a flag in settings.
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

		$dirty = false;

		// Migration: ensure schema is in place.
		if ( empty( $migrations['schema_v1'] ) ) {
			self::ensure_table();
			$migrations['schema_v1'] = NOTICE_VAULT_VERSION;
			$dirty                   = true;
		}

		// Migration: import legacy option-stored notices into the table.
		if ( empty( $migrations['notices_option_to_table'] ) ) {
			self::migrate_notices_option_to_table();
			$migrations['notices_option_to_table'] = NOTICE_VAULT_VERSION;
			$dirty                                 = true;
		}

		// Migration: flip the autoload state of notice_vault_settings to 'no'.
		if ( empty( $migrations['settings_autoload_no'] ) ) {
			if ( self::flip_settings_autoload_to_no() ) {
				$migrations['settings_autoload_no'] = NOTICE_VAULT_VERSION;
				$dirty                              = true;
			}
		}

		// Migration: add `html` column for sanitized notice markup.
		// dbDelta is idempotent so calling ensure_table() here just adds the missing column.
		if ( empty( $migrations['schema_html_column'] ) ) {
			self::ensure_table();
			$migrations['schema_html_column'] = NOTICE_VAULT_VERSION;
			$dirty                            = true;
		}

		if ( $dirty ) {
			$settings['migrations'] = $migrations;
			$settings['version']    = NOTICE_VAULT_VERSION;
			update_option( self::OPTION, $settings, false );
		}
	}

	/**
	 * Import legacy `notice_vault_notices` option (an array keyed by notice_id)
	 * into the new custom table. Idempotent — uses INSERT IGNORE on the
	 * notice_id UNIQUE index.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function migrate_notices_option_to_table() {
		global $wpdb;

		$legacy = get_option( self::LEGACY_NOTICES_OPTION );
		if ( ! is_array( $legacy ) || empty( $legacy ) ) {
			// Nothing to migrate. Clean up an empty/invalid option so it
			// doesn't keep loading.
			delete_option( self::LEGACY_NOTICES_OPTION );
			return;
		}

		$table = self::notices_table();
		$now   = current_time( 'mysql' );

		foreach ( $legacy as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$notice_id = isset( $row['id'] ) ? (string) $row['id'] : 'notice_' . wp_generate_uuid4();
			$user_id   = isset( $row['user_id'] ) ? (int) $row['user_id'] : 0;
			$type      = isset( $row['type'] ) ? sanitize_key( $row['type'] ) : 'other';
			$content   = isset( $row['content'] ) ? (string) $row['content'] : '';
			$hash      = isset( $row['hash'] ) ? (string) $row['hash'] : md5( $content );
			$is_read   = ! empty( $row['is_read'] ) ? 1 : 0;
			$created   = isset( $row['created_at'] ) ? (string) $row['created_at'] : $now;
			$expires   = isset( $row['expires_at'] ) ? (string) $row['expires_at'] : gmdate( 'Y-m-d H:i:s', strtotime( '+30 days' ) );

			// INSERT IGNORE so re-running this migration on the same data is harmless.
			// $table is built from $wpdb->prefix + a class constant (see notices_table()), so it is safe to interpolate.
			// One-shot migration write — caching not applicable.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$table}
						(notice_id, user_id, notice_type, content, hash, is_read, created_at, expires_at)
					VALUES (%s, %d, %s, %s, %s, %d, %s, %s)",
					$notice_id,
					$user_id,
					$type,
					$content,
					$hash,
					$is_read,
					$created,
					$expires
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		delete_option( self::LEGACY_NOTICES_OPTION );
	}

	/**
	 * Flip the `notice_vault_settings` option from autoload=yes to autoload=no.
	 *
	 * The original activator did not pass an autoload argument, so the
	 * option defaulted to autoload=yes and was loaded on every front-end
	 * request — contradicting the plugin's admin-only design.
	 *
	 * @since 1.0.0
	 * @return bool True if we did something (or the option is already correct).
	 */
	private static function flip_settings_autoload_to_no() {
		global $wpdb;

		// One-shot migration read against the options table — caching not applicable.
		$autoload = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::OPTION
			)
		);

		// Option doesn't exist yet — nothing to flip; mark done.
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
