<?php
/**
 * Plugin Name:       Notice Manager
 * Plugin URI:        https://example.com/notice-manager
 * Description:       Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       notice-manager
 * Domain Path:       /languages
 *
 * @package Notice_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Constants
 */
define( 'WPNM_VERSION', '1.0.0' );
define( 'WPNM_PLUGIN_FILE', __FILE__ );
define( 'WPNM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPNM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPNM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader
 */
require_once WPNM_PLUGIN_DIR . 'includes/core/class-autoloader.php';

// Initialize autoloader.
Notice_Manager\Core\Autoloader::register();

/**
 * Activation Hook
 */
function wpnm_activate_plugin() {
	require_once WPNM_PLUGIN_DIR . 'includes/core/class-activator.php';
	Notice_Manager\Core\Activator::activate();
}
register_activation_hook( __FILE__, 'wpnm_activate_plugin' );

/**
 * Deactivation Hook
 */
function wpnm_deactivate_plugin() {
	require_once WPNM_PLUGIN_DIR . 'includes/core/class-deactivator.php';
	Notice_Manager\Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'wpnm_deactivate_plugin' );

/**
 * Initialize Plugin
 */
function wpnm_init_plugin() {
	$plugin = Notice_Manager\Core\Plugin::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'wpnm_init_plugin' );

