<?php
/**
 * Plugin Class
 *
 * Main plugin orchestrator. Initializes all components.
 *
 * @package WP_Notice_Manager
 * @subpackage Core
 */

namespace WP_Notice_Manager\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Plugin Class
 *
 * Singleton pattern. Main entry point for the plugin.
 *
 * @since 1.0.0
 */
class Plugin
{

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Loader instance.
	 *
	 * @var Loader
	 */
	protected $loader;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Get plugin instance (Singleton).
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct()
	{
		$this->version = WPNM_VERSION;
		$this->loader = new Loader();

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_notice_hooks();

		// Initialize cleanup cron (admin only to avoid frontend overhead).
		if (is_admin()) {
			Cleanup::init();
		}
	}

	/**
	 * Load required dependencies.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies()
	{
		// Dependencies are autoloaded via PSR-4.
		// This method is here for any manual requires if needed.
	}

	/**
	 * Define admin-related hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function define_admin_hooks()
	{
		// Only load admin components in admin area.
		if (!is_admin()) {
			return;
		}

		// Initialize Settings Page.
		$settings_page = new \WP_Notice_Manager\Admin\Settings_Page();
		$this->loader->add_action('admin_menu', $settings_page, 'add_settings_page');
		$this->loader->add_action('admin_init', $settings_page, 'register_settings');
		$this->loader->add_action('admin_enqueue_scripts', $settings_page, 'enqueue_assets');
		$this->loader->add_action('wp_ajax_wpnm_search_users', $settings_page, 'ajax_search_users');
		$this->loader->add_filter('plugin_action_links_' . WPNM_PLUGIN_BASENAME, $settings_page, 'add_plugin_action_links');

		// Initialize Notice Popup.
		$notice_popup = new \WP_Notice_Manager\Admin\Notice_Popup();
		$this->loader->add_action('admin_enqueue_scripts', $notice_popup, 'enqueue_assets');
		$this->loader->add_action('admin_footer', $notice_popup, 'render_popup');
		$this->loader->add_action('wp_ajax_wpnm_get_notices', $notice_popup, 'ajax_get_notices');
		$this->loader->add_action('wp_ajax_wpnm_mark_read', $notice_popup, 'ajax_mark_read');
		$this->loader->add_action('wp_ajax_wpnm_dismiss_notice', $notice_popup, 'ajax_dismiss_notice');
		$this->loader->add_action('wp_ajax_wpnm_mark_all_read', $notice_popup, 'ajax_mark_all_read');
		$this->loader->add_action('wp_ajax_wpnm_clear_all', $notice_popup, 'ajax_clear_all');

		// Initialize Admin Toolbar.
		$admin_toolbar = new \WP_Notice_Manager\Toolbar\Admin_Toolbar();
		$this->loader->add_action('admin_bar_menu', $admin_toolbar, 'add_toolbar_item', 999);
	}

	/**
	 * Define notice capture hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function define_notice_hooks()
	{
		// Only capture notices in admin area.
		if (!is_admin()) {
			return;
		}

		// Initialize Notice Capture.
		$notice_capture = new \WP_Notice_Manager\Notices\Notice_Capture();
		$this->loader->add_action('admin_notices', $notice_capture, 'start_capture', 0);
		$this->loader->add_action('admin_notices', $notice_capture, 'end_capture', 9999);
		$this->loader->add_action('network_admin_notices', $notice_capture, 'start_capture', 0);
		$this->loader->add_action('network_admin_notices', $notice_capture, 'end_capture', 9999);
		$this->loader->add_action('user_admin_notices', $notice_capture, 'start_capture', 0);
		$this->loader->add_action('user_admin_notices', $notice_capture, 'end_capture', 9999);
		$this->loader->add_action('all_admin_notices', $notice_capture, 'start_capture', 0);
		$this->loader->add_action('all_admin_notices', $notice_capture, 'end_capture', 9999);
	}

	/**
	 * Run the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * Get the loader.
	 *
	 * @since 1.0.0
	 * @return Loader
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Get plugin version.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version()
	{
		return $this->version;
	}
}

