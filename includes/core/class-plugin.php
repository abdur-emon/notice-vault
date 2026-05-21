<?php
/**
 * Plugin Class
 *
 * Main plugin orchestrator. Initializes all components.
 *
 * @package Admin_Notice_Hub
 * @subpackage Core
 */

namespace Admin_Notice_Hub\Core;

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
	 * Notice Storage instance.
	 *
	 * @var \Admin_Notice_Hub\Notices\Notice_Storage
	 */
	protected $storage;

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
		$this->version = ANH_VERSION;
		$this->loader  = new Loader();

		// Run pending migrations BEFORE instantiating storage so the table
		// definitely exists. Admin-only — front-end requests never need this.
		if ( is_admin() ) {
			Upgrader::maybe_upgrade();
		}

		$this->storage = new \Admin_Notice_Hub\Notices\Notice_Storage();

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
		$settings_page = new \Admin_Notice_Hub\Admin\Settings_Page();
		$this->loader->add_action( 'admin_menu', $settings_page, 'add_settings_page' );
		$this->loader->add_action( 'admin_init', $settings_page, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $settings_page, 'enqueue_assets' );
		$this->loader->add_action( 'wp_ajax_anh_search_users', $settings_page, 'ajax_search_users' );
		$this->loader->add_filter( 'plugin_action_links_' . ANH_PLUGIN_BASENAME, $settings_page, 'add_plugin_action_links' );

		// Initialize Notice Popup.
		$notice_popup = new \Admin_Notice_Hub\Admin\Notice_Popup( $this->storage );
		$this->loader->add_action( 'admin_enqueue_scripts', $notice_popup, 'enqueue_assets' );
		$this->loader->add_action( 'admin_footer', $notice_popup, 'render_popup' );
		$this->loader->add_action( 'wp_ajax_anh_get_notices', $notice_popup, 'ajax_get_notices' );
		$this->loader->add_action( 'wp_ajax_anh_mark_read', $notice_popup, 'ajax_mark_read' );
		$this->loader->add_action( 'wp_ajax_anh_dismiss_notice', $notice_popup, 'ajax_dismiss_notice' );
		$this->loader->add_action( 'wp_ajax_anh_mark_all_read', $notice_popup, 'ajax_mark_all_read' );
		$this->loader->add_action( 'wp_ajax_anh_clear_all', $notice_popup, 'ajax_clear_all' );

		// Initialize Admin Toolbar.
		$admin_toolbar = new \Admin_Notice_Hub\Toolbar\Admin_Toolbar( $this->storage );
		$this->loader->add_action( 'admin_bar_menu', $admin_toolbar, 'add_toolbar_item', 999 );
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
		// NOTE: all_admin_notices fires *after* the three specialized hooks, so its
		// buffer is independent and must be bracketed separately.
		$notice_capture = new \Admin_Notice_Hub\Notices\Notice_Capture( $this->storage );
		$this->loader->add_action( 'admin_notices', $notice_capture, 'start_capture', 0 );
		$this->loader->add_action( 'admin_notices', $notice_capture, 'end_capture', 9999 );
		$this->loader->add_action( 'network_admin_notices', $notice_capture, 'start_capture', 0 );
		$this->loader->add_action( 'network_admin_notices', $notice_capture, 'end_capture', 9999 );
		$this->loader->add_action( 'user_admin_notices', $notice_capture, 'start_capture', 0 );
		$this->loader->add_action( 'user_admin_notices', $notice_capture, 'end_capture', 9999 );
		$this->loader->add_action( 'all_admin_notices', $notice_capture, 'start_capture', 0 );
		$this->loader->add_action( 'all_admin_notices', $notice_capture, 'end_capture', 9999 );
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

	/**
	 * Get the shared Notice_Storage instance.
	 *
	 * @since 1.0.0
	 * @return \Admin_Notice_Hub\Notices\Notice_Storage
	 */
	public function get_storage()
	{
		return $this->storage;
	}
}
