/**
 * Admin Notice Hub - Admin JavaScript
 *
 * @package Admin_Notice_Hub
 */

(function ($) {
	'use strict';

	/**
	 * Settings Page Handler
	 */
	const SettingsPage = {
		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
			this.toggleVisibilityUsers();
			this.initSelect2();
		},

		/**
		 * Initialize Select2
		 */
		initSelect2: function () {
			if (typeof $.fn.select2 !== 'undefined') {
				$('.anh-select2-users').select2({
					placeholder: 'Search for a user...',
					minimumInputLength: 2,
					ajax: {
						url: anhAdmin.ajaxUrl,
						type: 'POST',
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								action: 'anh_search_users',
								nonce: anhAdmin.nonce,
								q: params.term
							};
						},
						processResults: function (response) {
							return {
								results: response.success ? response.data.results : []
							};
						},
						cache: true
					}
				});
			}
		},

		/**
		 * Bind events
		 */
		bindEvents: function () {
			// Toggle visibility users field based on mode
			$(document).on('change', '#anh-visibility-mode', function () {
				SettingsPage.toggleVisibilityUsers();
			});
		},

		/**
		 * Toggle visibility users field
		 */
		toggleVisibilityUsers: function () {
			const mode = $('#anh-visibility-mode').val();
			const $usersField = $('#anh-visibility-users').closest('tr');

			if (mode === 'hide-selected' || mode === 'show-selected') {
				$usersField.show();
			} else {
				$usersField.hide();
			}
		}
	};

	// Initialize on document ready
	$(document).ready(function () {
		SettingsPage.init();
	});

})(jQuery);

