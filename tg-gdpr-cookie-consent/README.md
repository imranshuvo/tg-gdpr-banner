# TG GDPR Cookie Consent

**Performance-First GDPR Cookie Consent Plugin for WordPress**

A modern, lightweight, and fully GDPR-compliant cookie consent solution that works flawlessly with ALL optimization plugins (Perfmatters, WP Rocket, etc.). Beautiful CookieYes-inspired design with unmatched performance.

## 🚀 Key Features

### Free Version
- ✅ **Beautiful CookieYes-style UI** - Modern, responsive design
- ✅ **Performance-First** - <2ms overhead, cache-compatible
- ✅ **Works with ALL optimizations** - Perfmatters, WP Rocket, LiteSpeed, etc.
- ✅ **Server-side + Client-side blocking** - Bulletproof script blocking
- ✅ **4 Cookie Categories** - Necessary, Functional, Analytics, Marketing
- ✅ **Manual Cookie Management** - Full control over cookies
- ✅ **Privacy Policy Integration** - Link to your privacy policy
- ✅ **Multi-language Ready** - Translation-ready
- ✅ **Accessibility Compliant** - WCAG/ADA compliant
- ✅ **Developer API** - JavaScript API for custom integrations

### Pro Version
- 🎯 **Auto Cookie Scanner** - Detect cookies automatically
- 📝 **Consent Logging** - Record consent for GDPR compliance
- 📊 **Analytics Dashboard** - Track consent rates
- 🌍 **Geolocation** - Show banner only to EU visitors
- 🔄 **Auto-Categorization** - AI-powered cookie categorization
- 📧 **Admin Notifications** - Alerts for new cookies
- 📑 **Auto Cookie Declaration** - Dynamic cookie table generation
- 🏷️ **Google Consent Mode v2** - GTM integration
- 📤 **Export Consent Records** - For GDPR requests
- 🔐 **Advanced Security** - License validation & updates

## 💡 Why TG GDPR Cookie Consent?

### The Problem with Other Plugins
- ❌ **CookieYes**: Requires external connection, conflicts with Perfmatters
- ❌ **Other plugins**: Break with optimization plugins, slow, bulky

### Our Solution
- ✅ **Standalone**: No external dependencies
- ✅ **Performance-First**: Works WITH optimization, not against it
- ✅ **Cache-Compatible**: Works with all caching plugins
- ✅ **Lightweight**: Minimal code, maximum efficiency

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 🛠️ Installation

### Automatic Installation
1. Go to WordPress Dashboard → Plugins → Add New
2. Search for "TG GDPR Cookie Consent"
3. Click "Install Now" and then "Activate"

### Manual Installation
1. Download the plugin ZIP file
2. Go to WordPress Dashboard → Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Click "Activate"

## ⚙️ Configuration

### Basic Setup
1. Go to **Settings → TG GDPR Cookie Consent**
2. Configure banner appearance (colors, position, layout)
3. Customize banner text and messages
4. Enable/disable cookie categories
5. Add your Privacy Policy URL
6. Click "Save Changes"

### Adding Cookies
1. Go to **TG GDPR → Cookies**
2. Click "Add New Cookie"
3. Enter cookie details:
   - Name
   - Category
   - Description
   - Duration
   - Script pattern (for auto-blocking)
4. Click "Save"

### Pro Features Setup
1. Purchase a Pro license
2. Go to **Settings → TG GDPR → License**
3. Enter your license key
4. Click "Activate"
5. Pro features will be unlocked

## 🎨 Customization

### Colors & Design
Customize colors via Settings → TG GDPR → Banner:
- Primary Color
- Accent Color
- Text Color
- Background Color

### Banner Position
- Bottom Bar
- Top Bar
- Bottom Left (Box)
- Bottom Right (Box)

### Banner Layout
- Bar (full width)
- Box (compact)
- Popup (center)

## 👨‍💻 Developer API

### JavaScript API

```javascript
// Check if user has consented to a category
if (TG_GDPR.hasConsent('analytics')) {
    // Load analytics script
    gtag('config', 'GA_ID');
}

// Execute callback when consent is given
TG_GDPR.onConsent('analytics', function() {
    // This runs when user accepts analytics
    console.log('Analytics accepted!');
});

// Register a script to load on consent
TG_GDPR.registerScript('analytics', 
    'https://www.googletagmanager.com/gtag/js?id=GA_ID'
);

// Get full consent object
const consent = TG_GDPR.getConsent();
console.log(consent); 
// {necessary: true, functional: false, analytics: true, marketing: false}

// Listen to consent events
TG_GDPR.on('consent_saved', function(consent) {
    console.log('Consent updated:', consent);
});
```

### PHP Hooks & Filters

```php
// Modify script patterns
add_filter('tg_gdpr_script_patterns', function($patterns) {
    $patterns['analytics'][] = 'custom-analytics.js';
    return $patterns;
});

// Modify cookie domain
add_filter('tg_gdpr_cookie_domain', function($domain) {
    return '.yourdomain.com';
});

// Modify consent retention period
add_filter('tg_gdpr_log_retention_days', function($days) {
    return 1095; // 3 years
});
```

## 🔧 Compatibility

### Tested With
- ✅ WordPress 5.8 - 6.8+
- ✅ WP Rocket
- ✅ Perfmatters
- ✅ WP Fastest Cache
- ✅ LiteSpeed Cache
- ✅ W3 Total Cache
- ✅ WP-Optimize
- ✅ Autoptimize
- ✅ Cloudflare
- ✅ Google Tag Manager
- ✅ Google Analytics
- ✅ Facebook Pixel
- ✅ WooCommerce
- ✅ Elementor
- ✅ Gutenberg

## 📊 Performance Benchmarks

- **Inline Critical JS**: 2KB (minified)
- **Total CSS**: 8KB (minified)
- **Total JS**: 5KB (minified)
- **Execution Time**: <1ms
- **Server Overhead**: <2ms
- **Cache Compatible**: Yes
- **CDN Compatible**: Yes

## 🛡️ GDPR Compliance

### What We Do
- ✅ Block scripts until consent
- ✅ Granular cookie control
- ✅ Easy consent withdrawal
- ✅ Consent logging (Pro)
- ✅ IP anonymization
- ✅ Data retention management
- ✅ Export consent records (Pro)
- ✅ Privacy Policy integration

### What You Need To Do
- Configure cookie categories correctly
- Keep Privacy Policy updated
- Review consent logs regularly (Pro)
- Respond to data requests

## 🆘 Support

### Documentation
Visit our [documentation](https://techgenesis.com/docs/tg-gdpr/) for detailed guides.

### Support Forum
Get help in our [support forum](https://techgenesis.com/support/).

### Pro Support
Pro users get priority email support.

## 📝 Changelog

### 1.0.0 (2025-01-08)
- Initial release
- Beautiful CookieYes-inspired design
- Performance-first architecture
- Full GDPR compliance
- Works with all optimization plugins
- Developer API
- Pro features ready

## 📜 License

GPL v3 or later

## 🙏 Credits

Created by **TechGenesis**  
Inspired by CookieYes design  
Built with ❤️ for WordPress community

## 🚀 Upgrade to Pro

Get unlimited features for just **$59/year**:
- Auto cookie scanner
- Consent logging
- Analytics dashboard
- Geolocation targeting
- Priority support
- And much more!

[Upgrade Now](https://techgenesis.com/tg-gdpr-pro/)

---

**Made with ❤️ by TechGenesis**
