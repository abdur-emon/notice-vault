# Notice Manager - Installation & Testing Guide

## 📋 Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Methods](#installation-methods)
3. [Initial Configuration](#initial-configuration)
4. [Testing the Plugin](#testing-the-plugin)
5. [Troubleshooting](#troubleshooting)

---

## 🖥️ System Requirements

### Minimum Requirements
- **WordPress:** 5.0 or higher
- **PHP:** 7.2 or higher
- **MySQL:** 5.6 or higher
- **Web Server:** Apache or Nginx

### Recommended Requirements
- **WordPress:** 6.0 or higher
- **PHP:** 8.0 or higher
- **MySQL:** 5.7 or higher
- **HTTPS:** Enabled

### Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

---

## 📦 Installation Methods

### Method 1: WordPress Admin Panel (Recommended)

1. **Log in** to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the `notice-manager.zip` file
5. Click **Install Now**
6. Click **Activate Plugin**

### Method 2: FTP Upload

1. **Extract** the `notice-manager.zip` file
2. **Upload** the `notice-manager` folder to `/wp-content/plugins/`
3. **Log in** to WordPress admin panel
4. Navigate to **Plugins**
5. Find **Notice Manager** and click **Activate**

### Method 3: WP-CLI

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Install the plugin
wp plugin install notice-manager.zip

# Activate the plugin
wp plugin activate notice-manager
```

---

## ⚙️ Initial Configuration

### Step 1: Access Settings

After activation:
1. Navigate to **Settings → Notice Manager**
2. You'll see the settings page with 4 sections

### Step 2: Configure Notice Types

For each notice type, choose an action:

**Success Notices:**
- ✅ Show in popup & hide from dashboard (Recommended)
- Hide completely
- Do nothing

**Error Notices:**
- ✅ Show in popup & hide from dashboard (Recommended)
- Hide completely
- Do nothing

**Warning Notices:**
- ✅ Show in popup & hide from dashboard (Recommended)
- Hide completely
- Do nothing

**Info Notices:**
- ✅ Show in popup & hide from dashboard (Recommended)
- Hide completely
- Do nothing

**Non-standard Notices:**
- ✅ Show in popup & hide from dashboard (Recommended)
- Hide completely
- Do nothing

**WordPress System Notices:**
- ✅ Show in popup & hide from dashboard (Recommended)
- Do nothing

### Step 3: Choose Popup Style

Select your preferred popup style:
- **Slide from Right** (Default) - Smooth slide-in panel
- **Modal Popup** - Centered overlay
- **Slide Background Panel** - Full-height sidebar

### Step 4: Configure User Visibility

Choose who can see the notice manager:
- **Show to all users** (Default)
- Hide from all users
- Hide from selected users only
- Show to selected users only

### Step 5: Set Auto-Expire

Configure how long notices should be stored:
- Default: **30 days**
- Range: 1-365 days

### Step 6: Save Settings

Click **Save Changes** at the bottom of the page.

---

## 🧪 Testing the Plugin

### Test 1: Verify Installation

1. Check that the plugin is activated
2. Look for **"Notices"** in the admin toolbar (top-right)
3. Navigate to **Settings → Notice Manager**
4. Verify settings page loads correctly

### Test 2: Test Notice Capture

1. Install a plugin that shows admin notices (or trigger a WordPress update notice)
2. Check if the notice appears in the dashboard
3. Based on your settings, verify:
   - If set to "popup": Notice should NOT appear in dashboard
   - If set to "nothing": Notice should appear normally
   - If set to "hide": Notice should not appear anywhere

### Test 3: Test Popup Interface

1. Click **"Notices"** in the admin toolbar
2. Verify popup opens with the selected style
3. Check that captured notices are displayed
4. Verify notice count badge shows correct number

### Test 4: Test Notice Actions

**Mark as Read:**
1. Click the checkmark icon on an unread notice
2. Verify notice becomes semi-transparent
3. Check that counter decreases

**Dismiss Notice:**
1. Click the X icon on a notice
2. Verify notice is removed from list
3. Check that counter updates

**Filter Notices:**
1. Use the type dropdown to filter by notice type
2. Verify only selected type is shown

**Show Read Notices:**
1. Check the "Show Read" checkbox
2. Verify read notices appear in the list

### Test 5: Test Settings Changes

1. Change popup style in settings
2. Save and reload
3. Open popup and verify new style is applied

### Test 6: Test User Visibility

1. Set visibility to "Hide from selected users"
2. Select a test user
3. Log in as that user
4. Verify notice manager is hidden

### Test 7: Test AJAX Functionality

1. Open browser developer tools (F12)
2. Go to Network tab
3. Open the popup
4. Verify AJAX requests complete successfully
5. Check for any JavaScript errors in Console

### Test 8: Test Performance

1. Create multiple test notices (10-20)
2. Open popup
3. Verify it loads quickly (< 1 second)
4. Check browser performance tab for any issues

---

## 🔧 Troubleshooting

### Issue: Popup doesn't open

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify jQuery is loaded
3. Clear browser cache
4. Disable other plugins to check for conflicts

### Issue: Notices not being captured

**Solutions:**
1. Verify plugin is activated
2. Check settings - ensure notice types are set to "popup"
3. Clear WordPress cache
4. Check if notices are WordPress-standard notices

### Issue: Counter shows wrong number

**Solutions:**
1. Clear transient cache: Delete `wpnm_notice_count` transient
2. Reload the page
3. Check for duplicate notices

### Issue: Settings not saving

**Solutions:**
1. Check file permissions (wp-content should be writable)
2. Verify you have `manage_options` capability
3. Check for PHP errors in error log
4. Disable other plugins temporarily

### Issue: Popup style not changing

**Solutions:**
1. Clear browser cache
2. Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
3. Check if CSS file is loading correctly
4. Verify settings were saved

### Issue: AJAX errors

**Solutions:**
1. Check WordPress AJAX URL is correct
2. Verify nonce is being generated
3. Check server error logs
4. Ensure user has proper capabilities

---

## 🔍 Verification Checklist

After installation, verify:

- [ ] Plugin activated successfully
- [ ] Settings page accessible
- [ ] Admin toolbar shows "Notices"
- [ ] Popup opens when clicked
- [ ] Notices are being captured
- [ ] Counter updates correctly
- [ ] Mark as read works
- [ ] Dismiss notice works
- [ ] Filters work correctly
- [ ] Settings save properly
- [ ] No JavaScript errors
- [ ] No PHP errors
- [ ] Performance is acceptable

---

## 📞 Support

If you encounter issues:

1. Check this troubleshooting guide
2. Review [SECURITY.md](SECURITY.md) for security-related issues
3. Review [ARCHITECTURE.md](ARCHITECTURE.md) for technical details
4. Check WordPress error logs
5. Enable WordPress debug mode:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

---

## ✅ Success!

If all tests pass, your Notice Manager installation is complete and working correctly!

You can now enjoy a cleaner WordPress dashboard with all notices organized in one place.

