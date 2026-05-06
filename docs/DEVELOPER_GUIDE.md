# Notice Tracker - Developer Guide

## 🎯 For Developers

This guide is for developers who want to understand, extend, or contribute to Notice Tracker.

---

## 📚 Quick Start

### Understanding the Architecture

Notice Tracker follows a **modular, object-oriented architecture**:

1. **Core System** - Plugin bootstrap, autoloading, activation/deactivation
2. **Notice System** - Capture, classify, and store notices
3. **Admin System** - Settings page and popup UI
4. **Toolbar System** - Admin bar integration
5. **Permissions System** - User visibility management

### Key Design Patterns

- **Singleton** - Plugin class (single instance)
- **Registry** - Loader class (hook management)
- **Factory** - Notice creation
- **Strategy** - Popup display modes
- **Observer** - WordPress hooks system

---

## 🔧 Extending the Plugin

### Adding Custom Notice Types

```php
// Filter notice types
add_filter('wpnm_notice_types', function($types) {
    $types['custom'] = __('Custom Notices', 'your-plugin');
    return $types;
});

// Classify custom notices
add_filter('wpnm_classify_notice', function($type, $html) {
    if (strpos($html, 'custom-notice-class') !== false) {
        return 'custom';
    }
    return $type;
}, 10, 2);
```

### Adding Custom Popup Styles

```php
// Add custom popup style
add_filter('wpnm_popup_styles', function($styles) {
    $styles['custom-style'] = __('Custom Style', 'your-plugin');
    return $styles;
});

// Add custom CSS
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('wpnm-custom-style', 'path/to/custom.css');
});
```

### Hooking into Notice Storage

```php
// Before notice is stored
add_action('wpnm_before_store_notice', function($notice) {
    // Modify or log notice before storage
    error_log('Storing notice: ' . $notice['type']);
});

// After notice is stored
add_action('wpnm_after_store_notice', function($notice_id, $notice) {
    // Perform actions after storage
    // e.g., send email notification
}, 10, 2);
```

### Custom Notice Filtering

```php
// Filter notices before display
add_filter('wpnm_get_notices', function($notices) {
    // Filter or modify notices
    return array_filter($notices, function($notice) {
        // Custom filtering logic
        return $notice['type'] !== 'unwanted';
    });
});
```

---

## 🏗️ Code Structure

### Namespace Convention

```
Notice_Tracker\
├── Core\
│   ├── Plugin
│   ├── Loader
│   ├── Autoloader
│   ├── Activator
│   ├── Deactivator
│   └── Cleanup
├── Notices\
│   ├── Notice_Capture
│   ├── Notice_Classifier
│   └── Notice_Storage
├── Admin\
│   ├── Settings_Page
│   └── Notice_Popup
├── Toolbar\
│   └── Admin_Toolbar
└── Permissions\
    └── Visibility_Manager
```

### File Naming Convention

- **Classes:** `class-{name}.php` (lowercase, hyphenated)
- **Templates:** `{name}-template.php`
- **Assets:** `{name}.css` or `{name}.js`

### Class Naming Convention

- **Classes:** `Class_Name` (PascalCase with underscores)
- **Methods:** `method_name()` (lowercase with underscores)
- **Properties:** `$property_name` (lowercase with underscores)

---

## 🔌 Available Hooks

### Actions

```php
// Plugin initialization
do_action('wpnm_init');

// Before notice capture
do_action('wpnm_before_capture');

// After notice capture
do_action('wpnm_after_capture', $notices);

// Before notice storage
do_action('wpnm_before_store_notice', $notice);

// After notice storage
do_action('wpnm_after_store_notice', $notice_id, $notice);

// Before notice deletion
do_action('wpnm_before_delete_notice', $notice_id);

// After notice deletion
do_action('wpnm_after_delete_notice', $notice_id);

// Cleanup event
do_action('wpnm_cleanup_notices');
```

### Filters

```php
// Filter notice types
apply_filters('wpnm_notice_types', $types);

// Filter notice classification
apply_filters('wpnm_classify_notice', $type, $html);

// Filter notice before storage
apply_filters('wpnm_before_store_notice', $notice);

// Filter retrieved notices
apply_filters('wpnm_get_notices', $notices, $args);

// Filter popup styles
apply_filters('wpnm_popup_styles', $styles);

// Filter visibility check
apply_filters('wpnm_can_see_notices', $can_see, $user_id);

// Filter expiration days
apply_filters('wpnm_expiration_days', $days);
```

---

## 🧪 Testing

### Manual Testing

1. **Install** the plugin in a test environment
2. **Activate** and configure settings
3. **Trigger** various notice types
4. **Verify** capture and display
5. **Test** all AJAX actions
6. **Check** for JavaScript errors
7. **Validate** security measures

### Automated Testing (Future)

```bash
# PHPUnit tests (to be implemented)
composer install
vendor/bin/phpunit

# WordPress coding standards
phpcs --standard=WordPress notice-tracker.php
```

---

## 🔐 Security Checklist for Developers

When extending the plugin, always:

- ✅ Sanitize all inputs
- ✅ Escape all outputs
- ✅ Verify nonces on forms
- ✅ Check user capabilities
- ✅ Use prepared statements
- ✅ Validate data types
- ✅ Prevent direct file access

---

## 📊 Database Schema

### Options Table

```
wp_options
├── wpnm_settings (array)
│   ├── notice_success
│   ├── notice_error
│   ├── notice_warning
│   ├── notice_info
│   ├── notice_other
│   ├── notice_system
│   ├── popup_style
│   ├── visibility_mode
│   ├── visibility_users
│   └── auto_expire_days
│
└── wpnm_notices (array)
    └── [notice_id] (array)
        ├── id
        ├── type
        ├── content
        ├── html
        ├── hash
        ├── is_read
        ├── created_at
        └── expires_at
```

---

## 🚀 Performance Tips

### For Plugin Developers

1. **Use transients** for caching
2. **Lazy load** heavy components
3. **Minimize** database queries
4. **Batch** operations when possible
5. **Use** WordPress APIs

### Example: Efficient Notice Retrieval

```php
// Good - Uses transient caching
$count = get_transient('wpnm_notice_count');
if (false === $count) {
    $count = count(Notice_Storage::get_all(['is_read' => false]));
    set_transient('wpnm_notice_count', $count, HOUR_IN_SECONDS);
}

// Bad - Queries every time
$count = count(Notice_Storage::get_all(['is_read' => false]));
```

---

## 🤝 Contributing

### Code Style

Follow **WordPress PHP Coding Standards**:

```php
// Good
if ( ! empty( $variable ) ) {
    do_something( $variable );
}

// Bad
if(!empty($variable)){
    do_something($variable);
}
```

### Commit Messages

Use clear, descriptive commit messages:

```
✅ Good: "Add notice filtering by date range"
❌ Bad: "Update code"
```

---

## 📞 Support for Developers

- **Documentation:** Read all .md files in the root directory
- **Code Comments:** All classes and methods are documented
- **Architecture:** See ARCHITECTURE.md for design details
- **Security:** See SECURITY.md for security implementation

---

## 🎓 Learning Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)

---

**Happy Coding! 🚀**

