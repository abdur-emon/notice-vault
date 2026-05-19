<?php
/**
 * Visibility Manager Class
 *
 * Manages user visibility permissions.
 *
 * @package Quietboard_Notice_Manager
 * @subpackage Permissions
 */

namespace Quietboard_Notice_Manager\Permissions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visibility Manager Class
 *
 * Controls which users can see Quietboard Notice Manager.
 *
 * @since 1.0.0
 */
class Visibility_Manager {

	/**
	 * Check if current user can see notices.
	 *
	 * @since 1.0.0
	 * @param int $user_id Optional. User ID. Default current user.
	 * @return bool
	 */
	public static function can_see_notices( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Get settings.
		$settings = get_option( 'wpnm_settings', array() );
		$mode     = isset( $settings['visibility_mode'] ) ? $settings['visibility_mode'] : 'show-all';
		$users    = isset( $settings['visibility_users'] ) ? $settings['visibility_users'] : array();

		switch ( $mode ) {
			case 'hide-all':
				// Hide from all users.
				return false;

			case 'hide-selected':
				// Hide from selected users.
				return ! in_array( $user_id, $users, true );

			case 'show-selected':
				// Show only to selected users.
				return in_array( $user_id, $users, true );

			case 'show-all':
			default:
				// Show to all users.
				return true;
		}
	}

	/**
	 * Get visibility mode label.
	 *
	 * @since 1.0.0
	 * @param string $mode Visibility mode.
	 * @return string Label.
	 */
	public static function get_mode_label( $mode ) {
		$labels = array(
			'show-all'      => __( 'Show to all users', 'quietboard-notice-manager' ),
			'hide-all'      => __( 'Hide from all users', 'quietboard-notice-manager' ),
			'hide-selected' => __( 'Hide from selected users', 'quietboard-notice-manager' ),
			'show-selected' => __( 'Show to selected users', 'quietboard-notice-manager' ),
		);

		return isset( $labels[ $mode ] ) ? $labels[ $mode ] : $labels['show-all'];
	}
}

