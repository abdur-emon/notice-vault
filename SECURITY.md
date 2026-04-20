# Notice Manager - Security Documentation

## 🔒 Security Overview

This document outlines all security measures implemented in Notice Manager to ensure the plugin meets WordPress.org security standards and protects against common vulnerabilities.

---

## 🛡️ Security Principles

1. **Never Trust User Input**
2. **Escape All Output**
3. **Verify Intentions (Nonces)**
4. **Check Permissions (Capabilities)**
5. **Prevent Direct Access**
6. **Use WordPress APIs**

---

## 🔐 Security Implementations

### 1. Direct File Access Prevention

**All PHP files include:**
```php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
```

**Purpose:** Prevents direct execution of PHP files outside WordPress context.

---

### 2. Nonce Verification

**Implementation:**
```php
// Generate nonce
wp_nonce_field( 'wpnm_save_settings', 'wpnm_settings_nonce' );

// Verify nonce
if ( ! isset( $_POST['wpnm_settings_nonce'] ) || 
     ! wp_verify_nonce( $_POST['wpnm_settings_nonce'], 'wpnm_save_settings' ) ) {
    wp_die( 'Security check failed' );
}
```

**Applied to:**
- Settings form submissions
- AJAX requests
- Notice dismissal actions
- User visibility updates

**Purpose:** Prevents CSRF (Cross-Site Request Forgery) attacks.

---

### 3. Capability Checks

**Implementation:**
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized access' );
}
```

**Capabilities Used:**
- `manage_options` - Settings page access
- `read` - View notices (minimum)
- Custom capabilities for visibility control

**Purpose:** Ensures only authorized users can perform actions.

---

### 4. Input Sanitization

**Functions Used:**

| Input Type | Sanitization Function |
|-----------|----------------------|
| Text fields | `sanitize_text_field()` |
| Textarea | `sanitize_textarea_field()` |
| Email | `sanitize_email()` |
| URLs | `esc_url_raw()` |
| HTML | `wp_kses_post()` |
| Arrays | `array_map()` with sanitization |
| Integers | `absint()` or `intval()` |

**Example:**
```php
$notice_type = sanitize_text_field( $_POST['notice_type'] );
$user_ids = array_map( 'absint', $_POST['user_ids'] );
```

**Purpose:** Prevents malicious data from entering the database.

---

### 5. Output Escaping

**Functions Used:**

| Output Context | Escaping Function |
|---------------|------------------|
| HTML content | `esc_html()` |
| HTML attributes | `esc_attr()` |
| URLs | `esc_url()` |
| JavaScript | `esc_js()` |
| Textarea | `esc_textarea()` |
| Translation | `esc_html__()`, `esc_attr__()` |

**Example:**
```php
<div class="notice-content">
    <?php echo esc_html( $notice_content ); ?>
</div>

<a href="<?php echo esc_url( $notice_link ); ?>">
    <?php echo esc_html( $notice_title ); ?>
</a>
```

**Purpose:** Prevents XSS (Cross-Site Scripting) attacks.

---

### 6. SQL Injection Prevention

**Implementation:**
```php
global $wpdb;

// Use prepared statements
$results = $wpdb->get_results( 
    $wpdb->prepare( 
        "SELECT * FROM {$wpdb->prefix}notice_manager WHERE notice_type = %s AND is_read = %d",
        $notice_type,
        $is_read
    )
);

// Use %s for strings, %d for integers, %f for floats
```

**Purpose:** Prevents SQL injection attacks.

---

### 7. AJAX Security

**Implementation:**
```php
// Register AJAX action
add_action( 'wp_ajax_wpnm_mark_read', array( $this, 'mark_notice_read' ) );

// Handler function
public function mark_notice_read() {
    // Verify nonce
    check_ajax_referer( 'wpnm_ajax_nonce', 'nonce' );
    
    // Check capability
    if ( ! current_user_can( 'read' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    
    // Sanitize input
    $notice_id = absint( $_POST['notice_id'] );
    
    // Process...
    
    wp_send_json_success( $data );
}
```

**Purpose:** Secures AJAX endpoints.

---

### 8. Data Validation

**Implementation:**
```php
// Validate notice type
$allowed_types = array( 'success', 'error', 'warning', 'info', 'notice' );
if ( ! in_array( $notice_type, $allowed_types, true ) ) {
    return new WP_Error( 'invalid_type', 'Invalid notice type' );
}

// Validate user IDs
$user_ids = array_filter( $user_ids, function( $id ) {
    return get_user_by( 'id', $id ) !== false;
} );
```

**Purpose:** Ensures data integrity.

---

## 🚨 Vulnerability Prevention

### XSS (Cross-Site Scripting)
- ✅ All outputs escaped
- ✅ User-generated content sanitized
- ✅ HTML filtered with `wp_kses_post()`

### CSRF (Cross-Site Request Forgery)
- ✅ Nonces on all forms
- ✅ Nonces on AJAX requests
- ✅ Referrer checking

### SQL Injection
- ✅ Prepared statements used
- ✅ No direct SQL queries
- ✅ WordPress database API used

### Authentication Bypass
- ✅ Capability checks on all actions
- ✅ User verification
- ✅ Session validation

### File Inclusion
- ✅ No dynamic file includes
- ✅ Direct access prevented
- ✅ Autoloader uses whitelist

---

## 🔍 Security Audit Checklist

- [ ] All files have direct access prevention
- [ ] All forms have nonce verification
- [ ] All actions have capability checks
- [ ] All inputs are sanitized
- [ ] All outputs are escaped
- [ ] All database queries use prepared statements
- [ ] All AJAX endpoints are secured
- [ ] No sensitive data in JavaScript
- [ ] No credentials in code
- [ ] Error messages don't reveal system info

---

## 📋 WordPress.org Security Requirements

✅ **No external dependencies without disclosure**  
✅ **No obfuscated code**  
✅ **No phone-home functionality**  
✅ **No cryptocurrency mining**  
✅ **No unauthorized data collection**  
✅ **Proper licensing (GPL-compatible)**

---

## 🔐 Secure Coding Practices

### 1. Use WordPress Functions
```php
// ✅ Good
$value = get_option( 'wpnm_setting' );

// ❌ Bad
$value = $_COOKIE['wpnm_setting'];
```

### 2. Validate Before Sanitize
```php
// ✅ Good
if ( isset( $_POST['user_id'] ) ) {
    $user_id = absint( $_POST['user_id'] );
}

// ❌ Bad
$user_id = absint( $_POST['user_id'] );
```

### 3. Use Type Checking
```php
// ✅ Good
if ( in_array( $type, $allowed, true ) ) { }

// ❌ Bad
if ( in_array( $type, $allowed ) ) { }
```

---

## 🛠️ Security Testing

### Manual Testing
1. Test with user roles (admin, editor, subscriber)
2. Test AJAX without nonces
3. Test SQL injection attempts
4. Test XSS payloads
5. Test CSRF attacks

### Automated Testing
- Use WordPress Plugin Check
- Use PHP CodeSniffer with WordPress standards
- Use security scanners

---

## 📞 Security Disclosure

If you discover a security vulnerability, please email:
**security@example.com**

Do not create public GitHub issues for security vulnerabilities.

---

## 📝 Security Changelog

### Version 1.0.0
- Initial security implementation
- All security measures in place
- Security audit completed

