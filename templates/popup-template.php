<?php
/**
 * Notice Popup Template
 *
 * @package Notice_Tracker
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>

<div id="wpnm-popup-overlay" class="wpnm-popup-overlay" style="display: none;">
	<div id="wpnm-popup" class="wpnm-popup wpnm-popup-<?php echo esc_attr($popup_style); ?>">
		<!-- Popup Header -->
		<div class="wpnm-popup-header">
			<h2 class="wpnm-popup-title">
				<span class="dashicons dashicons-bell"></span>
				<?php esc_html_e('Notices', 'Notice-Tracker'); ?>
				<span class="wpnm-notice-count-badge" aria-live="polite" aria-atomic="true">0</span>
			</h2>
			<button type="button" class="wpnm-close-popup"
				aria-label="<?php esc_attr_e('Close', 'Notice-Tracker'); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<!-- Popup Toolbar -->
		<div class="wpnm-popup-toolbar">
			<div class="wpnm-filters">
				<select id="wpnm-filter-type" class="wpnm-filter-select">
					<option value="all"><?php esc_html_e('All Types', 'Notice-Tracker'); ?></option>
					<option value="success"><?php esc_html_e('Success', 'Notice-Tracker'); ?></option>
					<option value="error"><?php esc_html_e('Errors', 'Notice-Tracker'); ?></option>
					<option value="warning"><?php esc_html_e('Warnings', 'Notice-Tracker'); ?></option>
					<option value="info"><?php esc_html_e('Info', 'Notice-Tracker'); ?></option>
					<option value="system"><?php esc_html_e('System', 'Notice-Tracker'); ?></option>
					<option value="other"><?php esc_html_e('Other', 'Notice-Tracker'); ?></option>
				</select>

				<label class="wpnm-checkbox-label">
					<input type="checkbox" id="wpnm-show-read" class="wpnm-checkbox">
					<?php esc_html_e('Show Read', 'Notice-Tracker'); ?>
				</label>
			</div>

			<div class="wpnm-actions">
				<button type="button" class="wpnm-btn wpnm-btn-secondary" id="wpnm-mark-all-read">
					<?php esc_html_e('Mark All Read', 'Notice-Tracker'); ?>
				</button>
				<button type="button" class="wpnm-btn wpnm-btn-secondary" id="wpnm-clear-all">
					<?php esc_html_e('Clear All', 'Notice-Tracker'); ?>
				</button>
			</div>
		</div>

		<!-- Popup Content -->
		<div class="wpnm-popup-content">
			<div class="wpnm-loading" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e('Loading notices...', 'Notice-Tracker'); ?></p>
			</div>

			<div class="wpnm-notices-list" id="wpnm-notices-list">
				<!-- Notices will be loaded here via AJAX -->
			</div>

			<div class="wpnm-empty-state" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e('You\'re all caught up! No new notices.', 'Notice-Tracker'); ?></p>
			</div>
		</div>

		<!-- Popup Footer -->
		<div class="wpnm-popup-footer">
			<a href="<?php echo esc_url(admin_url('options-general.php?page=notice-tracker')); ?>"
				class="wpnm-settings-link">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e('Settings', 'Notice-Tracker'); ?>
			</a>
		</div>

		<!-- Toast Container -->
		<div id="wpnm-toast-container" class="wpnm-toast-container"></div>

		<!-- Custom Confirm Modal -->
		<div class="wpnm-confirm-modal" id="wpnm-confirm-modal" style="display: none;">
			<div class="wpnm-confirm-content">
				<h3><?php esc_html_e('Confirm Action', 'Notice-Tracker'); ?></h3>
				<p id="wpnm-confirm-message"><?php esc_html_e('Are you sure?', 'Notice-Tracker'); ?></p>
				<div class="wpnm-confirm-actions">
					<button type="button" class="wpnm-btn wpnm-btn-secondary"
						id="wpnm-confirm-cancel"><?php esc_html_e('Cancel', 'Notice-Tracker'); ?></button>
					<button type="button" class="wpnm-btn wpnm-btn-danger"
						id="wpnm-confirm-yes"><?php esc_html_e('Clear All', 'Notice-Tracker'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>