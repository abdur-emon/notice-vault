/**
 * Notice Vault - Admin JavaScript
 *
 * @package Notice_Vault
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
				$('.notice-vault-select2-users').select2({
					placeholder: 'Search for a user...',
					minimumInputLength: 2,
					ajax: {
						url: noticeVaultAdmin.ajaxUrl,
						type: 'POST',
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								action: 'notice_vault_search_users',
								nonce: noticeVaultAdmin.nonce,
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
			$(document).on('change', '#notice-vault-visibility-mode', function () {
				SettingsPage.toggleVisibilityUsers();
			});
		},

		/**
		 * Toggle visibility users field. PHP renders the wrapping <tr> with the
		 * `hidden` class when the saved mode doesn't need a user list (avoids
		 * a flash of visible row on load); we just keep that class in sync as
		 * the user changes the dropdown.
		 */
		toggleVisibilityUsers: function () {
			const mode = $('#notice-vault-visibility-mode').val();
			const $usersField = $('#notice-vault-visibility-users').closest('tr');
			const needsList = (mode === 'hide-selected' || mode === 'show-selected');
			$usersField.toggleClass('hidden', !needsList);
		}
	};

	// Initialize on document ready
	$(document).ready(function () {
		SettingsPage.init();
	});

})(jQuery);

