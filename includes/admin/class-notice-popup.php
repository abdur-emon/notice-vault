<?php
/**
 * Notice Popup Class
 *
 * Handles popup UI rendering and AJAX operations.
 *
 * @package Quietboard_Notice_Manager
 * @subpackage Admin
 */

namespace Quietboard_Notice_Manager\Admin;

use Quietboard_Notice_Manager\Notices\Notice_Storage;
use Quietboard_Notice_Manager\Notices\Notice_Classifier;

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
	 * @var \Quietboard_Notice_Manager\Notices\Notice_Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param \Quietboard_Notice_Manager\Notices\Notice_Storage $storage Notice Storage instance.
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
		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'wpnm-popup',
			WPNM_PLUGIN_URL . 'assets/css/popup.css',
			array(),
			WPNM_VERSION
		);

		// Enqueue JS.
		wp_enqueue_script(
			'wpnm-popup',
			WPNM_PLUGIN_URL . 'assets/js/popup.js',
			array('jquery'),
			WPNM_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'wpnm-popup',
			'wpnmPopup',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'wpnm_ajax_nonce' ),
				'popupStyle' => $this->get_popup_style(),
				'i18n'       => array(
					'noNotices'       => __( 'No notices to display', 'quietboard-notice-manager' ),
					'markAllRead'     => __( 'Mark All as Read', 'quietboard-notice-manager' ),
					'clearAll'        => __( 'Clear All', 'quietboard-notice-manager' ),
					'confirmClearAll' => __( 'Are you sure you want to clear all notices?', 'quietboard-notice-manager' ),
					'loading'         => __( 'Loading...', 'quietboard-notice-manager' ),
					'error'           => __( 'An error occurred', 'quietboard-notice-manager' ),
					'markAsRead'      => __( 'Mark as read', 'quietboard-notice-manager' ),
					'dismiss'         => __( 'Dismiss', 'quietboard-notice-manager' ),
					'dismissNotice'   => __( 'Dismiss notice', 'quietboard-notice-manager' ),
					'notices'         => __( 'Notices', 'quietboard-notice-manager' ),
					/* translators: %d: number of unread notices. */
					'noticesWithCount' => __( 'Notices (%d)', 'quietboard-notice-manager' ),
					'justNow'         => __( 'Just now', 'quietboard-notice-manager' ),
					/* translators: %d: number of minutes ago. */
					'minutesAgo'      => __( '%d minutes ago', 'quietboard-notice-manager' ),
					/* translators: %d: number of hours ago. */
					'hoursAgo'        => __( '%d hours ago', 'quietboard-notice-manager' ),
					/* translators: %d: number of days ago. */
					'daysAgo'         => __( '%d days ago', 'quietboard-notice-manager' ),
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
		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		$popup_style = $this->get_popup_style();
		include WPNM_PLUGIN_DIR . 'templates/popup-template.php';
	}

	/**
	 * Get popup style from settings.
	 *
	 * @since 1.0.0
	 * @return string Popup style.
	 */
	private function get_popup_style()
	{
		$settings = get_option('wpnm_settings', array());
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
		check_ajax_referer('wpnm_ajax_nonce', 'nonce');

		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
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
		check_ajax_referer('wpnm_ajax_nonce', 'nonce');

		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Mark as read.
		$result = $this->storage->mark_read( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message'      => __( 'Notice marked as read', 'quietboard-notice-manager' ),
					'count'        => $this->storage->get_unread_count(),
					'unread_total' => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to mark notice as read', 'quietboard-notice-manager' ) ) );
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
		check_ajax_referer('wpnm_ajax_nonce', 'nonce');

		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Delete notice.
		$result = $this->storage->delete( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message'      => __( 'Notice dismissed', 'quietboard-notice-manager' ),
					'count'        => $this->storage->get_unread_count(),
					'unread_total' => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to dismiss notice', 'quietboard-notice-manager' ) ) );
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
		check_ajax_referer('wpnm_ajax_nonce', 'nonce');

		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Notices are user-scoped in storage, so 'read' is sufficient.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		$result = $this->storage->mark_all_read();

		// mark_all_read returns false when there is nothing to mark; treat that as success.
		wp_send_json_success(
			array(
				'message'      => __( 'All notices marked as read', 'quietboard-notice-manager' ),
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
		check_ajax_referer('wpnm_ajax_nonce', 'nonce');

		if ( ! \Quietboard_Notice_Manager\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		// Notices are user-scoped in storage, so 'read' is sufficient.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quietboard-notice-manager' ) ) );
			return;
		}

		$this->storage->delete_all();

		// delete_all returns false when nothing changed; treat that as success.
		wp_send_json_success(
			array(
				'message'      => __( 'All notices cleared', 'quietboard-notice-manager' ),
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
