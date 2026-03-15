<?php
/**
 * Notice Popup Class
 *
 * Handles popup UI rendering and AJAX operations.
 *
 * @package WP_Notice_Manager
 * @subpackage Admin
 */

namespace WP_Notice_Manager\Admin;

use WP_Notice_Manager\Notices\Notice_Storage;
use WP_Notice_Manager\Notices\Notice_Classifier;

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
	 * Enqueue popup assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_assets()
	{
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
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wpnm_ajax_nonce'),
				'popupStyle' => $this->get_popup_style(),
				'i18n' => array(
					'noNotices' => __('No notices to display', 'wp-notice-manager'),
					'markAllRead' => __('Mark All as Read', 'wp-notice-manager'),
					'clearAll' => __('Clear All', 'wp-notice-manager'),
					'confirmClearAll' => __('Are you sure you want to clear all notices?', 'wp-notice-manager'),
					'loading' => __('Loading...', 'wp-notice-manager'),
					'error' => __('An error occurred', 'wp-notice-manager'),
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
	public function render_popup()
	{
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

		// Check capability.
		if (!current_user_can('read')) {
			wp_send_json_error(array('message' => __('Unauthorized', 'wp-notice-manager')));
		}

		// Get filter parameters.
		$filter_type = isset($_POST['filter_type']) ? sanitize_text_field(wp_unslash($_POST['filter_type'])) : '';
		$show_read = isset($_POST['show_read']) && 'true' === $_POST['show_read'];

		// Build query args.
		$args = array();

		if (!empty($filter_type) && 'all' !== $filter_type) {
			$args['type'] = $filter_type;
		}

		// Set read status filter.
		$args['is_read'] = $show_read;

		// Get notices.
		$notices = Notice_Storage::get_all($args);

		// Format notices for output.
		$formatted_notices = array();
		foreach ($notices as $notice) {
			$formatted_notices[] = $this->format_notice($notice);
		}

		wp_send_json_success(
			array(
				'notices' => $formatted_notices,
				'count' => count($formatted_notices),
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

		// Check capability.
		if (!current_user_can('read')) {
			wp_send_json_error(array('message' => __('Unauthorized', 'wp-notice-manager')));
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if (empty($notice_id)) {
			wp_send_json_error(array('message' => __('Invalid notice ID', 'wp-notice-manager')));
		}

		// Mark as read.
		$result = Notice_Storage::mark_read($notice_id);

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __('Notice marked as read', 'wp-notice-manager'),
					'count' => Notice_Storage::get_unread_count(),
				)
			);
		} else {
			wp_send_json_error(array('message' => __('Failed to mark notice as read', 'wp-notice-manager')));
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

		// Check capability.
		if (!current_user_can('read')) {
			wp_send_json_error(array('message' => __('Unauthorized', 'wp-notice-manager')));
		}

		// Get notice ID.
		$notice_id = isset($_POST['notice_id']) ? sanitize_text_field(wp_unslash($_POST['notice_id'])) : '';

		if (empty($notice_id)) {
			wp_send_json_error(array('message' => __('Invalid notice ID', 'wp-notice-manager')));
		}

		// Delete notice.
		$result = Notice_Storage::delete($notice_id);

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __('Notice dismissed', 'wp-notice-manager'),
					'count' => Notice_Storage::get_unread_count(),
				)
			);
		} else {
			wp_send_json_error(array('message' => __('Failed to dismiss notice', 'wp-notice-manager')));
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

		if (!current_user_can('read')) {
			wp_send_json_error(array('message' => __('Unauthorized', 'wp-notice-manager')));
		}

		$result = Notice_Storage::mark_all_read();

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __('All notices marked as read', 'wp-notice-manager'),
					'count' => 0,
				)
			);
		} else {
			wp_send_json_error(array('message' => __('Failed to mark notices as read', 'wp-notice-manager')));
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

		if (!current_user_can('read')) {
			wp_send_json_error(array('message' => __('Unauthorized', 'wp-notice-manager')));
		}

		$result = Notice_Storage::delete_all();

		if ($result) {
			wp_send_json_success(
				array(
					'message' => __('All notices cleared', 'wp-notice-manager'),
					'count' => 0,
				)
			);
		} else {
			wp_send_json_error(array('message' => __('Failed to clear notices', 'wp-notice-manager')));
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

