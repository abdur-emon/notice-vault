<?php
/**
 * Settings Page Template
 *
 * @package Quietboard_Notice_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wpnm-settings-wrap">
	<h1>
		<span class="dashicons dashicons-bell"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<p class="wpnm-description">
		<?php esc_html_e( 'Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.', 'quietboard-notice-manager' ); ?>
	</p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( \Quietboard_Notice_Manager\Admin\Settings_Page::OPTION_GROUP );
		do_settings_sections( \Quietboard_Notice_Manager\Admin\Settings_Page::PAGE_SLUG );
		submit_button();
		?>
	</form>

	<div class="wpnm-info-box">
		<h3><?php esc_html_e( 'How It Works', 'quietboard-notice-manager' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Configure how each notice type should be handled above', 'quietboard-notice-manager' ); ?></li>
			<li><?php esc_html_e( 'Notices will be captured and stored based on your settings', 'quietboard-notice-manager' ); ?></li>
			<li><?php esc_html_e( 'Click "Notices" in the admin toolbar to view all captured notices', 'quietboard-notice-manager' ); ?></li>
			<li><?php esc_html_e( 'Mark notices as read or dismiss them from the popup', 'quietboard-notice-manager' ); ?></li>
		</ol>
	</div>

	<div class="wpnm-stats-box">
		<h3><?php esc_html_e( 'Statistics', 'quietboard-notice-manager' ); ?></h3>
		<?php
		$wpnm_storage        = \Quietboard_Notice_Manager\Core\Plugin::get_instance()->get_storage();
		$wpnm_total_notices  = count( $wpnm_storage->get_all() );
		$wpnm_unread_notices = $wpnm_storage->get_unread_count();
		?>
		<p>
			<strong><?php esc_html_e( 'Total Notices:', 'quietboard-notice-manager' ); ?></strong>
			<?php echo esc_html( $wpnm_total_notices ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Unread Notices:', 'quietboard-notice-manager' ); ?></strong>
			<?php echo esc_html( $wpnm_unread_notices ); ?>
		</p>
	</div>
</div>

