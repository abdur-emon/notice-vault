<?php
/**
 * Settings Page Class
 *
 * Handles plugin settings page.
 *
 * @package Admin_Notice_Hub
 * @subpackage Admin
 */

namespace Admin_Notice_Hub\Admin;

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
	const PAGE_SLUG = 'admin-notice-hub';

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'admin_notice_hub_settings_group';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'admin_notice_hub_settings';

	/**
	 * Add settings page to admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Admin Notice Hub Settings', 'admin-notice-hub' ),
			__( 'Admin Notice Hub', 'admin-notice-hub' ),
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
			'admin_notice_hub_notice_types',
			__( 'Notice Type Settings', 'admin-notice-hub' ),
			array( $this, 'render_notice_types_section' ),
			self::PAGE_SLUG
		);

		// Popup Settings Section.
		add_settings_section(
			'admin_notice_hub_popup_settings',
			__( 'Popup Settings', 'admin-notice-hub' ),
			array( $this, 'render_popup_section' ),
			self::PAGE_SLUG
		);

		// User Visibility Section.
		add_settings_section(
			'admin_notice_hub_visibility',
			__( 'User Visibility Settings', 'admin-notice-hub' ),
			array( $this, 'render_visibility_section' ),
			self::PAGE_SLUG
		);

		// Advanced Settings Section.
		add_settings_section(
			'admin_notice_hub_advanced',
			__( 'Advanced Settings', 'admin-notice-hub' ),
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
		$notice_types = \Admin_Notice_Hub\Notices\Notice_Classifier::get_types();

		foreach ($notice_types as $type => $label) {
			add_settings_field(
				'notice_' . $type,
				$label,
				array($this, 'render_notice_type_field'),
				self::PAGE_SLUG,
				'admin_notice_hub_notice_types',
				array('type' => $type)
			);
		}

		// Popup style field.
		add_settings_field(
			'popup_style',
			__( 'Popup Style', 'admin-notice-hub' ),
			array( $this, 'render_popup_style_field' ),
			self::PAGE_SLUG,
			'admin_notice_hub_popup_settings'
		);

		// Visibility mode field.
		add_settings_field(
			'visibility_mode',
			__( 'Visibility Mode', 'admin-notice-hub' ),
			array( $this, 'render_visibility_mode_field' ),
			self::PAGE_SLUG,
			'admin_notice_hub_visibility'
		);

		// Visibility users field.
		add_settings_field(
			'visibility_users',
			__( 'Select Users', 'admin-notice-hub' ),
			array( $this, 'render_visibility_users_field' ),
			self::PAGE_SLUG,
			'admin_notice_hub_visibility'
		);

		// Auto expire days field.
		add_settings_field(
			'auto_expire_days',
			__( 'Auto-expire Notices After', 'admin-notice-hub' ),
			array( $this, 'render_auto_expire_field' ),
			self::PAGE_SLUG,
			'admin_notice_hub_advanced'
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
			wp_die( esc_html__( 'Unauthorized access', 'admin-notice-hub' ) );
		}

		include ADMIN_NOTICE_HUB_PLUGIN_DIR . 'templates/settings-page.php';
	}

	/**
	 * Render notice types section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_notice_types_section() {
		echo '<p>' . esc_html__( 'Configure how each notice type should be handled.', 'admin-notice-hub' ) . '</p>';
	}

	/**
	 * Render popup section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_popup_section() {
		echo '<p>' . esc_html__( 'Customize the popup appearance and behavior.', 'admin-notice-hub' ) . '</p>';
	}

	/**
	 * Render visibility section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_visibility_section() {
		echo '<p>' . esc_html__( 'Control which users can see Admin Notice Hub.', 'admin-notice-hub' ) . '</p>';
	}

	/**
	 * Render advanced section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_advanced_section() {
		echo '<p>' . esc_html__( 'Advanced plugin settings.', 'admin-notice-hub' ) . '</p>';
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
			'popup'   => __( 'Show in popup & hide from dashboard', 'admin-notice-hub' ),
			'hide'    => __( 'Hide completely', 'admin-notice-hub' ),
			'nothing' => __( 'Do nothing (leave in dashboard)', 'admin-notice-hub' ),
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
			'slide-right' => __( 'Slide from Right', 'admin-notice-hub' ),
			'modal'       => __( 'Modal Popup (Centered)', 'admin-notice-hub' ),
			'panel'       => __( 'Slide Background Panel', 'admin-notice-hub' ),
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
			'show-all'      => __( 'Show to all users', 'admin-notice-hub' ),
			'hide-all'      => __( 'Hide from all users', 'admin-notice-hub' ),
			'hide-selected' => __( 'Hide from selected users only', 'admin-notice-hub' ),
			'show-selected' => __( 'Show to selected users only', 'admin-notice-hub' ),
		);

		echo '<select name="' . esc_attr(self::OPTION_NAME . '[visibility_mode]') . '" id="admin-notice-hub-visibility-mode" class="regular-text">';
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

		echo '<select name="' . esc_attr( self::OPTION_NAME . '[visibility_users][]' ) . '" id="admin-notice-hub-visibility-users" class="regular-text admin-notice-hub-select2-users" multiple="multiple" style="width:100%; max-width:400px;">';
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
		echo '<p class="description">' . esc_html__( 'Search and select users.', 'admin-notice-hub' ) . '</p>';
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
			esc_html__( 'days', 'admin-notice-hub' )
		);
		echo '<p class="description">' . esc_html__( 'Notices older than this will be automatically deleted.', 'admin-notice-hub' ) . '</p>';
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
		$sanitized = array();

		// Sanitize notice type settings — uses the filtered type list so custom buckets
		// registered via `admin_notice_hub_notice_types` are validated too.
		$notice_types = array_keys( \Admin_Notice_Hub\Notices\Notice_Classifier::get_types() );
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

		// Keep version.
		$sanitized['version'] = ADMIN_NOTICE_HUB_VERSION;

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

		wp_enqueue_style( 'select2', ADMIN_NOTICE_HUB_PLUGIN_URL . 'assets/css/select2.min.css', array(), '4.0.13' );
		wp_enqueue_script( 'select2', ADMIN_NOTICE_HUB_PLUGIN_URL . 'assets/js/select2.min.js', array( 'jquery' ), '4.0.13', true );

		wp_enqueue_style(
			'admin-notice-hub-admin',
			ADMIN_NOTICE_HUB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ADMIN_NOTICE_HUB_VERSION
		);

		wp_enqueue_script(
			'admin-notice-hub-admin',
			ADMIN_NOTICE_HUB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'select2' ),
			ADMIN_NOTICE_HUB_VERSION,
			true
		);

		wp_localize_script(
			'admin-notice-hub-admin',
			'adminNoticeHubAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'admin_notice_hub_admin_nonce' ),
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
		$settings_link = '<a href="' . esc_url( menu_page_url( self::PAGE_SLUG, false ) ) . '">' . __( 'Settings', 'admin-notice-hub' ) . '</a>';
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
		check_ajax_referer( 'admin_notice_hub_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'admin-notice-hub' ) ) );
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
