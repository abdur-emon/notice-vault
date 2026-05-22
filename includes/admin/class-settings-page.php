<?php
/**
 * Settings Page Class
 *
 * Handles plugin settings page.
 *
 * @package Notice_Vault
 * @subpackage Admin
 */

namespace Notice_Vault\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings Page Class
 *
 * Creates and manages the settings page.
 *
 * @since 1.0.0
 */
class Settings_Page
{

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'notice-vault';

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'notice_vault_settings_group';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'notice_vault_settings';

	/**
	 * Add settings page to admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Notice Vault Settings', 'notice-vault' ),
			__( 'Notice Vault', 'notice-vault' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings()
	{
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array($this, 'sanitize_settings'),
			)
		);

		// Notice Type Settings Section.
		add_settings_section(
			'notice_vault_notice_types',
			__( 'Notice Type Settings', 'notice-vault' ),
			array( $this, 'render_notice_types_section' ),
			self::PAGE_SLUG
		);

		// Popup Settings Section.
		add_settings_section(
			'notice_vault_popup_settings',
			__( 'Popup Settings', 'notice-vault' ),
			array( $this, 'render_popup_section' ),
			self::PAGE_SLUG
		);

		// User Visibility Section.
		add_settings_section(
			'notice_vault_visibility',
			__( 'User Visibility Settings', 'notice-vault' ),
			array( $this, 'render_visibility_section' ),
			self::PAGE_SLUG
		);

		// Advanced Settings Section.
		add_settings_section(
			'notice_vault_advanced',
			__( 'Advanced Settings', 'notice-vault' ),
			array( $this, 'render_advanced_section' ),
			self::PAGE_SLUG
		);

		// Register individual fields.
		$this->register_fields();
	}

	/**
	 * Register individual settings fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_fields()
	{
		// Notice type fields — filtered list lets third parties register custom categories.
		$notice_types = \Notice_Vault\Notices\Notice_Classifier::get_types();

		foreach ($notice_types as $type => $label) {
			add_settings_field(
				'notice_' . $type,
				$label,
				array($this, 'render_notice_type_field'),
				self::PAGE_SLUG,
				'notice_vault_notice_types',
				array('type' => $type)
			);
		}

		// Popup style field.
		add_settings_field(
			'popup_style',
			__( 'Popup Style', 'notice-vault' ),
			array( $this, 'render_popup_style_field' ),
			self::PAGE_SLUG,
			'notice_vault_popup_settings'
		);

		// Visibility mode field.
		add_settings_field(
			'visibility_mode',
			__( 'Visibility Mode', 'notice-vault' ),
			array( $this, 'render_visibility_mode_field' ),
			self::PAGE_SLUG,
			'notice_vault_visibility'
		);

		// Visibility users field. The wrapping <tr> is hidden server-side when the
		// current mode doesn't need a user list, so there's no flash of visible
		// row before admin.js kicks in. JS still flips it on subsequent changes.
		$settings   = get_option( self::OPTION_NAME, array() );
		$mode       = isset( $settings['visibility_mode'] ) ? $settings['visibility_mode'] : 'show-all';
		$needs_list = in_array( $mode, array( 'hide-selected', 'show-selected' ), true );
		add_settings_field(
			'visibility_users',
			__( 'Select Users', 'notice-vault' ),
			array( $this, 'render_visibility_users_field' ),
			self::PAGE_SLUG,
			'notice_vault_visibility',
			array(
				'class' => $needs_list ? 'notice-vault-visibility-users-row' : 'notice-vault-visibility-users-row hidden',
			)
		);

		// Auto expire days field.
		add_settings_field(
			'auto_expire_days',
			__( 'Auto-expire Notices After', 'notice-vault' ),
			array( $this, 'render_auto_expire_field' ),
			self::PAGE_SLUG,
			'notice_vault_advanced'
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'notice-vault' ) );
		}

		include NOTICE_VAULT_PLUGIN_DIR . 'templates/settings-page.php';
	}

	/**
	 * Render notice types section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_notice_types_section() {
		echo '<p>' . esc_html__( 'Configure how each notice type should be handled.', 'notice-vault' ) . '</p>';
	}

	/**
	 * Render popup section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_popup_section() {
		echo '<p>' . esc_html__( 'Customize the popup appearance and behavior.', 'notice-vault' ) . '</p>';
	}

	/**
	 * Render visibility section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_visibility_section() {
		echo '<p>' . esc_html__( 'Control which users can see Notice Vault.', 'notice-vault' ) . '</p>';
	}

	/**
	 * Render advanced section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_advanced_section() {
		echo '<p>' . esc_html__( 'Advanced plugin settings.', 'notice-vault' ) . '</p>';
	}

	/**
	 * Render notice type field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_notice_type_field($args)
	{
		$settings = get_option(self::OPTION_NAME, array());
		$type = $args['type'];
		$value = isset($settings['notice_' . $type]) ? $settings['notice_' . $type] : 'popup';

		$options = array(
			'popup'   => __( 'Show in popup & hide from dashboard', 'notice-vault' ),
			'hide'    => __( 'Hide completely', 'notice-vault' ),
			'nothing' => __( 'Do nothing (leave in dashboard)', 'notice-vault' ),
		);

		// System notices only have popup or nothing.
		if ('system' === $type) {
			unset($options['hide']);
		}

		echo '<select name="' . esc_attr(self::OPTION_NAME . '[notice_' . $type . ']') . '" class="regular-text">';
		foreach ($options as $option_value => $option_label) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr($option_value),
				selected($value, $option_value, false),
				esc_html($option_label)
			);
		}
		echo '</select>';
	}

	/**
	 * Render popup style field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_popup_style_field()
	{
		$settings = get_option(self::OPTION_NAME, array());
		$value = isset($settings['popup_style']) ? $settings['popup_style'] : 'slide-right';

		$options = array(
			'slide-right' => __( 'Slide from Right', 'notice-vault' ),
			'modal'       => __( 'Modal Popup (Centered)', 'notice-vault' ),
			'panel'       => __( 'Slide Background Panel', 'notice-vault' ),
		);

		echo '<select name="' . esc_attr(self::OPTION_NAME . '[popup_style]') . '" class="regular-text">';
		foreach ($options as $option_value => $option_label) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr($option_value),
				selected($value, $option_value, false),
				esc_html($option_label)
			);
		}
		echo '</select>';
	}

	/**
	 * Render visibility mode field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_visibility_mode_field()
	{
		$settings = get_option(self::OPTION_NAME, array());
		$value = isset($settings['visibility_mode']) ? $settings['visibility_mode'] : 'show-all';

		$options = array(
			'show-all'      => __( 'Show to all users', 'notice-vault' ),
			'hide-all'      => __( 'Hide from all users', 'notice-vault' ),
			'hide-selected' => __( 'Hide from selected users only', 'notice-vault' ),
			'show-selected' => __( 'Show to selected users only', 'notice-vault' ),
		);

		echo '<select name="' . esc_attr(self::OPTION_NAME . '[visibility_mode]') . '" id="notice-vault-visibility-mode" class="regular-text">';
		foreach ($options as $option_value => $option_label) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr($option_value),
				selected($value, $option_value, false),
				esc_html($option_label)
			);
		}
		echo '</select>';
	}

	/**
	 * Render visibility users field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_visibility_users_field()
	{
		$settings = get_option(self::OPTION_NAME, array());
		$selected_users = isset($settings['visibility_users']) ? $settings['visibility_users'] : array();

		echo '<select name="' . esc_attr( self::OPTION_NAME . '[visibility_users][]' ) . '" id="notice-vault-visibility-users" class="regular-text notice-vault-select2-users" multiple="multiple" style="width:100%; max-width:400px;">';
		foreach ( $selected_users as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				printf(
					'<option value="%d" selected="selected">%s (%s)</option>',
					esc_attr( $user->ID ),
					esc_html( $user->display_name ),
					esc_html( $user->user_login )
				);
			}
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Search and select users.', 'notice-vault' ) . '</p>';
	}

	/**
	 * Render auto expire field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_auto_expire_field()
	{
		$settings = get_option(self::OPTION_NAME, array());
		$value = isset($settings['auto_expire_days']) ? $settings['auto_expire_days'] : 30;

		printf(
			'<input type="number" name="%s" value="%s" min="1" max="365" class="small-text"> %s',
			esc_attr( self::OPTION_NAME . '[auto_expire_days]' ),
			esc_attr( $value ),
			esc_html__( 'days', 'notice-vault' )
		);
		echo '<p class="description">' . esc_html__( 'Notices older than this will be automatically deleted.', 'notice-vault' ) . '</p>';
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 1.0.0
	 * @param array $input Input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings($input)
	{
		// Start from the existing option so internal sub-keys the form doesn't touch
		// (notably `migrations`, used by Upgrader to gate one-shot DB migrations)
		// survive a "Save Settings" round-trip. Without this, every save would
		// erase migration flags and force the next admin request to re-run them.
		$existing = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		$sanitized = $existing;

		// Sanitize notice type settings — uses the filtered type list so custom buckets
		// registered via `notice_vault_notice_types` are validated too.
		$notice_types = array_keys( \Notice_Vault\Notices\Notice_Classifier::get_types() );
		foreach ($notice_types as $type) {
			$key = 'notice_' . $type;
			if (isset($input[$key])) {
				$allowed = array('popup', 'hide', 'nothing');
				if ('system' === $type) {
					$allowed = array('popup', 'nothing');
				}
				$sanitized[$key] = in_array($input[$key], $allowed, true) ? $input[$key] : 'popup';
			}
		}

		// Sanitize popup style.
		if ( isset( $input['popup_style'] ) ) {
			$allowed_styles        = array( 'slide-right', 'modal', 'panel' );
			$sanitized['popup_style'] = in_array( $input['popup_style'], $allowed_styles, true ) ? $input['popup_style'] : 'slide-right';
		}

		// Sanitize visibility mode.
		if ( isset( $input['visibility_mode'] ) ) {
			$allowed_modes              = array( 'show-all', 'hide-all', 'hide-selected', 'show-selected' );
			$sanitized['visibility_mode'] = in_array( $input['visibility_mode'], $allowed_modes, true ) ? $input['visibility_mode'] : 'show-all';
		}

		// Sanitize visibility users.
		if (isset($input['visibility_users']) && is_array($input['visibility_users'])) {
			$sanitized['visibility_users'] = array_map('absint', $input['visibility_users']);
		} else {
			$sanitized['visibility_users'] = array();
		}

		// Sanitize auto expire days.
		if (isset($input['auto_expire_days'])) {
			$sanitized['auto_expire_days'] = absint($input['auto_expire_days']);
			if ($sanitized['auto_expire_days'] < 1) {
				$sanitized['auto_expire_days'] = 1;
			}
			if ($sanitized['auto_expire_days'] > 365) {
				$sanitized['auto_expire_days'] = 365;
			}
		}

		// Stamp current version on every save.
		$sanitized['version'] = NOTICE_VAULT_VERSION;

		return $sanitized;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets($hook)
	{
		// Only load on our settings page.
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'select2', NOTICE_VAULT_PLUGIN_URL . 'assets/css/select2.min.css', array(), '4.0.13' );
		wp_enqueue_script( 'select2', NOTICE_VAULT_PLUGIN_URL . 'assets/js/select2.min.js', array( 'jquery' ), '4.0.13', true );

		wp_enqueue_style(
			'notice-vault-admin',
			NOTICE_VAULT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			NOTICE_VAULT_VERSION
		);

		wp_enqueue_script(
			'notice-vault-admin',
			NOTICE_VAULT_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'select2' ),
			NOTICE_VAULT_VERSION,
			true
		);

		wp_localize_script(
			'notice-vault-admin',
			'noticeVaultAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'notice_vault_admin_nonce' ),
			)
		);
	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @since 1.0.0
	 * @param array $links Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( menu_page_url( self::PAGE_SLUG, false ) ) . '">' . __( 'Settings', 'notice-vault' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * AJAX: Search users for Select2.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_search_users() {
		check_ajax_referer( 'notice_vault_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'notice-vault' ) ) );
			return;
		}

		$search = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

		$args = array(
			'search'         => '*' . $search . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'number'         => 20,
			'fields'         => array( 'ID', 'display_name', 'user_login' ),
		);

		$users = get_users( $args );

		$results = array();
		foreach ( $users as $user ) {
			$results[] = array(
				'id'   => $user->ID,
				'text' => $user->display_name . ' (' . $user->user_login . ')',
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}
}
