<?php
/**
 * Notice Popup Template
 *
 * @package Notice_Vault
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>

<div id="notice-vault-popup-overlay" class="notice-vault-popup-overlay" style="display: none;" role="presentation">
	<div id="notice-vault-popup" class="notice-vault-popup notice-vault-popup-<?php echo esc_attr($popup_style); ?>" role="dialog" aria-modal="true" aria-labelledby="notice-vault-popup-title" tabindex="-1">
		<!-- Popup Header -->
		<div class="notice-vault-popup-header">
			<h2 class="notice-vault-popup-title" id="notice-vault-popup-title">
				<span class="dashicons dashicons-bell" aria-hidden="true"></span>
				<?php esc_html_e('Notices', 'notice-vault'); ?>
				<span class="notice-vault-notice-count-badge" aria-live="polite" aria-atomic="true">0</span>
			</h2>
			<button type="button" class="notice-vault-close-popup"
				aria-label="<?php esc_attr_e('Close', 'notice-vault'); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<!-- Popup Toolbar -->
		<div class="notice-vault-popup-toolbar">
			<div class="notice-vault-filters">
				<select id="notice-vault-filter-type" class="notice-vault-filter-select">
					<option value="all"><?php esc_html_e( 'All Types', 'notice-vault' ); ?></option>
					<?php
					// Render filter options from the same filterable type list the settings
					// page uses, so a third party that registers a custom bucket via
					// `notice_vault_notice_types` automatically gets a filter entry too.
					foreach ( \Notice_Vault\Notices\Notice_Classifier::get_types() as $notice_vault_type_key => $notice_vault_type_label ) {
						printf(
							'<option value="%1$s">%2$s</option>',
							esc_attr( $notice_vault_type_key ),
							esc_html( $notice_vault_type_label )
						);
					}
					?>
				</select>

				<label class="notice-vault-checkbox-label">
					<input type="checkbox" id="notice-vault-show-read" class="notice-vault-checkbox">
					<?php esc_html_e( 'Show Read', 'notice-vault' ); ?>
				</label>
			</div>

			<div class="notice-vault-actions">
				<button type="button" class="notice-vault-btn notice-vault-btn-secondary" id="notice-vault-mark-all-read">
					<?php esc_html_e('Mark All Read', 'notice-vault'); ?>
				</button>
				<button type="button" class="notice-vault-btn notice-vault-btn-secondary" id="notice-vault-clear-all">
					<?php esc_html_e('Clear All', 'notice-vault'); ?>
				</button>
			</div>
		</div>

		<!-- Popup Content -->
		<div class="notice-vault-popup-content">
			<div class="notice-vault-loading" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e('Loading notices...', 'notice-vault'); ?></p>
			</div>

			<div class="notice-vault-notices-list" id="notice-vault-notices-list">
				<!-- Notices will be loaded here via AJAX -->
			</div>

			<div class="notice-vault-load-more-wrap" id="notice-vault-load-more-wrap" style="display: none;">
				<button type="button"
					class="notice-vault-btn notice-vault-btn-secondary notice-vault-load-more"
					id="notice-vault-load-more">
					<?php esc_html_e( 'Load more', 'notice-vault' ); ?>
				</button>
			</div>

			<div class="notice-vault-empty-state" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e( 'You\'re all caught up! No new notices.', 'notice-vault' ); ?></p>
			</div>
		</div>

		<!-- Popup Footer -->
		<div class="notice-vault-popup-footer">
			<a href="<?php echo esc_url( menu_page_url( \Notice_Vault\Admin\Settings_Page::PAGE_SLUG, false ) ); ?>"
				class="notice-vault-settings-link">
				<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<?php esc_html_e('Settings', 'notice-vault'); ?>
			</a>
		</div>

		<!-- Toast Container -->
		<div id="notice-vault-toast-container" class="notice-vault-toast-container" aria-live="polite" aria-atomic="true"></div>

		<!-- Custom Confirm Modal -->
		<div class="notice-vault-confirm-modal" id="notice-vault-confirm-modal" style="display: none;" role="alertdialog" aria-labelledby="notice-vault-confirm-title" aria-describedby="notice-vault-confirm-message">
			<div class="notice-vault-confirm-content">
				<h3 id="notice-vault-confirm-title"><?php esc_html_e( 'Clear All Notices?', 'notice-vault' ); ?></h3>
				<p id="notice-vault-confirm-message"><?php esc_html_e( 'Are you sure you want to clear all notices? This cannot be undone.', 'notice-vault' ); ?></p>
				<div class="notice-vault-confirm-actions">
					<button type="button" class="notice-vault-btn notice-vault-btn-secondary"
						id="notice-vault-confirm-cancel"><?php esc_html_e( 'Cancel', 'notice-vault' ); ?></button>
					<button type="button" class="notice-vault-btn notice-vault-btn-danger"
						id="notice-vault-confirm-yes"><?php esc_html_e( 'Clear All', 'notice-vault' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>