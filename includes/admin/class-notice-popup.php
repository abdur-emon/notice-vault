<?php
/**
 * Notice Popup Class
 *
 * Handles popup UI rendering and AJAX operations.
 *
 * @package Notice_Tracker
 * @subpackage Admin
 */

namespace Notice_Tracker\Admin;

use Notice_Tracker\Notices\Notice_Storage;
use Notice_Tracker\Notices\Notice_Classifier;

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
	 * @var \Notice_Tracker\Notices\Notice_Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param \Notice_Tracker\Notices\Notice_Storage $storage Notice Storage instance.
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
		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
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
					'noNotices'      => __( 'No notices to display', 'notice-tracker' ),
					'markAllRead'    => __( 'Mark All as Read', 'notice-tracker' ),
					'clearAll'       => __( 'Clear All', 'notice-tracker' ),
					'confirmClearAll' => __( 'Are you sure you want to clear all notices?', 'notice-tracker' ),
					'loading'        => __( 'Loading...', 'notice-tracker' ),
					'error'          => __( 'An error occurred', 'notice-tracker' ),
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
		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
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

		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		// Get filter parameters.
		$filter_type = isset( $_POST['filter_type'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_type'] ) ) : '';
		$show_read   = isset( $_POST['show_read'] ) && 'true' === $_POST['show_read'];

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

		// Set read status filter.
		$args['is_read'] = $show_read;

		// Get notices.
		$notices = $this->storage->get_all( $args );

		// Get total count for pagination.
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
				'notices'     => $formatted_notices,
				'count'       => count( $formatted_notices ),
				'total_count' => $total_count,
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

		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'notice-tracker' ) ) );
			return;
		}

		// Mark as read.
		$result = $this->storage->mark_read( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __( 'Notice marked as read', 'notice-tracker' ),
					'count'   => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to mark notice as read', 'notice-tracker' ) ) );
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

		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if ( empty( $notice_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'notice-tracker' ) ) );
			return;
		}

		// Delete notice.
		$result = $this->storage->delete( $notice_id );

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __( 'Notice dismissed', 'notice-tracker' ),
					'count'   => $this->storage->get_unread_count(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to dismiss notice', 'notice-tracker' ) ) );
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

		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		$result = $this->storage->mark_all_read();

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __( 'All notices marked as read', 'notice-tracker' ),
					'count'   => 0,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to mark notices as read', 'notice-tracker' ) ) );
		}
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

		if ( ! \Notice_Tracker\Permissions\Visibility_Manager::can_see_notices() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-tracker' ) ) );
			return;
		}

		$result = $this->storage->delete_all();

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __( 'All notices cleared', 'notice-tracker' ),
					'count'   => 0,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to clear notices', 'notice-tracker' ) ) );
		}
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
		return array(
			'id' => $notice['id'],
			'type' => $notice['type'],
			'content' => $notice['content'],
			'html' => $notice['html'],
			'is_read' => $notice['is_read'],
			'created_at' => $notice['created_at'],
			'icon' => Notice_Classifier::get_icon($notice['type']),
			'color' => Notice_Classifier::get_color($notice['type']),
		);
	}
}
