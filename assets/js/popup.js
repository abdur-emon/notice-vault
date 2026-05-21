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
			$(document).on('click', '#wp-admin-bar-anh-notices > a, .anh-view-all', function (e) {
				e.preventDefault();
				NoticePopup.openPopup();
			});

			// Close popup
			$(document).on('click', '.anh-close-popup', function (e) {
				NoticePopup.closePopup();
			});

			$(document).on('click', '.anh-popup-overlay', function (e) {
				if (e.target === this) {
					NoticePopup.closePopup();
				}
			});

			// ESC key to close
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape' && $('#anh-popup-overlay').hasClass('anh-active')) {
					NoticePopup.closePopup();
				}
			});

			// Filter change
			$(document).on('change', '#anh-filter-type, #anh-show-read', function () {
				NoticePopup.loadNotices();
			});

			// Mark as read
			$(document).on('click', '.anh-mark-read', function (e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.markRead(noticeId);
			});

			// Dismiss notice
			$(document).on('click', '.anh-dismiss', function (e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.dismissNotice(noticeId);
			});

			// Mark all read
			$(document).on('click', '#anh-mark-all-read', function (e) {
				e.preventDefault();
				NoticePopup.markAllRead();
			});

			// Clear all
			$(document).on('click', '#anh-clear-all', function (e) {
				e.preventDefault();
				$('#anh-confirm-modal').fadeIn(200);
			});

			// Modal Confirm Cancel
			$(document).on('click', '#anh-confirm-cancel', function () {
				$('#anh-confirm-modal').fadeOut(200);
			});

			// Modal Confirm Yes
			$(document).on('click', '#anh-confirm-yes', function () {
				$('#anh-confirm-modal').fadeOut(200);
				NoticePopup.clearAll();
			});

			// Focus Trapping within Popup
			$(document).on('keydown', '#anh-popup', function (e) {
				if (e.key === 'Tab') {
					NoticePopup.trapFocus(e);
				}
			});
		},

		/**
		 * Trap focus inside popup
		 */
		trapFocus: function (e) {
			const focusableElements = $('#anh-popup').find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible').not('#anh-confirm-modal *');
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
			$('#anh-popup-overlay').show();
			setTimeout(function () {
				$('#anh-popup-overlay').addClass('anh-active');
				// Focus the close button for accessibility
				$('#anh-popup-overlay').find('.anh-close-popup').focus();
			}, 10);
			this.loadNotices();
		},

		/**
		 * Close popup
		 */
		closePopup: function () {
			$('#anh-popup-overlay').removeClass('anh-active');
			setTimeout(function () {
				$('#anh-popup-overlay').hide();
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
			const filterType = $('#anh-filter-type').val();
			const showRead = $('#anh-show-read').is(':checked');

			$('.anh-loading').show();
			$('.anh-notices-list').hide();
			$('.anh-empty-state').hide();

			$.ajax({
				url: anhPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'anh_get_notices',
					nonce: anhPopup.nonce,
					filter_type: filterType,
					show_read: showRead
				},
				success: function (response) {
					$('.anh-loading').hide();

					if (response.success && response.data.notices.length > 0) {
						NoticePopup.renderNotices(response.data.notices);
						$('.anh-notices-list').show();
					} else {
						$('.anh-empty-state').show();
					}

					if (response.success && response.data) {
						// In-popup badge tracks unread total (independent of active filter).
						NoticePopup.updateCount(response.data.unread_total);
						NoticePopup.updateToolbarCount(response.data.unread_total);
					}
				},
				error: function () {
					$('.anh-loading').hide();
					NoticePopup.showToast(anhPopup.i18n.error, 'error');
				}
			});
		},

		/**
		 * Render notices
		 */
		renderNotices: function (notices) {
			const $list = $('#anh-notices-list');
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
			const readClass = notice.is_read ? 'anh-notice-read' : '';
			const typeClass = 'anh-notice-' + notice.type;
			const timeAgo = NoticePopup.timeAgo(notice.created_at);
			const i18n = anhPopup.i18n || {};

			const $item = $('<div>')
				.addClass('anh-notice-item ' + typeClass + ' ' + readClass)
				.attr('data-notice-id', notice.id);

			const $header = $('<div class="anh-notice-header">');
			$header.append(
				$('<div class="anh-notice-type">')
					.append($('<span class="dashicons">').addClass(notice.icon).attr('aria-hidden', 'true'))
					.append(document.createTextNode(' ' + notice.type))
			);

			const $actions = $('<div class="anh-notice-actions">');
			if (!notice.is_read) {
				$actions.append(
					$('<button type="button" class="anh-notice-action anh-mark-read">')
						.attr('data-notice-id', notice.id)
						.attr('title', i18n.markAsRead || 'Mark as read')
						.attr('aria-label', i18n.markAsRead || 'Mark as read')
						.append('<span class="dashicons dashicons-yes" aria-hidden="true"></span>')
				);
			}
			$actions.append(
				$('<button type="button" class="anh-notice-action anh-dismiss">')
					.attr('data-notice-id', notice.id)
					.attr('title', i18n.dismiss || 'Dismiss')
					.attr('aria-label', i18n.dismissNotice || 'Dismiss notice')
					.append('<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>')
			);
			$header.append($actions);

			$item.append($header);
			$item.append($('<div class="anh-notice-content">').text(notice.content || ''));
			$item.append(
				$('<div class="anh-notice-meta">').append(
					$('<div class="anh-notice-time">')
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
				url: anhPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'anh_mark_read',
					nonce: anhPopup.nonce,
					notice_id: noticeId
				},
				success: function (response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').addClass('anh-notice-read');
						$('[data-notice-id="' + noticeId + '"] .anh-mark-read').remove();
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
				url: anhPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'anh_dismiss_notice',
					nonce: anhPopup.nonce,
					notice_id: noticeId
				},
				success: function (response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').fadeOut(300, function () {
							$(this).slideUp(200, function () {
								$(this).remove();
								if ($('.anh-notice-item').length === 0) {
									$('.anh-empty-state').fadeIn(200);
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
				url: anhPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'anh_mark_all_read',
					nonce: anhPopup.nonce
				},
				success: function (response) {
					if (response.success) {
						$('.anh-notice-item').addClass('anh-notice-read');
						$('.anh-mark-read').remove();
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast(response.data.message || anhPopup.i18n.markAllRead);
					}
				}
			});
		},

		/**
		 * Clear all notices (single bulk AJAX call)
		 */
		clearAll: function () {
			$.ajax({
				url: anhPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'anh_clear_all',
					nonce: anhPopup.nonce
				},
				success: function (response) {
					if (response.success) {
						$('#anh-notices-list').empty();
						$('.anh-empty-state').show();
						$('.anh-notices-list').hide();
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast(response.data.message || anhPopup.i18n.clearAll);
					}
				}
			});
		},

		/**
		 * Update count badge
		 */
		updateCount: function (count) {
			$('.anh-notice-count-badge').text(count);
		},

		/**
		 * Update toolbar count
		 */
		updateToolbarCount: function (count) {
			const i18n = anhPopup.i18n || {};
			const $label = $('#wp-admin-bar-anh-notices .ab-label');
			const num = parseInt(count, 10) || 0;

			if (num > 0) {
				const tmpl = i18n.noticesWithCount || 'Notices (%d)';
				$label.text(tmpl.replace('%d', num));
				$('.anh-count-badge').text(num).show();
			} else {
				$label.text(i18n.notices || 'Notices');
				$('.anh-count-badge').hide();
			}
			this.updateCount(num);
		},

		/**
		 * Time ago helper (translatable via anhPopup.i18n).
		 */
		timeAgo: function (datetime) {
			const i18n = anhPopup.i18n || {};
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
				.addClass('anh-toast anh-toast-' + type)
				.text(message);

			$('#anh-toast-container').append($toast);

			// Trigger reflow for transition
			$toast[0].offsetHeight;
			$toast.addClass('anh-toast-show');

			setTimeout(function () {
				$toast.removeClass('anh-toast-show');
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

