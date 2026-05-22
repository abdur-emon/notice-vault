=== Notice Vault ===
Contributors: abduremon, mdismail-cse
Tags: admin notices, dashboard, notifications, productivity
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control your WordPress dashboard with Notice Vault - hide, manage, and organize admin alerts for a clean, distraction-free site.

== Description ==

= Take Control of Your Dashboard with Notice Vault =
Is your WordPress dashboard cluttered with constant plugin alerts, theme warnings, and update notices? **Notice Vault** captures every admin notice as it fires and moves it into a tidy popup, so you can keep working without the visual noise — and read everything later at your convenience.

WordPress admin notices can be helpful, but they often become overwhelming, taking up valuable screen real estate and disrupting your workflow. Notice Vault gives you per-type rules, per-user visibility controls, and a single counter in the admin bar so nothing is ever missed.



= Why Choose Notice Vault? =
* **Declutter Your Workspace:** Move noisy admin notices out of the dashboard and into a dedicated popup.
* **Improve Productivity:** Focus on your content instead of closing endless alert boxes.
* **Granular Control:** Configure every notice category separately — popup, hide, or leave alone.
* **Lightweight & Fast:** Admin-only by design. Zero overhead on the front end.


= Key Features =
* **Capture & Centralize:** Admin notices fired through `admin_notices`, `network_admin_notices`, `user_admin_notices`, and `all_admin_notices` are captured into a single popup.
* **Per-Type Rules:** Configure each of the six notice categories (success, error, warning, info, system, non-standard) to either show in the popup, hide completely, or leave on the dashboard as usual.
* **Admin Bar Counter:** A "Notices" item appears in the WordPress admin bar with a live unread counter and a quick preview of your most recent notices.
* **Three Popup Styles:** Pick between Slide from Right (default), Modal Popup (centered), or Slide Background Panel.
* **Per-User Visibility:** Show the tracker to everyone, hide it from everyone, restrict it to a chosen list of users, or hide it from a chosen list.
* **Mark as Read & Dismiss:** Mark notices read individually or in bulk, or dismiss them entirely. Read state is per-user, so each admin keeps their own view.
* **Auto-Expire:** Notices older than a configurable number of days (default 30, range 1–365) are removed automatically by a daily cron job.
* **Privacy-First:** All notices are stored locally in your WordPress database. No external services, no tracking, no phone-home.


= Experience a Distraction-Free WordPress Workflow =
Whether you are managing multiple sites or a blogger tired of constant upselling, **Notice Vault** helps you maintain a professional, organized, and distraction-free environment.


**Stop the clutter and start focusing on what matters: Your Website.**


== Privacy Policy ==

**Notice Vault does not:**

* Collect any user data
* Send data to external servers
* Use cookies or tracking
* Store personal information

**All notices are stored locally in your WordPress database and are automatically deleted after the configured expiration period.**



= About =
**Notice Vault** is developed and maintained by Abdur Rahman Emon and Md. Ismail. Source code, issue tracking, and contributions are welcome on the project's GitHub repository.


== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for "Notice Vault"
4. Click **Install Now**
5. Activate the plugin

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Activate the plugin

== Frequently Asked Questions ==

= Does this plugin hide all admin notices? =

No, you have full control. You can configure each notice type separately to either show in the pop-up, hide completely, or leave it in the dashboard.

= Will I miss important notices? =

No! Notices are captured and stored in the pop-up. You'll see a counter in the admin toolbar showing how many unread notices you have.

= Can I control who sees Notice Vault? =

Yes! You can configure visibility settings to show or hide Notice Vault for specific users or user roles.

= Does this work with multisite? =

Notice Vault runs per-site on a multisite network — activate it on each subsite you want to manage. Network-wide automatic activation and shared notice storage across the network are not supported in 1.0.0.

= Will this slow down my site? =

No! The plugin uses lazy loading, transient caching, and minimal database queries. It has zero impact on frontend performance.

= Can I customize the pop-up appearance? =

Yes! You can choose from 3 popup styles: Slide from Right, Modal Popup, or Slide Background Panel.

= How long are notices stored? =

By default, notices are automatically deleted after 30 days. You can customize this in the settings.

= Is this plugin translation-ready? =

Yes! Notice Vault is fully translation-ready with a .pot file included.

= What happens immediately after I activate the plugin? =

By default, every captured notice category (success, error, warning, info, system, non-standard) is set to **"Show in popup & hide from dashboard"**. The moment the plugin is active, admin notices from every plugin and theme will move from your dashboard into the Notices popup instead of appearing inline. You can change this per category at **Notice Vault → Settings** — choose **"Hide completely"** to drop the notice entirely, or **"Do nothing"** to let it render on the dashboard as it normally would.


== Screenshots ==

1. Notice Type panel
2. Pop-up, User visibility & Auto Expires panel


== Changelog ==

= 1.0.0 - 2026-05-22 =
* Initial release.
* Notice capture system covering `admin_notices`, `network_admin_notices`, `user_admin_notices`, and `all_admin_notices`.
* Admin toolbar counter with quick-preview submenu.
* Popup interface with three display styles (Slide from Right, Modal, Slide Background Panel).
* Settings page with per-type rules, per-user visibility controls, and configurable auto-expire window.
* "Load more" pagination in the popup for users with large notice lists.
* Captured notice content preserves clickable links and basic formatting through a strict server-side `wp_kses` allowlist (`<a>`, `<strong>`, `<em>`, `<code>`, lists, …); scripts, iframes, and inline event handlers always stripped.
* System notices (`update-nag`, `update-message`, …) are detected before generic `notice-*` severity classes so they reliably route to the System bucket.
* Filterable popup type dropdown — custom buckets registered via the `notice_vault_notice_types` filter appear here too.
* Multisite uninstall iterates every blog and drops its per-blog notices table.
* Daily cleanup cron sweeps per-user unread-count transients when anything actually expires, so the admin-bar badge can't show a stale count past expiry.
* Long URLs inside notice content wrap instead of overflowing the popup.
* Confirm modal is localized and specific to the clear-all action.
* AJAX errors (mark-as-read / dismiss / mark-all-read / clear-all) surface a toast on failure instead of silently doing nothing.


== Upgrade Notice ==

= 1.0.0 =
Initial release.
