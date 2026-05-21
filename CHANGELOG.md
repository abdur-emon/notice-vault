# Changelog

All notable changes to Admin Notice Hub will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-05-18

### Added
- Initial release of Admin Notice Hub
- Notice capture system with output buffering
- Notice classification by type (success, error, warning, info, system, other)
- Notice storage using WordPress options API
- Admin toolbar integration with notice counter
- Popup interface with 3 display styles:
  - Slide from Right
  - Modal Popup
  - Slide Background Panel
- Settings page with comprehensive controls
- Notice type configuration (popup, hide, nothing)
- User visibility controls (show all, hide all, hide selected, show selected)
- Auto-expire functionality (configurable days)
- Mark notices as read/unread
- Dismiss individual notices
- Clear all notices functionality
- AJAX-powered popup loading
- Real-time notice counter updates
- Transient caching for performance
- Scheduled cleanup cron job
- PSR-4 autoloading
- Object-oriented architecture
- WordPress coding standards compliance
- Security hardening:
  - Nonce verification
  - Capability checks
  - Input sanitization
  - Output escaping
  - Direct file access prevention
- Translation ready with .pot file
- README.md for GitHub browsers (the wp.org listing is rendered from `readme.txt`)
- Uninstall script for clean removal
- .htaccess for security
- Index.php files in all directories

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

- **1.0.0** - Initial Release (2026-05-18)

