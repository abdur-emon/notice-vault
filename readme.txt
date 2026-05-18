=== Notice Tracker ===
Contributors: abduremon, mdismail-cse
Tags: Admin notices, hide admin notices, hide admin notifications, dashboard notices, notices
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control your WordPress dashboard with Notice Tracker - hide, manage, and organize admin alerts for a clean, distraction-free site.

== Description ==

= Take Control of Your Dashboard with Notice Tracker =
Is your WordPress dashboard cluttered with constant plugin alerts, theme warnings, and update notices? **Notice Tracker** captures every admin notice as it fires and moves it into a tidy popup, so you can keep working without the visual noise — and read everything later at your convenience.

WordPress admin notices can be helpful, but they often become overwhelming, taking up valuable screen real estate and disrupting your workflow. Notice Tracker gives you per-type rules, per-user visibility controls, and a single counter in the admin bar so nothing is ever missed.



= Why Choose Notice Tracker? =
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
Whether you are managing multiple sites or a blogger tired of constant upselling, **Notice Tracker** helps you maintain a professional, organized, and distraction-free environment.


**Stop the clutter and start focusing on what matters: Your Website.**


== Privacy Policy ==

**Notice Tracker does not:**

* Collect any user data
* Send data to external servers
* Use cookies or tracking
* Store personal information

**All notices are stored locally in your WordPress database and are automatically deleted after the configured expiration period.**



= About =
**Notice Tracker** is developed and maintained by Abdur Rahman Emon. Source code, issue tracking, and contributions are welcome on the project's GitHub repository.


== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for "Notice Tracker"
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

= Can I control who sees the notice tracker? =

Yes! You can configure visibility settings to show/hide the notice tracker for specific users or user roles.

= Does this work with multisite? =

Notice Tracker runs per-site on a multisite network — activate it on each subsite you want to manage. Network-wide automatic activation and shared notice storage across the network are not supported in 1.0.0.

= Will this slow down my site? =

No! The plugin uses lazy loading, transient caching, and minimal database queries. It has zero impact on frontend performance.

= Can I customize the pop-up appearance? =

Yes! You can choose from 3 popup styles: Slide from Right, Modal Popup, or Slide Background Panel.

= How long are notices stored? =

By default, notices are automatically deleted after 30 days. You can customize this in the settings.

= Is this plugin translation-ready? =

Yes! Notice Tracker is fully translation-ready with a .pot file included.


== Screenshots ==

1. Notice Type Setting panel
2. Pop-up Setting panel
3. User visibility panel
4. Auto Expires panel


== Changelog ==

= 1.0.0 (2026-03-05) =
* Initial release
* Notice capture system
* Admin toolbar integration
* Popup interface with 3 styles
* Settings page
* User visibility controls
* Auto-expire functionality


== Upgrade Notice ==

= 1.0.0 =
* [Major Update] Must Update.
