# TG GDPR Cookie Consent - Quick Start Guide

## 🚀 Installation

1. Upload the `tg-gdpr-cookie-consent` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Go to **TG GDPR → Settings**
4. Configure and save!

## ⚡ 5-Minute Setup

### Step 1: Enable the Banner
- Go to **TG GDPR → Settings**
- Check "Enable Cookie Consent Banner"
- Check "Auto-block scripts" (recommended)

### Step 2: Customize Appearance
- Choose banner position (Bottom/Top/Corner)
- Set your brand colors
- Customize heading and message

### Step 3: Add Privacy Policy
- Enter your Privacy Policy URL
- Save settings

### Step 4: Test
- Open your site in incognito mode
- You should see the banner
- Test Accept/Reject/Settings

## ✅ Done!

Your site is now GDPR compliant!

## 🎯 Common Configurations

### For Google Analytics

1. Remove existing GA code
2. Add this to your theme's footer:

```javascript
TG_GDPR.onConsent('analytics', function() {
    // Load GA4
    var script = document.createElement('script');
    script.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX';
    document.head.appendChild(script);
    
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX');
});
```

### For Facebook Pixel

```javascript
TG_GDPR.onConsent('marketing', function() {
    !function(f,b,e,v,n,t,s){
        // Facebook Pixel code
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', 'YOUR_PIXEL_ID');
    fbq('track', 'PageView');
});
```

### For Google Tag Manager

```javascript
TG_GDPR.onConsent('analytics', function() {
    (function(w,d,s,l,i){
        // GTM code
    })(window,document,'script','dataLayer','GTM-XXXXXXX');
});
```

## 🛠️ Perfmatters Users

**No special configuration needed!**

The plugin automatically works with Perfmatters delay JS.

Optional: You can still exclude our plugin from delay for extra assurance:
- Go to Perfmatters → Assets → Delay JavaScript
- Add to exclusions: `tg-gdpr`

## 📊 Pro Features Setup

1. Purchase Pro license
2. Go to **TG GDPR → Settings → Pro Features**
3. Enter your license key
4. Click Activate
5. Enable desired Pro features:
   - Consent Logging
   - Auto Cookie Scanner
   - Geolocation

## ❓ FAQ

### Banner doesn't appear?
- Check if "Enable Cookie Consent Banner" is checked
- Clear your cookies and cache
- Try incognito mode

### Scripts still loading?
- Enable "Auto-block scripts"
- Check if scripts have proper patterns in Cookie Manager
- Clear cache

### Conflicts with optimization plugins?
- None! Works with all optimization plugins
- But ensure cookie consent script isn't delayed

## 🆘 Need Help?

- Documentation: https://techgenesis.com/docs/tg-gdpr/
- Support: https://techgenesis.com/support/
- Pro Support: support@techgenesis.com

---

**That's it! You're GDPR compliant! 🎉**
