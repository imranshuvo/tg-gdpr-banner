# TG GDPR CMP - Full Implementation Roadmap

## Executive Summary

Building a **100% EU GDPR/ePrivacy compliant** Cookie Management Platform with:
- **Performance-first** architecture (< 100ms consent interaction)
- **TCF 2.2** compliance for programmatic advertising
- **Google Consent Mode v2** for Google Ads/Analytics
- **Full DSAR** (Data Subject Access Request) management
- **Premium-only** pricing with 30-day trial

---

## 🏗️ Architecture Overview

### Hybrid Performance-First Design

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           USER BROWSER                                   │
├─────────────────────────────────────────────────────────────────────────┤
│  • Consent Cookie (tg_gdpr_consent)                                     │
│  • TCF String Cookie (euconsent-v2)                                     │
│  • Google Consent Mode State                                            │
│  • GPP String (future US states)                                        │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                               ▼
┌─────────────────────────────────┐   ┌─────────────────────────────────┐
│      WORDPRESS PLUGIN           │   │        LARAVEL SAAS API         │
│      (tg-gdpr-cookie-consent)   │   │   (tg-gdpr-licensing-api)       │
├─────────────────────────────────┤   ├─────────────────────────────────┤
│ • Banner Rendering (cached)     │   │ • Consent Record Storage        │
│ • Script Blocking (real-time)   │   │ • DSAR Management               │
│ • Consent Capture (local)       │   │ • Analytics Dashboard           │
│ • GCM v2 Signals (instant)      │   │ • Cookie Database API           │
│ • TCF API (__tcfapi)            │   │ • TCF Vendor List Hosting       │
│ • Session Counting              │   │ • License & Billing             │
│ • Async Consent Sync            │   │ • Compliance Reports            │
└─────────────────────────────────┘   └─────────────────────────────────┘
                    │                               ▲
                    └───────────────┬───────────────┘
                                    │
                         Async Batch Sync (5 min)
```

---

## 📊 Database Schema Design

### WordPress Tables (Performance-Critical)

```sql
-- Local consent cache for immediate access
CREATE TABLE wp_tg_gdpr_consents (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    consent_id VARCHAR(36) UNIQUE,          -- UUID for cross-reference
    visitor_id VARCHAR(64),                  -- Hashed identifier
    consent_data JSON,                       -- {necessary:true, analytics:false, ...}
    tcf_string TEXT,                         -- IAB TCF v2.2 string
    gcm_state JSON,                          -- Google Consent Mode state
    ip_country VARCHAR(2),                   -- For geo compliance
    consent_version INT,                     -- Policy version consented to
    synced_at DATETIME,                      -- When synced to SaaS
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_visitor (visitor_id),
    INDEX idx_synced (synced_at)
);

-- Session counting for billing
CREATE TABLE wp_tg_gdpr_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    date DATE,
    session_count INT DEFAULT 0,
    consent_count INT DEFAULT 0,
    accept_all_count INT DEFAULT 0,
    reject_all_count INT DEFAULT 0,
    customize_count INT DEFAULT 0,
    synced_at DATETIME,
    INDEX idx_date (date)
);

-- Cookie definitions cache
CREATE TABLE wp_tg_gdpr_cookie_cache (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cookie_pattern VARCHAR(255),
    category ENUM('necessary','functional','analytics','marketing'),
    provider VARCHAR(255),
    description TEXT,
    duration VARCHAR(100),
    is_regex BOOLEAN DEFAULT FALSE,
    updated_at DATETIME,
    INDEX idx_pattern (cookie_pattern)
);
```

### Laravel Tables (Compliance & Analytics)

```sql
-- Consent Records (GDPR Proof)
CREATE TABLE consent_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT,                          -- Foreign key to sites
    consent_id VARCHAR(36) UNIQUE,           -- UUID from WordPress
    visitor_hash VARCHAR(64),                -- SHA256 of IP+UA (anonymized)
    ip_anonymized VARCHAR(45),               -- Last octet zeroed (192.168.1.0)
    country_code VARCHAR(2),
    consent_categories JSON,                 -- {necessary:true, analytics:false, ...}
    tcf_string TEXT,                         -- Full TCF string
    tcf_vendors JSON,                        -- Consented vendor IDs
    gcm_state JSON,                          -- Google Consent Mode
    consent_method ENUM('accept_all','reject_all','customize','implicit'),
    policy_version INT,
    user_agent_hash VARCHAR(64),
    created_at TIMESTAMP,
    expires_at TIMESTAMP,                    -- Consent expiry
    withdrawn_at TIMESTAMP NULL,             -- If consent withdrawn
    INDEX idx_site_date (site_id, created_at),
    INDEX idx_visitor (visitor_hash),
    INDEX idx_consent_id (consent_id)
);

