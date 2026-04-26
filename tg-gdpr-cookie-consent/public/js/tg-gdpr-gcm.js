/**
 * TG GDPR - Google Consent Mode v2 Handler
 * 
 * This module implements Google Consent Mode v2 which is required for
 * Google Ads and Analytics in the EU since March 2024.
 * 
 * Consent Types:
 * - ad_storage: Enables storage for advertising (cookies)
 * - analytics_storage: Enables storage for analytics (cookies)
 * - ad_user_data: Consent to send user data to Google for advertising
 * - ad_personalization: Consent for personalized advertising
 * - functionality_storage: Enables storage for functionality features
 * - personalization_storage: Enables storage for personalization
 * - security_storage: Enables storage for security (always granted)
 * 
 * @package TG_GDPR_Cookie_Consent
 */

(function() {
    'use strict';

    // Initialize dataLayer
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }

    // Get settings from localized script
    const settings = window.TG_GDPR_GCM_Settings || {};

    // Debug-flag-gated console helpers; default no-op for production sites.
    const log  = settings.debug ? console.log.bind(console)  : function () {};
    const warn = settings.debug ? console.warn.bind(console) : function () {};
    const defaultState = settings.default_state || {
        'ad_storage': 'denied',
        'analytics_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted'
    };

    const waitForUpdate = settings.wait_for_update !== false;
    const waitTimeout = settings.wait_timeout_ms || 500;
    const regionSettings = settings.region_settings || {};

    /**
     * Initialize Google Consent Mode with default denied state
     * This MUST run before any Google tags
     */
    function initializeGCM() {
        // Set default consent state
        gtag('consent', 'default', {
            ...defaultState,
            'wait_for_update': waitForUpdate ? waitTimeout : undefined
        });

        // Apply region-specific defaults if configured
        Object.keys(regionSettings).forEach(region => {
            gtag('consent', 'default', {
                ...regionSettings[region],
                'region': [region]
            });
        });

        // Enable URL passthrough for better conversion tracking
        gtag('set', 'url_passthrough', true);

        // Enable ads data redaction when consent is denied
        gtag('set', 'ads_data_redaction', true);

        log('[TG GDPR] Google Consent Mode v2 initialized with default state:', defaultState);
    }

    /**
     * Update consent state based on user preferences
     * @param {Object} consent - Consent object from cookie/banner
     */
    function updateConsentState(consent) {
        if (!consent || typeof consent !== 'object') {
            warn('[TG GDPR] Invalid consent object provided');
            return;
        }

        const gcmState = mapConsentToGCM(consent);

        gtag('consent', 'update', gcmState);

        log('[TG GDPR] Google Consent Mode updated:', gcmState);

        // Dispatch event for other scripts to listen
        window.dispatchEvent(new CustomEvent('tg_gdpr_gcm_updated', {
            detail: { consent: gcmState }
        }));
    }

    /**
     * Map TG GDPR consent categories to Google Consent Mode types
     * @param {Object} consent - TG GDPR consent object
     * @returns {Object} Google Consent Mode state object
     */
    function mapConsentToGCM(consent) {
        const hasAnalytics = consent.analytics === true;
        const hasMarketing = consent.marketing === true;
        const hasFunctional = consent.functional === true;

        return {
            // Analytics storage - tied to analytics category
            'analytics_storage': hasAnalytics ? 'granted' : 'denied',
            
            // Ad storage - tied to marketing category
            'ad_storage': hasMarketing ? 'granted' : 'denied',
            
            // Ad user data - tied to marketing category (required for Google Ads)
            'ad_user_data': hasMarketing ? 'granted' : 'denied',
            
            // Ad personalization - tied to marketing category
            'ad_personalization': hasMarketing ? 'granted' : 'denied',
            
            // Functionality storage - tied to functional category
            'functionality_storage': hasFunctional ? 'granted' : 'denied',
            
            // Personalization storage - tied to functional category
            'personalization_storage': hasFunctional ? 'granted' : 'denied',
            
            // Security storage - always granted (necessary)
            'security_storage': 'granted'
        };
    }

    /**
     * Get current GCM state
     * @returns {Object} Current consent state
     */
    function getCurrentState() {
        // Try to get from existing consent cookie
        const consentCookie = getCookie('tg_gdpr_consent');
        
        if (consentCookie) {
            try {
                const consent = JSON.parse(consentCookie);
                return mapConsentToGCM(consent);
            } catch (e) {
                warn('[TG GDPR] Failed to parse consent cookie');
            }
        }
        
        return defaultState;
    }

    /**
     * Get cookie value by name
     * @param {string} name - Cookie name
     * @returns {string|null} Cookie value or null
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
     * Check if user has existing consent
     * @returns {boolean}
     */
    function hasExistingConsent() {
        return getCookie('tg_gdpr_consent') !== null;
    }

    // Initialize GCM immediately (before any Google tags load)
    initializeGCM();

    // If user already has consent, update GCM state immediately
    if (hasExistingConsent()) {
        const consentCookie = getCookie('tg_gdpr_consent');
        try {
            const consent = JSON.parse(consentCookie);
            updateConsentState(consent);
        } catch (e) {
            warn('[TG GDPR] Failed to parse existing consent');
        }
    }

    // Expose public API
    window.TG_GDPR_GCM = {
        /**
         * Update consent state
         * @param {Object} consent - Consent preferences
         */
        update: updateConsentState,

        /**
         * Get current GCM state
         * @returns {Object}
         */
        getState: getCurrentState,

        /**
         * Check if GCM is initialized
         * @returns {boolean}
         */
        isInitialized: function() {
            return true;
        },

        /**
         * Get default state
         * @returns {Object}
         */
        getDefaultState: function() {
            return { ...defaultState };
        },

        /**
         * Grant all consent (accept all)
         */
        grantAll: function() {
            updateConsentState({
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true
            });
        },

        /**
         * Deny all consent (reject all)
         */
        denyAll: function() {
            updateConsentState({
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false
            });
        }
    };

    // Listen for consent changes from the main TG GDPR script
    window.addEventListener('tg_gdpr_consent_saved', function(event) {
        if (event.detail && event.detail.consent) {
            updateConsentState(event.detail.consent);
        }
    });

    // Also listen on document for broader compatibility
    document.addEventListener('tg_gdpr_consent_updated', function(event) {
        if (event.detail && event.detail.consent) {
            updateConsentState(event.detail.consent);
        }
    });

    log('[TG GDPR] Google Consent Mode v2 module loaded');

})();
