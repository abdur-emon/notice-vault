<?php
/**
 * Plugin Name:       Notice Vault
 * Plugin URI:        https://github.com/abdur-emon/notice-vault
 * Description:       Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Abdur Rahman Emon
 * Author URI:        https://github.com/abdur-emon
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       notice-vault
 * Domain Path:       /languages
 *
 * @package Notice_Vault
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Constants
 */
define( 'NOTICE_VAULT_VERSION', '1.0.0' );
define( 'NOTICE_VAULT_PLUGIN_FILE', __FILE__ );
define( 'NOTICE_VAULT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOTICE_VAULT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOTICE_VAULT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader
 */
require_once NOTICE_VAULT_PLUGIN_DIR . 'includes/core/class-autoloader.php';

// Initialize autoloader.
Notice_Vault\Core\Autoloader::register();

/**
 * Activation Hook
 */
function notice_vault_activate_plugin() {
	require_once NOTICE_VAULT_PLUGIN_DIR . 'includes/core/class-activator.php';
	Notice_Vault\Core\Activator::activate();
}
register_activation_hook( __FILE__, 'notice_vault_activate_plugin' );

/**
 * Deactivation Hook
 */
function notice_vault_deactivate_plugin() {
	require_once NOTICE_VAULT_PLUGIN_DIR . 'includes/core/class-deactivator.php';
	Notice_Vault\Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'notice_vault_deactivate_plugin' );

/**
 * Initialize Plugin.
 *
 * Translations are loaded automatically by WordPress 4.6+ for plugins hosted
 * on WordPress.org whose text domain matches the slug (which is the case here:
 * `notice-vault`). A manual call to load_plugin_textdomain() is no longer
 * necessary and is flagged as a discouraged function by Plugin Check, so we
 * rely on the auto-loader.
 */
function notice_vault_init_plugin() {
	$plugin = Notice_Vault\Core\Plugin::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'notice_vault_init_plugin' );

