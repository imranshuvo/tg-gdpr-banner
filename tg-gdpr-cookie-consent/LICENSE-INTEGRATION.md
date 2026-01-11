# WordPress Plugin - License Integration Guide

## Overview
This guide explains how the TG GDPR Cookie Consent WordPress plugin integrates with the Laravel licensing API.

---

## Architecture

```
WordPress Plugin
├── License Manager (class-tg-gdpr-license-manager.php)
│   ├── activate_license()
│   ├── deactivate_license()
│   ├── verify_license() 
│   └── has_feature()
│
├── Pro Features (feature-gated)
│   ├── Auto Scanner (class-tg-gdpr-auto-scanner.php)
│   ├── Analytics Dashboard
│   └── Advanced Logging
│
└── Admin Interface
    └── License Page (tg-gdpr-license-display.php)
```

---

## API Integration

### 1. License Activation

**When**: User enters license key in WordPress admin  
**What**: Calls Laravel API to activate license for this domain

```php
// User submits license key
$license_manager = new TG_GDPR_License_Manager();
$result = $license_manager->activate_license('XXXX-XXXX-XXXX-XXXX');

// API Call
POST https://your-domain.com/api/v1/licenses/activate
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "client-site.com",
  "site_url": "https://client-site.com"
}

// Stores locally
update_option('tg_gdpr_license_key', 'XXXX-XXXX-XXXX-XXXX');
update_option('tg_gdpr_license_data', $response_data);
update_option('tg_gdpr_license_status', 'active');
```

### 2. Daily License Verification (Heartbeat)

**When**: Daily via WordPress cron  
**What**: Verifies license is still valid

```php
// Scheduled via WP-Cron
add_action('tg_gdpr_daily_license_check', array($license_manager, 'verify_license_cron'));
wp_schedule_event(time(), 'daily', 'tg_gdpr_daily_license_check');

// API Call
POST https://your-domain.com/api/v1/licenses/verify
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "client-site.com"
}

// Updates local status
update_option('tg_gdpr_license_status', 'active' or 'inactive');
update_option('tg_gdpr_license_data', $updated_data);
```

### 3. Feature Gating

**When**: User tries to access Pro features  
**What**: Checks if license allows feature

```php
$license_manager = new TG_GDPR_License_Manager();

// Check if license is active
if (!$license_manager->is_license_active()) {
    echo 'Please activate a license to use Pro features';
    return;
}

// Check specific feature
if ($license_manager->has_feature('auto_scanner')) {
    // Show Auto Scanner
    $scanner = new TG_GDPR_Auto_Scanner();
    $scanner->scan_site();
} else {
    // Show upgrade prompt
    echo 'Upgrade to Pro for Auto Scanner';
}
```

### 4. License Deactivation

**When**: User deactivates license or uninstalls plugin  
**What**: Frees up activation slot

```php
// User clicks "Deactivate License"
$license_manager->deactivate_license();

// API Call
POST https://your-domain.com/api/v1/licenses/deactivate
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "client-site.com"
}

// Removes local data
delete_option('tg_gdpr_license_key');
delete_option('tg_gdpr_license_data');
delete_option('tg_gdpr_license_status');
```

---

## WordPress Admin Interface

### License Page Location
```
WordPress Admin → TG GDPR → License
```

### Features:
1. **License Status Card**
   - Active/Inactive badge
   - Current plan
   - Expiry date
   - Days remaining

2. **License Activation Form**
   - Input field for license key
   - Activate button
   - Purchase link for new customers

3. **License Management** (when active)
   - Display license key
   - Display domain
   - Verify license button
   - Deactivate license button

4. **Feature Comparison Table**
   - Shows Free vs Pro features
   - Helps users understand upgrade benefits

---

## Pro Features Implementation

### Example: Auto Cookie Scanner

```php
class TG_GDPR_Auto_Scanner {
    private $license_manager;
    
    public function scan_site() {
        // Check license first
        if (!$this->license_manager->has_feature('auto_scanner')) {
            return array(
                'success' => false,
                'message' => 'Auto Scanner is a Pro feature'
            );
        }
        
        // Pro feature implementation
        $cookies = $this->detect_cookies();
        return array('success' => true, 'data' => $cookies);
    }
}
```

