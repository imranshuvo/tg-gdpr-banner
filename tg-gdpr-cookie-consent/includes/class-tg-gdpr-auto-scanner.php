<?php
/**
 * Auto Cookie Scanner (Pro Feature)
 *
 * @package    TG_GDPR_Cookie_Consent
 * @subpackage TG_GDPR_Cookie_Consent/includes
 */

class TG_GDPR_Auto_Scanner {

    /**
     * License manager instance
     */
    private $license_manager;

    /**
     * Cookie manager instance.
     */
    private $cookie_manager;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->license_manager = new TG_GDPR_License_Manager();
        $this->cookie_manager = new TG_GDPR_Cookie_Manager();
    }

    /**
     * Check if auto scanner feature is available
     */
    public function is_available() {
        return $this->license_manager->has_feature('auto_scanner');
    }

    /**
     * Check if scheduled scanning is enabled in settings.
     *
     * @return bool
     */
    public function is_enabled() {
        $settings = get_option('tg_gdpr_settings', array());

        return !empty($settings['pro']['auto_scan_enabled']);
    }

    /**
     * Scan site for cookies
     */
    public function scan_site($force = false) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'message' => 'Auto Scanner is a Pro feature. Please activate your license to use this feature.',
            );
        }

        if (!$force && !$this->is_enabled()) {
            return array(
                'success' => false,
                'message' => 'Auto Scanner is disabled. Enable it in TG GDPR settings to run scheduled scans.',
            );
        }

        $scan_urls = $this->get_scan_urls();
        $scan_report = $this->detect_cookies($scan_urls);
        $cookies = $scan_report['cookies'];
        $saved_count = $this->persist_detected_cookies($cookies);

        $report = array(
            'scanned_at' => current_time('timestamp'),
            'scanned_urls' => $scan_urls,
            'cookies_found' => count($cookies),
            'cookies_saved' => $saved_count,
            'errors' => $scan_report['errors'],
        );

        update_option('tg_gdpr_last_cookie_scan', $report['scanned_at']);
        update_option('tg_gdpr_last_cookie_scan_report', $report, false);

        return array(
            'success' => true,
            'message' => count($cookies) > 0 ? 'Scan completed successfully.' : 'Scan completed, but no new cookies were detected.',
            'data' => array(
                'cookies_found' => count($cookies),
                'cookies_saved' => $saved_count,
                'cookies' => $cookies,
                'errors' => $scan_report['errors'],
                'scanned_urls' => $scan_urls,
            ),
        );
    }

    /**
     * Detect cookies on the site (Pro implementation)
     */
    private function detect_cookies($scan_urls) {
        $detected = array();
        $errors = array();

        foreach ($scan_urls as $scan_url) {
            $response = wp_remote_get($scan_url, array(
                'timeout' => 15,
                'redirection' => 3,
                'user-agent' => 'TG GDPR Cookie Scanner/' . TG_GDPR_VERSION,
            ));

            if (is_wp_error($response)) {
                $errors[] = sprintf('%s: %s', $scan_url, $response->get_error_message());
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                continue;
            }

            $domain = wp_parse_url($scan_url, PHP_URL_HOST);

            $this->detect_from_stored_patterns($body, $domain, $detected);
            $this->detect_from_known_trackers($body, $domain, $detected);
            $this->detect_from_inline_cookie_writes($body, $domain, $detected);
        }

        return array(
            'cookies' => array_values($detected),
            'errors' => $errors,
        );
    }

    /**
     * Get scanner status
     */
    public function get_status() {
        if (!$this->is_available()) {
            return 'Pro Feature - License Required';
        }

        $last_scan = get_option('tg_gdpr_last_cookie_scan', null);
        $report = get_option('tg_gdpr_last_cookie_scan_report', array());
        
        if ($last_scan) {
            $status = 'Last scanned: ' . human_time_diff($last_scan, current_time('timestamp')) . ' ago';

            if (!empty($report['cookies_found'])) {
                $status .= ' (' . intval($report['cookies_found']) . ' cookies detected)';
            }

            return $status;
        }

        return 'Never scanned';
    }

    /**
     * Schedule automatic scans
     */
    public function maybe_schedule_auto_scan() {
        if (!$this->is_available() || !$this->is_enabled()) {
            wp_clear_scheduled_hook('tg_gdpr_auto_cookie_scan');
            return false;
        }

        if (!wp_next_scheduled('tg_gdpr_auto_cookie_scan')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', 'tg_gdpr_auto_cookie_scan');
        }

        return true;
    }

    /**
     * Run automated scan (called by cron)
     */
    public function run_auto_scan() {
        if (!$this->is_available() || !$this->is_enabled()) {
            return;
        }

        $this->scan_site();
    }

    /**
     * Get the URLs that should be inspected during a scan.
     *
     * @return array
     */
    private function get_scan_urls() {
        $urls = array(home_url('/'));
        $privacy_policy_url = get_privacy_policy_url();

        if (!empty($privacy_policy_url) && $this->is_internal_url($privacy_policy_url)) {
            $urls[] = $privacy_policy_url;
        }

        $page_ids = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'fields' => 'ids',
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'post__not_in' => array_filter(array(
                (int) get_option('page_on_front'),
                (int) get_option('wp_page_for_privacy_policy'),
            )),
        ));

        foreach ($page_ids as $page_id) {
            $permalink = get_permalink($page_id);

            if (!empty($permalink) && $this->is_internal_url($permalink)) {
                $urls[] = $permalink;
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * Check whether a URL belongs to the current site.
     *
     * @param string $url URL to validate.
     * @return bool
     */
    private function is_internal_url($url) {
        $url_host = wp_parse_url($url, PHP_URL_HOST);
        $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

        return !empty($url_host) && !empty($site_host) && strtolower($url_host) === strtolower($site_host);
    }

    /**
     * Detect cookies from script patterns already stored in the cookie database.
     *
     * @param string $body Page body.
     * @param string $domain Page domain.
     * @param array  $detected Detected cookies.
     */
    private function detect_from_stored_patterns($body, $domain, array &$detected) {
        $cookies = $this->cookie_manager->get_cookies();

        foreach ($cookies as $cookie) {
            if (empty($cookie->script_pattern)) {
                continue;
            }

            $pattern = '#(?:' . $cookie->script_pattern . ')#i';

            if (@preg_match($pattern, $body)) {
                $this->store_cookie($detected, array(
                    'name' => $cookie->cookie_name,
                    'category' => $cookie->category,
                    'duration' => $cookie->duration,
                    'description' => $cookie->description,
                    'domain' => !empty($cookie->domain) ? $cookie->domain : $domain,
                    'script_pattern' => $cookie->script_pattern,
                ));
            }
        }
    }

    /**
     * Detect common tracker cookies from known script signatures.
     *
     * @param string $body Page body.
     * @param string $domain Page domain.
     * @param array  $detected Detected cookies.
     */
    private function detect_from_known_trackers($body, $domain, array &$detected) {
        $detectors = array(
            array(
                'pattern' => '#google-analytics\.com|googletagmanager\.com/gtag/js|gtag\(#i',
                'cookies' => array(
                    array(
                        'name' => '_ga',
                        'category' => 'analytics',
                        'duration' => '2 years',
                        'description' => 'Google Analytics cookie used to distinguish users.',
                        'script_pattern' => 'google-analytics.com|googletagmanager.com/gtag/js|gtag(',
                    ),
                    array(
                        'name' => '_gid',
                        'category' => 'analytics',
                        'duration' => '24 hours',
                        'description' => 'Google Analytics cookie used to distinguish users during a browsing session.',
                        'script_pattern' => 'google-analytics.com|googletagmanager.com/gtag/js|gtag(',
                    ),
                ),
            ),
            array(
                'pattern' => '#connect\.facebook\.net/.*/fbevents\.js|fbq\(#i',
                'cookies' => array(
                    array(
                        'name' => '_fbp',
                        'category' => 'marketing',
                        'duration' => '3 months',
                        'description' => 'Facebook Pixel cookie used to track marketing conversions.',
                        'script_pattern' => 'connect.facebook.net|fbq(',
                    ),
                ),
            ),
            array(
                'pattern' => '#clarity\.ms|window\.clarity#i',
                'cookies' => array(
                    array(
                        'name' => '_clck',
                        'category' => 'analytics',
                        'duration' => '1 year',
                        'description' => 'Microsoft Clarity cookie that stores a browser identifier.',
                        'script_pattern' => 'clarity.ms|window.clarity',
                    ),
                    array(
                        'name' => '_clsk',
                        'category' => 'analytics',
                        'duration' => '24 hours',
                        'description' => 'Microsoft Clarity cookie that stores and combines pageviews into a single session.',
                        'script_pattern' => 'clarity.ms|window.clarity',
                    ),
                ),
            ),
            array(
                'pattern' => '#static\.hotjar\.com|hj\(|hotjar#i',
                'cookies' => array(
                    array(
                        'name' => '_hjSessionUser_',
                        'category' => 'analytics',
                        'duration' => '1 year',
                        'description' => 'Hotjar cookie that persists a unique user identifier.',
                        'script_pattern' => 'static.hotjar.com|hj(|hotjar',
                    ),
                    array(
                        'name' => '_hjSession_',
                        'category' => 'analytics',
                        'duration' => '30 minutes',
                        'description' => 'Hotjar cookie that holds current session data.',
                        'script_pattern' => 'static.hotjar.com|hj(|hotjar',
                    ),
                ),
            ),
            array(
                'pattern' => '#js\.hs-scripts\.com|hs-script-loader|hubspot#i',
                'cookies' => array(
                    array(
                        'name' => 'hubspotutk',
                        'category' => 'analytics',
                        'duration' => '6 months',
                        'description' => 'HubSpot cookie used to track a visitor identity.',
                        'script_pattern' => 'js.hs-scripts.com|hubspot',
                    ),
                    array(
                        'name' => '__hstc',
                        'category' => 'analytics',
                        'duration' => '6 months',
                        'description' => 'HubSpot cookie used for analytics tracking and session timestamps.',
                        'script_pattern' => 'js.hs-scripts.com|hubspot',
                    ),
                ),
            ),
            array(
                'pattern' => '#snap\.licdn\.com|linkedin#i',
                'cookies' => array(
                    array(
                        'name' => 'li_gc',
                        'category' => 'marketing',
                        'duration' => '6 months',
                        'description' => 'LinkedIn cookie used to store consent and advertising preferences.',
                        'script_pattern' => 'snap.licdn.com|linkedin',
                    ),
                    array(
                        'name' => 'li_fat_id',
                        'category' => 'marketing',
                        'duration' => '30 days',
                        'description' => 'LinkedIn cookie used for conversion attribution.',
                        'script_pattern' => 'snap.licdn.com|linkedin',
                    ),
                ),
            ),
            array(
                'pattern' => '#analytics\.tiktok\.com|ttq\.#i',
                'cookies' => array(
                    array(
                        'name' => '_ttp',
                        'category' => 'marketing',
                        'duration' => '13 months',
                        'description' => 'TikTok cookie used for performance and marketing attribution.',
                        'script_pattern' => 'analytics.tiktok.com|ttq.',
                    ),
                ),
            ),
        );

        foreach ($detectors as $detector) {
            if (!preg_match($detector['pattern'], $body)) {
                continue;
            }

            foreach ($detector['cookies'] as $cookie) {
                $cookie['domain'] = $domain;
                $this->store_cookie($detected, $cookie);
            }
        }
    }

    /**
     * Detect cookies explicitly written from inline scripts.
     *
     * @param string $body Page body.
     * @param string $domain Page domain.
     * @param array  $detected Detected cookies.
     */
    private function detect_from_inline_cookie_writes($body, $domain, array &$detected) {
        $patterns = array(
            '#document\.cookie\s*=\s*["\']([^=;"\']+)#i',
            '#Cookies\.set\s*\(\s*["\']([^"\']+)["\']#i',
            '#setCookie\s*\(\s*["\']([^"\']+)["\']#i',
        );

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $body, $matches)) {
                continue;
            }

            foreach ($matches[1] as $cookie_name) {
                $cookie_name = sanitize_text_field($cookie_name);
                if (empty($cookie_name)) {
                    continue;
                }

                $this->store_cookie($detected, $this->guess_cookie_metadata($cookie_name, $domain));
            }
        }
    }

    /**
     * Guess metadata for a cookie when only the name is available.
     *
     * @param string $cookie_name Cookie name.
     * @param string $domain Cookie domain.
     * @return array
     */
    private function guess_cookie_metadata($cookie_name, $domain) {
        $normalized_name = strtolower($cookie_name);
        $cookie = array(
            'name' => $cookie_name,
            'category' => 'functional',
            'duration' => 'Unknown',
            'description' => 'Automatically detected cookie. Review and confirm its purpose before publishing.',
            'domain' => $domain,
            'script_pattern' => 'document.cookie|Cookies.set|setCookie',
        );

        if (strpos($normalized_name, 'wordpress_') === 0 || strpos($normalized_name, 'wp-') === 0 || strpos($normalized_name, 'comment_author') === 0 || strpos($normalized_name, 'woocommerce_') === 0 || strpos($normalized_name, 'wc_') === 0) {
            $cookie['category'] = 'necessary';
            $cookie['description'] = 'Automatically detected platform cookie used by WordPress or WooCommerce.';
            return $cookie;
        }

        if (strpos($normalized_name, 'consent') !== false || strpos($normalized_name, 'privacy') !== false || strpos($normalized_name, 'cookie') !== false) {
            $cookie['category'] = 'necessary';
            $cookie['description'] = 'Automatically detected cookie likely used to store consent or privacy preferences.';
            return $cookie;
        }

        if (preg_match('/^(_ga|_gid|_gat|_gcl)/', $normalized_name) || strpos($normalized_name, 'analytics') !== false || strpos($normalized_name, 'clarity') !== false || strpos($normalized_name, 'hotjar') !== false) {
            $cookie['category'] = 'analytics';
            $cookie['description'] = 'Automatically detected analytics cookie. Review its provider and retention period.';
            return $cookie;
        }

        if (preg_match('/^(_fbp|_ttp|li_|tt_|pin_|sc_)/', $normalized_name) || strpos($normalized_name, 'ad') !== false || strpos($normalized_name, 'pixel') !== false || strpos($normalized_name, 'marketing') !== false) {
            $cookie['category'] = 'marketing';
            $cookie['description'] = 'Automatically detected marketing cookie. Review its provider and advertising use.';
        }

        return $cookie;
    }

    /**
     * Store a detected cookie in the working result set.
     *
     * @param array $detected Detected cookies keyed by name.
     * @param array $cookie Cookie metadata.
     */
    private function store_cookie(array &$detected, array $cookie) {
        if (empty($cookie['name'])) {
            return;
        }

        $key = strtolower($cookie['name']);
        $normalized = array(
            'name' => sanitize_text_field($cookie['name']),
            'category' => !empty($cookie['category']) ? $cookie['category'] : 'functional',
            'duration' => !empty($cookie['duration']) ? $cookie['duration'] : 'Unknown',
            'description' => !empty($cookie['description']) ? $cookie['description'] : 'Automatically detected cookie. Review and confirm its purpose before publishing.',
            'domain' => !empty($cookie['domain']) ? $cookie['domain'] : wp_parse_url(home_url('/'), PHP_URL_HOST),
            'script_pattern' => !empty($cookie['script_pattern']) ? $cookie['script_pattern'] : '',
        );

        if (!isset($detected[$key])) {
            $detected[$key] = $normalized;
            return;
        }

        if (empty($detected[$key]['script_pattern'])) {
            $detected[$key]['script_pattern'] = $normalized['script_pattern'];
        } elseif (!empty($normalized['script_pattern']) && strpos($detected[$key]['script_pattern'], $normalized['script_pattern']) === false) {
            $detected[$key]['script_pattern'] .= '|' . $normalized['script_pattern'];
        }
    }

    /**
     * Persist detected cookies to the local cookie database.
     *
     * @param array $cookies Detected cookies.
     * @return int
     */
    private function persist_detected_cookies($cookies) {
        $saved_count = 0;

        foreach ($cookies as $cookie) {
            $saved = $this->cookie_manager->save_detected_cookie(array(
                'cookie_name' => $cookie['name'],
                'category' => $cookie['category'],
                'description' => $cookie['description'],
                'duration' => $cookie['duration'],
                'domain' => $cookie['domain'],
                'script_pattern' => $cookie['script_pattern'],
                'is_active' => 1,
            ));

            if ($saved !== false) {
                $saved_count++;
            }
        }

        return $saved_count;
    }
}
