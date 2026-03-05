/**
 * WP Notice Manager - Popup JavaScript
 *
 * @package WP_Notice_Manager
 */

(function($) {
	'use strict';

	/**
	 * Notice Popup Handler
	 */
	const NoticePopup = {
		previousFocus: null,

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Open popup when clicking toolbar item
			$(document).on('click', '#wp-admin-bar-wpnm-notices > a, .wpnm-view-all', function(e) {
				e.preventDefault();
				NoticePopup.openPopup();
			});

			// Close popup
			$(document).on('click', '.wpnm-close-popup, .wpnm-popup-overlay', function(e) {
				if (e.target === this) {
					NoticePopup.closePopup();
				}
			});

			// ESC key to close
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && $('#wpnm-popup-overlay').hasClass('wpnm-active')) {
					NoticePopup.closePopup();
				}
			});

			// Filter change
			$(document).on('change', '#wpnm-filter-type, #wpnm-show-read', function() {
				NoticePopup.loadNotices();
			});

			// Mark as read
			$(document).on('click', '.wpnm-mark-read', function(e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.markRead(noticeId);
			});

			// Dismiss notice
			$(document).on('click', '.wpnm-dismiss', function(e) {
				e.preventDefault();
				const noticeId = $(this).data('notice-id');
				NoticePopup.dismissNotice(noticeId);
			});

			// Mark all read
			$(document).on('click', '#wpnm-mark-all-read', function(e) {
				e.preventDefault();
				NoticePopup.markAllRead();
			});

			// Clear all
			$(document).on('click', '#wpnm-clear-all', function(e) {
				e.preventDefault();
				$('#wpnm-confirm-modal').fadeIn(200);
			});

			// Modal Confirm Cancel
			$(document).on('click', '#wpnm-confirm-cancel', function() {
				$('#wpnm-confirm-modal').fadeOut(200);
			});

			// Modal Confirm Yes
			$(document).on('click', '#wpnm-confirm-yes', function() {
				$('#wpnm-confirm-modal').fadeOut(200);
				NoticePopup.clearAll();
			});

			// Focus Trapping within Popup
			$(document).on('keydown', '#wpnm-popup', function(e) {
				if (e.key === 'Tab') {
					NoticePopup.trapFocus(e);
				}
			});
		},

		/**
		 * Trap focus inside popup
		 */
		trapFocus: function(e) {
			const focusableElements = $('#wpnm-popup').find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible').not('#wpnm-confirm-modal *');
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
		openPopup: function() {
			this.previousFocus = document.activeElement;
			$('#wpnm-popup-overlay').show();
			setTimeout(function() {
				$('#wpnm-popup-overlay').addClass('wpnm-active');
				// Focus the close button for accessibility
				$('#wpnm-popup-overlay').find('.wpnm-close-popup').focus();
			}, 10);
			this.loadNotices();
		},

		/**
		 * Close popup
		 */
		closePopup: function() {
			$('#wpnm-popup-overlay').removeClass('wpnm-active');
			setTimeout(function() {
				$('#wpnm-popup-overlay').hide();
				// Restore focus
				if (NoticePopup.previousFocus) {
					NoticePopup.previousFocus.focus();
				}
			}, 300);
		},

		/**
		 * Load notices via AJAX
		 */
		loadNotices: function() {
			const filterType = $('#wpnm-filter-type').val();
			const showRead = $('#wpnm-show-read').is(':checked');

			$('.wpnm-loading').show();
			$('.wpnm-notices-list').hide();
			$('.wpnm-empty-state').hide();

			$.ajax({
				url: wpnmPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpnm_get_notices',
					nonce: wpnmPopup.nonce,
					filter_type: filterType,
					show_read: showRead
				},
				success: function(response) {
					$('.wpnm-loading').hide();

					if (response.success && response.data.notices.length > 0) {
						NoticePopup.renderNotices(response.data.notices);
						$('.wpnm-notices-list').show();
					} else {
						$('.wpnm-empty-state').show();
					}

					NoticePopup.updateCount(response.data.count);
				},
				error: function() {
					$('.wpnm-loading').hide();
					alert(wpnmPopup.i18n.error);
				}
			});
		},

		/**
		 * Render notices
		 */
		renderNotices: function(notices) {
			const $list = $('#wpnm-notices-list');
			$list.empty();

			notices.forEach(function(notice) {
				const $item = NoticePopup.createNoticeItem(notice);
				$list.append($item);
			});
		},

		/**
		 * Create notice item HTML
		 */
		createNoticeItem: function(notice) {
			const readClass = notice.is_read ? 'wpnm-notice-read' : '';
			const typeClass = 'wpnm-notice-' + notice.type;
			const timeAgo = NoticePopup.timeAgo(notice.created_at);

			return $('<div>')
				.addClass('wpnm-notice-item ' + typeClass + ' ' + readClass)
				.attr('data-notice-id', notice.id)
				.html(
					'<div class="wpnm-notice-header">' +
						'<div class="wpnm-notice-type">' +
							'<span class="dashicons ' + notice.icon + '"></span>' +
							notice.type +
						'</div>' +
						'<div class="wpnm-notice-actions">' +
							(!notice.is_read ? '<button class="wpnm-notice-action wpnm-mark-read" data-notice-id="' + notice.id + '" title="Mark as read" aria-label="Mark as read"><span class="dashicons dashicons-yes"></span></button>' : '') +
							'<button class="wpnm-notice-action wpnm-dismiss" data-notice-id="' + notice.id + '" title="Dismiss" aria-label="Dismiss notice"><span class="dashicons dashicons-no-alt"></span></button>' +
						'</div>' +
					'</div>' +
					'<div class="wpnm-notice-content">' + notice.content + '</div>' +
					'<div class="wpnm-notice-meta">' +
						'<div class="wpnm-notice-time">' +
							'<span class="dashicons dashicons-clock"></span>' +
							timeAgo +
						'</div>' +
					'</div>'
				);
		},

		/**
		 * Mark notice as read
		 */
		markRead: function(noticeId) {
			$.ajax({
				url: wpnmPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpnm_mark_read',
					nonce: wpnmPopup.nonce,
					notice_id: noticeId
				},
				success: function(response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').addClass('wpnm-notice-read');
						$('[data-notice-id="' + noticeId + '"] .wpnm-mark-read').remove();
						NoticePopup.updateToolbarCount(response.data.count);
					}
				}
			});
		},

		/**
		 * Dismiss notice
		 */
		dismissNotice: function(noticeId) {
			$.ajax({
				url: wpnmPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpnm_dismiss_notice',
					nonce: wpnmPopup.nonce,
					notice_id: noticeId
				},
				success: function(response) {
					if (response.success) {
						$('[data-notice-id="' + noticeId + '"]').fadeOut(300, function() {
							$(this).slideUp(200, function() {
								$(this).remove();
								if ($('.wpnm-notice-item').length === 0) {
									$('.wpnm-empty-state').fadeIn(200);
								}
							});
						});
						NoticePopup.updateToolbarCount(response.data.count);
					}
				}
			});
		},

		/**
		 * Mark all as read (single bulk AJAX call)
		 */
		markAllRead: function() {
			$.ajax({
				url: wpnmPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpnm_mark_all_read',
					nonce: wpnmPopup.nonce
				},
				success: function(response) {
					if (response.success) {
						$('.wpnm-notice-item').addClass('wpnm-notice-read');
						$('.wpnm-mark-read').remove();
						NoticePopup.updateToolbarCount(0);
						NoticePopup.showToast(response.data.message || wpnmPopup.i18n.markAllRead);
					}
				}
			});
		},

		/**
		 * Clear all notices (single bulk AJAX call)
		 */
		clearAll: function() {
			$.ajax({
				url: wpnmPopup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpnm_clear_all',
					nonce: wpnmPopup.nonce
				},
				success: function(response) {
					if (response.success) {
						$('#wpnm-notices-list').empty();
						$('.wpnm-empty-state').show();
						$('.wpnm-notices-list').hide();
						NoticePopup.updateToolbarCount(0);
						NoticePopup.showToast(response.data.message || wpnmPopup.i18n.clearAll);
					}
				}
			});
		},

		/**
		 * Update count badge
		 */
		updateCount: function(count) {
			$('.wpnm-notice-count-badge').text(count);
		},

		/**
		 * Update toolbar count
		 */
		updateToolbarCount: function(count) {
			if (count > 0) {
				$('#wp-admin-bar-wpnm-notices .ab-label').html('Notices (' + count + ')');
				$('.wpnm-count-badge').text(count).show();
			} else {
				$('#wp-admin-bar-wpnm-notices .ab-label').text('Notices');
				$('.wpnm-count-badge').hide();
			}
			this.updateCount(count);
		},

		/**
		 * Time ago helper
		 */
		timeAgo: function(datetime) {
			const now = new Date();
			const past = new Date(datetime);
			const seconds = Math.floor((now - past) / 1000);

			if (seconds < 60) return 'Just now';
			if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
			if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
			if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
			return past.toLocaleDateString();
		},

		/**
		 * Show Toast Notification
		 */
		showToast: function(message, type = 'success') {
			const $toast = $('<div>')
				.addClass('wpnm-toast wpnm-toast-' + type)
				.text(message);
				
			$('#wpnm-toast-container').append($toast);
			
			// Trigger reflow for transition
			$toast[0].offsetHeight;
			$toast.addClass('wpnm-toast-show');
			
			setTimeout(function() {
				$toast.removeClass('wpnm-toast-show');
				setTimeout(function() {
					$toast.remove();
				}, 300);
			}, 3000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		NoticePopup.init();
	});

})(jQuery);

