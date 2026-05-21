<?php
/**
 * Plugin Name:       Admin Notice Hub
 * Plugin URI:        https://github.com/abdur-emon/admin-notice-hub
 * Description:       Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Abdur Rahman Emon
 * Author URI:        https://github.com/abdur-emon
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       admin-notice-hub
 * Domain Path:       /languages
 *
 * @package Admin_Notice_Hub
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Constants
 */
define( 'ADMIN_NOTICE_HUB_VERSION', '1.0.0' );
define( 'ADMIN_NOTICE_HUB_PLUGIN_FILE', __FILE__ );
define( 'ADMIN_NOTICE_HUB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADMIN_NOTICE_HUB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADMIN_NOTICE_HUB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader
 */
require_once ADMIN_NOTICE_HUB_PLUGIN_DIR . 'includes/core/class-autoloader.php';

// Initialize autoloader.
Admin_Notice_Hub\Core\Autoloader::register();

/**
 * Activation Hook
 */
function admin_notice_hub_activate_plugin() {
	require_once ADMIN_NOTICE_HUB_PLUGIN_DIR . 'includes/core/class-activator.php';
	Admin_Notice_Hub\Core\Activator::activate();
}
register_activation_hook( __FILE__, 'admin_notice_hub_activate_plugin' );

/**
 * Deactivation Hook
 */
function admin_notice_hub_deactivate_plugin() {
	require_once ADMIN_NOTICE_HUB_PLUGIN_DIR . 'includes/core/class-deactivator.php';
	Admin_Notice_Hub\Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'admin_notice_hub_deactivate_plugin' );

/**
 * Initialize Plugin.
 *
 * Translations are loaded automatically by WordPress 4.6+ for plugins hosted
 * on WordPress.org whose text domain matches the slug (which is the case here:
 * `admin-notice-hub`). A manual call to load_plugin_textdomain() is no longer
 * necessary and is flagged as a discouraged function by Plugin Check, so we
 * rely on the auto-loader.
 */
function admin_notice_hub_init_plugin() {
	$plugin = Admin_Notice_Hub\Core\Plugin::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'admin_notice_hub_init_plugin' );

