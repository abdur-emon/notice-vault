<?php
/**
 * Notice Popup Class
 *
 * Handles popup UI rendering and AJAX operations.
 *
 * @package Notice_Vault
 * @subpackage Admin
 */

namespace Notice_Vault\Admin;

use Notice_Vault\Notices\Notice_Storage;
use Notice_Vault\Notices\Notice_Classifier;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Notice Popup Class
 *
 * Renders popup and handles AJAX requests.
 *
 * @since 1.0.0
 */
class Notice_Popup
{

	/**
	 * Notice Storage instance.
	 *
	 * @var \Notice_Vault\Notices\Notice_Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param \Notice_Vault\Notices\Notice_Storage $storage Notice Storage instance.
	 */
	public function __construct( $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Enqueue popup assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'notice-vault-popup',
			NOTICE_VAULT_PLUGIN_URL . 'assets/css/popup.css',
			array(),
			NOTICE_VAULT_VERSION
		);

		// Enqueue JS.
		wp_enqueue_script(
			'notice-vault-popup',
			NOTICE_VAULT_PLUGIN_URL . 'assets/js/popup.js',
			array('jquery'),
			NOTICE_VAULT_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'notice-vault-popup',
			'noticeVaultPopup',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'notice_vault_ajax_nonce' ),
				'popupStyle' => $this->get_popup_style(),
				'i18n'       => array(
					'noNotices'        => __( 'No notices to display', 'notice-vault' ),
					'markAllRead'      => __( 'Mark All as Read', 'notice-vault' ),
					'clearAll'         => __( 'Clear All', 'notice-vault' ),
					'confirmClearAll'  => __( 'Are you sure you want to clear all notices?', 'notice-vault' ),
					'loading'          => __( 'Loading...', 'notice-vault' ),
					'error'            => __( 'An error occurred. Please try again.', 'notice-vault' ),
					'markAsRead'       => __( 'Mark as read', 'notice-vault' ),
					'dismiss'          => __( 'Dismiss', 'notice-vault' ),
					'dismissNotice'    => __( 'Dismiss notice', 'notice-vault' ),
					'notices'          => __( 'Notices', 'notice-vault' ),
					/* translators: %d: number of unread notices. */
					'noticesWithCount' => __( 'Notices (%d)', 'notice-vault' ),
					'justNow'          => __( 'Just now', 'notice-vault' ),
					/* translators: %d: number of minutes ago. */
					'minutesAgo'       => __( '%d minutes ago', 'notice-vault' ),
					/* translators: %d: number of hours ago. */
					'hoursAgo'         => __( '%d hours ago', 'notice-vault' ),
					/* translators: %d: number of days ago. */
					'daysAgo'          => __( '%d days ago', 'notice-vault' ),
					'loadMore'         => __( 'Load more', 'notice-vault' ),
					'loadMoreLoading'  => __( 'Loading…', 'notice-vault' ),
					'allLoaded'        => __( 'All notices loaded', 'notice-vault' ),
					'markedAllRead'    => __( 'All notices marked as read', 'notice-vault' ),
					'cleared'          => __( 'All notices cleared', 'notice-vault' ),
				),
			)
		);
	}

	/**
	 * Render popup HTML.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_popup() {
		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		$popup_style = $this->get_popup_style();
		include NOTICE_VAULT_PLUGIN_DIR . 'templates/popup-template.php';
	}

	/**
	 * Get popup style from settings.
	 *
	 * @since 1.0.0
	 * @return string Popup style.
	 */
	private function get_popup_style()
	{
		$settings = get_option('notice_vault_settings', array());
		return isset($settings['popup_style']) ? $settings['popup_style'] : 'slide-right';
	}

	/**
	 * AJAX: Get notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_get_notices()
	{
		// Verify nonce.
		check_ajax_referer('notice_vault_ajax_nonce', 'nonce');

		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Get filter parameters.
		$filter_type = isset( $_POST['filter_type'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_type'] ) ) : '';
		$show_read   = isset( $_POST['show_read'] )
			&& 'true' === sanitize_text_field( wp_unslash( $_POST['show_read'] ) );

		// Pagination parameters.
		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = 20;

		// Build query args.
		$args = array(
			'limit'  => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
		);

		if (!empty($filter_type) && 'all' !== $filter_type) {
			$args['type'] = $filter_type;
		}

		// "Show Read" unchecked -> only unread. Checked -> include all (no read filter).
		if ( ! $show_read ) {
			$args['is_read'] = false;
		}

		// Get notices.
		$notices = $this->storage->get_all( $args );

		// Total count for pagination, same filters but no limit/offset — a single COUNT(*) query.
		$count_args = array();
		if ( isset( $args['type'] ) ) {
			$count_args['type'] = $args['type'];
		}
		if ( isset( $args['is_read'] ) ) {
			$count_args['is_read'] = $args['is_read'];
		}
		$total_count = $this->storage->count( $count_args );

		// Format notices for output.
		$formatted_notices = array();
		foreach ($notices as $notice) {
			$formatted_notices[] = $this->format_notice($notice);
		}

		wp_send_json_success(
			array(
				'notices'      => $formatted_notices,
				'count'        => count( $formatted_notices ),
				'total_count'  => $total_count,
				'page'         => $page,
				'per_page'     => $per_page,
				'has_more'     => ( $page * $per_page ) < $total_count,
				'unread_total' => $this->storage->get_unread_count(),
			)
		);
	}

	/**
	 * AJAX: Mark notice as read.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_mark_read()
	{
		// Verify nonce.
		check_ajax_referer('notice_vault_ajax_nonce', 'nonce');

		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'notice-vault' ) ) );
			return;
		}

		// Mark as read.
		$result = $this->storage->mark_read( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message'      => __( 'Notice marked as read', 'notice-vault' ),
					'count'        => $this->storage->get_unread_count(),
					'unread_total' => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to mark notice as read', 'notice-vault' ) ) );
		}
	}

	/**
	 * AJAX: Dismiss notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_dismiss_notice()
	{
		// Verify nonce.
		check_ajax_referer('notice_vault_ajax_nonce', 'nonce');

		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'notice-vault' ) ) );
			return;
		}

		// Delete notice.
		$result = $this->storage->delete( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message'      => __( 'Notice dismissed', 'notice-vault' ),
					'count'        => $this->storage->get_unread_count(),
					'unread_total' => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to dismiss notice', 'notice-vault' ) ) );
		}
	}

	/**
	 * AJAX: Mark all notices as read.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_mark_all_read()
	{
		check_ajax_referer('notice_vault_ajax_nonce', 'nonce');

		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Notices are user-scoped in storage, so 'read' is sufficient.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		$result = $this->storage->mark_all_read();

		// mark_all_read returns false when there is nothing to mark; treat that as success.
		wp_send_json_success(
			array(
				'message'      => __( 'All notices marked as read', 'notice-vault' ),
				'count'        => 0,
				'unread_total' => $this->storage->get_unread_count(),
			)
		);
	}

	/**
	 * AJAX: Clear all notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_clear_all()
	{
		check_ajax_referer('notice_vault_ajax_nonce', 'nonce');

		if ( ! \Notice_Vault\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		// Notices are user-scoped in storage, so 'read' is sufficient.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		$this->storage->delete_all();

		// delete_all returns false when nothing changed; treat that as success.
		wp_send_json_success(
			array(
				'message'      => __( 'All notices cleared', 'notice-vault' ),
				'count'        => 0,
				'unread_total' => $this->storage->get_unread_count(),
			)
		);
	}

	/**
	 * Format notice for output.
	 *
	 * @since 1.0.0
	 * @param array $notice Notice data.
	 * @return array Formatted notice.
	 */
	private function format_notice($notice)
	{
		// The stored `html` field is already sanitized through a strict allowlist at
		// capture time (see Notice_Capture::sanitize_notice_html). Re-running wp_kses
		// here is belt-and-braces — defends rows that pre-date the allowlist (when
		// `html` was empty) and rows from any direct callers that bypassed Capture.
		$html = isset( $notice['html'] ) ? (string) $notice['html'] : '';
		if ( '' !== $html ) {
			$html = \Notice_Vault\Notices\Notice_Capture::sanitize_notice_html( $html );
		}

		$type        = $notice['type'];
		$types_index = Notice_Classifier::get_types();
		$type_label  = isset( $types_index[ $type ] ) ? $types_index[ $type ] : ucfirst( $type );

		return array(
			'id'         => $notice['id'],
			'type'       => $type,
			'type_label' => $type_label,
			'content'    => $notice['content'],
			'html'       => $html,
			'is_read'    => ! empty( $notice['is_read'] ),
			'created_at' => isset( $notice['created_at'] ) ? $notice['created_at'] : '',
			'icon'       => Notice_Classifier::get_icon( $notice['type'] ),
			'color'      => Notice_Classifier::get_color( $notice['type'] ),
		);
	}
}