-- DSAR Requests
CREATE TABLE dsar_requests (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT NULL,                     -- NULL = all sites for customer
    customer_id BIGINT,
    request_type ENUM('access','erasure','rectification','portability','restriction'),
    requester_email VARCHAR(255),
    requester_name VARCHAR(255),
    verification_token VARCHAR(64),
    verified_at TIMESTAMP NULL,
    status ENUM('pending_verification','verified','processing','completed','rejected'),
    data_export_path VARCHAR(255) NULL,      -- Path to exported data
    completed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_customer (customer_id)
);

-- Cookie Database (Central)
CREATE TABLE cookie_definitions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cookie_name VARCHAR(255),
    cookie_pattern VARCHAR(255),             -- Regex pattern
    is_regex BOOLEAN DEFAULT FALSE,
    category ENUM('necessary','functional','analytics','marketing'),
    provider VARCHAR(255),
    provider_url VARCHAR(500),
    description TEXT,
    description_translations JSON,           -- {de: "...", fr: "...", ...}
    duration VARCHAR(100),
    duration_seconds INT,
    platform VARCHAR(100),                   -- "Google Analytics", "Facebook", etc.
    source ENUM('open_database','scanned','manual','ai_categorized'),
    confidence_score DECIMAL(3,2),           -- 0.00 to 1.00
    verified BOOLEAN DEFAULT FALSE,
    verified_by BIGINT NULL,
    usage_count INT DEFAULT 0,               -- How many sites use this
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_cookie (cookie_name, provider),
    INDEX idx_category (category),
    INDEX idx_pattern (cookie_pattern),
    FULLTEXT idx_search (cookie_name, provider, description)
);

-- Site Sessions (Billing)
CREATE TABLE site_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT,
    period_start DATE,
    period_end DATE,
    session_count INT DEFAULT 0,
    consent_given_count INT DEFAULT 0,
    consent_denied_count INT DEFAULT 0,
    created_at TIMESTAMP,
    INDEX idx_site_period (site_id, period_start, period_end)
);

