/**
 * Admin Notice Hub - Popup JavaScript
 *
 * @package Admin_Notice_Hub
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
			$(document).on('click', '#wp-admin-bar-admin-notice-hub-notices > a, .admin-notice-hub-view-all', function (e) {
				e.preventDefault();
				NoticePopup.openPopup();
			});

			// Close popup
			$(document).on('click', '.admin-notice-hub-close-popup', function (e) {
				NoticePopup.closePopup();
			});

			$(document).on('click', '.admin-notice-hub-popup-overlay', function (e) {
				if (e.target === this) {
					NoticePopup.closePopup();
				}
			});

			// ESC key to close
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape' && $('#admin-notice-hub-popup-overlay').hasClass('admin-notice-hub-active')) {
					NoticePopup.closePopup();
				}
			});

			// Filter change
			$(document).on('change', '#admin-notice-hub-filter-type, #admin-notice-hub-show-read', function () {
				NoticePopup.loadNotices();
			});

			// Mark as read
			$(document).on('click', '.admin-notice-hub-mark-read', function (e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.markRead(noticeId);
			});

			// Dismiss notice
			$(document).on('click', '.admin-notice-hub-dismiss', function (e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.dismissNotice(noticeId);
			});

			// Mark all read
			$(document).on('click', '#admin-notice-hub-mark-all-read', function (e) {
				e.preventDefault();
				NoticePopup.markAllRead();
			});

			// Clear all
			$(document).on('click', '#admin-notice-hub-clear-all', function (e) {
				e.preventDefault();
				$('#admin-notice-hub-confirm-modal').fadeIn(200);
			});

			// Modal Confirm Cancel
			$(document).on('click', '#admin-notice-hub-confirm-cancel', function () {
				$('#admin-notice-hub-confirm-modal').fadeOut(200);
			});

			// Modal Confirm Yes
			$(document).on('click', '#admin-notice-hub-confirm-yes', function () {
				$('#admin-notice-hub-confirm-modal').fadeOut(200);
				NoticePopup.clearAll();
			});

			// Focus Trapping within Popup
			$(document).on('keydown', '#admin-notice-hub-popup', function (e) {
				if (e.key === 'Tab') {
					NoticePopup.trapFocus(e);
				}
			});
		},

		/**
		 * Trap focus inside popup
		 */
		trapFocus: function (e) {
			const focusableElements = $('#admin-notice-hub-popup').find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible').not('#admin-notice-hub-confirm-modal *');
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
			$('#admin-notice-hub-popup-overlay').show();
			setTimeout(function () {
				$('#admin-notice-hub-popup-overlay').addClass('admin-notice-hub-active');
				// Focus the close button for accessibility
				$('#admin-notice-hub-popup-overlay').find('.admin-notice-hub-close-popup').focus();
			}, 10);
			this.loadNotices();
		},

		/**
		 * Close popup
		 */
		closePopup: function () {
			$('#admin-notice-hub-popup-overlay').removeClass('admin-notice-hub-active');
			setTimeout(function () {
				$('#admin-notice-hub-popup-overlay').hide();
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
			const filterType = $('#admin-notice-hub-filter-type').val();
			const showRead = $('#admin-notice-hub-show-read').is(':checked');

			$('.admin-notice-hub-loading').show();
			$('.admin-notice-hub-notices-list').hide();
			$('.admin-notice-hub-empty-state').hide();

			$.ajax({
				url: adminNoticeHubPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'admin_notice_hub_get_notices',
					nonce: adminNoticeHubPopup.nonce,
					filter_type: filterType,
					show_read: showRead
				},
				success: function (response) {
					$('.admin-notice-hub-loading').hide();

					if (response.success && response.data.notices.length > 0) {
						NoticePopup.renderNotices(response.data.notices);
						$('.admin-notice-hub-notices-list').show();
					} else {
						$('.admin-notice-hub-empty-state').show();
					}

					if (response.success && response.data) {
						// In-popup badge tracks unread total (independent of active filter).
						NoticePopup.updateCount(response.data.unread_total);
						NoticePopup.updateToolbarCount(response.data.unread_total);
					}
				},
				error: function () {
					$('.admin-notice-hub-loading').hide();
					NoticePopup.showToast(adminNoticeHubPopup.i18n.error, 'error');
				}
			});
		},

		/**
		 * Render notices
		 */
		renderNotices: function (notices) {
			const $list = $('#admin-notice-hub-notices-list');
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
			const readClass = notice.is_read ? 'admin-notice-hub-notice-read' : '';
			const typeClass = 'admin-notice-hub-notice-' + notice.type;
			const timeAgo = NoticePopup.timeAgo(notice.created_at);
			const i18n = adminNoticeHubPopup.i18n || {};

			const $item = $('<div>')
				.addClass('admin-notice-hub-notice-item ' + typeClass + ' ' + readClass)
				.attr('data-notice-id', notice.id);

			const $header = $('<div class="admin-notice-hub-notice-header">');
			$header.append(
				$('<div class="admin-notice-hub-notice-type">')
					.append($('<span class="dashicons">').addClass(notice.icon).attr('aria-hidden', 'true'))
					.append(document.createTextNode(' ' + notice.type))
			);

			const $actions = $('<div class="admin-notice-hub-notice-actions">');
			if (!notice.is_read) {
				$actions.append(
					$('<button type="button" class="admin-notice-hub-notice-action admin-notice-hub-mark-read">')
						.attr('data-notice-id', notice.id)
						.attr('title', i18n.markAsRead || 'Mark as read')
						.attr('aria-label', i18n.markAsRead || 'Mark as read')
						.append('<span class="dashicons dashicons-yes" aria-hidden="true"></span>')
				);
			}
			$actions.append(
				$('<button type="button" class="admin-notice-hub-notice-action admin-notice-hub-dismiss">')
					.attr('data-notice-id', notice.id)
					.attr('title', i18n.dismiss || 'Dismiss')
					.attr('aria-label', i18n.dismissNotice || 'Dismiss notice')
					.append('<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>')
			);
			$header.append($actions);

			$item.append($header);
			$item.append($('<div class="admin-notice-hub-notice-content">').text(notice.content || ''));
			$item.append(
				$('<div class="admin-notice-hub-notice-meta">').append(
					$('<div class="admin-notice-hub-notice-time">')
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
				url: adminNoticeHubPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'admin_notice_hub_mark_read',
					nonce: adminNoticeHubPopup.nonce,
					notice_id: noticeId
				},
				success: function (response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').addClass('admin-notice-hub-notice-read');
						$('[data-notice-id="' + noticeId + '"] .admin-notice-hub-mark-read').remove();
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
				url: adminNoticeHubPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'admin_notice_hub_dismiss_notice',
					nonce: adminNoticeHubPopup.nonce,
					notice_id: noticeId
				},
				success: function (response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').fadeOut(300, function () {
							$(this).slideUp(200, function () {
								$(this).remove();
								if ($('.admin-notice-hub-notice-item').length === 0) {
									$('.admin-notice-hub-empty-state').fadeIn(200);
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
				url: adminNoticeHubPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'admin_notice_hub_mark_all_read',
					nonce: adminNoticeHubPopup.nonce
				},
				success: function (response) {
					if (response.success) {
						$('.admin-notice-hub-notice-item').addClass('admin-notice-hub-notice-read');
						$('.admin-notice-hub-mark-read').remove();
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast(response.data.message || adminNoticeHubPopup.i18n.markAllRead);
					}
				}
			});
		},

		/**
		 * Clear all notices (single bulk AJAX call)
		 */
		clearAll: function () {
			$.ajax({
				url: adminNoticeHubPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'admin_notice_hub_clear_all',
					nonce: adminNoticeHubPopup.nonce
				},
				success: function (response) {
					if (response.success) {
						$('#admin-notice-hub-notices-list').empty();
						$('.admin-notice-hub-empty-state').show();
						$('.admin-notice-hub-notices-list').hide();
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast(response.data.message || adminNoticeHubPopup.i18n.clearAll);
					}
				}
			});
		},

		/**
		 * Update count badge
		 */
		updateCount: function (count) {
			$('.admin-notice-hub-notice-count-badge').text(count);
		},

		/**
		 * Update toolbar count
		 */
		updateToolbarCount: function (count) {
			const i18n = adminNoticeHubPopup.i18n || {};
			const $label = $('#wp-admin-bar-admin-notice-hub-notices .ab-label');
			const num = parseInt(count, 10) || 0;

			if (num > 0) {
				const tmpl = i18n.noticesWithCount || 'Notices (%d)';
				$label.text(tmpl.replace('%d', num));
				$('.admin-notice-hub-count-badge').text(num).show();
			} else {
				$label.text(i18n.notices || 'Notices');
				$('.admin-notice-hub-count-badge').hide();
			}
			this.updateCount(num);
		},

		/**
		 * Time ago helper (translatable via adminNoticeHubPopup.i18n).
		 */
		timeAgo: function (datetime) {
			const i18n = adminNoticeHubPopup.i18n || {};
			const now = new Date();
			const past = new Date(datetime);
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
				.addClass('admin-notice-hub-toast admin-notice-hub-toast-' + type)
				.text(message);

			$('#admin-notice-hub-toast-container').append($toast);

			// Trigger reflow for transition
			$toast[0].offsetHeight;
			$toast.addClass('admin-notice-hub-toast-show');

			setTimeout(function () {
				$toast.removeClass('admin-notice-hub-toast-show');
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

