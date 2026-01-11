<?php
/**
 * Script Blocker - The heart of GDPR compliance
 * 
 * This class blocks scripts until user consent is given.
 * Performance-first approach with cache compatibility.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Script_Blocker {

    /**
     * Tracking script patterns by category.
     *
     * @var array
     */
    private $script_patterns = array();

    /**
     * Initialize the script blocker.
     */
    public function __construct() {
        $this->load_script_patterns();
    }

    /**
     * Load script patterns from database and defaults.
     */
    private function load_script_patterns() {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        // Get patterns from database
        $cookies = $wpdb->get_results("SELECT category, script_pattern FROM $table WHERE script_pattern != '' AND is_active = 1");
        
        $patterns = array(
            'analytics' => array(),
            'marketing' => array(),
            'functional' => array(),
        );
        
        foreach ($cookies as $cookie) {
            if (!empty($cookie->script_pattern)) {
                $pattern_array = explode('|', $cookie->script_pattern);
                $patterns[$cookie->category] = array_merge(
                    $patterns[$cookie->category],
                    $pattern_array
                );
            }
        }
        
        // Add default patterns as fallback
        $default_patterns = array(
            'analytics' => array(
                'google-analytics.com/analytics.js',
                'google-analytics.com/ga.js',
                'googletagmanager.com/gtag/js',
                'googletagmanager.com/gtm.js',
                'gtag(',
                'ga(',
                '_gaq',
                'matomo.js',
                'matomo.php',
                'plausible.io/js',
                'analytics.tiktok.com',
            ),
            'marketing' => array(
                'connect.facebook.net/en_US/fbevents.js',
                'facebook.net/en_US/fbevents.js',
                'fbq(',
                'doubleclick.net',
                'googleadservices.com',
                'ads-twitter.com',
                'static.ads-twitter.com',
                'bat.bing.com',
                'snap.licdn.com',
                'pinterest.com/ct',
                'reddit.com/gtm',
            ),
            'functional' => array(
                'maps.googleapis.com',
                'maps.google.com',
                'youtube.com/iframe_api',
                'youtube-nocookie.com',
                'vimeo.com/api',
                'player.vimeo.com',
                'recaptcha',
                'gstatic.com/recaptcha',
                'hcaptcha.com',
            ),
        );
        
        // Merge with defaults
        foreach ($default_patterns as $category => $default) {
            $patterns[$category] = array_unique(array_merge($patterns[$category], $default));
        }
        
        $this->script_patterns = apply_filters('tg_gdpr_script_patterns', $patterns);
    }

    /**
     * Block scripts in HTML based on consent.
     *
     * @param string $html The HTML content.
     * @return string Modified HTML with blocked scripts.
     */
    public function block_scripts($html) {
        // Skip if already processed (cached pages)
        if (strpos($html, 'data-tg-gdpr-processed') !== false) {
            return $html;
        }
        
        // Get user consent
        $consent = $this->get_consent();
        
        // Mark as processed
        $html = str_replace('<html', '<html data-tg-gdpr-processed="1"', $html);
        
        // Block scripts by category
        foreach ($this->script_patterns as $category => $patterns) {
            // Skip if user has consented
            if (isset($consent[$category]) && $consent[$category] === true) {
                continue;
            }
            
            // Block scripts for this category
            $html = $this->block_category_scripts($html, $category, $patterns);
        }
        
        return $html;
    }

    /**
     * Block scripts for a specific category.
     *
     * @param string $html HTML content.
     * @param string $category Cookie category.
     * @param array $patterns Script patterns to block.
     * @return string Modified HTML.
     */
    private function block_category_scripts($html, $category, $patterns) {
        // Process external scripts
        foreach ($patterns as $pattern) {
            // Escape special regex characters
            $pattern_escaped = preg_quote($pattern, '/');
            
            // Block <script src="..."> tags
            $html = preg_replace_callback(
                '/<script([^>]*?)src=["\']([^"\']*' . $pattern_escaped . '[^"\']*)["\'](.*?)>(.*?)<\/script>/is',
                function($matches) use ($category) {
                    return $this->convert_to_blocked_script($matches, $category);
                },
                $html
            );
            
            // Block inline scripts containing the pattern
            $html = preg_replace_callback(
                '/<script([^>]*)>(.*?' . $pattern_escaped . '.*?)<\/script>/is',
                function($matches) use ($category) {
                    return $this->convert_to_blocked_script($matches, $category, true);
                },
                $html
            );
        }
        
        return $html;
    }

    /**
     * Convert script tag to blocked version.
     *
     * @param array $matches Regex matches.
     * @param string $category Cookie category.
     * @param bool $is_inline Whether this is an inline script.
     * @return string Blocked script tag.
     */
    private function convert_to_blocked_script($matches, $category, $is_inline = false) {
        if ($is_inline) {
            // Inline script: <script>code</script>
            $attributes = $matches[1];
            $content = $matches[2];
            
            // Check if already blocked
            if (strpos($attributes, 'data-tg-category') !== false) {
                return $matches[0];
            }
            
            return sprintf(
                '<script type="text/plain" data-tg-category="%s" data-tg-blocked="1"%s>%s</script>',
                esc_attr($category),
                $attributes,
                $content
            );
        } else {
            // External script: <script src="...">
            $before_attrs = $matches[1];
            $src = $matches[2];
            $after_attrs = $matches[3];
            $content = $matches[4];
            
            // Check if already blocked
            if (strpos($before_attrs . $after_attrs, 'data-tg-category') !== false) {
                return $matches[0];
            }
            
            return sprintf(
                '<script type="text/plain" data-tg-category="%s" data-tg-blocked="1" data-tg-src="%s"%s%s>%s</script>',
                esc_attr($category),
                esc_attr($src),
                $before_attrs,
                $after_attrs,
                $content
            );
        }
    }

    /**
     * Get user consent from cookie.
     *
     * @return array Consent preferences.
     */
    private function get_consent() {
        if (isset($_COOKIE['tg_gdpr_consent'])) {
            $consent_data = json_decode(stripslashes($_COOKIE['tg_gdpr_consent']), true);
            
            if (is_array($consent_data)) {
                return $consent_data;
            }
        }
        
        // Default: only necessary cookies allowed
        return array(
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        );
    }

    /**
     * Check if page caching is active.
     *
     * @return bool
     */
    public function is_page_cached() {
        return (
            defined('WP_CACHE') && WP_CACHE ||
            defined('WPFC_CACHE_PATH') || // WP Fastest Cache
            class_exists('W3_CacheAdmin') || // W3 Total Cache
            class_exists('WP_Optimize') ||
            class_exists('LiteSpeed_Cache') ||
            class_exists('WPO_Page_Cache') ||
            function_exists('rocket_clean_domain') || // WP Rocket
            function_exists('wp_cache_get') // Object cache
        );
    }

    /**
     * Get detected caching plugins.
     *
     * @return array
     */
    public function get_active_cache_plugins() {
        $cache_plugins = array();
        
        if (defined('WP_CACHE') && WP_CACHE) {
            $cache_plugins[] = 'WP Cache';
        }
        if (defined('WPFC_CACHE_PATH')) {
            $cache_plugins[] = 'WP Fastest Cache';
        }
        if (class_exists('W3_CacheAdmin')) {
            $cache_plugins[] = 'W3 Total Cache';
        }
        if (class_exists('WP_Optimize')) {
            $cache_plugins[] = 'WP-Optimize';
        }
        if (class_exists('LiteSpeed_Cache')) {
            $cache_plugins[] = 'LiteSpeed Cache';
        }
        if (function_exists('rocket_clean_domain')) {
            $cache_plugins[] = 'WP Rocket';
        }
        if (class_exists('autoptimizeCache')) {
            $cache_plugins[] = 'Autoptimize';
        }
        
        return $cache_plugins;
    }
}
