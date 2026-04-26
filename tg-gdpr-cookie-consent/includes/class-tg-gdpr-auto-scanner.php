<?php
/**
 * Auto Cookie Scanner (Pro Feature)
 *
 * @package    TG_GDPR_Cookie_Consent
 * @subpackage TG_GDPR_Cookie_Consent/includes
 */

class TG_GDPR_Auto_Scanner {

    /**
     * Maximum number of URLs to inspect during a single scan.
     */
    private $max_scan_urls = 100;

    /**
     * Maximum number of response bytes to inspect per page.
     */
    private $max_response_bytes = 262144;

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
     * Polite-mode option keys + cron hook.
     *
     * The scanner is async: kickoff enqueues URLs, a wp-cron tick processes
     * one URL at a time with a configurable interval (default 60s) + jitter so
     * the customer's site (and its CDN/origin) never sees a burst.
     */
    const STATE_OPTION = 'tg_gdpr_scan_state';
    const TICK_HOOK    = 'tg_gdpr_scan_tick';

    /**
     * Kick off a scan. Returns immediately; the actual work happens via
     * wp-cron ticks (one URL per interval). Use get_scan_progress() to poll.
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
                'message' => 'Auto Scanner is disabled. Enable it in Cookiely settings to run scheduled scans.',
            );
        }

        if ($this->is_scan_in_progress()) {
            return array(
                'success' => false,
                'message' => 'A scan is already in progress. Cancel it before starting a new one.',
                'data'    => $this->get_scan_progress(),
            );
        }

        $scan_urls = $this->get_scan_urls();
        if (empty($scan_urls)) {
            return array(
                'success' => false,
                'message' => 'No URLs available to scan.',
            );
        }

        // Fetch robots.txt once at the start; cache for the lifetime of this scan.
        $robots_rules = $this->fetch_robots_disallow_rules();

        update_option(self::STATE_OPTION, array(
            'status'       => 'running',
            'queue'        => array_values($scan_urls),
            'total_urls'   => count($scan_urls),
            'started_at'   => current_time('timestamp'),
            'detected'     => array(),
            'errors'       => array(),
            'robots_rules' => $robots_rules,
        ), false);

        // First tick a few seconds out so the admin redirect completes cleanly.
        wp_schedule_single_event(time() + 5, self::TICK_HOOK);

        $eta_minutes = (int) ceil(count($scan_urls) * $this->get_scan_interval() / 60);

        return array(
            'success' => true,
            'message' => sprintf(
                'Scan started: %d page(s) queued. Pages will be fetched every %ds (~%d min total).',
                count($scan_urls),
                $this->get_scan_interval(),
                $eta_minutes
            ),
            'data' => $this->get_scan_progress(),
        );
    }

    /**
     * Process exactly ONE URL from the queue, then schedule the next tick.
     * Hooked to the `tg_gdpr_scan_tick` cron action.
     */
    public function process_scan_tick() {
        $state = get_option(self::STATE_OPTION, null);
        if (!is_array($state) || ($state['status'] ?? '') !== 'running') {
            return;
        }

        if (empty($state['queue'])) {
            $this->complete_scan($state);
            return;
        }

        $url = array_shift($state['queue']);

        $detected = $state['detected'];
        $errors   = $state['errors'];
        $this->fetch_one_url($url, $detected, $errors, $state['robots_rules']);
        $state['detected'] = $detected;
        $state['errors']   = $errors;

        update_option(self::STATE_OPTION, $state, false);

        if (empty($state['queue'])) {
            $this->complete_scan($state);
            return;
        }

        // Stagger: interval + ±jitter so two concurrent scans on different sites
        // don't lock-step and look like a coordinated crawl.
        $interval = $this->get_scan_interval();
        $jitter   = $this->get_scan_jitter();
        $next_in  = $interval + ($jitter > 0 ? random_int(-$jitter, $jitter) : 0);
        wp_schedule_single_event(time() + max(5, $next_in), self::TICK_HOOK);
    }

