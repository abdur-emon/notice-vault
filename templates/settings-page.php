<?php
/**
 * Settings Page Template
 *
 * @package Admin_Notice_Hub
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap anh-settings-wrap">
	<h1>
		<span class="dashicons dashicons-bell"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<p class="anh-description">
		<?php esc_html_e( 'Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.', 'admin-notice-hub' ); ?>
	</p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( \Admin_Notice_Hub\Admin\Settings_Page::OPTION_GROUP );
		do_settings_sections( \Admin_Notice_Hub\Admin\Settings_Page::PAGE_SLUG );
		submit_button();
		?>
	</form>

	<div class="anh-info-box">
		<h3><?php esc_html_e( 'How It Works', 'admin-notice-hub' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Configure how each notice type should be handled above', 'admin-notice-hub' ); ?></li>
			<li><?php esc_html_e( 'Notices will be captured and stored based on your settings', 'admin-notice-hub' ); ?></li>
			<li><?php esc_html_e( 'Click "Notices" in the admin toolbar to view all captured notices', 'admin-notice-hub' ); ?></li>
			<li><?php esc_html_e( 'Mark notices as read or dismiss them from the popup', 'admin-notice-hub' ); ?></li>
		</ol>
	</div>

	<div class="anh-stats-box">
		<h3><?php esc_html_e( 'Statistics', 'admin-notice-hub' ); ?></h3>
		<?php
		$anh_storage        = \Admin_Notice_Hub\Core\Plugin::get_instance()->get_storage();
		$anh_total_notices  = count( $anh_storage->get_all() );
		$anh_unread_notices = $anh_storage->get_unread_count();
		?>
		<p>
			<strong><?php esc_html_e( 'Total Notices:', 'admin-notice-hub' ); ?></strong>
			<?php echo esc_html( $anh_total_notices ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Unread Notices:', 'admin-notice-hub' ); ?></strong>
			<?php echo esc_html( $anh_unread_notices ); ?>
		</p>
	</div>
</div>

