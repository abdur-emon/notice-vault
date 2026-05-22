# Changelog

All notable changes to Notice Vault will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-05-22

### Added
- Initial release of Notice Vault
- Notice capture system using output buffering across `admin_notices`, `network_admin_notices`, `user_admin_notices`, and `all_admin_notices`
- Notice classification by type (success, error, warning, info, system, other), with system detection running first so update-nag-style notices route to the System bucket even when they carry generic `notice-warning`/`notice-info` classes
- Per-user notice storage in a custom database table (`{prefix}notice_vault_notices`)
- Sanitized HTML rendering — captured notices preserve clickable links and basic formatting (`<a>`, `<strong>`, `<em>`, `<code>`, lists, …) through a strict server-side `wp_kses` allowlist; scripts, iframes, and inline event handlers always stripped
- Admin toolbar integration with notice counter + 5-item recent-notice preview submenu (clicking any preview opens the popup)
- Popup interface with 3 display styles (Slide from Right, Modal, Slide Background Panel) and "Load more" pagination for large notice lists
- Settings page with per-type rules (popup / hide / nothing), per-user visibility controls (show all, hide all, hide-selected, show-selected) with Select2-powered user picker, and configurable auto-expire window (1–365 days, default 30)
- Filterable popup type dropdown — custom buckets registered via the `notice_vault_notice_types` filter appear here too
- Mark-as-read, dismiss-one, mark-all-read, and clear-all (per-user) actions
- Daily cleanup cron that prunes expired notices and sweeps per-user unread-count transients
- Multisite-aware uninstall — iterates every blog and drops its per-blog notices table
- AJAX-powered popup loading with toast-based error feedback
- Transient caching for unread counts (1h TTL, invalidated on every write)
- PSR-4 autoloading, namespaced (`Notice_Vault\…`), object-oriented architecture
- Translation ready — `.pot` template + WP 4.6+ auto-loaded textdomain
- Uninstall script for clean removal of options, table, transients, and cron
- Silence `index.php` files in every directory

### Security
- All user inputs sanitized
- All outputs escaped
- Nonce verification on all forms and AJAX requests
- Capability checks on all admin actions
- SQL injection prevention with prepared statements
- XSS prevention
- CSRF prevention
- Direct file access prevented

### Performance
- Lazy loading of popup
- Transient caching for notice counts
- Minimal database queries
- No frontend performance impact
- Efficient output buffering

### Developer Features
- Clean, modular architecture
- PSR-4 autoloading
- SOLID principles
- Extensible with hooks and filters
- Well-documented code
- WordPress.org compliant

## [Unreleased]

### Planned Features
- Notice search functionality
- Notice export (CSV, JSON)
- Notice categories/tags
- Custom notice retention periods per type
- Email notifications for critical notices
- Dashboard widget
- WP-CLI commands
- REST API endpoints
- Notice statistics and analytics
- Bulk actions (mark all read, delete all)
- Notice filtering by date range
- Notice priority levels
- Custom notice colors
- Notice templates
- Integration with popular plugins

---

## Version History

- **1.0.0** - Initial Release (2026-05-22)