### Feature List

| Feature | Free | Pro | Check Method |
|---------|------|-----|--------------|
| Cookie Banner | ✅ | ✅ | Always available |
| Script Blocking | ✅ | ✅ | Always available |
| Basic Logging | ✅ | ✅ | Always available |
| Auto Scanner | ❌ | ✅ | `has_feature('auto_scanner')` |
| Analytics Dashboard | ❌ | ✅ | `has_feature('analytics_dashboard')` |
| Advanced Logging | ❌ | ✅ | `has_feature('advanced_logging')` |
| Priority Support | ❌ | ✅ | `has_feature('priority_support')` |

---

## Error Handling

### Network Errors
```php
// If API is unreachable during verification
// Don't deactivate license immediately
// Keep last known status for 7 days grace period

if (is_wp_error($response)) {
    // Don't change license_status
    // Log error for admin review
    error_log('License verification failed: ' . $response->get_error_message());
}
```

### License Expired
```php
// API returns expired status
if ($response['message'] === 'License has expired') {
    update_option('tg_gdpr_license_status', 'inactive');
    
    // Show admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error">';
        echo '<p>Your TG GDPR license has expired. Please renew to continue using Pro features.</p>';
        echo '</div>';
    });
}
```

### Invalid License Key
```php
// API returns invalid key
if ($response['message'] === 'Invalid license key') {
    delete_option('tg_gdpr_license_key');
    delete_option('tg_gdpr_license_status');
    
    // Show error in admin
    echo '<div class="notice notice-error">';
    echo '<p>Invalid license key. Please check and try again.</p>';
    echo '</div>';
}
```

---

## Security Considerations

1. **API Communication**
   - Always use HTTPS for API calls
   - Timeout set to 30 seconds
   - Validate API responses

2. **License Key Storage**
   - Stored in WordPress options table
   - Can only be accessed by admin users
   - Use `sanitize_text_field()` on input

3. **Nonce Verification**
   - All form submissions use WordPress nonces
   - `wp_nonce_field('tg_gdpr_license_action')`
   - `check_admin_referer('tg_gdpr_license_action')`

4. **Capability Checks**
   - Only users with `manage_options` can activate licenses
   - `if (!current_user_can('manage_options')) return;`

---

## Testing Checklist

- [ ] Activate license with valid key
- [ ] Activate license with invalid key
- [ ] Verify license (heartbeat)
- [ ] Deactivate license
- [ ] Check feature gating (Pro features)
- [ ] Test license expiry notification
- [ ] Test network error handling
- [ ] Test max activations limit
- [ ] Test license reactivation on same domain
- [ ] Test admin UI display

---

## Cron Jobs

### Daily License Check
```php
Hook: tg_gdpr_daily_license_check
Frequency: Once per day
Action: Verify license is still valid
```

### Weekly Cookie Scan (Pro)
```php
Hook: tg_gdpr_auto_cookie_scan
Frequency: Once per week
Action: Auto-scan site for cookies
Requirement: Pro license active
```

---

## Configuration

### Set API URL

Edit `class-tg-gdpr-license-manager.php`:

```php
private $api_url = 'https://your-domain.com/api/v1/licenses';
```

Replace `https://your-domain.com` with your actual Laravel API URL.

---

## Deployment Steps

1. **Update API URL** in license manager class
2. **Upload plugin** to WordPress site
3. **Activate plugin** via WordPress admin
4. **Navigate to** TG GDPR → License
5. **Enter license key** and click Activate
6. **Verify** license shows as active
7. **Test Pro features** are now accessible

---

## Support

For issues with:
- **License activation**: Check API URL and network connectivity
- **Feature access**: Verify license is active and plan includes feature
- **Heartbeat failures**: Check cron is running (`wp cron event list`)
- **API errors**: Check Laravel logs on server

---

**Status**: ✅ Fully Integrated  
**Last Updated**: October 8, 2025
