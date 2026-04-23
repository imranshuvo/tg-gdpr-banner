/**
 * TG GDPR - Enhanced Consent Banner
 * 
 * Full-featured consent banner with:
 * - Cookie category management
 * - Google Consent Mode v2 integration
 * - TCF 2.2 signals support
 * - Accessibility compliance (WCAG 2.1)
 * - Performance optimized (<5KB gzipped)
 * 
 * @package TG_GDPR_Cookie_Consent
 */

(function() {
    'use strict';

    // Settings from WordPress localization
    const settings = window.TG_GDPR_Banner_Settings || {};
    const siteToken = settings.site_token || '';
    const apiUrl = settings.api_url || '';
    const analyticsAjaxUrl = settings.ajax_url || '';
    const analyticsNonce = settings.nonce || '';
    
    // Cookie settings
    const COOKIE_NAME = 'tg_gdpr_consent';
    const VISITOR_HASH_COOKIE = 'tg_gdpr_visitor_hash';
    const COOKIE_DAYS = settings.cookie_expiry || 365;
    const COOKIE_VERSION = settings.policy_version || '1.0';
    
    // Banner state
    let bannerElement = null;
    let overlayElement = null;
    let preferencesPanel = null;
    let isOpen = false;

    // Consent categories
    const CATEGORIES = {
        necessary: {
            id: 'necessary',
            name: settings.i18n?.necessary_name || 'Necessary',
            description: settings.i18n?.necessary_desc || 'Essential cookies required for the website to function properly. Cannot be disabled.',
            required: true,
            default: true
        },
        functional: {
            id: 'functional',
            name: settings.i18n?.functional_name || 'Functional',
            description: settings.i18n?.functional_desc || 'Cookies that enable enhanced functionality and personalization.',
            required: false,
            default: false
        },
        analytics: {
            id: 'analytics',
            name: settings.i18n?.analytics_name || 'Analytics',
            description: settings.i18n?.analytics_desc || 'Cookies that help us understand how visitors interact with our website.',
            required: false,
            default: false
        },
        marketing: {
            id: 'marketing',
            name: settings.i18n?.marketing_name || 'Marketing',
            description: settings.i18n?.marketing_desc || 'Cookies used to deliver personalized advertisements.',
            required: false,
            default: false
        }
    };

    // Current consent state
    let currentConsent = {
        necessary: true,
        functional: false,
        analytics: false,
        marketing: false,
        timestamp: null,
        version: COOKIE_VERSION,
        interaction: null // 'accept_all', 'reject_all', 'custom', 'implicit'
    };

    /**
     * Initialize the consent banner
     */
    function init() {
        // Check for existing consent
        const existingConsent = getStoredConsent();
        
        if (existingConsent) {
            currentConsent = existingConsent;
            
            // Check if consent version is outdated
            if (existingConsent.version !== COOKIE_VERSION) {
                console.log('[TG GDPR] Consent version outdated, showing banner');
                showBanner();
            } else {
                // Apply existing consent
                applyConsent(currentConsent);
                console.log('[TG GDPR] Existing consent applied');
            }
        } else {
            // No consent - show banner
            showBanner();
        }

        // Expose public API
        exposePublicAPI();

        // Listen for settings icon clicks
        setupSettingsButton();

        console.log('[TG GDPR] Banner initialized');
    }

    /**
     * Get stored consent from cookie
     * @returns {Object|null}
     */
    function getStoredConsent() {
        const cookie = getCookie(COOKIE_NAME);
        if (!cookie) return null;

        try {
            return JSON.parse(cookie);
        } catch (e) {
            console.warn('[TG GDPR] Failed to parse consent cookie');
            return null;
        }
    }

    /**
     * Get cookie by name
     * @param {string} name
     * @returns {string|null}
     */
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }
        return null;
    }

    /**
     * Set cookie
     * @param {string} name
     * @param {string} value
     * @param {number} days
     */
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        
        const cookieOptions = [
            `${name}=${encodeURIComponent(value)}`,
            `expires=${date.toUTCString()}`,
            'path=/',
            'SameSite=Lax'
        ];

        // Add Secure flag if on HTTPS
        if (window.location.protocol === 'https:') {
            cookieOptions.push('Secure');
        }

        document.cookie = cookieOptions.join('; ');
    }

    /**
     * Check whether a visitor hash is valid.
     * @param {string|null} value
     * @returns {boolean}
     */
    function isValidVisitorHash(value) {
        return typeof value === 'string' && /^[a-f0-9]{64}$/i.test(value);
    }

    /**
     * Generate random hexadecimal bytes.
     * @param {number} bytes
     * @returns {string}
     */
    function generateRandomHex(bytes) {
        if (window.crypto?.getRandomValues) {
            const buffer = new Uint8Array(bytes);
            window.crypto.getRandomValues(buffer);

            return Array.from(buffer, byte => byte.toString(16).padStart(2, '0')).join('');
        }

        let value = '';

        for (let i = 0; i < bytes; i++) {
            value += Math.floor(Math.random() * 256).toString(16).padStart(2, '0');
        }

        return value;
    }

    /**
     * Generate a UUID for consent records.
     * @returns {string}
     */
    function generateConsentId() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        const hex = generateRandomHex(16);

        return [
            hex.slice(0, 8),
            hex.slice(8, 12),
            `4${hex.slice(13, 16)}`,
            `${((parseInt(hex.slice(16, 18), 16) & 0x3f) | 0x80).toString(16).padStart(2, '0')}${hex.slice(18, 20)}`,
            hex.slice(20, 32)
        ].join('-');
    }

    /**
     * Convert banner interaction state into the Laravel API contract.
     * @param {string|null} interaction
     * @returns {string}
     */
    function normalizeConsentMethod(interaction) {
        switch (interaction) {
            case 'accept_all':
                return 'accept_all';
            case 'reject_all':
                return 'reject_all';
            case 'implicit':
                return 'implicit';
            default:
                return 'customize';
        }
    }

    /**
     * Normalize the policy version to the integer schema expected by the API.
     * @param {string|number} version
     * @returns {number}
     */
    function normalizePolicyVersion(version) {
        const normalized = parseInt(String(version || COOKIE_VERSION), 10);

        return Number.isFinite(normalized) && normalized > 0 ? normalized : 1;
    }

    /**
     * Show the consent banner
     */
    function showBanner() {
        if (bannerElement) return;

        createBannerElements();
        
        // Animate in
        requestAnimationFrame(() => {
            bannerElement.classList.add('tg-gdpr-banner--visible');
            if (settings.overlay_enabled) {
                overlayElement.classList.add('tg-gdpr-overlay--visible');
            }
        });

        isOpen = true;
        
        // Focus management for accessibility
        bannerElement.querySelector('.tg-gdpr-btn--primary')?.focus();

        // Dispatch event
        dispatchEvent('tg_gdpr_banner_shown', {});
        trackAnalyticsEvent('banner_shown');
    }

    /**
     * Hide the consent banner
     */
    function hideBanner() {
        if (!bannerElement) return;

        bannerElement.classList.remove('tg-gdpr-banner--visible');
        if (overlayElement) {
            overlayElement.classList.remove('tg-gdpr-overlay--visible');
        }

        // Remove after animation
        setTimeout(() => {
            bannerElement?.remove();
            overlayElement?.remove();
            preferencesPanel?.remove();
            bannerElement = null;
            overlayElement = null;
            preferencesPanel = null;
        }, 300);

        isOpen = false;

        // Dispatch event
        dispatchEvent('tg_gdpr_banner_hidden', {});
    }

    /**
     * Create banner DOM elements
     */
    function createBannerElements() {
        // Create overlay
        if (settings.overlay_enabled !== false) {
            overlayElement = document.createElement('div');
            overlayElement.className = 'tg-gdpr-overlay';
            overlayElement.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlayElement);
        }

        // Create banner
        bannerElement = document.createElement('div');
        bannerElement.className = `tg-gdpr-banner tg-gdpr-banner--${settings.position || 'bottom'}`;
        bannerElement.setAttribute('role', 'dialog');
        bannerElement.setAttribute('aria-modal', 'true');
        bannerElement.setAttribute('aria-label', settings.i18n?.banner_title || 'Cookie Consent');

        const layout = settings.layout || 'box';
        bannerElement.classList.add(`tg-gdpr-banner--${layout}`);

        bannerElement.innerHTML = getBannerHTML();

        // Apply custom styles
        applyCustomStyles(bannerElement);

        document.body.appendChild(bannerElement);

        // Attach event listeners
        attachBannerEvents();
    }

    /**
     * Get banner HTML
     * @returns {string}
     */
    function getBannerHTML() {
        const title = settings.content?.title || settings.i18n?.default_title || 'We value your privacy';
        const message = settings.content?.message || settings.i18n?.default_message || 
            'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.';
        const privacyUrl = settings.privacy_url || '#';
        const privacyText = settings.i18n?.privacy_link || 'Privacy Policy';

        return `
            <div class="tg-gdpr-banner__container">
                <div class="tg-gdpr-banner__content">
                    <h2 class="tg-gdpr-banner__title">${escapeHTML(title)}</h2>
                    <p class="tg-gdpr-banner__message">
                        ${escapeHTML(message)}
                        <a href="${escapeHTML(privacyUrl)}" class="tg-gdpr-banner__link" target="_blank" rel="noopener">
                            ${escapeHTML(privacyText)}
                        </a>
                    </p>
                </div>
                <div class="tg-gdpr-banner__actions">
                    <button type="button" class="tg-gdpr-btn tg-gdpr-btn--secondary" data-action="reject">
                        ${escapeHTML(settings.i18n?.reject_all || 'Reject All')}
                    </button>
                    <button type="button" class="tg-gdpr-btn tg-gdpr-btn--outline" data-action="settings">
                        ${escapeHTML(settings.i18n?.manage || 'Manage Preferences')}
                    </button>
                    <button type="button" class="tg-gdpr-btn tg-gdpr-btn--primary" data-action="accept">
                        ${escapeHTML(settings.i18n?.accept_all || 'Accept All')}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Attach event listeners to banner
     */
    function attachBannerEvents() {
        bannerElement.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            
            switch (action) {
                case 'accept':
                    acceptAll();
                    break;
                case 'reject':
                    rejectAll();
                    break;
                case 'settings':
                    showPreferences();
                    break;
            }
        });

        // Keyboard navigation
        bannerElement.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Don't close on escape - GDPR requires explicit choice
                // But focus the reject button as an alternative
                bannerElement.querySelector('[data-action="reject"]')?.focus();
            }
        });
    }

    /**
     * Show preferences panel
     */
    function showPreferences() {
        if (preferencesPanel) return;

        preferencesPanel = document.createElement('div');
        preferencesPanel.className = 'tg-gdpr-preferences';
        preferencesPanel.setAttribute('role', 'dialog');
        preferencesPanel.setAttribute('aria-modal', 'true');
        preferencesPanel.setAttribute('aria-label', settings.i18n?.preferences_title || 'Cookie Preferences');

        preferencesPanel.innerHTML = getPreferencesHTML();
        
        applyCustomStyles(preferencesPanel);

        document.body.appendChild(preferencesPanel);

        // Animate in
        requestAnimationFrame(() => {
            preferencesPanel.classList.add('tg-gdpr-preferences--visible');
        });

        // Attach events
        attachPreferencesEvents();

        // Focus first toggle
        preferencesPanel.querySelector('input[type="checkbox"]:not([disabled])')?.focus();
    }

    /**
     * Get preferences panel HTML
     * @returns {string}
     */
    function getPreferencesHTML() {
        let categoriesHTML = '';

        Object.values(CATEGORIES).forEach(category => {
            const isChecked = currentConsent[category.id] ? 'checked' : '';
            const isDisabled = category.required ? 'disabled' : '';

            categoriesHTML += `
                <div class="tg-gdpr-category">
                    <div class="tg-gdpr-category__header">
                        <label class="tg-gdpr-toggle">
                            <input type="checkbox" 
                                   name="${category.id}" 
                                   ${isChecked} 
                                   ${isDisabled}
                                   aria-describedby="desc-${category.id}">
                            <span class="tg-gdpr-toggle__slider"></span>
                        </label>
                        <span class="tg-gdpr-category__name">${escapeHTML(category.name)}</span>
                        ${category.required ? '<span class="tg-gdpr-category__required">' + (settings.i18n?.required || 'Required') + '</span>' : ''}
                    </div>
                    <p class="tg-gdpr-category__description" id="desc-${category.id}">
                        ${escapeHTML(category.description)}
                    </p>
                </div>
            `;
        });

        return `
            <div class="tg-gdpr-preferences__container">
                <div class="tg-gdpr-preferences__header">
                    <h2 class="tg-gdpr-preferences__title">
                        ${escapeHTML(settings.i18n?.preferences_title || 'Cookie Preferences')}
                    </h2>
                    <button type="button" class="tg-gdpr-preferences__close" data-action="close" aria-label="Close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="tg-gdpr-preferences__body">
                    <p class="tg-gdpr-preferences__intro">
                        ${escapeHTML(settings.i18n?.preferences_intro || 'Choose which cookie categories you want to allow. You can change these settings at any time.')}
                    </p>
                    <div class="tg-gdpr-categories">
                        ${categoriesHTML}
                    </div>
                </div>
                <div class="tg-gdpr-preferences__footer">
                    <button type="button" class="tg-gdpr-btn tg-gdpr-btn--secondary" data-action="reject-all">
                        ${escapeHTML(settings.i18n?.reject_all || 'Reject All')}
                    </button>
                    <button type="button" class="tg-gdpr-btn tg-gdpr-btn--primary" data-action="save">
                        ${escapeHTML(settings.i18n?.save_preferences || 'Save Preferences')}
                    </button>
                    <button type="button" class="tg-gdpr-btn tg-gdpr-btn--primary" data-action="accept-all">
                        ${escapeHTML(settings.i18n?.accept_all || 'Accept All')}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Attach events to preferences panel
     */
    function attachPreferencesEvents() {
        preferencesPanel.addEventListener('click', (e) => {
            const action = e.target.dataset.action || e.target.closest('[data-action]')?.dataset.action;
            
            switch (action) {
                case 'close':
                    hidePreferences();
                    break;
                case 'save':
                    savePreferences();
                    break;
                case 'accept-all':
                    acceptAll();
                    break;
                case 'reject-all':
                    rejectAll();
                    break;
            }
        });

        preferencesPanel.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                hidePreferences();
            }
        });
    }

    /**
     * Hide preferences panel
     */
    function hidePreferences() {
        if (!preferencesPanel) return;

        preferencesPanel.classList.remove('tg-gdpr-preferences--visible');

        setTimeout(() => {
            preferencesPanel?.remove();
            preferencesPanel = null;
        }, 300);
    }

    /**
     * Accept all cookies
     */
    function acceptAll() {
        currentConsent = {
            necessary: true,
            functional: true,
            analytics: true,
            marketing: true,
            timestamp: Date.now(),
            version: COOKIE_VERSION,
            interaction: 'accept_all'
        };

        saveConsent(currentConsent);
        applyConsent(currentConsent);
        hideBanner();
        hidePreferences();
    }

    /**
     * Reject all optional cookies
     */
    function rejectAll() {
        currentConsent = {
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
            timestamp: Date.now(),
            version: COOKIE_VERSION,
            interaction: 'reject_all'
        };

        saveConsent(currentConsent);
        applyConsent(currentConsent);
        hideBanner();
        hidePreferences();
    }

    /**
     * Save custom preferences
     */
    function savePreferences() {
        if (!preferencesPanel) return;

        const checkboxes = preferencesPanel.querySelectorAll('input[type="checkbox"]');
        
        checkboxes.forEach(checkbox => {
            const category = checkbox.name;
            if (CATEGORIES[category]) {
                currentConsent[category] = checkbox.checked;
            }
        });

        currentConsent.timestamp = Date.now();
        currentConsent.version = COOKIE_VERSION;
        currentConsent.interaction = 'custom';

        saveConsent(currentConsent);
        applyConsent(currentConsent);
        hideBanner();
        hidePreferences();
    }

    /**
     * Save consent to cookie
     * @param {Object} consent
     */
    function saveConsent(consent) {
        setCookie(COOKIE_NAME, JSON.stringify(consent), COOKIE_DAYS);

        // Dispatch events
        dispatchEvent('tg_gdpr_consent_saved', { consent });
        dispatchEvent('tg_gdpr_consent_updated', { consent });

        // Update GCM if available
        if (window.TG_GDPR_GCM && typeof window.TG_GDPR_GCM.update === 'function') {
            window.TG_GDPR_GCM.update(consent);
        }

        trackAnalyticsEvent('consent_saved', consent);

        // Sync with API (async, non-blocking)
        syncConsentToAPI(consent);

        console.log('[TG GDPR] Consent saved:', consent);
    }

    /**
     * Apply consent by enabling/disabling scripts
     * @param {Object} consent
     */
    function applyConsent(consent) {
        // Dispatch category-specific events
        Object.keys(consent).forEach(category => {
            if (category === 'timestamp' || category === 'version' || category === 'interaction') return;

            if (consent[category]) {
                dispatchEvent(`tg_gdpr_category_enabled`, { category });
                dispatchEvent(`tg_gdpr_${category}_enabled`, {});
            } else {
                dispatchEvent(`tg_gdpr_category_disabled`, { category });
                dispatchEvent(`tg_gdpr_${category}_disabled`, {});
            }
        });

        // Enable blocked scripts based on consent
        enableScriptsByConsent(consent);

        // Dispatch global apply event
        dispatchEvent('tg_gdpr_consent_applied', { consent });
    }

    /**
     * Enable scripts based on consent
     * @param {Object} consent
     */
    function enableScriptsByConsent(consent) {
        // Look for script tags with data-category attribute
        document.querySelectorAll('script[data-category][type="text/plain"]').forEach(script => {
            const category = script.dataset.category;
            
            if (consent[category]) {
                // Clone and enable the script
                const enabledScript = document.createElement('script');
                
                // Copy attributes
                Array.from(script.attributes).forEach(attr => {
                    if (attr.name !== 'type' && attr.name !== 'data-category') {
                        enabledScript.setAttribute(attr.name, attr.value);
                    }
                });
                
                // Copy content or src
                if (script.src) {
                    enabledScript.src = script.src;
                } else {
                    enabledScript.textContent = script.textContent;
                }

                // Replace original
                script.parentNode.replaceChild(enabledScript, script);
                
                console.log('[TG GDPR] Enabled script for category:', category);
            }
        });

        // Handle iframe elements
        document.querySelectorAll('iframe[data-category][data-src]').forEach(iframe => {
            const category = iframe.dataset.category;
            
            if (consent[category] && iframe.dataset.src) {
                iframe.src = iframe.dataset.src;
                iframe.removeAttribute('data-src');
                console.log('[TG GDPR] Enabled iframe for category:', category);
            }
        });
    }

    /**
     * Sync consent to API
     * @param {Object} consent
     */
    function syncConsentToAPI(consent) {
        if (!apiUrl || !siteToken) {
            console.log('[TG GDPR] API sync skipped - no API configured');
            return;
        }

        const visitorHash = generateVisitorHash();
        const payload = {
            site_token: siteToken,
            consent_id: generateConsentId(),
            visitor_hash: visitorHash,
            consent_categories: {
                necessary: !!consent.necessary,
                functional: !!consent.functional,
                analytics: !!consent.analytics,
                marketing: !!consent.marketing,
            },
            consent_method: normalizeConsentMethod(consent.interaction),
            policy_version: normalizePolicyVersion(consent.version),
            device_type: detectDeviceType(window.navigator?.userAgent || ''),
            browser: detectBrowser(window.navigator?.userAgent || ''),
        };

        // Use sendBeacon for reliability (won't block page unload)
        if (navigator.sendBeacon) {
            navigator.sendBeacon(
                `${apiUrl}/api/v1/consents/record`,
                new Blob([JSON.stringify(payload)], { type: 'application/json' })
            );
        } else {
            // Fallback to fetch
            fetch(`${apiUrl}/api/v1/consents/record`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(err => {
                console.warn('[TG GDPR] API sync failed:', err);
            });
        }
    }

    /**
     * Generate a stable visitor hash for consent and DSAR matching.
     * @returns {string}
     */
    function generateVisitorHash() {
        const existing = getCookie(VISITOR_HASH_COOKIE);

        if (isValidVisitorHash(existing)) {
            return existing.toLowerCase();
        }

        const generated = generateRandomHex(32);
        setCookie(VISITOR_HASH_COOKIE, generated, COOKIE_DAYS);

        return generated;
    }

    /**
     * Track a lightweight analytics event in WordPress.
     * @param {string} eventName
     * @param {Object|null} consent
     */
    function trackAnalyticsEvent(eventName, consent = null) {
        if (!analyticsAjaxUrl || !analyticsNonce) {
            return;
        }

        const body = new FormData();
        body.append('action', 'tg_gdpr_track_analytics_event');
        body.append('nonce', analyticsNonce);
        body.append('event_name', eventName);

        if (consent) {
            body.append('consent', JSON.stringify(consent));
        }

        if (navigator.sendBeacon) {
            navigator.sendBeacon(analyticsAjaxUrl, body);
            return;
        }

        fetch(analyticsAjaxUrl, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            keepalive: true,
        }).catch(() => {
            // Ignore analytics tracking failures.
        });
    }

    /**
     * Detect device type from user agent.
     * @param {string} userAgent
     * @returns {string}
     */
    function detectDeviceType(userAgent) {
        if (/tablet|ipad|kindle|playbook/i.test(userAgent)) {
            return 'tablet';
        }

        if (/mobile|android|iphone|ipod|blackberry|windows phone/i.test(userAgent)) {
            return 'mobile';
        }

        if (/mozilla|chrome|safari|firefox|edge|opera/i.test(userAgent)) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Detect browser from user agent.
     * @param {string} userAgent
     * @returns {string|null}
     */
    function detectBrowser(userAgent) {
        if (/edg/i.test(userAgent)) return 'Edge';
        if (/chrome/i.test(userAgent)) return 'Chrome';
        if (/safari/i.test(userAgent)) return 'Safari';
        if (/firefox/i.test(userAgent)) return 'Firefox';
        if (/opera|opr/i.test(userAgent)) return 'Opera';
        if (/msie|trident/i.test(userAgent)) return 'IE';

        return null;
    }

    /**
     * Setup settings button for reopening preferences
     */
    function setupSettingsButton() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.tg-gdpr-settings-button, [data-tg-gdpr-settings]')) {
                e.preventDefault();
                showPreferencesStandalone();
            }
        });
    }

    /**
     * Show preferences panel without banner
     */
    function showPreferencesStandalone() {
        // Load current consent
        const stored = getStoredConsent();
        if (stored) {
            currentConsent = stored;
        }

        showPreferences();
    }

    /**
     * Apply custom styles from settings
     * @param {HTMLElement} element
     */
    function applyCustomStyles(element) {
        if (!settings.appearance) return;

        const style = settings.appearance;

        if (style.primary_color) {
            element.style.setProperty('--tg-gdpr-primary', style.primary_color);
        }
        if (style.secondary_color) {
            element.style.setProperty('--tg-gdpr-secondary', style.secondary_color);
        }
        if (style.text_color) {
            element.style.setProperty('--tg-gdpr-text', style.text_color);
        }
        if (style.background_color) {
            element.style.setProperty('--tg-gdpr-bg', style.background_color);
        }
        if (style.border_radius) {
            element.style.setProperty('--tg-gdpr-radius', style.border_radius + 'px');
        }
        if (style.font_family) {
            element.style.setProperty('--tg-gdpr-font', style.font_family);
        }
    }

    /**
     * Dispatch custom event
     * @param {string} name
     * @param {Object} detail
     */
    function dispatchEvent(name, detail) {
        window.dispatchEvent(new CustomEvent(name, { detail }));
        document.dispatchEvent(new CustomEvent(name, { detail }));
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} str
     * @returns {string}
     */
    function escapeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Expose public API
     */
    function exposePublicAPI() {
        window.TG_GDPR = {
            // Show the banner
            showBanner: showBanner,

            // Hide the banner
            hideBanner: hideBanner,

            // Show preferences
            showPreferences: showPreferencesStandalone,

            // Accept all
            acceptAll: acceptAll,

            // Reject all
            rejectAll: rejectAll,

            // Get current consent
            getConsent: () => ({ ...currentConsent }),

            // Check if category is consented
            hasConsent: (category) => currentConsent[category] === true,

            // Check if any consent exists
            hasStoredConsent: () => getStoredConsent() !== null,

            // Withdraw consent (same as reject all)
            withdrawConsent: rejectAll,

            // Update specific category
            setCategory: (category, value) => {
                if (CATEGORIES[category] && !CATEGORIES[category].required) {
                    currentConsent[category] = !!value;
                    currentConsent.timestamp = Date.now();
                    currentConsent.interaction = 'custom';
                    saveConsent(currentConsent);
                    applyConsent(currentConsent);
                }
            },

            // Get categories info
            getCategories: () => ({ ...CATEGORIES }),

            // Is banner currently visible
            isVisible: () => isOpen,

            // Get version
            version: '1.0.0'
        };
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
