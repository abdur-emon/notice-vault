<?php
/**
 * Notice Popup Template
 *
 * @package Admin_Notice_Hub
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>

<div id="admin-notice-hub-popup-overlay" class="admin-notice-hub-popup-overlay" style="display: none;" role="presentation">
	<div id="admin-notice-hub-popup" class="admin-notice-hub-popup admin-notice-hub-popup-<?php echo esc_attr($popup_style); ?>" role="dialog" aria-modal="true" aria-labelledby="admin-notice-hub-popup-title" tabindex="-1">
		<!-- Popup Header -->
		<div class="admin-notice-hub-popup-header">
			<h2 class="admin-notice-hub-popup-title" id="admin-notice-hub-popup-title">
				<span class="dashicons dashicons-bell" aria-hidden="true"></span>
				<?php esc_html_e('Notices', 'admin-notice-hub'); ?>
				<span class="admin-notice-hub-notice-count-badge" aria-live="polite" aria-atomic="true">0</span>
			</h2>
			<button type="button" class="admin-notice-hub-close-popup"
				aria-label="<?php esc_attr_e('Close', 'admin-notice-hub'); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<!-- Popup Toolbar -->
		<div class="admin-notice-hub-popup-toolbar">
			<div class="admin-notice-hub-filters">
				<select id="admin-notice-hub-filter-type" class="admin-notice-hub-filter-select">
					<option value="all"><?php esc_html_e('All Types', 'admin-notice-hub'); ?></option>
					<option value="success"><?php esc_html_e('Success', 'admin-notice-hub'); ?></option>
					<option value="error"><?php esc_html_e('Errors', 'admin-notice-hub'); ?></option>
					<option value="warning"><?php esc_html_e('Warnings', 'admin-notice-hub'); ?></option>
					<option value="info"><?php esc_html_e('Info', 'admin-notice-hub'); ?></option>
					<option value="system"><?php esc_html_e('System', 'admin-notice-hub'); ?></option>
					<option value="other"><?php esc_html_e('Other', 'admin-notice-hub'); ?></option>
				</select>

				<label class="admin-notice-hub-checkbox-label">
					<input type="checkbox" id="admin-notice-hub-show-read" class="admin-notice-hub-checkbox">
					<?php esc_html_e('Show Read', 'admin-notice-hub'); ?>
				</label>
			</div>

			<div class="admin-notice-hub-actions">
				<button type="button" class="admin-notice-hub-btn admin-notice-hub-btn-secondary" id="admin-notice-hub-mark-all-read">
					<?php esc_html_e('Mark All Read', 'admin-notice-hub'); ?>
				</button>
				<button type="button" class="admin-notice-hub-btn admin-notice-hub-btn-secondary" id="admin-notice-hub-clear-all">
					<?php esc_html_e('Clear All', 'admin-notice-hub'); ?>
				</button>
			</div>
		</div>

		<!-- Popup Content -->
		<div class="admin-notice-hub-popup-content">
			<div class="admin-notice-hub-loading" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e('Loading notices...', 'admin-notice-hub'); ?></p>
			</div>

			<div class="admin-notice-hub-notices-list" id="admin-notice-hub-notices-list">
				<!-- Notices will be loaded here via AJAX -->
			</div>

			<div class="admin-notice-hub-empty-state" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e('You\'re all caught up! No new notices.', 'admin-notice-hub'); ?></p>
			</div>
		</div>

		<!-- Popup Footer -->
		<div class="admin-notice-hub-popup-footer">
			<a href="<?php echo esc_url( menu_page_url( \Admin_Notice_Hub\Admin\Settings_Page::PAGE_SLUG, false ) ); ?>"
				class="admin-notice-hub-settings-link">
				<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<?php esc_html_e('Settings', 'admin-notice-hub'); ?>
			</a>
		</div>

		<!-- Toast Container -->
		<div id="admin-notice-hub-toast-container" class="admin-notice-hub-toast-container" aria-live="polite" aria-atomic="true"></div>

		<!-- Custom Confirm Modal -->
		<div class="admin-notice-hub-confirm-modal" id="admin-notice-hub-confirm-modal" style="display: none;" role="alertdialog" aria-labelledby="admin-notice-hub-confirm-title" aria-describedby="admin-notice-hub-confirm-message">
			<div class="admin-notice-hub-confirm-content">
				<h3 id="admin-notice-hub-confirm-title"><?php esc_html_e('Confirm Action', 'admin-notice-hub'); ?></h3>
				<p id="admin-notice-hub-confirm-message"><?php esc_html_e('Are you sure?', 'admin-notice-hub'); ?></p>
				<div class="admin-notice-hub-confirm-actions">
					<button type="button" class="admin-notice-hub-btn admin-notice-hub-btn-secondary"
						id="admin-notice-hub-confirm-cancel"><?php esc_html_e('Cancel', 'admin-notice-hub'); ?></button>
					<button type="button" class="admin-notice-hub-btn admin-notice-hub-btn-danger"
						id="admin-notice-hub-confirm-yes"><?php esc_html_e('Clear All', 'admin-notice-hub'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>