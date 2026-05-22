/**
 * Notice Vault - Popup JavaScript
 *
 * @package Notice_Vault
 */

(function ($) {
	'use strict';

	const PER_PAGE = 20;

	/**
	 * Notice Popup Handler
	 */
	const NoticePopup = {
		previousFocus: null,
		currentPage: 1,
		totalCount: 0,
		hasMore: false,
		isLoading: false,

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
			// Open popup when clicking the toolbar item OR any preview submenu link
			// (everything inside #wp-admin-bar-notice-vault-notices). Without this,
			// the unread-preview submenu items were clickable but did nothing.
			$(document).on('click', '#wp-admin-bar-notice-vault-notices a, .notice-vault-view-all', function (e) {
				e.preventDefault();
				NoticePopup.openPopup();
			});

			// Close popup
			$(document).on('click', '.notice-vault-close-popup', function () {
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

			// Filter change — reset to page 1 and reload.
			$(document).on('change', '#notice-vault-filter-type, #notice-vault-show-read', function () {
				NoticePopup.resetAndLoad();
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

			// Load more pagination
			$(document).on('click', '#notice-vault-load-more', function (e) {
				e.preventDefault();
				NoticePopup.loadMore();
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

			if (e.shiftKey) {
				if (document.activeElement === firstElement) {
					lastElement.focus();
					e.preventDefault();
				}
			} else {
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
			// Only capture the previous focus if we don't already have one stashed —
			// otherwise a double-click or programmatic re-open would overwrite the
			// real return target with the popup's own close button.
			if (!this.previousFocus) {
				this.previousFocus = document.activeElement;
			}
			$('#notice-vault-popup-overlay').show();
			setTimeout(function () {
				$('#notice-vault-popup-overlay').addClass('notice-vault-active');
				$('#notice-vault-popup-overlay').find('.notice-vault-close-popup').focus();
			}, 10);
			this.resetAndLoad();
		},

		/**
		 * Close popup
		 */
		closePopup: function () {
			$('#notice-vault-popup-overlay').removeClass('notice-vault-active');
			setTimeout(function () {
				$('#notice-vault-popup-overlay').hide();
				if (NoticePopup.previousFocus && typeof NoticePopup.previousFocus.focus === 'function') {
					NoticePopup.previousFocus.focus();
				}
				// Clear so the next open re-captures whatever the user came from.
				NoticePopup.previousFocus = null;
			}, 300);
		},

		/**
		 * Reset state to first page, then load.
		 */
		resetAndLoad: function () {
			this.currentPage = 1;
			$('#notice-vault-notices-list').empty();
			$('#notice-vault-load-more-wrap').hide();
			this.loadNotices(false);
		},

		/**
		 * Load notices via AJAX. When `append` is false the list is replaced;
		 * when true (used by "Load more"), new rows are appended.
		 */
		loadNotices: function (append) {
			if (this.isLoading) {
				return;
			}
			this.isLoading = true;

			const filterType = $('#notice-vault-filter-type').val();
			const showRead = $('#notice-vault-show-read').is(':checked');
			const i18n = noticeVaultPopup.i18n || {};

			if (!append) {
				$('.notice-vault-loading').show();
				$('.notice-vault-notices-list').hide();
				$('.notice-vault-empty-state').hide();
			} else {
				$('#notice-vault-load-more')
					.prop('disabled', true)
					.text(i18n.loadMoreLoading || 'Loading…');
			}

			$.ajax({
				url: noticeVaultPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'notice_vault_get_notices',
					nonce: noticeVaultPopup.nonce,
					filter_type: filterType,
					show_read: showRead,
					page: NoticePopup.currentPage
				},
				success: function (response) {
					$('.notice-vault-loading').hide();

					if (response.success && response.data) {
						const notices = response.data.notices || [];
						NoticePopup.totalCount = response.data.total_count || 0;
						NoticePopup.hasMore = !!response.data.has_more;

						if (append) {
							NoticePopup.appendNotices(notices);
						} else {
							NoticePopup.renderNotices(notices);
						}

						if ($('#notice-vault-notices-list').children().length > 0) {
							$('.notice-vault-notices-list').show();
							$('.notice-vault-empty-state').hide();
						} else {
							$('.notice-vault-notices-list').hide();
							$('.notice-vault-empty-state').show();
						}

						NoticePopup.updateLoadMore();
						NoticePopup.updateCount(response.data.unread_total);
						NoticePopup.updateToolbarCount(response.data.unread_total);
					} else {
						NoticePopup.showToast(i18n.error, 'error');
					}
				},
				error: function () {
					$('.notice-vault-loading').hide();
					NoticePopup.showToast(i18n.error, 'error');
				},
				complete: function () {
					NoticePopup.isLoading = false;
					$('#notice-vault-load-more')
						.prop('disabled', false)
						.text(i18n.loadMore || 'Load more');
				}
			});
		},

		/**
		 * Load the next page (called by the Load-more button).
		 */
		loadMore: function () {
			if (!this.hasMore || this.isLoading) {
				return;
			}
			this.currentPage += 1;
			this.loadNotices(true);
		},

		/**
		 * Show/hide the Load-more button based on hasMore.
		 */
		updateLoadMore: function () {
			if (this.hasMore) {
				$('#notice-vault-load-more-wrap').show();
			} else {
				$('#notice-vault-load-more-wrap').hide();
			}
		},

		/**
		 * Render notices (replace).
		 */
		renderNotices: function (notices) {
			const $list = $('#notice-vault-notices-list');
			$list.empty();
			this.appendNotices(notices);
		},

		/**
		 * Append notices (used for Load more).
		 */
		appendNotices: function (notices) {
			const $list = $('#notice-vault-notices-list');
			notices.forEach(function (notice) {
				$list.append(NoticePopup.createNoticeItem(notice));
			});
		},

		/**
		 * Create notice item.
		 *
		 * Content rendering: prefer notice.html (which the server has already passed
		 * through a strict wp_kses allowlist — see Notice_Capture::sanitize_notice_html),
		 * fall back to .text(notice.content) for legacy rows where html is empty.
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
					.append(document.createTextNode(' ' + (notice.type_label || notice.type)))
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

			const $content = $('<div class="notice-vault-notice-content">');
			if (notice.html && notice.html.length > 0) {
				// Server-sanitized via wp_kses with a strict tag allowlist.
				$content.html(notice.html);
			} else {
				$content.text(notice.content || '');
			}
			$item.append($content);

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
			const i18n = noticeVaultPopup.i18n || {};
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
					} else {
						NoticePopup.showToast((response.data && response.data.message) || i18n.error, 'error');
					}
				},
				error: function () {
					NoticePopup.showToast(i18n.error, 'error');
				}
			});
		},

		/**
		 * Dismiss notice
		 */
		dismissNotice: function (noticeId) {
			const i18n = noticeVaultPopup.i18n || {};
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
					} else {
						NoticePopup.showToast((response.data && response.data.message) || i18n.error, 'error');
					}
				},
				error: function () {
					NoticePopup.showToast(i18n.error, 'error');
				}
			});
		},

		/**
		 * Mark all as read
		 */
		markAllRead: function () {
			const i18n = noticeVaultPopup.i18n || {};
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
						NoticePopup.showToast((response.data && response.data.message) || i18n.markedAllRead);
					} else {
						NoticePopup.showToast((response.data && response.data.message) || i18n.error, 'error');
					}
				},
				error: function () {
					NoticePopup.showToast(i18n.error, 'error');
				}
			});
		},

		/**
		 * Clear all notices
		 */
		clearAll: function () {
			const i18n = noticeVaultPopup.i18n || {};
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
						$('#notice-vault-load-more-wrap').hide();
						NoticePopup.hasMore = false;
						NoticePopup.totalCount = 0;
						NoticePopup.updateToolbarCount(response.data.unread_total || 0);
						NoticePopup.showToast((response.data && response.data.message) || i18n.cleared);
					} else {
						NoticePopup.showToast((response.data && response.data.message) || i18n.error, 'error');
					}
				},
				error: function () {
					NoticePopup.showToast(i18n.error, 'error');
				}
			});
		},

		/**
		 * Update count badge inside the popup header.
		 */
		updateCount: function (count) {
			$('.notice-vault-notice-count-badge').text(count);
		},

		/**
		 * Update the admin-bar count.
		 *
		 * Server-side renders the badge element even at 0 (hidden via inline style)
		 * so this can always find it. As a defensive last resort, if it's missing
		 * we still inject one.
		 */
		updateToolbarCount: function (count) {
			const i18n = noticeVaultPopup.i18n || {};
			const $label = $('#wp-admin-bar-notice-vault-notices .ab-label');
			const num = parseInt(count, 10) || 0;

			let $badge = $('#wp-admin-bar-notice-vault-notices .notice-vault-count-badge');
			if ($badge.length === 0) {
				$badge = $('<span class="notice-vault-count-badge" style="display:none;"></span>');
				$label.after($badge);
			}

			if (num > 0) {
				const tmpl = i18n.noticesWithCount || 'Notices (%d)';
				$label.text(tmpl.replace('%d', num));
				$badge.text(num).show();
			} else {
				$label.text(i18n.notices || 'Notices');
				$badge.hide();
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
		showToast: function (message, type) {
			type = type || 'success';
			const $toast = $('<div>')
				.addClass('notice-vault-toast notice-vault-toast-' + type)
				.text(message || '');

			$('#notice-vault-toast-container').append($toast);

			// Trigger reflow for transition
			$toast[0].offsetHeight; // eslint-disable-line no-unused-expressions
			$toast.addClass('notice-vault-toast-show');

			setTimeout(function () {
				$toast.removeClass('notice-vault-toast-show');
				setTimeout(function () {
					$toast.remove();
				}, 300);
			}, 3000);
		}
	};

	// Expose for parity with the earlier surface; PER_PAGE intentionally unused on
	// the client but kept here so future "page size" tweaks are obvious.
	NoticePopup.PER_PAGE = PER_PAGE;

	// Initialize on document ready
	$(document).ready(function () {
		NoticePopup.init();
	});

})(jQuery);