    /**
     * Fetch a single URL, detect cookies, and accumulate into the running state.
     */
    private function fetch_one_url($scan_url, array &$detected, array &$errors, array $robots_rules) {
        if ($this->is_disallowed_by_robots($scan_url, $robots_rules)) {
            $errors[] = sprintf('%s: skipped (robots.txt Disallow)', $scan_url);
            return;
        }

        $response = wp_remote_get($scan_url, array(
            'timeout'             => 8,
            'redirection'         => 2,
            'user-agent'          => 'TG GDPR Cookie Scanner/' . TG_GDPR_VERSION . ' (+https://cookiely.site/scanner)',
            'limit_response_size' => $this->max_response_bytes,
            'reject_unsafe_urls'  => true,
        ));

        if (is_wp_error($response)) {
            $errors[] = sprintf('%s: %s', $scan_url, $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return;
        }

        $domain = wp_parse_url($scan_url, PHP_URL_HOST);
        $this->detect_from_stored_patterns($body, $domain, $detected);
        $this->detect_from_known_trackers($body, $domain, $detected);
        $this->detect_from_inline_cookie_writes($body, $domain, $detected);
    }

    /**
     * Final pass after the queue empties: persist detected cookies, write the
     * report, fire the `tg_gdpr_scan_completed` action so other components
     * (auto-categorize, auto-block-rule generation) can hook in.
     */
    private function complete_scan(array $state) {
        $cookies     = array_values($state['detected']);
        $saved_count = $this->persist_detected_cookies($cookies);
        $duration    = current_time('timestamp') - (int) $state['started_at'];

        $report = array(
            'scanned_at'       => current_time('timestamp'),
            'urls_scanned'     => (int) $state['total_urls'],
            'cookies_found'    => count($cookies),
            'cookies_saved'    => $saved_count,
            'errors'           => $state['errors'],
            'duration_seconds' => $duration,
            // Detected cookies (with name/category/script_pattern) are needed
            // by the runtime auto-blocker so customer-specific trackers get
            // blocked even when they aren't on our universal pattern list.
            'cookies'          => $cookies,
        );

        update_option('tg_gdpr_last_cookie_scan', $report['scanned_at']);
        update_option('tg_gdpr_last_cookie_scan_report', $report, false);

        delete_option(self::STATE_OPTION);
        wp_clear_scheduled_hook(self::TICK_HOOK);

        /**
         * Fired when a scan finishes. Listeners receive the array of detected
         * cookies and can react (e.g. categorize them against CookieDefinitions,
         * auto-generate block rules).
         */
        do_action('tg_gdpr_scan_completed', $cookies, $report);
    }

    public function is_scan_in_progress() {
        $state = get_option(self::STATE_OPTION, null);
        return is_array($state) && ($state['status'] ?? '') === 'running';
    }

    public function get_scan_progress() {
        $state = get_option(self::STATE_OPTION, null);
        if (!is_array($state) || ($state['status'] ?? '') !== 'running') {
            return null;
        }
        $total     = (int) $state['total_urls'];
        $remaining = count($state['queue']);
        return array(
            'total'           => $total,
            'remaining'       => $remaining,
            'scanned'         => $total - $remaining,
            'started_at'      => (int) $state['started_at'],
            'detected_so_far' => count($state['detected']),
            'eta_seconds'     => $remaining * $this->get_scan_interval(),
        );
    }

    public function cancel_scan() {
        if (!$this->is_scan_in_progress()) {
            return false;
        }
        delete_option(self::STATE_OPTION);
        wp_clear_scheduled_hook(self::TICK_HOOK);
        return true;
    }

    private function get_scan_interval() {
        $settings = get_option('tg_gdpr_settings', array());
        $secs = isset($settings['scanner']['interval_seconds'])
            ? (int) $settings['scanner']['interval_seconds']
            : 60;
        // Floor at 5s — anything smaller defeats the polite-mode purpose.
        return max(5, $secs);
    }

    private function get_scan_jitter() {
        $settings = get_option('tg_gdpr_settings', array());
        $secs = isset($settings['scanner']['jitter_seconds'])
            ? (int) $settings['scanner']['jitter_seconds']
            : 15;
        return max(0, $secs);
    }

    /**
     * Fetch and parse the site's /robots.txt into a list of Disallow path
     * prefixes that apply to our crawler ("*" or our identifying UA).
     *
     * Result is small (paths only), no UA caching across scans — robots.txt
     * is fetched once per scan run, never per URL.
     *
     * @return string[]
     */
    private function fetch_robots_disallow_rules() {
        $response = wp_remote_get(home_url('/robots.txt'), array(
            'timeout' => 5,
            'redirection' => 2,
            'limit_response_size' => 32768, // 32 KB cap on robots.txt
            'reject_unsafe_urls' => true,
        ));

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array();
        }

        $rules    = array();
        $applies  = false;
        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $m)) {
                $ua      = strtolower(trim($m[1]));
                $applies = ($ua === '*'
                    || strpos($ua, 'tg gdpr') !== false
                    || strpos($ua, 'cookiely') !== false);
                continue;
            }

            if ($applies && preg_match('/^Disallow:\s*(.*)$/i', $line, $m)) {
                $rule = trim($m[1]);
                if ($rule !== '') {
                    $rules[] = $rule;
                }
            }
        }

        return $rules;
    }

    /**
     * Path-prefix match against the parsed robots.txt rules.
     */
    private function is_disallowed_by_robots($url, array $rules) {
        if (empty($rules)) {
            return false;
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        foreach ($rules as $rule) {
            // Prefix match (RFC-style); does not implement wildcard `*` — sufficient for MVP.
            if (strpos($path, $rule) === 0) {
                return true;
            }
        }

        return false;
    }

    // detect_cookies($scan_urls) was the old synchronous batch method.
    // Replaced by polite-mode: scan_site() → process_scan_tick() → fetch_one_url().

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

        $post_types = array_diff(
            get_post_types(array('public' => true), 'names'),
            array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation')
        );

        if (empty($post_types)) {
            return array_values(array_slice(array_unique(array_filter($urls)), 0, $this->max_scan_urls));
        }

        $content_ids = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $this->max_scan_urls * 2,
            'fields' => 'ids',
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
            'suppress_filters' => true,
            'post__not_in' => array_filter(array(
                (int) get_option('page_on_front'),
                (int) get_option('wp_page_for_privacy_policy'),
            )),
        ));

        foreach ($content_ids as $content_id) {
            if (count($urls) >= $this->max_scan_urls) {
                break;
            }

            $permalink = get_permalink($content_id);

            if (!empty($permalink) && $this->is_internal_url($permalink)) {
                $urls[] = $permalink;
            }
        }

        return array_values(array_slice(array_unique(array_filter($urls)), 0, $this->max_scan_urls));
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

            $pattern = $this->build_script_pattern_regex($cookie->script_pattern);

            if (empty($pattern)) {
                continue;
            }

            if (preg_match($pattern, $body) === 1) {
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
     * Build a safe regex from stored script fragments.
     *
     * @param string $script_pattern Stored pipe-delimited fragments.
     * @return string|null
     */
    private function build_script_pattern_regex($script_pattern) {
        $fragments = array_filter(array_map('trim', explode('|', (string) $script_pattern)));

        if (empty($fragments)) {
            return null;
        }

        $escaped_fragments = array_map(function ($fragment) {
            return preg_quote($fragment, '#');
        }, $fragments);

        return '#(?:' . implode('|', $escaped_fragments) . ')#i';
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
