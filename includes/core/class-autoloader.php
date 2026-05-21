<?php
/**
 * Autoloader Class
 *
 * PSR-4 compliant autoloader for the plugin.
 *
 * @package Admin_Notice_Hub
 * @subpackage Core
 */

namespace Admin_Notice_Hub\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader Class
 *
 * Automatically loads classes following PSR-4 naming convention.
 *
 * @since 1.0.0
 */
class Autoloader {

	/**
	 * Namespace prefix for the plugin.
	 *
	 * @var string
	 */
	private static $prefix = 'Admin_Notice_Hub\\';

	/**
	 * Base directory for the namespace prefix.
	 *
	 * @var string
	 */
	private static $base_dir;

	/**
	 * Register the autoloader.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register() {
		self::$base_dir = ADMIN_NOTICE_HUB_PLUGIN_DIR . 'includes/';
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes.
	 *
	 * @since 1.0.0
	 * @param string $class The fully-qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		// Check if the class uses the namespace prefix.
		$len = strlen( self::$prefix );
		if ( strncmp( self::$prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Convert namespace to directory structure.
		// Admin_Notice_Hub\Core\Plugin -> core/class-plugin.php
		$parts = explode( '\\', $relative_class );

		// Last part is the class name.
		$class_name = array_pop( $parts );

		// Convert class name to file name (Class_Name -> class-class-name).
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		// Convert namespace parts to directory path.
		$directory = strtolower( implode( '/', $parts ) );

		// Build the file path.
		$file = self::$base_dir . ( $directory ? $directory . '/' : '' ) . $file_name;

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

