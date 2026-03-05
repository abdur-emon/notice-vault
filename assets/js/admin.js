/**
 * WP Notice Manager - Admin JavaScript
 *
 * @package WP_Notice_Manager
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
				$('.wpnm-select2-users').select2({
					placeholder: 'Search for a user...',
					minimumInputLength: 2,
					ajax: {
						url: wpnmAdmin.ajaxUrl,
						type: 'POST',
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								action: 'wpnm_search_users',
								nonce: wpnmAdmin.nonce,
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
			$(document).on('change', '#wpnm-visibility-mode', function () {
				SettingsPage.toggleVisibilityUsers();
			});
		},

		/**
		 * Toggle visibility users field
		 */
		toggleVisibilityUsers: function () {
			const mode = $('#wpnm-visibility-mode').val();
			const $usersField = $('#wpnm-visibility-users').closest('tr');

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

