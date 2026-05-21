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

<div id="anh-popup-overlay" class="anh-popup-overlay" style="display: none;" role="presentation">
	<div id="anh-popup" class="anh-popup anh-popup-<?php echo esc_attr($popup_style); ?>" role="dialog" aria-modal="true" aria-labelledby="anh-popup-title" tabindex="-1">
		<!-- Popup Header -->
		<div class="anh-popup-header">
			<h2 class="anh-popup-title" id="anh-popup-title">
				<span class="dashicons dashicons-bell" aria-hidden="true"></span>
				<?php esc_html_e('Notices', 'admin-notice-hub'); ?>
				<span class="anh-notice-count-badge" aria-live="polite" aria-atomic="true">0</span>
			</h2>
			<button type="button" class="anh-close-popup"
				aria-label="<?php esc_attr_e('Close', 'admin-notice-hub'); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<!-- Popup Toolbar -->
		<div class="anh-popup-toolbar">
			<div class="anh-filters">
				<select id="anh-filter-type" class="anh-filter-select">
					<option value="all"><?php esc_html_e('All Types', 'admin-notice-hub'); ?></option>
					<option value="success"><?php esc_html_e('Success', 'admin-notice-hub'); ?></option>
					<option value="error"><?php esc_html_e('Errors', 'admin-notice-hub'); ?></option>
					<option value="warning"><?php esc_html_e('Warnings', 'admin-notice-hub'); ?></option>
					<option value="info"><?php esc_html_e('Info', 'admin-notice-hub'); ?></option>
					<option value="system"><?php esc_html_e('System', 'admin-notice-hub'); ?></option>
					<option value="other"><?php esc_html_e('Other', 'admin-notice-hub'); ?></option>
				</select>

				<label class="anh-checkbox-label">
					<input type="checkbox" id="anh-show-read" class="anh-checkbox">
					<?php esc_html_e('Show Read', 'admin-notice-hub'); ?>
				</label>
			</div>

			<div class="anh-actions">
				<button type="button" class="anh-btn anh-btn-secondary" id="anh-mark-all-read">
					<?php esc_html_e('Mark All Read', 'admin-notice-hub'); ?>
				</button>
				<button type="button" class="anh-btn anh-btn-secondary" id="anh-clear-all">
					<?php esc_html_e('Clear All', 'admin-notice-hub'); ?>
				</button>
			</div>
		</div>

		<!-- Popup Content -->
		<div class="anh-popup-content">
			<div class="anh-loading" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e('Loading notices...', 'admin-notice-hub'); ?></p>
			</div>

			<div class="anh-notices-list" id="anh-notices-list">
				<!-- Notices will be loaded here via AJAX -->
			</div>

			<div class="anh-empty-state" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e('You\'re all caught up! No new notices.', 'admin-notice-hub'); ?></p>
			</div>
		</div>

		<!-- Popup Footer -->
		<div class="anh-popup-footer">
			<a href="<?php echo esc_url( menu_page_url( \Admin_Notice_Hub\Admin\Settings_Page::PAGE_SLUG, false ) ); ?>"
				class="anh-settings-link">
				<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<?php esc_html_e('Settings', 'admin-notice-hub'); ?>
			</a>
		</div>

		<!-- Toast Container -->
		<div id="anh-toast-container" class="anh-toast-container" aria-live="polite" aria-atomic="true"></div>

		<!-- Custom Confirm Modal -->
		<div class="anh-confirm-modal" id="anh-confirm-modal" style="display: none;" role="alertdialog" aria-labelledby="anh-confirm-title" aria-describedby="anh-confirm-message">
			<div class="anh-confirm-content">
				<h3 id="anh-confirm-title"><?php esc_html_e('Confirm Action', 'admin-notice-hub'); ?></h3>
				<p id="anh-confirm-message"><?php esc_html_e('Are you sure?', 'admin-notice-hub'); ?></p>
				<div class="anh-confirm-actions">
					<button type="button" class="anh-btn anh-btn-secondary"
						id="anh-confirm-cancel"><?php esc_html_e('Cancel', 'admin-notice-hub'); ?></button>
					<button type="button" class="anh-btn anh-btn-danger"
						id="anh-confirm-yes"><?php esc_html_e('Clear All', 'admin-notice-hub'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>