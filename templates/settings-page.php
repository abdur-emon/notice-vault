<?php
/**
 * Settings Page Template
 *
 * @package Notice_Vault
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap notice-vault-settings-wrap">
	<h1>
		<span class="dashicons dashicons-bell"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<p class="notice-vault-description">
		<?php esc_html_e( 'Manage and organize WordPress admin notices by moving them from the cluttered dashboard into a centralized notice management system.', 'notice-vault' ); ?>
	</p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( \Notice_Vault\Admin\Settings_Page::OPTION_GROUP );
		do_settings_sections( \Notice_Vault\Admin\Settings_Page::PAGE_SLUG );
		submit_button();
		?>
	</form>

	<div class="notice-vault-info-box">
		<h3><?php esc_html_e( 'How It Works', 'notice-vault' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Configure how each notice type should be handled above', 'notice-vault' ); ?></li>
			<li><?php esc_html_e( 'Notices will be captured and stored based on your settings', 'notice-vault' ); ?></li>
			<li><?php esc_html_e( 'Click "Notices" in the admin toolbar to view all captured notices', 'notice-vault' ); ?></li>
			<li><?php esc_html_e( 'Mark notices as read or dismiss them from the popup', 'notice-vault' ); ?></li>
		</ol>
	</div>

	<div class="notice-vault-stats-box">
		<h3><?php esc_html_e( 'Statistics', 'notice-vault' ); ?></h3>
		<?php
		$notice_vault_storage        = \Notice_Vault\Core\Plugin::get_instance()->get_storage();
		$notice_vault_total_notices  = count( $notice_vault_storage->get_all() );
		$notice_vault_unread_notices = $notice_vault_storage->get_unread_count();
		?>
		<p>
			<strong><?php esc_html_e( 'Total Notices:', 'notice-vault' ); ?></strong>
			<?php echo esc_html( $notice_vault_total_notices ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Unread Notices:', 'notice-vault' ); ?></strong>
			<?php echo esc_html( $notice_vault_unread_notices ); ?>
		</p>
	</div>
</div>

