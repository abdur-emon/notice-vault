<?php
/**
 * Notice Storage Class
 *
 * Backed by a custom database table (created by Upgrader::ensure_table)
 * with row-level UPDATEs, eliminating the read-modify-write races and
 * cross-user FIFO eviction of the previous options-based storage.
 *
 * Public API (signatures and return shapes) is identical to the
 * pre-1.0 options-based implementation, so every caller — Notice_Capture,
 * Notice_Popup, Admin_Toolbar, Cleanup, templates/settings-page.php —
 * works unchanged.
 *
 * @package Notice_Tracker
 * @subpackage Notices
 */

namespace Notice_Tracker\Notices;

use Notice_Tracker\Core\Upgrader;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice Storage Class
 *
 * @since 1.0.0
 */
class Notice_Storage {

	/**
	 * Maximum number of notices retained PER USER. Enforced with a
	 * post-insert trim. Cross-user eviction (the v0.x problem) no longer
	 * happens because notices are now indexed by user_id in their own rows.
	 *
	 * @var int
	 */
	const MAX_NOTICES = 100;

	/**
	 * Constructor. The legacy $option_name parameter is preserved so callers
	 * that pass it don't error, but it's no longer used — storage lives in
	 * a custom table now.
	 *
	 * @param string $option_name Legacy parameter; ignored.
	 */
	public function __construct( $option_name = 'wpnm_notices' ) {
		unset( $option_name ); // explicitly discard.
	}

	/**
	 * Fully-qualified table name for the current blog.
	 *
	 * @return string
	 */
	private function table() {
		return Upgrader::notices_table();
	}

	/**
	 * Transient key for the current user's unread count cache.
	 *
	 * @return string
	 */
	private function unread_count_cache_key() {
		return 'wpnm_notice_count_' . absint( get_current_user_id() );
	}

	/**
	 * Store a notice.
	 *
	 * @since 1.0.0
	 * @param array $notice Notice data. Expects at least `type`, `content`, `hash`.
	 * @return string|false The generated notice_id on success, false otherwise.
	 */
	public function store( $notice ) {
		global $wpdb;

		$notice_id = $this->generate_id();
		$user_id   = (int) get_current_user_id();
		$created   = current_time( 'mysql' );
		$expires   = $this->get_expiration_date();

		// Compose the array the filter sees. Mirrors v0.x shape.
		$notice['id']         = $notice_id;
		$notice['user_id']    = $user_id;
		$notice['is_read']    = false;
		$notice['created_at'] = $created;
		$notice['expires_at'] = $expires;

		/**
		 * Filter a notice array immediately before it is persisted.
		 *
		 * Return a falsy/empty value to abort the store — useful for veto-style
		 * integrations that don't want certain notices captured. Return the
		 * (possibly mutated) array to proceed.
		 *
		 * @since 1.0.0
		 * @param array $notice The notice data.
		 */
		$notice = apply_filters( 'wpnm_before_store_notice', $notice );
		if ( ! is_array( $notice ) || empty( $notice ) ) {
			return false;
		}

		// Normalize for INSERT. We let the filter override anything it likes.
		$row = array(
			'notice_id'   => isset( $notice['id'] ) ? (string) $notice['id'] : $notice_id,
			'user_id'     => isset( $notice['user_id'] ) ? (int) $notice['user_id'] : $user_id,
			'notice_type' => isset( $notice['type'] ) ? sanitize_key( $notice['type'] ) : 'other',
			'content'     => isset( $notice['content'] ) ? (string) $notice['content'] : '',
			'hash'        => isset( $notice['hash'] ) ? (string) $notice['hash'] : md5( (string) ( $notice['content'] ?? '' ) ),
			'is_read'     => empty( $notice['is_read'] ) ? 0 : 1,
			'created_at'  => isset( $notice['created_at'] ) ? (string) $notice['created_at'] : $created,
			'expires_at'  => isset( $notice['expires_at'] ) ? (string) $notice['expires_at'] : $expires,
		);
		$formats = array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' );

		$inserted = $wpdb->insert( $this->table(), $row, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $inserted || 0 === $inserted ) {
			return false;
		}

		// Per-user FIFO cap. Atomic enough — even under concurrency we'll just
		// trim slightly more aggressively, never less.
		$this->enforce_max_per_user( (int) $row['user_id'] );

		delete_transient( $this->unread_count_cache_key() );

		/**
		 * Fires after a notice has been successfully stored.
		 *
		 * @since 1.0.0
		 * @param string $notice_id The generated notice ID (UUID-prefixed).
		 * @param array  $notice    The full notice array as inserted.
		 */
		do_action( 'wpnm_notice_stored', $row['notice_id'], $notice );

		return $row['notice_id'];
	}

	/**
	 * Trim the current user's rows down to MAX_NOTICES, oldest first.
	 *
	 * @param int $user_id User to trim.
	 * @return void
	 */
	private function enforce_max_per_user( $user_id ) {
		global $wpdb;
		$table = $this->table();

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);

		if ( $count <= self::MAX_NOTICES ) {
			return;
		}

