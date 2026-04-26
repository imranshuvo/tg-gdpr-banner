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
                $settings = $this->extract_saas_settings($body);

                if (!empty($settings)) {
                    $this->saas_settings = $settings;
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
        // Modern banner CSS (BEM selectors matching the runtime DOM that
        // banner.js builds via createElement). The legacy `tg-gdpr-public.css`
        // styled the server-rendered partial that we no longer emit, so it's
        // intentionally not enqueued — saves ~2.5 KB gzipped per pageview.
        wp_enqueue_style(
            $this->plugin_name . '-banner',
            TG_GDPR_PLUGIN_URL . 'public/css/tg-gdpr-banner.css',
            array(),
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

        // Legacy public.js (jQuery-based) was previously enqueued here for
        // backward compatibility, but it duplicated banner.js's UI and consent
        // flow against the now-removed server-rendered banner partial. Removed
        // to drop ~3 KB gzipped + the jQuery dependency. The TG_GDPR global is
        // now exported by banner.js itself (window.TG_GDPR — see exposePublicAPI).
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
        $gcm = isset($settings['gcm']) && is_array($settings['gcm']) ? $settings['gcm'] : array();
        $geo_mode = $this->get_geo_targeting_mode($settings);
        $default_state = $this->get_default_gcm_state($settings);

        return array(
            'default_state' => $geo_mode === 'all' ? $default_state : $this->get_permissive_gcm_state(),
            'wait_for_update' => array_key_exists('wait_for_update', $gcm)
                ? (bool) $gcm['wait_for_update']
                : (isset($settings['gcm_wait_for_update']) ? (bool) $settings['gcm_wait_for_update'] : true),
            'wait_timeout_ms' => isset($gcm['wait_timeout_ms'])
                ? (int) $gcm['wait_timeout_ms']
                : (isset($settings['gcm_wait_timeout_ms']) ? (int) $settings['gcm_wait_timeout_ms'] : 500),
            'region_settings' => $this->get_region_settings($settings, $default_state),
        );
    }

    /**
     * Get region-specific GCM settings.
     *
     * @param array $settings Plugin settings.
     * @return array
     */
    private function get_region_settings($settings, $default_state = null) {
        $gcm = isset($settings['gcm']) && is_array($settings['gcm']) ? $settings['gcm'] : array();
        $configured_region_settings = array();

        if (isset($gcm['region_settings']) && is_array($gcm['region_settings'])) {
            $configured_region_settings = $gcm['region_settings'];
        } elseif (isset($settings['gcm_region_settings']) && is_array($settings['gcm_region_settings'])) {
            $configured_region_settings = $settings['gcm_region_settings'];
        }

        $target_countries = $this->get_geo_target_countries($settings);

        if (empty($target_countries)) {
            return $configured_region_settings;
        }

        $default_state = is_array($default_state) ? $default_state : $this->get_default_gcm_state($settings);
        $region_settings = array();

        foreach ($target_countries as $country) {
            $region_settings[$country] = isset($configured_region_settings[$country]) && is_array($configured_region_settings[$country])
                ? array_merge($default_state, $configured_region_settings[$country])
                : $default_state;
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
        $banner = $this->get_banner_config($settings);
        $content = $this->get_content_config($settings);
        $behavior = $this->get_behavior_config($settings);
        $visitor_country = $this->detect_visitor_country();
        $default_title = isset($content['heading']) ? $content['heading'] : (isset($content['title']) ? $content['title'] : __('We value your privacy', 'tg-gdpr-cookie-consent'));
        $default_message = isset($content['message'])
            ? $content['message']
            : __('We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'tg-gdpr-cookie-consent');

        return array(
            'site_token' => isset($license['site_token']) ? $license['site_token'] : '',
            'api_url' => isset($license['api_url']) ? $license['api_url'] : '',
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tg_gdpr_nonce'),
            'cookie_expiry' => $this->get_cookie_expiry($settings),
            'policy_version' => isset($settings['policy_version']) ? $settings['policy_version'] : '1.0',
            'visitor_country' => $visitor_country,
            'consent_enforced' => $this->should_enforce_consent($settings, $visitor_country),
            'geo_targeting_mode' => $this->get_geo_targeting_mode($settings),
            'geo_countries' => $this->get_geo_target_countries($settings),
            
            // Position and layout
            'position' => isset($banner['position']) ? $banner['position'] : 'bottom',
            'layout' => isset($banner['layout']) ? $banner['layout'] : 'box',
            'overlay_enabled' => isset($behavior['show_overlay']) ? (bool) $behavior['show_overlay'] : true,
            
            // Appearance
            'appearance' => array(
                'primary_color' => isset($banner['primary_color']) ? $banner['primary_color'] : '#2563eb',
                'secondary_color' => isset($banner['accent_color']) ? $banner['accent_color'] : (isset($banner['secondary_color']) ? $banner['secondary_color'] : '#64748b'),
                'text_color' => isset($banner['text_color']) ? $banner['text_color'] : '#1e293b',
                'background_color' => isset($banner['bg_color']) ? $banner['bg_color'] : (isset($banner['background_color']) ? $banner['background_color'] : '#ffffff'),
                'border_radius' => isset($banner['border_radius']) ? (int) $banner['border_radius'] : 8,
                'font_family' => isset($banner['font_family']) ? $banner['font_family'] : '',
            ),
            
            // Content
            'content' => array(
                'title' => $default_title,
                'message' => $default_message,
            ),
            
            // URLs
            'privacy_url' => isset($content['privacy_policy_url']) ? $content['privacy_policy_url'] : (isset($settings['privacy_url']) ? $settings['privacy_url'] : get_privacy_policy_url()),
            
            // Internationalization
            'i18n' => array(
                'accept_all' => isset($content['accept_all_text']) ? $content['accept_all_text'] : __('Accept All', 'tg-gdpr-cookie-consent'),
                'reject_all' => isset($content['reject_all_text']) ? $content['reject_all_text'] : __('Reject All', 'tg-gdpr-cookie-consent'),
                'manage' => isset($content['settings_text']) ? $content['settings_text'] : (isset($content['customize_text']) ? $content['customize_text'] : __('Manage Preferences', 'tg-gdpr-cookie-consent')),
                'privacy_link' => isset($content['privacy_policy_text']) ? $content['privacy_policy_text'] : __('Privacy Policy', 'tg-gdpr-cookie-consent'),
                'save_preferences' => isset($content['save_preferences_text']) ? $content['save_preferences_text'] : __('Save Preferences', 'tg-gdpr-cookie-consent'),
                'banner_title' => __('Cookie Consent', 'tg-gdpr-cookie-consent'),
                'default_title' => $default_title,
                'default_message' => $default_message,
                'preferences_title' => __('Cookie Preferences', 'tg-gdpr-cookie-consent'),
                'preferences_intro' => __('Choose which cookie categories you want to allow. You can change these settings at any time.', 'tg-gdpr-cookie-consent'),
                'required' => __('Required', 'tg-gdpr-cookie-consent'),
                
                // Category names
                'necessary_name' => $this->get_category_label($settings, 'necessary', __('Necessary', 'tg-gdpr-cookie-consent')),
                'necessary_desc' => $this->get_category_description($settings, 'necessary', __('Essential cookies required for the website to function properly. Cannot be disabled.', 'tg-gdpr-cookie-consent')),
                'functional_name' => $this->get_category_label($settings, 'functional', __('Functional', 'tg-gdpr-cookie-consent')),
                'functional_desc' => $this->get_category_description($settings, 'functional', __('Cookies that enable enhanced functionality and personalization.', 'tg-gdpr-cookie-consent')),
                'analytics_name' => $this->get_category_label($settings, 'analytics', __('Analytics', 'tg-gdpr-cookie-consent')),
                'analytics_desc' => $this->get_category_description($settings, 'analytics', __('Cookies that help us understand how visitors interact with our website.', 'tg-gdpr-cookie-consent')),
                'marketing_name' => $this->get_category_label($settings, 'marketing', __('Marketing', 'tg-gdpr-cookie-consent')),
                'marketing_desc' => $this->get_category_description($settings, 'marketing', __('Cookies used to deliver personalized advertisements.', 'tg-gdpr-cookie-consent')),
            ),
        );
    }

    /**
     * Inject critical inline script in <head> - RUNS BEFORE EVERYTHING
     * This is the HEART of our performance-first, cache-compatible approach.
     */
    public function inject_critical_inline_script() {
        $settings = $this->get_merged_settings();

        if (!$this->should_enforce_consent($settings)) {
            return;
        }

        // Add inline CSS for hiding blocked scripts
        echo '<style id="tg-gdpr-hide-blocked">[data-tg-blocked]{display:none!important;}</style>';

        // ─── GCM v2 default-deny bootstrap (wp_head priority 0) ───────────────
        // This MUST run before any Google tag (gtag.js, GTM) so they queue
        // commands against a denied state until the visitor decides. The full
        // gcm.js script (enqueued at default priority) takes over from here for
        // region rules and consent updates. ~400 bytes, no network.
        if ($this->is_gcm_enabled($settings)) {
            $existing = isset($_COOKIE['tg_gdpr_consent']) ? $_COOKIE['tg_gdpr_consent'] : '';
            // If the visitor already has a consent cookie, we let gcm.js push the
            // 'update' state from JS — server-side we always emit deny-defaults.
            ?>
            <script id="tg-gdpr-gcm-bootstrap">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('consent', 'default', {
                'ad_storage':'denied',
                'ad_user_data':'denied',
                'ad_personalization':'denied',
                'analytics_storage':'denied',
                'functionality_storage':'denied',
                'personalization_storage':'denied',
                'security_storage':'granted',
                'wait_for_update': 500
            });
            gtag('set','ads_data_redaction', true);
            gtag('set','url_passthrough', true);
            </script>
            <?php
        }

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
            
            // Script blocking patterns. The base list is the universally-known
            // tracker fingerprints; the auto-scanner appends per-site script_patterns
            // to this object so customer-specific scripts are caught too.
            var patterns = <?php echo wp_json_encode($this->build_blocker_patterns()); ?>;

            /**
             * Detect script category by content/src.
             * IMPORTANT: unknown scripts default to 'marketing' (block-by-default),
             * NOT 'necessary'. Customers can mark specific scripts as necessary
             * via the admin Cookies page if they need to. This is the regulator-
             * safe default: better to block a legitimate analytics tag than to
             * accidentally let an undeclared marketing tracker through.
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
                return 'marketing';
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

        if (!$this->should_enforce_consent()) {
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
        $settings = $this->get_merged_settings();

        if (!$this->should_enforce_consent($settings)) {
            return;
        }

        $banner = new TG_GDPR_Banner($settings);
        
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
    /**
     * Build the script-blocker pattern map for the inline `<script>` in <head>.
     *
     * Strategy:
     *   1. Start with a hardcoded list of universally-recognised tracker
     *      fingerprints (the major ad networks, analytics platforms, etc.).
     *   2. Append per-site `script_pattern` strings from the most recent
     *      auto-scan, indexed by the cookie's category. This is what makes the
     *      blocker "auto" — customer-specific scripts the scanner discovered
     *      get blocked even if they're not on our hardcoded list.
     *   3. Filter via the `tg_gdpr_blocker_patterns` action so site owners can
     *      override per-script (mark something as `necessary` to allow it pre-consent).
     *
     * Anything that doesn't match any pattern falls through to the default in
     * detectCategory(), which is `marketing` (block-by-default).
     *
     * @return array<string, string[]>  category => list of substring patterns
     */
    private function build_blocker_patterns() {
        $base = array(
            'analytics' => array(
                // Google
                'google-analytics.com', 'googletagmanager.com', 'gtag', 'ga(',
                // Open-source / privacy-friendly
                'matomo', 'plausible', 'simpleanalytics', 'umami',
                // Microsoft Clarity
                'clarity.ms',
                // Adobe / Mixpanel / Segment / Amplitude
                'omtrdc.net', 'mixpanel.com', 'segment.io', 'amplitude.com',
                // Yandex
                'mc.yandex.ru',
            ),
            'marketing' => array(
                // Meta / Facebook
                'facebook.net', 'fbevents', 'fbq(', 'connect.facebook.net',
                // Google Ads / DoubleClick
                'doubleclick.net', 'googleadservices', 'googlesyndication',
                // Twitter / X
                'ads-twitter.com', 'static.ads-twitter.com', 't.co/i/adsct',
                // LinkedIn
                'snap.licdn.com', 'linkedin.com/li/track', 'linkedin.com/insight',
                // TikTok
                'analytics.tiktok.com', 'tiktok.com/i18n/pixel', 'ttq.load',
                // Pinterest
                'ct.pinterest.com', 's.pinimg.com',
                // Snapchat
                'sc-static.net', 'tr.snapchat.com',
                // Reddit
                'alb.reddit.com', 'redditstatic.com',
                // Microsoft / Bing Ads
                'bat.bing.com',
                // Hotjar / Lucky Orange / FullStory (session replay = marketing)
                'hotjar.com', 'static.hotjar.com', 'luckyorange.com', 'fullstory.com',
                // Pardot, HubSpot, Marketo
                'pi.pardot.com', 'js.hs-scripts.com', 'munchkin.marketo.net',
            ),
            'functional' => array(
                // Maps
                'maps.googleapis.com', 'api.mapbox.com',
                // Embeds (iframes pulled in as functional)
                'youtube.com/iframe', 'youtube.com/embed', 'player.vimeo.com',
                // Captchas (necessary for security but classed functional)
                'recaptcha', 'hcaptcha.com', 'cloudflare.com/turnstile',
                // Live chat
                'intercom.io', 'crisp.chat', 'tawk.to',
            ),
            'necessary' => array(
                // Payment processors — never block these without explicit admin override.
                'js.stripe.com', 'checkout.stripe.com',
                'paypal.com/sdk', 'www.paypalobjects.com',
                'klarna.com',
            ),
        );

        // Append per-site script_patterns from the most recent scan.
        $report = get_option('tg_gdpr_last_cookie_scan_report', array());
        if (!empty($report['cookies']) && is_array($report['cookies'])) {
            foreach ($report['cookies'] as $cookie) {
                if (empty($cookie['script_pattern']) || empty($cookie['category'])) {
                    continue;
                }
                $cat = $cookie['category'];
                if (!isset($base[$cat])) {
                    $base[$cat] = array();
                }
                if (!in_array($cookie['script_pattern'], $base[$cat], true)) {
                    $base[$cat][] = $cookie['script_pattern'];
                }
            }
        }

        /**
         * Filter the script-blocker pattern map. Use this to override defaults
         * (e.g. mark a corporate analytics script as `necessary` to allow it
         * pre-consent for ops monitoring).
         *
         * @param array<string,string[]> $patterns
         */
        return apply_filters('tg_gdpr_blocker_patterns', $base);
    }

    private function get_cookie_expiry($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_merged_settings();
        $behavior = $this->get_behavior_config($settings);

        if (isset($behavior['consent_expiry_days'])) {
            return (int) $behavior['consent_expiry_days'];
        }

        if (isset($settings['advanced']['consent_expiry'])) {
            return (int) $settings['advanced']['consent_expiry'];
        }

        return 365;
    }

    /**
     * Extract SaaS settings from API responses with either legacy or current response shapes.
     *
     * @param array|null $body
     * @return array
     */
    private function extract_saas_settings($body) {
        if (!is_array($body)) {
            return array();
        }

        if (isset($body['settings']) && is_array($body['settings'])) {
            return $body['settings'];
        }

        if (isset($body['data']) && is_array($body['data'])) {
            if (isset($body['data']['settings']) && is_array($body['data']['settings'])) {
                return $body['data']['settings'];
            }

            return $body['data'];
        }

        return array();
    }

    /**
     * Get normalized banner config for either local or SaaS settings shapes.
     *
     * @param array $settings
     * @return array
     */
    private function get_banner_config($settings) {
        if (isset($settings['banner']) && is_array($settings['banner'])) {
            return $settings['banner'];
        }

        if (isset($settings['appearance']) && is_array($settings['appearance'])) {
            return $settings['appearance'];
        }

        return array();
    }

    /**
     * Get normalized content config.
     *
     * @param array $settings
     * @return array
     */
    private function get_content_config($settings) {
        return isset($settings['content']) && is_array($settings['content']) ? $settings['content'] : array();
    }

    /**
     * Get normalized behavior config.
     *
     * @param array $settings
     * @return array
     */
    private function get_behavior_config($settings) {
        return isset($settings['behavior']) && is_array($settings['behavior']) ? $settings['behavior'] : array();
    }

    /**
     * Get normalized geo targeting mode.
     *
     * @param array $settings
     * @return string
     */
    private function get_geo_targeting_mode($settings) {
        if (isset($settings['geo_targeting_mode'])) {
            return sanitize_key($settings['geo_targeting_mode']);
        }

        if (empty($settings['geo_targeting_enabled'])) {
            return 'all';
        }

        $countries = isset($settings['geo_countries']) && is_array($settings['geo_countries']) ? $settings['geo_countries'] : array();

        if (empty($countries) || in_array('EU', $countries, true)) {
            return 'eu';
        }

        return 'selected';
    }

    /**
     * Get targeted countries for geo enforcement.
     *
     * @param array $settings
     * @return array
     */
    private function get_geo_target_countries($settings) {
        $mode = $this->get_geo_targeting_mode($settings);

        if ($mode === 'all') {
            return array();
        }

        if ($mode === 'eu') {
            return $this->get_supported_european_countries();
        }

        $countries = isset($settings['geo_countries']) && is_array($settings['geo_countries']) ? $settings['geo_countries'] : array();

        return array_values(array_unique(array_filter(array_map('strtoupper', $countries), function($country) {
            return in_array($country, $this->get_supported_european_countries(), true);
        })));
    }

    /**
     * Determine whether consent enforcement should apply for the current request.
     *
     * @param array|null $settings
     * @param string|null $visitor_country
     * @return bool
     */
    private function should_enforce_consent($settings = null, $visitor_country = null) {
        $settings = is_array($settings) ? $settings : $this->get_merged_settings();
        $mode = $this->get_geo_targeting_mode($settings);

        if ($mode === 'all') {
            return true;
        }

        $visitor_country = is_string($visitor_country) ? strtoupper($visitor_country) : $this->detect_visitor_country();

        if (empty($visitor_country)) {
            return true;
        }

        return in_array($visitor_country, $this->get_geo_target_countries($settings), true);
    }

    /**
     * Detect the current visitor country from trusted proxy headers.
     *
     * @return string|null
     */
    private function detect_visitor_country() {
        $header_keys = array(
            'HTTP_CF_IPCOUNTRY',
            'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
            'HTTP_FASTLY_COUNTRY_CODE',
            'HTTP_X_COUNTRY_CODE',
            'HTTP_X_COUNTRY',
            'GEOIP_COUNTRY_CODE',
        );

        foreach ($header_keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            $country = strtoupper(substr(sanitize_text_field(wp_unslash($_SERVER[$key])), 0, 2));

            if (preg_match('/^[A-Z]{2}$/', $country) === 1 && !in_array($country, array('XX', 'T1'), true)) {
                return $country;
            }
        }

        return null;
    }

    /**
     * Get the configured GCM default state.
     *
     * @param array $settings
     * @return array
     */
    private function get_default_gcm_state($settings) {
        $fallback = array(
            'ad_storage' => 'denied',
            'analytics_storage' => 'denied',
            'ad_user_data' => 'denied',
            'ad_personalization' => 'denied',
            'functionality_storage' => 'denied',
            'personalization_storage' => 'denied',
            'security_storage' => 'granted',
        );

        if (isset($settings['gcm']['default_state']) && is_array($settings['gcm']['default_state'])) {
            return array_merge($fallback, $settings['gcm']['default_state']);
        }

        if (isset($settings['gcm_default_state']) && is_array($settings['gcm_default_state'])) {
            return array_merge($fallback, $settings['gcm_default_state']);
        }

        return $fallback;
    }

    /**
     * Get a permissive GCM state for non-targeted regions.
     *
     * @return array
     */
    private function get_permissive_gcm_state() {
        return array(
            'ad_storage' => 'granted',
            'analytics_storage' => 'granted',
            'ad_user_data' => 'granted',
            'ad_personalization' => 'granted',
            'functionality_storage' => 'granted',
            'personalization_storage' => 'granted',
            'security_storage' => 'granted',
        );
    }

    /**
     * Resolve a category label for either local or SaaS settings.
     *
     * @param array $settings
     * @param string $category
     * @param string $default
     * @return string
     */
    private function get_category_label($settings, $category, $default) {
        if (isset($settings['categories'][$category]['title'])) {
            return $settings['categories'][$category]['title'];
        }

        if (isset($settings['categories'][$category]) && is_string($settings['categories'][$category])) {
            return $settings['categories'][$category];
        }

        return $default;
    }

    /**
     * Resolve a category description for either local or SaaS settings.
     *
     * @param array $settings
     * @param string $category
     * @param string $default
     * @return string
     */
    private function get_category_description($settings, $category, $default) {
        if (isset($settings['categories'][$category]['description'])) {
            return $settings['categories'][$category]['description'];
        }

        if (isset($settings['category_descriptions'][$category]) && is_string($settings['category_descriptions'][$category])) {
            return $settings['category_descriptions'][$category];
        }

        return $default;
    }

    /**
     * Get supported European country codes for geo targeting.
     *
     * @return array
     */
    private function get_supported_european_countries() {
        return array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'IS', 'LI', 'NO', 'CH');
    }
}
