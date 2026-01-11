<?php
/**
 * The public-facing functionality of the plugin.
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
     * Initialize the class.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->script_blocker = new TG_GDPR_Script_Blocker();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            TG_GDPR_PLUGIN_URL . 'public/css/tg-gdpr-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            TG_GDPR_PLUGIN_URL . 'public/js/tg-gdpr-public.js',
            array(),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script(
            $this->plugin_name,
            'TG_GDPR',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tg_gdpr_nonce'),
                'cookie_name' => 'tg_gdpr_consent',
                'cookie_expiry' => $this->get_cookie_expiry(),
            )
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
