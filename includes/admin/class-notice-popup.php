<?php
/**
 * Notice Popup Class
 *
 * Handles popup UI rendering and AJAX operations.
 *
 * @package Admin_Notice_Hub
 * @subpackage Admin
 */

namespace Admin_Notice_Hub\Admin;

use Admin_Notice_Hub\Notices\Notice_Storage;
use Admin_Notice_Hub\Notices\Notice_Classifier;

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
	 * @var \Admin_Notice_Hub\Notices\Notice_Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param \Admin_Notice_Hub\Notices\Notice_Storage $storage Notice Storage instance.
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
		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'anh-popup',
			ANH_PLUGIN_URL . 'assets/css/popup.css',
			array(),
			ANH_VERSION
		);

		// Enqueue JS.
		wp_enqueue_script(
			'anh-popup',
			ANH_PLUGIN_URL . 'assets/js/popup.js',
			array('jquery'),
			ANH_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'anh-popup',
			'anhPopup',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'anh_ajax_nonce' ),
				'popupStyle' => $this->get_popup_style(),
				'i18n'       => array(
					'noNotices'       => __( 'No notices to display', 'admin-notice-hub' ),
					'markAllRead'     => __( 'Mark All as Read', 'admin-notice-hub' ),
					'clearAll'        => __( 'Clear All', 'admin-notice-hub' ),
					'confirmClearAll' => __( 'Are you sure you want to clear all notices?', 'admin-notice-hub' ),
					'loading'         => __( 'Loading...', 'admin-notice-hub' ),
					'error'           => __( 'An error occurred', 'admin-notice-hub' ),
					'markAsRead'      => __( 'Mark as read', 'admin-notice-hub' ),
					'dismiss'         => __( 'Dismiss', 'admin-notice-hub' ),
					'dismissNotice'   => __( 'Dismiss notice', 'admin-notice-hub' ),
					'notices'         => __( 'Notices', 'admin-notice-hub' ),
					/* translators: %d: number of unread notices. */
					'noticesWithCount' => __( 'Notices (%d)', 'admin-notice-hub' ),
					'justNow'         => __( 'Just now', 'admin-notice-hub' ),
					/* translators: %d: number of minutes ago. */
					'minutesAgo'      => __( '%d minutes ago', 'admin-notice-hub' ),
					/* translators: %d: number of hours ago. */
					'hoursAgo'        => __( '%d hours ago', 'admin-notice-hub' ),
					/* translators: %d: number of days ago. */
					'daysAgo'         => __( '%d days ago', 'admin-notice-hub' ),
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
		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		$popup_style = $this->get_popup_style();
		include ANH_PLUGIN_DIR . 'templates/popup-template.php';
	}

	/**
	 * Get popup style from settings.
	 *
	 * @since 1.0.0
	 * @return string Popup style.
	 */
	private function get_popup_style()
	{
		$settings = get_option('anh_settings', array());
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
		check_ajax_referer('anh_ajax_nonce', 'nonce');

		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
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

		// Get total count for pagination (same filters, no limit/offset).
		$total_args           = $args;
		$total_args['limit']  = 0;
		$total_args['offset'] = 0;
		$total_count          = count( $this->storage->get_all( $total_args ) );

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
		check_ajax_referer('anh_ajax_nonce', 'nonce');

		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'admin-notice-hub' ) ) );
			return;
		}

		// Mark as read.
		$result = $this->storage->mark_read( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message'      => __( 'Notice marked as read', 'admin-notice-hub' ),
					'count'        => $this->storage->get_unread_count(),
					'unread_total' => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to mark notice as read', 'admin-notice-hub' ) ) );
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
		check_ajax_referer('anh_ajax_nonce', 'nonce');

		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'admin-notice-hub' ) ) );
			return;
		}

		// Delete notice.
		$result = $this->storage->delete( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message'      => __( 'Notice dismissed', 'admin-notice-hub' ),
					'count'        => $this->storage->get_unread_count(),
					'unread_total' => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to dismiss notice', 'admin-notice-hub' ) ) );
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
		check_ajax_referer('anh_ajax_nonce', 'nonce');

		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Notices are user-scoped in storage, so 'read' is sufficient.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		$result = $this->storage->mark_all_read();

		// mark_all_read returns false when there is nothing to mark; treat that as success.
		wp_send_json_success(
			array(
				'message'      => __( 'All notices marked as read', 'admin-notice-hub' ),
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
		check_ajax_referer('anh_ajax_nonce', 'nonce');

		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		// Notices are user-scoped in storage, so 'read' is sufficient.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
			return;
		}

		$this->storage->delete_all();

		// delete_all returns false when nothing changed; treat that as success.
		wp_send_json_success(
			array(
				'message'      => __( 'All notices cleared', 'admin-notice-hub' ),
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
		// 'html' intentionally omitted from the AJAX payload. The popup only renders
		// the stripped 'content' field; shipping the full HTML wastes bandwidth and
		// widens the trust surface if a future UI ever decides to render it.
		return array(
			'id'         => $notice['id'],
			'type'       => $notice['type'],
			'content'    => $notice['content'],
			'is_read'    => ! empty( $notice['is_read'] ),
			'created_at' => isset( $notice['created_at'] ) ? $notice['created_at'] : '',
			'icon'       => Notice_Classifier::get_icon( $notice['type'] ),
			'color'      => Notice_Classifier::get_color( $notice['type'] ),
		);
	}
}
