# Notice Manager - Architecture Documentation

## 🏗️ Architecture Overview

Notice Manager follows a **modular, object-oriented architecture** based on SOLID principles and WordPress coding standards.

---

## 📁 Directory Structure

```
notice-manager/
│
├── notice-manager.php          # Main plugin file (bootstrap)
│
├── includes/
│   ├── core/
│   │   ├── class-plugin.php       # Main plugin orchestrator
│   │   ├── class-loader.php       # Hooks & filters manager
│   │   ├── class-autoloader.php   # PSR-4 autoloader
│   │   ├── class-activator.php    # Plugin activation logic
│   │   └── class-deactivator.php  # Plugin deactivation logic
│   │
│   ├── notices/
│   │   ├── class-notice-capture.php    # Captures admin notices
│   │   ├── class-notice-classifier.php # Classifies notice types
│   │   ├── class-notice-storage.php    # Stores notices
│   │   └── class-notice-manager.php    # Manages notice lifecycle
│   │
│   ├── admin/
│   │   ├── class-settings-page.php     # Settings page UI
│   │   └── class-notice-popup.php      # Popup UI renderer
│   │
│   ├── toolbar/
│   │   └── class-admin-toolbar.php     # Admin bar integration
│   │
│   └── permissions/
│       └── class-visibility-manager.php # User visibility control
│
├── assets/
│   ├── css/
│   │   ├── admin.css              # Admin settings styles
│   │   └── popup.css              # Popup UI styles
│   │
│   └── js/
│       ├── admin.js               # Admin settings scripts
│       └── popup.js               # Popup UI scripts
│
├── templates/
│   ├── popup-template.php         # Popup HTML template
│   └── settings-page.php          # Settings page template
│
├── languages/
│   └── notice-manager.pot      # Translation template
│
├── docs/
│   ├── ARCHITECTURE.md            # This file
│   ├── SECURITY.md                # Security documentation
│   └── DEVELOPMENT_TRACKING.md    # Development progress
│
└── README.md                      # Plugin readme
```

---

## 🔧 Core Components

### 1. Plugin Bootstrap (`notice-manager.php`)
- Entry point for WordPress
- Defines constants
- Loads autoloader
- Initializes main plugin class

### 2. Core System (`includes/core/`)

#### Plugin Class
- **Responsibility:** Main orchestrator
- **Pattern:** Singleton
- **Functions:**
  - Initialize all components
  - Define plugin metadata
  - Coordinate subsystems

#### Loader Class
- **Responsibility:** Manage hooks and filters
- **Pattern:** Registry
- **Functions:**
  - Register actions
  - Register filters
  - Execute registered hooks

#### Autoloader Class
- **Responsibility:** Class autoloading
- **Pattern:** PSR-4
- **Functions:**
  - Map namespaces to directories
  - Load classes on demand

#### Activator Class
- **Responsibility:** Plugin activation
- **Functions:**
  - Create database tables (if needed)
  - Set default options
  - Check system requirements

#### Deactivator Class
- **Responsibility:** Plugin deactivation
- **Functions:**
  - Cleanup temporary data
  - Clear scheduled events

---

## 🎯 Notice Capture System

### How It Works

```
WordPress Admin Notice
        ↓
Output Buffer Capture (ob_start)
        ↓
Notice Classifier (detect type)
        ↓
Notice Storage (save to DB/options)
        ↓
Remove from Dashboard (ob_clean)
        ↓
Display in Popup (on demand)
```

### Notice Classifier
Detects notice types by analyzing:
- CSS classes (`notice-success`, `notice-error`, etc.)
- HTML structure
- WordPress core patterns

### Notice Storage Strategy
- **Small sites:** Use `wp_options` with transients
- **Large sites:** Custom table (if > 100 notices)
- **Expiration:** Auto-delete after 30 days

---

## 🎨 UI Components

### Admin Toolbar
- Position: WordPress admin bar (top-right)
- Display: "Notices (count)"
- Action: Opens popup on click

### Popup System
Three display modes:
1. **Slide from Right** (default)
2. **Modal Popup** (centered overlay)
3. **Slide Background Panel** (full-height sidebar)

---

## 🔐 Security Architecture

### Defense Layers
1. **Input Validation:** All user inputs validated
2. **Sanitization:** All data sanitized before storage
3. **Escaping:** All outputs escaped
4. **Nonces:** All forms protected
5. **Capabilities:** All actions checked
6. **Direct Access:** All files protected

---

## ⚡ Performance Strategy

### Optimization Techniques
- **Lazy Loading:** Load popup only when clicked
- **Transient Caching:** Cache notice counts
- **Minimal Queries:** Batch database operations
- **Conditional Loading:** Load assets only on admin pages

---

## 🔄 Data Flow

### Notice Capture Flow
```
1. User triggers admin action
2. WordPress generates notice
3. Notice Capture hooks into 'admin_notices'
4. Output buffer captures HTML
5. Classifier determines type
6. Storage saves notice
7. Buffer cleared (notice hidden)
```

### Notice Display Flow
```
1. User clicks toolbar icon
2. AJAX request to load notices
3. Notice Manager retrieves from storage
4. Popup renders notices
5. User marks as read
6. AJAX updates read status
```

---

## 🧩 Design Patterns Used

- **Singleton:** Plugin class
- **Registry:** Loader class
- **Factory:** Notice creation
- **Strategy:** Popup display modes
- **Observer:** WordPress hooks system

---

## 🌐 WordPress Integration Points

### Hooks Used
- `admin_notices` - Capture notices
- `admin_bar_menu` - Add toolbar item
- `admin_enqueue_scripts` - Load assets
- `wp_ajax_*` - AJAX handlers
- `admin_menu` - Settings page

### WordPress APIs Used
- Options API
- Transients API
- Settings API
- Admin Bar API
- AJAX API

---

## 📊 Database Schema (Optional)

If using custom table:

```sql
CREATE TABLE {prefix}_notice_manager (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notice_type VARCHAR(20) NOT NULL,
    notice_content TEXT NOT NULL,
    notice_hash VARCHAR(32) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_type (notice_type),
    INDEX idx_read (is_read),
    INDEX idx_expires (expires_at)
);
```

---

## 🔮 Extensibility

### Hooks for Developers
```php
// Filter notice before storage
apply_filters('wpnm_before_store_notice', $notice);

// Filter notice types
apply_filters('wpnm_notice_types', $types);

// Action after notice stored
do_action('wpnm_notice_stored', $notice_id);
```

---

## 📝 Naming Conventions

- **Classes:** `class-{name}.php`
- **Namespace:** `Notice_Manager\`
- **Prefix:** `wpnm_` (functions, options)
- **Constants:** `WPNM_` (uppercase)