-- Sites (Customer's websites)
CREATE TABLE sites (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT,
    license_id BIGINT,
    domain VARCHAR(255),
    site_url VARCHAR(500),
    site_name VARCHAR(255),
    settings JSON,                           -- Banner customization
    policy_version INT DEFAULT 1,
    tcf_enabled BOOLEAN DEFAULT TRUE,
    gcm_enabled BOOLEAN DEFAULT TRUE,
    last_scan_at TIMESTAMP NULL,
    status ENUM('active','paused','deleted'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_domain (domain)
);

-- Scan Results
CREATE TABLE cookie_scans (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT,
    scan_type ENUM('full','quick','scheduled'),
    status ENUM('pending','running','completed','failed'),
    pages_scanned INT DEFAULT 0,
    cookies_found INT DEFAULT 0,
    new_cookies_found INT DEFAULT 0,
    uncategorized_count INT DEFAULT 0,
    results JSON,                            -- Detailed results
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    INDEX idx_site (site_id),
    INDEX idx_status (status)
);

-- TCF Vendor Preferences
CREATE TABLE tcf_vendor_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT,
    vendor_id INT,                           -- IAB Vendor ID
    vendor_name VARCHAR(255),
    status ENUM('enabled','disabled'),
    legitimate_interest BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_site_vendor (site_id, vendor_id)
);
```

---

## 🚀 Implementation Phases

### Phase 1: Core Compliance Foundation (Weeks 1-3)
**Goal: Google Consent Mode v2 + Consent Records + Enhanced Blocking**

#### 1.1 Google Consent Mode v2 Implementation
```javascript
// Must signal these consent types
{
    'ad_storage': 'denied',           // Advertising cookies
    'analytics_storage': 'denied',    // Analytics cookies
    'ad_user_data': 'denied',         // User data for ads
    'ad_personalization': 'denied',   // Personalized ads
    'functionality_storage': 'denied', // Functional cookies
    'personalization_storage': 'denied', // Personalization
    'security_storage': 'granted'     // Always granted (necessary)
}
```

**Files to Create/Modify:**
- `public/js/tg-gdpr-gcm.js` - Google Consent Mode handler
- `includes/class-tg-gdpr-gcm.php` - GCM configuration
- Modify `class-tg-gdpr-consent-manager.php` - Emit GCM signals on consent

#### 1.2 Consent Records System
**WordPress Side:**
- Create consent records table
- Generate UUID for each consent
- Queue for async sync

**Laravel Side:**
- Consent records API endpoint
- Batch import endpoint
- IP anonymization (zero last octet)

#### 1.3 Enhanced Script Blocking
- Improve regex patterns
- Add iframe blocking (YouTube, Maps)
- Add image/pixel blocking (tracking pixels)

**Deliverables:**
- [ ] GCM v2 fully functional
- [ ] Consent records storing locally
- [ ] Async sync to Laravel working
- [ ] Enhanced blocking for iframes/pixels

---

### Phase 2: TCF 2.2 Integration (Weeks 4-6)
**Goal: Full IAB Transparency & Consent Framework compliance**

#### 2.1 TCF Requirements
- Register as CMP with IAB (get CMP ID)
- Implement `__tcfapi` JavaScript API
- Generate valid TC Strings
- Load Global Vendor List (GVL)
- Handle all 10 TCF Purposes
- Handle Special Features & Purposes

#### 2.2 TCF Purposes (All 10)
```javascript
const TCF_PURPOSES = {
    1: 'Store and/or access information on a device',
    2: 'Select basic ads',
    3: 'Create a personalised ads profile',
    4: 'Select personalised ads',
    5: 'Create a personalised content profile',
    6: 'Select personalised content',
    7: 'Measure ad performance',
    8: 'Measure content performance',
    9: 'Apply market research to generate audience insights',
    10: 'Develop and improve products'
};
```

#### 2.3 TCF UI Components
- Purpose toggles with descriptions
- Vendor list (searchable)
- Legitimate interest toggles
- "Object to all" functionality

#### 2.4 TCF String Generation
- Use `@iabtcf/core` library
- Store in `euconsent-v2` cookie
- Decode/encode properly

**Files to Create:**
- `public/js/tg-gdpr-tcf.js` - TCF API implementation
- `includes/class-tg-gdpr-tcf.php` - TCF configuration
- `includes/class-tg-gdpr-tcf-encoder.php` - TC String encoder
- `public/partials/tg-gdpr-tcf-panel.php` - Vendor UI

**Deliverables:**
- [ ] IAB CMP registration completed
- [ ] `__tcfapi` fully implemented
- [ ] Valid TC String generation
- [ ] Vendor management UI
- [ ] GVL integration

---

### Phase 3: DSAR Management System (Weeks 7-9)
**Goal: Full Data Subject Rights compliance**

#### 3.1 DSAR Types (GDPR Articles 15-22)
| Right | Article | Our Implementation |
|-------|---------|-------------------|
| Access | Art. 15 | Export all consent records |
| Rectification | Art. 16 | Update consent preferences |
| Erasure | Art. 17 | Delete all consent records |
| Restriction | Art. 18 | Pause processing |
| Portability | Art. 20 | JSON/CSV export |
| Object | Art. 21 | Withdraw consent |

#### 3.2 DSAR Workflow
```
1. Request Submission (Form on site or portal)
           │
           ▼
2. Email Verification (24hr token expiry)
           │
           ▼
3. Identity Verification (Optional: ID upload)
           │
           ▼
4. Request Processing (Automated where possible)
           │
           ▼
5. Data Compilation (Cross-site if same email)
           │
           ▼
6. Delivery (Secure download link, 30 days)
           │
           ▼
7. Confirmation & Logging
```

#### 3.3 Components
**WordPress Plugin:**
- DSAR request form shortcode `[tg_gdpr_dsar_form]`
- Consent withdrawal widget
- "Forget me" button in banner

**Laravel API:**
- DSAR request endpoints
- Email verification system
- Automated data compilation
- PDF report generation
- Admin DSAR dashboard
- SLA tracking (must respond within 30 days)

**Deliverables:**
- [ ] DSAR request form
- [ ] Email verification flow
- [ ] Automated data export
- [ ] Admin DSAR dashboard
- [ ] SLA alerts (approaching 30 days)

---

### Phase 4: Cookie Database & Scanning (Weeks 10-12)
**Goal: Comprehensive cookie detection and categorization**

#### 4.1 Cookie Database Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                    COOKIE DATABASE LAYERS                        │
├─────────────────────────────────────────────────────────────────┤
│  Layer 1: Open Cookie Database (5000+ cookies)                  │
│  ├── Community maintained                                       │
│  ├── Regular updates                                            │
│  └── Free to use                                                │
├─────────────────────────────────────────────────────────────────┤
│  Layer 2: Scanned Cookies (Crowdsourced)                        │
│  ├── Every customer scan contributes                            │
│  ├── AI categorization for unknown                              │
│  └── Human verification pipeline                                │
├─────────────────────────────────────────────────────────────────┤
│  Layer 3: Customer Custom (Per-Site)                            │
│  ├── Customer-specific cookies                                  │
│  └── Custom categorizations                                     │
└─────────────────────────────────────────────────────────────────┘
```

#### 4.2 Scanning Service
**Technology:** Puppeteer/Playwright in Docker
- Headless browser scanning
- JavaScript execution (catches dynamic cookies)
- Network request monitoring
- LocalStorage/SessionStorage detection
- Third-party script detection

#### 4.3 AI Categorization
For unknown cookies:
1. Pattern matching (regex rules)
2. Domain reputation lookup
3. Script context analysis
4. LLM categorization (GPT-4 API)
5. Confidence scoring

**Deliverables:**
- [ ] Cookie database seeded (5000+)
- [ ] Scanning microservice deployed
- [ ] AI categorization pipeline
- [ ] Cookie API for plugins
- [ ] Auto-update mechanism

---

### Phase 5: Analytics & Billing (Weeks 13-15)
**Goal: Usage metering, analytics dashboard, billing integration**

#### 5.1 Analytics Dashboard
```
┌─────────────────────────────────────────────────────────────────┐
│  CONSENT ANALYTICS DASHBOARD                                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Sessions This Month: 45,234 / 100,000 (45.2%)                  │
│  ████████████████████░░░░░░░░░░░░░░░░░░░░                       │
│                                                                  │
│  ┌──────────────┬──────────────┬──────────────┬──────────────┐  │
│  │ Accept All   │ Reject All   │ Customize    │ No Action    │  │
│  │    67.3%     │    12.1%     │    8.2%      │    12.4%     │  │
│  └──────────────┴──────────────┴──────────────┴──────────────┘  │
│                                                                  │
│  Category Acceptance Rates:                                      │
│  Necessary:   ████████████████████ 100%                         │
│  Functional:  ██████████████░░░░░░  72%                         │
│  Analytics:   ████████████░░░░░░░░  58%                         │
│  Marketing:   ██████░░░░░░░░░░░░░░  34%                         │
│                                                                  │
│  Geographic Breakdown:                                           │
│  🇩🇪 Germany: 34% | 🇫🇷 France: 22% | 🇳🇱 NL: 15% | Other: 29%   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 5.2 Session Counting (Billing)
- Count unique sessions per site per month
- Use fingerprint-free method (session cookie only)
- WordPress counts locally, syncs daily
- Laravel aggregates for billing

#### 5.3 Billing Integration
**Stripe Integration:**
- Subscription management
- Usage-based billing (sessions)
- Invoice generation
- Plan upgrades/downgrades

**Pricing Tiers:**
| Tier | Sessions/Month | Domains | Price |
|------|----------------|---------|-------|
| Starter | 25,000 | 1 | €29/mo |
| Growth | 100,000 | 3 | €49/mo |
| Business | 500,000 | 10 | €99/mo |
| Enterprise | Unlimited | Unlimited | Custom |

**Deliverables:**
- [ ] Analytics dashboard complete
- [ ] Session counting accurate
- [ ] Stripe integration working
- [ ] Trial management (30 days)
- [ ] Usage alerts (80%, 100%)

---

### Phase 6: Polish & Compliance Audit (Weeks 16-17)

#### 6.1 Accessibility (WCAG 2.1 AA)
- Keyboard navigation
- Screen reader support
- Focus management
- Color contrast (4.5:1)
- Touch targets (44px min)

#### 6.2 Performance Optimization
- Bundle size < 40KB gzipped
- Critical CSS inline
- Defer non-critical JS
- Preload fonts
- CDN delivery

#### 6.3 Security Audit
- OWASP Top 10 review
- Penetration testing
- GDPR data handling audit
- Encryption at rest
- Encryption in transit

#### 6.4 Legal Review
- Privacy policy template
- Cookie policy template
- DPA (Data Processing Agreement)
- Terms of service

---

## 📁 Final File Structure

### WordPress Plugin
```
tg-gdpr-cookie-consent/
├── tg-gdpr-cookie-consent.php
├── includes/
│   ├── class-tg-gdpr-core.php
│   ├── class-tg-gdpr-loader.php
│   ├── class-tg-gdpr-activator.php
│   ├── class-tg-gdpr-deactivator.php
│   ├── class-tg-gdpr-i18n.php
│   ├── class-tg-gdpr-script-blocker.php
│   ├── class-tg-gdpr-consent-manager.php
│   ├── class-tg-gdpr-cookie-manager.php
│   ├── class-tg-gdpr-banner.php
│   ├── class-tg-gdpr-license-manager.php
│   ├── class-tg-gdpr-auto-scanner.php
│   ├── class-tg-gdpr-gcm.php              # NEW: Google Consent Mode
│   ├── class-tg-gdpr-tcf.php              # NEW: TCF 2.2 Handler
│   ├── class-tg-gdpr-tcf-encoder.php      # NEW: TC String
│   ├── class-tg-gdpr-consent-sync.php     # NEW: Async Sync
│   ├── class-tg-gdpr-session-counter.php  # NEW: Session Billing
│   ├── class-tg-gdpr-dsar.php             # NEW: DSAR Handler
│   └── class-tg-gdpr-geo.php              # NEW: Geolocation
├── admin/
│   ├── class-tg-gdpr-admin.php
│   ├── css/tg-gdpr-admin.css
│   ├── js/tg-gdpr-admin.js
│   └── partials/
│       ├── tg-gdpr-admin-display.php
│       ├── tg-gdpr-cookies-display.php
│       ├── tg-gdpr-license-display.php
│       ├── tg-gdpr-tcf-vendors.php        # NEW
│       └── tg-gdpr-analytics.php          # NEW
├── public/
│   ├── class-tg-gdpr-public.php
│   ├── css/
│   │   ├── tg-gdpr-public.css
│   │   └── tg-gdpr-tcf.css                # NEW
│   ├── js/
│   │   ├── tg-gdpr-public.js
│   │   ├── tg-gdpr-gcm.js                 # NEW: GCM v2
│   │   ├── tg-gdpr-tcf.js                 # NEW: TCF API
│   │   └── tg-gdpr-stub.js                # NEW: Pre-consent stub
│   └── partials/
│       ├── tg-gdpr-banner.php
│       ├── tg-gdpr-tcf-panel.php          # NEW
│       └── tg-gdpr-dsar-form.php          # NEW
└── languages/
```

### Laravel API
```
tg-gdpr-licensing-api/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   │   ├── LicenseController.php
│   │   │   ├── ConsentController.php      # NEW
│   │   │   ├── CookieController.php       # NEW
│   │   │   ├── ScanController.php         # NEW
│   │   │   ├── DsarController.php         # NEW
│   │   │   └── SessionController.php      # NEW
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── LicenseController.php
│   │   │   ├── DsarController.php         # NEW
│   │   │   ├── CookieController.php       # NEW
│   │   │   └── AnalyticsController.php    # NEW
│   │   └── Customer/
│   │       ├── DashboardController.php
│   │       ├── LicenseController.php
│   │       ├── SiteController.php         # NEW
│   │       ├── AnalyticsController.php    # NEW
│   │       └── DsarController.php         # NEW
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── License.php
│   │   ├── Activation.php
│   │   ├── Site.php                       # NEW
│   │   ├── ConsentRecord.php              # NEW
│   │   ├── CookieDefinition.php           # NEW
│   │   ├── CookieScan.php                 # NEW
│   │   ├── DsarRequest.php                # NEW
│   │   ├── SiteSession.php                # NEW
│   │   └── TcfVendorSetting.php           # NEW
│   ├── Services/
│   │   ├── LicenseService.php
│   │   ├── ConsentService.php             # NEW
│   │   ├── CookieScanService.php          # NEW
│   │   ├── TcfService.php                 # NEW
│   │   ├── DsarService.php                # NEW
│   │   ├── GeoService.php                 # NEW
│   │   └── BillingService.php             # NEW
│   └── Jobs/
│       ├── ProcessCookieScan.php          # NEW
│       ├── ProcessDsarRequest.php         # NEW
│       ├── SyncConsentRecords.php         # NEW
│       └── UpdateVendorList.php           # NEW
├── database/migrations/
│   ├── create_sites_table.php             # NEW
│   ├── create_consent_records_table.php   # NEW
│   ├── create_cookie_definitions_table.php # NEW
│   ├── create_cookie_scans_table.php      # NEW
│   ├── create_dsar_requests_table.php     # NEW
│   ├── create_site_sessions_table.php     # NEW
│   └── create_tcf_vendor_settings_table.php # NEW
└── routes/
    ├── api.php                            # API routes
    ├── admin.php                          # Admin routes
    └── customer.php                       # Customer routes
```

---

## 📅 Timeline Summary

| Phase | Duration | Key Deliverables |
|-------|----------|------------------|
| **Phase 1** | Weeks 1-3 | GCM v2, Consent Records, Enhanced Blocking |
| **Phase 2** | Weeks 4-6 | TCF 2.2, IAB Registration, Vendor Management |
| **Phase 3** | Weeks 7-9 | Full DSAR System |
| **Phase 4** | Weeks 10-12 | Cookie Database, Scanning Service |
| **Phase 5** | Weeks 13-15 | Analytics, Billing, Trials |
| **Phase 6** | Weeks 16-17 | Accessibility, Security, Legal |

**Total: ~17 weeks (4 months)**

---

## ✅ Compliance Checklist

### GDPR Requirements
- [ ] Prior consent before non-essential cookies
- [ ] Granular consent (per purpose/category)
- [ ] Easy withdrawal of consent
- [ ] Consent proof storage (5+ years recommended)
- [ ] IP anonymization
- [ ] Re-consent mechanism (12 months)
- [ ] DSAR: Right to Access
- [ ] DSAR: Right to Erasure
- [ ] DSAR: Right to Rectification
- [ ] DSAR: Right to Portability
- [ ] DSAR: Right to Restriction
- [ ] DSAR: Right to Object
- [ ] Data Processing Agreement available

### ePrivacy Requirements
- [ ] Cookie information before consent
- [ ] Clear accept/reject options
- [ ] No pre-ticked boxes
- [ ] No cookie walls (can use degraded experience)

### TCF 2.2 Requirements
- [ ] IAB CMP Registration
- [ ] __tcfapi implementation
- [ ] Valid TC String generation
- [ ] All 10 purposes supported
- [ ] Vendor list management
- [ ] Legitimate interest handling

### Google Consent Mode v2
- [ ] ad_storage signal
- [ ] analytics_storage signal
- [ ] ad_user_data signal
- [ ] ad_personalization signal
- [ ] Default denied state
- [ ] Update on consent

---

## 🚦 Ready to Start

**Recommended Starting Point: Phase 1 - Google Consent Mode v2**

This is the most urgent because:
1. Required for Google Ads since March 2024
2. Relatively simple to implement
3. Provides immediate value
4. Foundation for TCF integration

Would you like me to begin implementing Phase 1?
