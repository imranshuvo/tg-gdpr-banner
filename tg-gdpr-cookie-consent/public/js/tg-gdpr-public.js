/**
 * TG GDPR Cookie Consent - Public JavaScript
 * Handles banner interactions and consent management
 *
 * @package TG_GDPR_Cookie_Consent
 */

(function($) {
    'use strict';

    /**
     * TG GDPR Cookie Consent Class
     */
    class TGGDPRConsent {
        
        constructor() {
            this.banner = $('#tg-gdpr-banner');
            this.mainView = $('#tg-gdpr-main-view');
            this.settingsView = $('#tg-gdpr-settings-view');
            this.revisitBtn = $('#tg-gdpr-revisit');
            
            this.init();
        }
        
        /**
         * Initialize
         */
        init() {
            // Check if user already has consent
            if (this.hasConsent()) {
                this.showRevisitButton();
                return;
            }
            
            // Show banner
            this.showBanner();
            
            // Bind events
            this.bindEvents();
        }
        
        /**
         * Bind events
         */
        bindEvents() {
            // Accept All
            $('#tg-gdpr-accept-all, #tg-gdpr-accept-all-settings').on('click', (e) => {
                e.preventDefault();
                this.acceptAll();
            });
            
            // Reject All
            $('#tg-gdpr-reject-all').on('click', (e) => {
                e.preventDefault();
                this.rejectAll();
            });
            
            // Open Settings
            $('#tg-gdpr-settings-btn, #tg-gdpr-revisit').on('click', (e) => {
                e.preventDefault();
                this.openSettings();
            });
            
            // Back to Main View
            $('#tg-gdpr-back-btn').on('click', (e) => {
                e.preventDefault();
                this.closeSettings();
            });
            
            // Save Settings
            $('#tg-gdpr-save-settings').on('click', (e) => {
                e.preventDefault();
                this.saveSettings();
            });
            
            // Close Banner
            $('#tg-gdpr-close').on('click', (e) => {
                e.preventDefault();
                this.rejectAll();
            });
            
            // Toggle cookie details
            $('.tg-gdpr-cookies-toggle').on('click', function(e) {
                e.preventDefault();
                const category = $(this).data('category');
                const details = $(`.tg-gdpr-cookies-details[data-category="${category}"]`);
                
                $(this).toggleClass('active');
                details.slideToggle(300);
            });
            
            // Keyboard navigation
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.banner.is(':visible')) {
                    this.rejectAll();
                }
            });
        }
        
        /**
         * Show banner
         */
        showBanner() {
            this.banner.fadeIn(300);
            this.banner.attr('aria-hidden', 'false');
            this.trackAnalyticsEvent('banner_shown');
            
            // Trap focus
            this.trapFocus();
        }
        
        /**
         * Hide banner
         */
        hideBanner() {
            this.banner.fadeOut(300);
            this.banner.attr('aria-hidden', 'true');
            
            // Show revisit button after a delay
            setTimeout(() => {
                this.showRevisitButton();
            }, 500);
        }
        
        /**
         * Open settings view
         */
        openSettings() {
            this.mainView.fadeOut(200, () => {
                this.settingsView.fadeIn(200);
            });
            
            // Load current consent
            this.loadCurrentConsent();
        }
        
        /**
         * Close settings view
         */
        closeSettings() {
            this.settingsView.fadeOut(200, () => {
                this.mainView.fadeIn(200);
            });
        }
        
        /**
         * Accept all cookies
         */
        acceptAll() {
            const consent = {
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true,
                interaction: 'accept_all',
                version: TG_GDPR.policy_version || 1
            };
            
            this.saveConsent(consent);
        }
        
        /**
         * Reject all non-essential cookies
         */
        rejectAll() {
            const consent = {
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false,
                interaction: 'reject_all',
                version: TG_GDPR.policy_version || 1
            };
            
            this.saveConsent(consent);
        }
        
        /**
         * Save custom settings
         */
        saveSettings() {
            const consent = {
                necessary: true, // Always true
                functional: $('.tg-gdpr-category-checkbox[data-category="functional"]').is(':checked'),
                analytics: $('.tg-gdpr-category-checkbox[data-category="analytics"]').is(':checked'),
                marketing: $('.tg-gdpr-category-checkbox[data-category="marketing"]').is(':checked'),
                interaction: 'custom',
                version: TG_GDPR.policy_version || 1
            };
            
            this.saveConsent(consent);
        }
        
        /**
         * Save consent to server and cookie
         */
        saveConsent(consent) {
            // Show loading state
            this.showLoading();
            
            $.ajax({
                url: TG_GDPR.ajax_url,
                type: 'POST',
                data: {
                    action: 'tg_gdpr_save_consent',
                    nonce: TG_GDPR.nonce,
                    consent: JSON.stringify(consent)
                },
                success: (response) => {
                    if (response.success) {
                        // Update blocker
                        if (typeof window.TG_GDPR_Blocker !== 'undefined') {
                            window.TG_GDPR_Blocker.updateConsent(consent);
                        }
                        
                        // Hide banner
                        this.hideBanner();
                        
                        // Fire custom event
                        $(document).trigger('tg_gdpr_consent_saved', [consent]);
                        
                        // Reload page to activate scripts
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('TG GDPR: Failed to save consent', error);
                    alert('Failed to save consent. Please try again.');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        /**
         * Load current consent into checkboxes
         */
        loadCurrentConsent() {
            const consent = this.getConsent();
            
            if (consent) {
                $('.tg-gdpr-category-checkbox[data-category="functional"]').prop('checked', consent.functional);
                $('.tg-gdpr-category-checkbox[data-category="analytics"]').prop('checked', consent.analytics);
                $('.tg-gdpr-category-checkbox[data-category="marketing"]').prop('checked', consent.marketing);
            }
        }
        
        /**
         * Get consent from cookie
         */
        getConsent() {
            const cookie = this.getCookie(TG_GDPR.cookie_name);
            
            if (cookie) {
                try {
                    return JSON.parse(decodeURIComponent(cookie));
                } catch (e) {
                    return null;
                }
            }
            
            return null;
        }
        
        /**
         * Check if user has consent
         */
        hasConsent() {
            return this.getCookie(TG_GDPR.cookie_name) !== null;
        }
        
        /**
         * Get cookie value
         */
        getCookie(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? match[2] : null;
        }
        
        /**
         * Show revisit button
         */
        showRevisitButton() {
            this.revisitBtn.fadeIn(300);
        }
        
        /**
         * Show loading state
         */
        showLoading() {
            $('.tg-gdpr-btn').prop('disabled', true).css('opacity', '0.6');
        }
        
        /**
         * Hide loading state
         */
        hideLoading() {
            $('.tg-gdpr-btn').prop('disabled', false).css('opacity', '1');
        }

        /**
         * Track a lightweight analytics event.
         * @param {string} eventName
         */
        trackAnalyticsEvent(eventName) {
            if (!TG_GDPR.ajax_url || !TG_GDPR.nonce) {
                return;
            }

            const body = new FormData();
            body.append('action', 'tg_gdpr_track_analytics_event');
            body.append('nonce', TG_GDPR.nonce);
            body.append('event_name', eventName);

            if (navigator.sendBeacon) {
                navigator.sendBeacon(TG_GDPR.ajax_url, body);
                return;
            }

            $.ajax({
                url: TG_GDPR.ajax_url,
                type: 'POST',
                data: body,
                processData: false,
                contentType: false
            });
        }
        
        /**
         * Trap focus within banner (accessibility)
         */
        trapFocus() {
            const focusableElements = this.banner.find('button, a, input, [tabindex]:not([tabindex="-1"])');
            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();
            
            this.banner.on('keydown', (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement[0]) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement[0]) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
            
            // Focus first element
            setTimeout(() => {
                firstElement.focus();
            }, 100);
        }
    }
    
    /**
     * Public API for developers
     */
    window.TG_GDPR = window.TG_GDPR || {};
    
    /**
     * Check if user has consented to a category
     */
    window.TG_GDPR.hasConsent = function(category) {
        const consent = this.getConsent();
        return consent && consent[category] === true;
    };
    
    /**
     * Get full consent object
     */
    window.TG_GDPR.getConsent = function() {
        const cookie = document.cookie.match(/tg_gdpr_consent=([^;]+)/);
        if (!cookie) return null;
        
        try {
            return JSON.parse(decodeURIComponent(cookie[1]));
        } catch (e) {
            return null;
        }
    };
    
    /**
     * Execute callback when consent is given for category
     */
    window.TG_GDPR.onConsent = function(category, callback) {
        if (this.hasConsent(category)) {
            callback();
        } else {
            $(document).on('tg_gdpr_consent_saved', function(e, consent) {
                if (consent[category]) {
                    callback();
                }
            });
        }
    };
    
    /**
     * Register a script to be loaded when consent is given
     */
    window.TG_GDPR.registerScript = function(category, scriptUrl, attributes) {
        this.onConsent(category, function() {
            const script = document.createElement('script');
            script.src = scriptUrl;
            
            if (attributes) {
                Object.keys(attributes).forEach(key => {
                    script.setAttribute(key, attributes[key]);
                });
            }
            
            document.head.appendChild(script);
        });
    };
    
    /**
     * Show banner/settings
     */
    window.TG_GDPR.showBanner = function() {
        if (window.tgGdprInstance) {
            window.tgGdprInstance.showBanner();
        }
    };
    
    /**
     * Event API
     */
    window.TG_GDPR.on = function(event, callback) {
        $(document).on('tg_gdpr_' + event, function(e, data) {
            callback(data);
        });
    };
    
    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        window.tgGdprInstance = new TGGDPRConsent();
    });

})(jQuery);
