<?php
/**
 * Admin Toolbar Class
 *
 * Adds notice counter to WordPress admin bar.
 *
 * @package Admin_Notice_Hub
 * @subpackage Toolbar
 */

namespace Admin_Notice_Hub\Toolbar;

use Admin_Notice_Hub\Notices\Notice_Storage;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Toolbar Class
 *
 * Integrates with WordPress admin bar.
 *
 * @since 1.0.0
 */
class Admin_Toolbar {

	/**
	 * Notice Storage instance.
	 *
	 * @var \Admin_Notice_Hub\Notices\Notice_Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @param \Admin_Notice_Hub\Notices\Notice_Storage $storage Notice Storage instance.
	 */
	public function __construct( $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Add toolbar item to admin bar.
	 *
	 * @since 1.0.0
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function add_toolbar_item( $wp_admin_bar ) {
		// Only show to users who can see notices.
		if ( ! \Admin_Notice_Hub\Permissions\Visibility_Manager::can_see_notices() ) {
			return;
		}

		// Get unread notice count.
		$count = $this->storage->get_unread_count();

		// Build title with count.
		$title = $this->get_toolbar_title( $count );

		// Add parent menu item.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'admin-notice-hub-notices',
				'title' => $title,
				'href'  => '#',
				'meta'  => array(
					'class' => 'admin-notice-hub-toolbar-item',
					'title' => __( 'View Notices', 'admin-notice-hub' ),
				),
			)
		);

		// Add submenu items if there are notices.
		if ( $count > 0 ) {
			$this->add_submenu_items( $wp_admin_bar );
		}
	}

	/**
	 * Get toolbar title with count badge.
	 *
	 * @since 1.0.0
	 * @param int $count Notice count.
	 * @return string HTML title.
	 */
	private function get_toolbar_title( $count ) {
		$icon = '<span class="ab-icon dashicons dashicons-bell"></span>';

		if ( $count > 0 ) {
			$badge = sprintf(
				'<span class="admin-notice-hub-count-badge">%s</span>',
				esc_html( $count )
			);
			$text = esc_html__( 'Notices', 'admin-notice-hub' );
			return $icon . '<span class="ab-label">' . $text . '</span>' . $badge;
		}

		return $icon . '<span class="ab-label">' . esc_html__( 'Notices', 'admin-notice-hub' ) . '</span>';
	}

	/**
	 * Add submenu items.
	 *
	 * @since 1.0.0
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	private function add_submenu_items( $wp_admin_bar ) {
		// Get recent notices (limit to 5).
		$notices = $this->storage->get_all(
			array(
				'is_read' => false,
				'limit'   => 5,
			)
		);

		foreach ( $notices as $notice ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'admin-notice-hub-notices',
					'id'     => 'admin-notice-hub-notice-' . $notice['id'],
					'title'  => $this->get_notice_preview( $notice ),
					'href'   => '#',
					'meta'   => array(
						'class'           => 'admin-notice-hub-notice-preview',
						'data-notice-id'  => $notice['id'],
						'data-notice-type' => $notice['type'],
					),
				)
			);
		}

		// Add "View All" link.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'admin-notice-hub-notices',
				'id'     => 'admin-notice-hub-view-all',
				'title'  => esc_html__( 'View All Notices', 'admin-notice-hub' ),
				'href'   => '#',
				'meta'   => array(
					'class' => 'admin-notice-hub-view-all',
				),
			)
		);
	}

	/**
	 * Get notice preview text.
	 *
	 * @since 1.0.0
	 * @param array $notice Notice data.
	 * @return string Preview HTML.
	 */
	private function get_notice_preview( $notice ) {
		$content = isset( $notice['content'] ) ? $notice['content'] : '';

		// Strip tags to prevent broken HTML when truncating.
		$content = wp_strip_all_tags( $content );

		// Truncate to 50 characters.
		if ( strlen( $content ) > 50 ) {
			$content = substr( $content, 0, 50 ) . '...';
		}

		// Get icon.
		$icon_class = \Admin_Notice_Hub\Notices\Notice_Classifier::get_icon( $notice['type'] );

		return sprintf(
			'<span class="dashicons %s"></span> %s',
			esc_attr( $icon_class ),
			esc_html( $content )
		);
	}
}