		$excess = $count - self::MAX_NOTICES;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE user_id = %d ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$excess
			)
		);
	}

	/**
	 * Get notices for the current user, filtered.
	 *
	 * @since 1.0.0
	 * @param array $args {
	 *     @type string $type    Restrict to this notice_type.
	 *     @type bool   $is_read Restrict to read (true) or unread (false) — null for both.
	 *     @type int    $limit   0 for no limit.
	 *     @type int    $offset  Skip this many rows.
	 * }
	 * @return array Notices keyed by notice_id, newest first, same shape as v0.x.
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'type'    => '',
			'is_read' => null,
			'limit'   => 0,
			'offset'  => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$user_id = (int) get_current_user_id();
		$now     = current_time( 'mysql' );
		$table   = $this->table();

		$where      = array( 'user_id = %d', 'expires_at > %s' );
		$where_args = array( $user_id, $now );

		if ( ! empty( $args['type'] ) ) {
			$where[]      = 'notice_type = %s';
			$where_args[] = $args['type'];
		}

		if ( null !== $args['is_read'] ) {
			$where[]      = 'is_read = %d';
			$where_args[] = $args['is_read'] ? 1 : 0;
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC';

		$limit  = (int) $args['limit'];
		$offset = max( 0, (int) $args['offset'] );

		if ( $limit > 0 ) {
			$sql         .= ' LIMIT %d OFFSET %d';
			$where_args[] = $limit;
			$where_args[] = $offset;
		} elseif ( $offset > 0 ) {
			// Effectively no limit, but we still need an offset; use a huge bound.
			$sql         .= ' LIMIT 18446744073709551615 OFFSET %d';
			$where_args[] = $offset;
		}

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$out[ $row['notice_id'] ] = $this->row_to_notice( $row );
		}
		return $out;
	}

	/**
	 * Convert a DB row to the array shape v0.x callers expect.
	 *
	 * @param array $row Row from $wpdb->get_results( ..., ARRAY_A ).
	 * @return array
	 */
	private function row_to_notice( $row ) {
		return array(
			'id'         => $row['notice_id'],
			'user_id'    => (int) $row['user_id'],
			'type'       => $row['notice_type'],
			'content'    => $row['content'],
			'hash'       => $row['hash'],
			'is_read'    => (bool) $row['is_read'],
			'created_at' => $row['created_at'],
			'expires_at' => $row['expires_at'],
		);
	}

	/**
	 * Mark a notice as read. Scoped to the current user.
	 *
	 * @since 1.0.0
	 * @param string $notice_id Notice ID (UUID-prefixed).
	 * @return bool True on success.
	 */
	public function mark_read( $notice_id ) {
		global $wpdb;

		if ( ! is_string( $notice_id ) || '' === $notice_id ) {
			return false;
		}

		$user_id = (int) get_current_user_id();
		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$this->table(),
			array( 'is_read' => 1 ),
			array(
				'notice_id' => $notice_id,
				'user_id'   => $user_id,
			),
			array( '%d' ),
			array( '%s', '%d' )
		);

		if ( $updated ) {
			delete_transient( $this->unread_count_cache_key() );
		}

		return false !== $updated && $updated > 0;
	}

	/**
	 * Delete a single notice. Scoped to the current user.
	 *
	 * @since 1.0.0
	 * @param string $notice_id Notice ID.
	 * @return bool True on success.
	 */
	public function delete( $notice_id ) {
		global $wpdb;

		if ( ! is_string( $notice_id ) || '' === $notice_id ) {
			return false;
		}

		$user_id = (int) get_current_user_id();
		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$this->table(),
			array(
				'notice_id' => $notice_id,
				'user_id'   => $user_id,
			),
			array( '%s', '%d' )
		);

		if ( $deleted ) {
			delete_transient( $this->unread_count_cache_key() );
		}

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Count unread notices for the current user. Transient-cached for an hour.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_unread_count() {
		global $wpdb;

		$cache_key = $this->unread_count_cache_key();
		$count     = get_transient( $cache_key );

		if ( false === $count ) {
			$user_id = (int) get_current_user_id();
			$now     = current_time( 'mysql' );
			$table   = $this->table();
			$count   = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0 AND expires_at > %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id,
					$now
				)
			);
			set_transient( $cache_key, $count, HOUR_IN_SECONDS );
		}

		return absint( $count );
	}

	/**
	 * Generate a unique notice ID.
	 *
	 * @return string Format: `notice_<uuid4>`. The `notice_` prefix is for legacy
	 *                callers that match on that pattern.
	 */
	private function generate_id() {
		return 'notice_' . wp_generate_uuid4();
	}

	/**
	 * MySQL datetime N days from now (auto-expire window from settings).
	 *
	 * @return string
	 */
	private function get_expiration_date() {
		$settings = get_option( 'wpnm_settings', array() );
		$days     = isset( $settings['auto_expire_days'] ) ? absint( $settings['auto_expire_days'] ) : 30;
		return gmdate( 'Y-m-d H:i:s', strtotime( "+{$days} days" ) );
	}

	/**
	 * Mark all of the current user's unread notices as read.
	 *
	 * @since 1.0.0
	 * @return bool Always true (no-op on empty result is still "success").
	 */
	public function mark_all_read() {
		global $wpdb;
		$user_id = (int) get_current_user_id();
		$table   = $this->table();

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"UPDATE {$table} SET is_read = 1 WHERE user_id = %d AND is_read = 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);

		delete_transient( $this->unread_count_cache_key() );
		return true;
	}

	/**
	 * Delete all of the current user's notices.
	 *
	 * @since 1.0.0
	 * @return bool Always true (no-op on empty result is still "success").
	 */
	public function delete_all() {
		global $wpdb;
		$user_id = (int) get_current_user_id();

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$this->table(),
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		delete_transient( $this->unread_count_cache_key() );
		return true;
	}

	/**
	 * Delete every expired notice (called by the daily cleanup cron).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clean_expired() {
		global $wpdb;
		$now   = current_time( 'mysql' );
		$table = $this->table();

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expires_at <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now
			)
		);
		// Per-user unread-count transients will refresh on their next read (1h TTL).
	}
}
