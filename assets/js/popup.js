/**
 * Notice Vault - Popup JavaScript
 *
 * @package Notice_Vault
 */

(function ($) {
	'use strict';

	/**
	 * Notice Popup Handler
	 */
	const NoticePopup = {
		previousFocus: null,

		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function () {
			// Open popup when clicking toolbar item
			$(document).on('click', '#wp-admin-bar-notice-vault-notices > a, .notice-vault-view-all', function (e) {
				e.preventDefault();
				NoticePopup.openPopup();
			});

			// Close popup
			$(document).on('click', '.notice-vault-close-popup', function (e) {
				NoticePopup.closePopup();
			});

			$(document).on('click', '.notice-vault-popup-overlay', function (e) {
				if (e.target === this) {
					NoticePopup.closePopup();
				}
			});

			// ESC key to close
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape' && $('#notice-vault-popup-overlay').hasClass('notice-vault-active')) {
					NoticePopup.closePopup();
				}
			});

			// Filter change
			$(document).on('change', '#notice-vault-filter-type, #notice-vault-show-read', function () {
				NoticePopup.loadNotices();
			});

			// Mark as read
			$(document).on('click', '.notice-vault-mark-read', function (e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.markRead(noticeId);
			});

			// Dismiss notice
			$(document).on('click', '.notice-vault-dismiss', function (e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.dismissNotice(noticeId);
			});

			// Mark all read
			$(document).on('click', '#notice-vault-mark-all-read', function (e) {
				e.preventDefault();
				NoticePopup.markAllRead();
			});

			// Clear all
			$(document).on('click', '#notice-vault-clear-all', function (e) {
				e.preventDefault();
				$('#notice-vault-confirm-modal').fadeIn(200);
			});

			// Modal Confirm Cancel
			$(document).on('click', '#notice-vault-confirm-cancel', function () {
				$('#notice-vault-confirm-modal').fadeOut(200);
			});

			// Modal Confirm Yes
			$(document).on('click', '#notice-vault-confirm-yes', function () {
				$('#notice-vault-confirm-modal').fadeOut(200);
				NoticePopup.clearAll();
			});

			// Focus Trapping within Popup
			$(document).on('keydown', '#notice-vault-popup', function (e) {
				if (e.key === 'Tab') {
					NoticePopup.trapFocus(e);
				}
			});
		},

		/**
		 * Trap focus inside popup
		 */
		trapFocus: function (e) {
			const focusableElements = $('#notice-vault-popup').find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible').not('#notice-vault-confirm-modal *');
			if (focusableElements.length === 0) return;

			const firstElement = focusableElements[0];
			const lastElement = focusableElements[focusableElements.length - 1];

			if (e.shiftKey) { // Shift + Tab
				if (document.activeElement === firstElement) {
					lastElement.focus();
					e.preventDefault();
				}
			} else { // Tab
				if (document.activeElement === lastElement) {
					firstElement.focus();
					e.preventDefault();
				}
			}
		},

		/**
		 * Open popup
		 */
		openPopup: function () {
			this.previousFocus = document.activeElement;
			$('#notice-vault-popup-overlay').show();
			setTimeout(function () {
				$('#notice-vault-popup-overlay').addClass('notice-vault-active');
				// Focus the close button for accessibility
				$('#notice-vault-popup-overlay').find('.notice-vault-close-popup').focus();
			}, 10);
			this.loadNotices();
		},

		/**
		 * Close popup
		 */
		closePopup: function () {
			$('#notice-vault-popup-overlay').removeClass('notice-vault-active');
			setTimeout(function () {
				$('#notice-vault-popup-overlay').hide();
				// Restore focus
				if (NoticePopup.previousFocus) {
					NoticePopup.previousFocus.focus();
				}
			}, 300);
		},

		/**
		 * Load notices via AJAX
		 */
		loadNotices: function () {
			const filterType = $('#notice-vault-filter-type').val();
			const showRead = $('#notice-vault-show-read').is(':checked');

			$('.notice-vault-loading').show();
			$('.notice-vault-notices-list').hide();
			$('.notice-vault-empty-state').hide();

			$.ajax({
				url: noticeVaultPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'notice_vault_get_notices',
					nonce: noticeVaultPopup.nonce,
					filter_type: filterType,
					show_read: showRead
				},
				success: function (response) {
					$('.notice-vault-loading').hide();

					if (response.success && response.data.notices.length > 0) {
						NoticePopup.renderNotices(response.data.notices);
						$('.notice-vault-notices-list').show();
					} else {
						$('.notice-vault-empty-state').show();
					}

					if (response.success && response.data) {
						// In-popup badge tracks unread total (independent of active filter).
						NoticePopup.updateCount(response.data.unread_total);
						NoticePopup.updateToolbarCount(response.data.unread_total);
					}
				},
				error: function () {
					$('.notice-vault-loading').hide();
					NoticePopup.showToast(noticeVaultPopup.i18n.error, 'error');
				}
			});
		},

		/**
		 * Render notices
		 */
		renderNotices: function (notices) {
			const $list = $('#notice-vault-notices-list');
			$list.empty();

			notices.forEach(function (notice) {
				const $item = NoticePopup.createNoticeItem(notice);
				$list.append($item);
			});
		},

		/**
		 * Create notice item HTML.
		 *
		 * Notice content is appended via .text() so any literal <, >, & in the
		 * stripped notice copy can never be re-interpreted as HTML.
		 */
		createNoticeItem: function (notice) {
			const readClass = notice.is_read ? 'notice-vault-notice-read' : '';
			const typeClass = 'notice-vault-notice-' + notice.type;
			const timeAgo = NoticePopup.timeAgo(notice.created_at);
			const i18n = noticeVaultPopup.i18n || {};

			const $item = $('<div>')
				.addClass('notice-vault-notice-item ' + typeClass + ' ' + readClass)
				.attr('data-notice-id', notice.id);

			const $header = $('<div class="notice-vault-notice-header">');
			$header.append(
				$('<div class="notice-vault-notice-type">')
					.append($('<span class="dashicons">').addClass(notice.icon).attr('aria-hidden', 'true'))
					.append(document.createTextNode(' ' + notice.type))
			);

			const $actions = $('<div class="notice-vault-notice-actions">');
			if (!notice.is_read) {
				$actions.append(
					$('<button type="button" class="notice-vault-notice-action notice-vault-mark-read">')
						.attr('data-notice-id', notice.id)
						.attr('title', i18n.markAsRead || 'Mark as read')
						.attr('aria-label', i18n.markAsRead || 'Mark as read')
						.append('<span class="dashicons dashicons-yes" aria-hidden="true"></span>')
				);
			}
			$actions.append(
				$('<button type="button" class="notice-vault-notice-action notice-vault-dismiss">')
					.attr('data-notice-id', notice.id)
					.attr('title', i18n.dismiss || 'Dismiss')
					.attr('aria-label', i18n.dismissNotice || 'Dismiss notice')
					.append('<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>')
			);
			$header.append($actions);

			$item.append($header);
			$item.append($('<div class="notice-vault-notice-content">').text(notice.content || ''));
			$item.append(
				$('<div class="notice-vault-notice-meta">').append(
					$('<div class="notice-vault-notice-time">')
						.append('<span class="dashicons dashicons-clock" aria-hidden="true"></span>')
						.append(document.createTextNode(' ' + timeAgo))
				)
			);

			return $item;
		},

		/**
		 * Mark notice as read
		 */
		markRead: function (noticeId) {
			$.ajax({
				url: noticeVaultPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'notice_vault_mark_read',
					nonce: noticeVaultPopup.nonce,
					notice_id: noticeId
				},
				success: function (response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').addClass('notice-vault-notice-read');
						$('[data-notice-id="' + noticeId + '"] .notice-vault-mark-read').remove();
						NoticePopup.updateToolbarCount(response.data.unread_total);
					}
				}
			});
		},

		/**
		 * Dismiss notice
		 */
		dismissNotice: function (noticeId) {
			$.ajax({
				url: noticeVaultPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'notice_vault_dismiss_notice',
					nonce: noticeVaultPopup.nonce,
					notice_id: noticeId
				},
				success: function (response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').fadeOut(300, function () {
							$(this).slideUp(200, function () {
								$(this).remove();
								if ($('.notice-vault-notice-item').length === 0) {
									$('.notice-vault-empty-state').fadeIn(200);
								}
							});
						});
						NoticePopup.updateToolbarCount(response.data.unread_total);
					}
				}
			});
		},

		/**
		 * Mark all as read (single bulk AJAX call)
		 */
		markAllRead: function () {
			$.ajax({
				url: noticeVaultPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'notice_vault_mark_all_read',
					nonce: noticeVaultPopup.nonce
				},
				success: function (response) {
					if (response.success) {
						$('.notice-vault-notice-item').addClass('notice-vault-notice-read');
						$('.notice-vault-mark-read').remove();
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast(response.data.message || noticeVaultPopup.i18n.markAllRead);
					}
				}
			});
		},

		/**
		 * Clear all notices (single bulk AJAX call)
		 */
		clearAll: function () {
			$.ajax({
				url: noticeVaultPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'notice_vault_clear_all',
					nonce: noticeVaultPopup.nonce
				},
				success: function (response) {
					if (response.success) {
						$('#notice-vault-notices-list').empty();
						$('.notice-vault-empty-state').show();
						$('.notice-vault-notices-list').hide();
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast(response.data.message || noticeVaultPopup.i18n.clearAll);
					}
				}
			});
		},

		/**
		 * Update count badge
		 */
		updateCount: function (count) {
			$('.notice-vault-notice-count-badge').text(count);
		},

		/**
		 * Update toolbar count
		 */
		updateToolbarCount: function (count) {
			const i18n = noticeVaultPopup.i18n || {};
			const $label = $('#wp-admin-bar-notice-vault-notices .ab-label');
			const num = parseInt(count, 10) || 0;

			if (num > 0) {
				const tmpl = i18n.noticesWithCount || 'Notices (%d)';
				$label.text(tmpl.replace('%d', num));
				$('.notice-vault-count-badge').text(num).show();
			} else {
				$label.text(i18n.notices || 'Notices');
				$('.notice-vault-count-badge').hide();
			}
			this.updateCount(num);
		},

		/**
		 * Time ago helper (translatable via noticeVaultPopup.i18n).
		 */
		timeAgo: function (datetime) {
			const i18n = noticeVaultPopup.i18n || {};
			const now = new Date();
			// Stored datetimes are UTC (PHP current_time('mysql', true));
			// force UTC parsing so "X minutes ago" is right regardless of TZ.
			const past = new Date(datetime.replace(' ', 'T') + 'Z');
			const seconds = Math.floor((now - past) / 1000);
			const sprintf = function (tmpl, n) {
				return (tmpl || '').replace('%d', n);
			};

			if (isNaN(past.getTime())) return '';
			if (seconds < 60) return i18n.justNow || 'Just now';
			if (seconds < 3600) return sprintf(i18n.minutesAgo, Math.floor(seconds / 60));
			if (seconds < 86400) return sprintf(i18n.hoursAgo, Math.floor(seconds / 3600));
			if (seconds < 604800) return sprintf(i18n.daysAgo, Math.floor(seconds / 86400));
			return past.toLocaleDateString();
		},

		/**
		 * Show Toast Notification
		 */
		showToast: function (message, type = 'success') {
			const $toast = $('<div>')
				.addClass('notice-vault-toast notice-vault-toast-' + type)
				.text(message);

			$('#notice-vault-toast-container').append($toast);

			// Trigger reflow for transition
			$toast[0].offsetHeight;
			$toast.addClass('notice-vault-toast-show');

			setTimeout(function () {
				$toast.removeClass('notice-vault-toast-show');
				setTimeout(function () {
					$toast.remove();
				}, 300);
			}, 3000);
		}
	};

	// Initialize on document ready
	$(document).ready(function () {
		NoticePopup.init();
	});

})(jQuery);

