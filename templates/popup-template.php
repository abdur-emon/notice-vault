<?php
/**
 * Notice Popup Template
 *
 * @package WP_Notice_Manager
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
				<?php esc_html_e('Notices', 'wp-notice-manager'); ?>
				<span class="wpnm-notice-count-badge" aria-live="polite" aria-atomic="true">0</span>
			</h2>
			<button type="button" class="wpnm-close-popup"
				aria-label="<?php esc_attr_e('Close', 'wp-notice-manager'); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<!-- Popup Toolbar -->
		<div class="wpnm-popup-toolbar">
			<div class="wpnm-filters">
				<select id="wpnm-filter-type" class="wpnm-filter-select">
					<option value="all"><?php esc_html_e('All Types', 'wp-notice-manager'); ?></option>
					<option value="success"><?php esc_html_e('Success', 'wp-notice-manager'); ?></option>
					<option value="error"><?php esc_html_e('Errors', 'wp-notice-manager'); ?></option>
					<option value="warning"><?php esc_html_e('Warnings', 'wp-notice-manager'); ?></option>
					<option value="info"><?php esc_html_e('Info', 'wp-notice-manager'); ?></option>
					<option value="system"><?php esc_html_e('System', 'wp-notice-manager'); ?></option>
					<option value="other"><?php esc_html_e('Other', 'wp-notice-manager'); ?></option>
				</select>

				<label class="wpnm-checkbox-label">
					<input type="checkbox" id="wpnm-show-read" class="wpnm-checkbox">
					<?php esc_html_e('Show Read', 'wp-notice-manager'); ?>
				</label>
			</div>

			<div class="wpnm-actions">
				<button type="button" class="wpnm-btn wpnm-btn-secondary" id="wpnm-mark-all-read">
					<?php esc_html_e('Mark All Read', 'wp-notice-manager'); ?>
				</button>
				<button type="button" class="wpnm-btn wpnm-btn-secondary" id="wpnm-clear-all">
					<?php esc_html_e('Clear All', 'wp-notice-manager'); ?>
				</button>
			</div>
		</div>

		<!-- Popup Content -->
		<div class="wpnm-popup-content">
			<div class="wpnm-loading" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e('Loading notices...', 'wp-notice-manager'); ?></p>
			</div>

			<div class="wpnm-notices-list" id="wpnm-notices-list">
				<!-- Notices will be loaded here via AJAX -->
			</div>

			<div class="wpnm-empty-state" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e('You\'re all caught up! No new notices.', 'wp-notice-manager'); ?></p>
			</div>
		</div>

		<!-- Popup Footer -->
		<div class="wpnm-popup-footer">
			<a href="<?php echo esc_url(admin_url('options-general.php?page=wp-notice-manager')); ?>"
				class="wpnm-settings-link">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e('Settings', 'wp-notice-manager'); ?>
			</a>
		</div>

		<!-- Toast Container -->
		<div id="wpnm-toast-container" class="wpnm-toast-container"></div>

		<!-- Custom Confirm Modal -->
		<div class="wpnm-confirm-modal" id="wpnm-confirm-modal" style="display: none;">
			<div class="wpnm-confirm-content">
				<h3><?php esc_html_e('Confirm Action', 'wp-notice-manager'); ?></h3>
				<p id="wpnm-confirm-message"><?php esc_html_e('Are you sure?', 'wp-notice-manager'); ?></p>
				<div class="wpnm-confirm-actions">
					<button type="button" class="wpnm-btn wpnm-btn-secondary"
						id="wpnm-confirm-cancel"><?php esc_html_e('Cancel', 'wp-notice-manager'); ?></button>
					<button type="button" class="wpnm-btn wpnm-btn-danger"
						id="wpnm-confirm-yes"><?php esc_html_e('Clear All', 'wp-notice-manager'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>