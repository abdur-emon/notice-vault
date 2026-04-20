<?php
/**
 * Settings Page Template
 *
 * @package Notice_Manager
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
		<?php esc_html_e( 'Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.', 'notice-manager' ); ?>
	</p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( \Notice_Manager\Admin\Settings_Page::OPTION_GROUP );
		do_settings_sections( \Notice_Manager\Admin\Settings_Page::PAGE_SLUG );
		submit_button();
		?>
	</form>

	<div class="wpnm-info-box">
		<h3><?php esc_html_e( 'How It Works', 'notice-manager' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Configure how each notice type should be handled above', 'notice-manager' ); ?></li>
			<li><?php esc_html_e( 'Notices will be captured and stored based on your settings', 'notice-manager' ); ?></li>
			<li><?php esc_html_e( 'Click "Notices" in the admin toolbar to view all captured notices', 'notice-manager' ); ?></li>
			<li><?php esc_html_e( 'Mark notices as read or dismiss them from the popup', 'notice-manager' ); ?></li>
		</ol>
	</div>

	<div class="wpnm-stats-box">
		<h3><?php esc_html_e( 'Statistics', 'notice-manager' ); ?></h3>
		<?php
		$total_notices  = count( \Notice_Manager\Notices\Notice_Storage::get_all() );
		$unread_notices = \Notice_Manager\Notices\Notice_Storage::get_unread_count();
		?>
		<p>
			<strong><?php esc_html_e( 'Total Notices:', 'notice-manager' ); ?></strong>
			<?php echo esc_html( $total_notices ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Unread Notices:', 'notice-manager' ); ?></strong>
			<?php echo esc_html( $unread_notices ); ?>
		</p>
	</div>
</div>

