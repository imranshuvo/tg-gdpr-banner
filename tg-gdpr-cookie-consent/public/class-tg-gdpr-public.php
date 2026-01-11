<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Handles frontend rendering, script loading, and consent management
 * with full Google Consent Mode v2 integration.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Public {

    /**
     * The ID of this plugin.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string
     */
    private $version;

    /**
     * Script blocker instance.
     *
     * @var TG_GDPR_Script_Blocker
     */
    private $script_blocker;

    /**
     * Cached settings from SaaS API.
     *
     * @var array|null
     */
    private $saas_settings = null;

    /**
     * Initialize the class.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->script_blocker = new TG_GDPR_Script_Blocker();
        
        // Load settings from SaaS if available
        $this->load_saas_settings();
    }

    /**
     * Load settings from SaaS API (cached locally).
     */
    private function load_saas_settings() {
        $cached = get_transient('tg_gdpr_saas_settings');
        
        if ($cached !== false) {
            $this->saas_settings = $cached;
            return;
        }
        
        // Try to fetch from API
        $settings = get_option('tg_gdpr_settings', array());
        $site_token = isset($settings['license']['site_token']) ? $settings['license']['site_token'] : '';
        $api_url = isset($settings['license']['api_url']) ? $settings['license']['api_url'] : '';
        
        if (!empty($site_token) && !empty($api_url)) {
            $response = wp_remote_get(
                trailingslashit($api_url) . 'api/v1/site/settings?site_token=' . $site_token,
                array(
                    'timeout' => 5,
                    'headers' => array(
                        'Accept' => 'application/json',
                    ),
                )
            );
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['settings'])) {
                    $this->saas_settings = $body['settings'];
                    // Cache for 5 minutes
                    set_transient('tg_gdpr_saas_settings', $this->saas_settings, 5 * MINUTE_IN_SECONDS);
                }
            }
        }
    }

    /**
     * Get merged settings (local + SaaS override).
     *
     * @return array
     */
    private function get_merged_settings() {
        $local = get_option('tg_gdpr_settings', array());
        
        if (!empty($this->saas_settings)) {
            // SaaS settings override local settings
            return wp_parse_args($this->saas_settings, $local);
        }
        
        return $local;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        // Load new enhanced banner CSS
        wp_enqueue_style(
            $this->plugin_name . '-banner',
            TG_GDPR_PLUGIN_URL . 'public/css/tg-gdpr-banner.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            $this->plugin_name,
            TG_GDPR_PLUGIN_URL . 'public/css/tg-gdpr-public.css',
            array($this->plugin_name . '-banner'),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        $settings = $this->get_merged_settings();
        $license = isset($settings['license']) ? $settings['license'] : array();

        // 1. Google Consent Mode v2 - MUST load first
        if ($this->is_gcm_enabled($settings)) {
            wp_enqueue_script(
                $this->plugin_name . '-gcm',
                TG_GDPR_PLUGIN_URL . 'public/js/tg-gdpr-gcm.js',
                array(),
                $this->version,
                false // Load in head
            );

            wp_localize_script(
                $this->plugin_name . '-gcm',
                'TG_GDPR_GCM_Settings',
                $this->get_gcm_settings($settings)
            );
        }

        // 2. Enhanced banner script
        wp_enqueue_script(
            $this->plugin_name . '-banner',
            TG_GDPR_PLUGIN_URL . 'public/js/tg-gdpr-banner.js',
            array(),
            $this->version,
            true
        );

        // Banner settings
        wp_localize_script(
            $this->plugin_name . '-banner',
            'TG_GDPR_Banner_Settings',
            $this->get_banner_settings($settings)
        );

        // 3. Legacy script for backward compatibility
        wp_enqueue_script(
            $this->plugin_name,
            TG_GDPR_PLUGIN_URL . 'public/js/tg-gdpr-public.js',
            array($this->plugin_name . '-banner'),
            $this->version,
            true
        );
        
        // Legacy localization
        wp_localize_script(
            $this->plugin_name,
            'TG_GDPR',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tg_gdpr_nonce'),
                'cookie_name' => 'tg_gdpr_consent',
                'cookie_expiry' => $this->get_cookie_expiry(),
                'api_url' => isset($license['api_url']) ? $license['api_url'] : '',
                'site_token' => isset($license['site_token']) ? $license['site_token'] : '',
            )
        );
    }

    /**
     * Check if Google Consent Mode is enabled.
     *
     * @param array $settings Plugin settings.
     * @return bool
     */
    private function is_gcm_enabled($settings) {
        // GCM is enabled by default for EU compliance
        if (isset($settings['gcm_enabled'])) {
            return (bool) $settings['gcm_enabled'];
        }
        return true;
    }

    /**
     * Get Google Consent Mode settings.
     *
     * @param array $settings Plugin settings.
     * @return array
     */
    private function get_gcm_settings($settings) {
        return array(
            'default_state' => array(
                'ad_storage' => 'denied',
                'analytics_storage' => 'denied',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
                'functionality_storage' => 'denied',
                'personalization_storage' => 'denied',
                'security_storage' => 'granted',
            ),
            'wait_for_update' => true,
            'wait_timeout_ms' => isset($settings['gcm_timeout']) ? (int) $settings['gcm_timeout'] : 500,
            'region_settings' => $this->get_region_settings($settings),
        );
    }

    /**
     * Get region-specific GCM settings.
     *
     * @param array $settings Plugin settings.
     * @return array
     */
    private function get_region_settings($settings) {
        // EU countries require strict consent
        $eu_countries = array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 
                              'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 
                              'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'IS', 'LI', 
                              'NO', 'CH');
        
        $region_settings = array();
        
        foreach ($eu_countries as $country) {
            $region_settings[$country] = array(
                'ad_storage' => 'denied',
                'analytics_storage' => 'denied',
                'ad_user_data' => 'denied',
                'ad_personalization' => 'denied',
            );
        }
        
        return $region_settings;
    }

    /**
     * Get banner settings for JavaScript.
     *
     * @param array $settings Plugin settings.
     * @return array
     */
    private function get_banner_settings($settings) {
        $license = isset($settings['license']) ? $settings['license'] : array();
        $appearance = isset($settings['appearance']) ? $settings['appearance'] : array();
        $content = isset($settings['content']) ? $settings['content'] : array();
        $behavior = isset($settings['behavior']) ? $settings['behavior'] : array();

        return array(
            'site_token' => isset($license['site_token']) ? $license['site_token'] : '',
            'api_url' => isset($license['api_url']) ? $license['api_url'] : '',
            'cookie_expiry' => $this->get_cookie_expiry(),
            'policy_version' => isset($settings['policy_version']) ? $settings['policy_version'] : '1.0',
            
            // Position and layout
            'position' => isset($appearance['position']) ? $appearance['position'] : 'bottom',
            'layout' => isset($appearance['layout']) ? $appearance['layout'] : 'box',
            'overlay_enabled' => isset($behavior['show_overlay']) ? (bool) $behavior['show_overlay'] : true,
            
            // Appearance
            'appearance' => array(
                'primary_color' => isset($appearance['primary_color']) ? $appearance['primary_color'] : '#2563eb',
                'secondary_color' => isset($appearance['secondary_color']) ? $appearance['secondary_color'] : '#64748b',
                'text_color' => isset($appearance['text_color']) ? $appearance['text_color'] : '#1e293b',
                'background_color' => isset($appearance['background_color']) ? $appearance['background_color'] : '#ffffff',
                'border_radius' => isset($appearance['border_radius']) ? (int) $appearance['border_radius'] : 8,
                'font_family' => isset($appearance['font_family']) ? $appearance['font_family'] : '',
            ),
            
            // Content
            'content' => array(
                'title' => isset($content['title']) ? $content['title'] : __('We value your privacy', 'tg-gdpr-cookie-consent'),
                'message' => isset($content['message']) ? $content['message'] : __('We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'tg-gdpr-cookie-consent'),
            ),
            
            // URLs
            'privacy_url' => isset($settings['privacy_url']) ? $settings['privacy_url'] : get_privacy_policy_url(),
            
            // Internationalization
            'i18n' => array(
                'accept_all' => __('Accept All', 'tg-gdpr-cookie-consent'),
                'reject_all' => __('Reject All', 'tg-gdpr-cookie-consent'),
                'manage' => __('Manage Preferences', 'tg-gdpr-cookie-consent'),
                'privacy_link' => __('Privacy Policy', 'tg-gdpr-cookie-consent'),
                'save_preferences' => __('Save Preferences', 'tg-gdpr-cookie-consent'),
                'preferences_title' => __('Cookie Preferences', 'tg-gdpr-cookie-consent'),
                'preferences_intro' => __('Choose which cookie categories you want to allow. You can change these settings at any time.', 'tg-gdpr-cookie-consent'),
                'required' => __('Required', 'tg-gdpr-cookie-consent'),
                
                // Category names
                'necessary_name' => __('Necessary', 'tg-gdpr-cookie-consent'),
                'necessary_desc' => __('Essential cookies required for the website to function properly. Cannot be disabled.', 'tg-gdpr-cookie-consent'),
                'functional_name' => __('Functional', 'tg-gdpr-cookie-consent'),
                'functional_desc' => __('Cookies that enable enhanced functionality and personalization.', 'tg-gdpr-cookie-consent'),
                'analytics_name' => __('Analytics', 'tg-gdpr-cookie-consent'),
                'analytics_desc' => __('Cookies that help us understand how visitors interact with our website.', 'tg-gdpr-cookie-consent'),
                'marketing_name' => __('Marketing', 'tg-gdpr-cookie-consent'),
                'marketing_desc' => __('Cookies used to deliver personalized advertisements.', 'tg-gdpr-cookie-consent'),
            ),
        );
    }

    /**
     * Inject critical inline script in <head> - RUNS BEFORE EVERYTHING
     * This is the HEART of our performance-first, cache-compatible approach.
     */
    public function inject_critical_inline_script() {
        // Add inline CSS for hiding blocked scripts
        echo '<style id="tg-gdpr-hide-blocked">[data-tg-blocked]{display:none!important;}</style>';
        
        // Inject critical JavaScript
        ?>
        <script id="tg-gdpr-critical">
        /**
         * TG GDPR Cookie Consent - Critical Inline Script
         * Size: ~2KB minified
         * Execution: <1ms
         * Purpose: Block scripts BEFORE any optimization plugin runs
         */
        (function() {
            'use strict';
            
            // Read consent from cookie (fastest method)
            var consent = (function() {
                var match = document.cookie.match(/tg_gdpr_consent=([^;]+)/);
                if (!match) return null;
                try {
                    return JSON.parse(decodeURIComponent(match[1]));
                } catch(e) {
                    return null;
                }
            })();
            
            // Script blocking patterns
            var patterns = {
                analytics: [
                    'google-analytics.com', 'googletagmanager.com', 'gtag', 'ga(',
                    'matomo', 'plausible'
                ],
                marketing: [
                    'facebook.net', 'fbevents', 'fbq(', 'doubleclick',
                    'ads-twitter', 'bat.bing'
                ],
                functional: [
                    'maps.googleapis.com', 'youtube.com/iframe', 'recaptcha'
                ]
            };
            
            /**
             * Detect script category by content/src
             */
            function detectCategory(script) {
                var src = script.src || script.innerHTML || '';
                
                for (var category in patterns) {
                    for (var i = 0; i < patterns[category].length; i++) {
                        if (src.indexOf(patterns[category][i]) !== -1) {
                            return category;
                        }
                    }
                }
                return 'necessary';
            }
            
            /**
             * Block a script tag
             */
            function blockScript(script, category) {
                if (script.hasAttribute('data-tg-gdpr-checked')) {
                    return; // Already processed
                }
                
                // Save original src if exists
                if (script.src) {
                    script.setAttribute('data-tg-src', script.src);
                    script.removeAttribute('src');
                }
                
                // Change type to prevent execution
                script.type = 'text/plain';
                script.setAttribute('data-tg-category', category);
                script.setAttribute('data-tg-blocked', '1');
                script.setAttribute('data-tg-gdpr-checked', '1');
            }
            
            /**
             * Unblock a script (when consent is given)
             */
            function unblockScript(script) {
                // Restore src if exists
                if (script.hasAttribute('data-tg-src')) {
                    script.src = script.getAttribute('data-tg-src');
                }
                
                // Change back to executable type
                script.type = 'text/javascript';
                script.removeAttribute('data-tg-blocked');
                
                // Re-execute inline scripts
                if (!script.src && script.innerHTML) {
                    var newScript = document.createElement('script');
                    newScript.textContent = script.innerHTML;
                    script.parentNode.replaceChild(newScript, script);
                }
            }
            
            // If no consent yet, set up blocking
            if (!consent) {
                // Use MutationObserver for real-time script blocking
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.tagName === 'SCRIPT') {
                                var category = detectCategory(node);
                                if (category !== 'necessary') {
                                    blockScript(node, category);
                                } else {
                                    node.setAttribute('data-tg-gdpr-checked', '1');
                                }
                            }
                        });
                    });
                });
                
                // Start observing
                if (document.documentElement) {
                    observer.observe(document.documentElement, {
                        childList: true,
                        subtree: true
                    });
                }
                
                // Also block existing scripts (for cache compatibility)
                document.addEventListener('DOMContentLoaded', function() {
                    var scripts = document.getElementsByTagName('script');
                    for (var i = 0; i < scripts.length; i++) {
                        var script = scripts[i];
                        if (!script.hasAttribute('data-tg-gdpr-checked')) {
                            var category = detectCategory(script);
                            if (category !== 'necessary') {
                                blockScript(script, category);
                            }
                        }
                    }
                });
            } else {
                // Has consent - unblock allowed categories
                document.addEventListener('DOMContentLoaded', function() {
                    var scripts = document.querySelectorAll('script[data-tg-blocked]');
                    scripts.forEach(function(script) {
                        var category = script.getAttribute('data-tg-category');
                        if (consent[category]) {
                            unblockScript(script);
                        }
                    });
                });
            }
            
            /**
             * Public API
             */
            window.TG_GDPR_Blocker = {
                consent: consent,
                detectCategory: detectCategory,
                blockScript: blockScript,
                unblockScript: unblockScript,
                
                /**
                 * Update consent and reload scripts
                 */
                updateConsent: function(newConsent) {
                    consent = newConsent;
                    
                    // Unblock newly allowed scripts
                    var blockedScripts = document.querySelectorAll('script[data-tg-blocked]');
                    blockedScripts.forEach(function(script) {
                        var category = script.getAttribute('data-tg-category');
                        if (consent[category]) {
                            unblockScript(script);
                        }
                    });
                    
                    // Reload page to fully activate scripts
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                }
            };
            
        })();
        </script>
        <?php
    }

    /**
     * Start output buffering to modify HTML.
     */
    public function start_output_buffering() {
        // Only on frontend
        if (is_admin()) {
            return;
        }
        
        // Check if caching is active
        if ($this->script_blocker->is_page_cached()) {
            // Cache is active - rely on client-side blocking only
            return;
        }
        
        // No cache - use server-side blocking for best performance
        ob_start(array($this, 'modify_html'));
    }

    /**
     * Modify HTML buffer to block scripts.
     *
     * @param string $html HTML content.
     * @return string Modified HTML.
     */
    public function modify_html($html) {
        return $this->script_blocker->block_scripts($html);
    }

    /**
     * Render the cookie banner.
     */
    public function render_banner() {
        $banner = new TG_GDPR_Banner();
        
        // Inject inline styles
        echo $banner->get_inline_styles();
        
        // Render banner
        $banner->render();
    }

    /**
     * Get cookie expiry in days.
     *
     * @return int
     */
    private function get_cookie_expiry() {
        $settings = get_option('tg_gdpr_settings', array());
        return isset($settings['advanced']['consent_expiry']) ? (int) $settings['advanced']['consent_expiry'] : 365;
    }
}
