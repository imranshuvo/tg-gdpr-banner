<?php
/**
 * SaaS API Sync Handler
 *
 * Handles communication between WordPress plugin and Laravel SaaS API.
 * Implements batch sync, retry logic, and local caching for performance.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_API_Sync {

    /**
     * Visitor hash cookie name.
     *
     * @var string
     */
    private $visitor_hash_cookie = 'tg_gdpr_visitor_hash';

    /**
     * Session cookie name.
     *
     * @var string
     */
    private $session_cookie = 'tg_gdpr_session';

    /**
     * Pending session stats option key.
     *
     * @var string
     */
    private $pending_session_stats_option = 'tg_gdpr_pending_session_stats';

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url;

    /**
     * Site token for authentication.
     *
     * @var string
     */
    private $site_token;

    /**
     * Singleton instance.
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $settings = get_option('tg_gdpr_settings', array());
        $license = isset($settings['license']) ? $settings['license'] : array();
        
        $this->api_url = isset($license['api_url']) ? trailingslashit($license['api_url']) : '';
        $this->site_token = isset($license['site_token']) ? $license['site_token'] : '';
        
        add_filter('cron_schedules', array($this, 'register_cron_schedules'));

        // Register cron hooks
        add_action('tg_gdpr_sync_consents', array($this, 'sync_pending_consents'));
        add_action('tg_gdpr_sync_sessions', array($this, 'sync_sessions'));
        add_action('wp_ajax_tg_gdpr_track_analytics_event', array($this, 'ajax_track_analytics_event'));
        add_action('wp_ajax_nopriv_tg_gdpr_track_analytics_event', array($this, 'ajax_track_analytics_event'));
        
        // Schedule cron jobs
        $this->ensure_scheduled_event('tg_gdpr_sync_consents', 'five_minutes');
        $this->ensure_scheduled_event('tg_gdpr_sync_sessions', 'five_minutes');
    }

    /**
     * Register custom cron schedules.
     *
     * @param array $schedules Existing schedules.
     * @return array
     */
    public function register_cron_schedules($schedules) {
        if (!isset($schedules['five_minutes'])) {
            $schedules['five_minutes'] = array(
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display' => __('Every Five Minutes', 'tg-gdpr-cookie-consent'),
            );
        }

        return $schedules;
    }

    /**
     * Check if API is configured.
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->api_url) && !empty($this->site_token);
    }

    /**
     * Queue consent record for sync.
     *
     * @param array $consent Consent data.
     * @return void
     */
    public function queue_consent($consent) {
        $pending = get_option('tg_gdpr_pending_consents', array());
        $consent_categories = array(
            'necessary' => !empty($consent['necessary']),
            'functional' => !empty($consent['functional']),
            'analytics' => !empty($consent['analytics']),
            'marketing' => !empty($consent['marketing']),
        );
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        $pending[] = array(
            'consent_id' => wp_generate_uuid4(),
            'visitor_hash' => $this->generate_visitor_hash(),
            'ip_anonymized' => $this->get_anonymized_ip(),
            'consent_categories' => $consent_categories,
            'consent_method' => $this->normalize_consent_method($consent, $consent_categories),
            'policy_version' => $this->normalize_policy_version(isset($consent['version']) ? $consent['version'] : null),
            'user_agent_hash' => !empty($user_agent) ? hash('sha256', $user_agent) : null,
            'device_type' => $this->detect_device_type($user_agent),
            'browser' => $this->detect_browser($user_agent),
            'created_at' => gmdate('c'),
        );
        
        update_option('tg_gdpr_pending_consents', $pending);
        
        // If we have many pending, sync immediately
        if (count($pending) >= 50) {
            $this->sync_pending_consents();
        }
    }

    /**
     * Sync pending consents to API (batch).
     *
     * @return bool
     */
    public function sync_pending_consents() {
        if (!$this->is_configured()) {
            return false;
        }

        $pending = get_option('tg_gdpr_pending_consents', array());
        
        if (empty($pending)) {
            return true;
        }

        $response = $this->api_request('POST', 'api/v1/consents/sync', array(
            'site_token' => $this->site_token,
            'consents' => $pending,
        ));

        if ($response['success']) {
            // Clear synced consents
            delete_option('tg_gdpr_pending_consents');
            return true;
        } else {
            // Keep pending for retry
            error_log('[TG GDPR] Failed to sync consents: ' . $response['error']);
            return false;
        }
    }

    /**
     * Record a session.
     *
     * @return void
     */
    public function record_session() {
        if (!$this->is_configured() || is_admin() || wp_doing_ajax()) {
            return;
        }

        if ((defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return;
        }

        if (function_exists('is_feed') && is_feed()) {
            return;
        }

        $session_state = $this->get_session_state();
        $this->refresh_session_cookie($session_state['id']);

        if (!$session_state['is_new']) {
            return;
        }

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $device_type = $this->detect_device_type($user_agent);

        $this->mutate_daily_session_stats($this->get_stats_date(), function($stats) use ($device_type) {
            $stats['total_sessions']++;
            $stats['device_breakdown'] = $this->increment_breakdown_count($stats['device_breakdown'], $device_type);

            return $stats;
        });
    }

    /**
     * Record a banner impression for the current day.
     *
     * @return void
     */
    public function record_banner_impression() {
        if (!$this->is_configured()) {
            return;
        }

        $this->mutate_daily_session_stats($this->get_stats_date(), function($stats) {
            $stats['banner_shown']++;

            return $stats;
        });
    }

    /**
     * Record a consent interaction for the current day.
     *
     * @param array $consent Consent data.
     * @return void
     */
    public function record_consent_interaction($consent) {
        if (!$this->is_configured() || !is_array($consent)) {
            return;
        }

        $consent_categories = array(
            'necessary' => !empty($consent['necessary']),
            'functional' => !empty($consent['functional']),
            'analytics' => !empty($consent['analytics']),
            'marketing' => !empty($consent['marketing']),
        );
        $consent_method = $this->normalize_consent_method($consent, $consent_categories);

        $this->mutate_daily_session_stats($this->get_stats_date(), function($stats) use ($consent_categories, $consent_method) {
            switch ($consent_method) {
                case 'accept_all':
                    $stats['consent_given']++;
                    $stats['accepted_functional']++;
                    $stats['accepted_analytics']++;
                    $stats['accepted_marketing']++;
                    break;

                case 'reject_all':
                    $stats['consent_denied']++;
                    break;

                default:
                    $stats['consent_customized']++;

                    if (!empty($consent_categories['functional'])) {
                        $stats['accepted_functional']++;
                    }

                    if (!empty($consent_categories['analytics'])) {
                        $stats['accepted_analytics']++;
                    }

                    if (!empty($consent_categories['marketing'])) {
                        $stats['accepted_marketing']++;
                    }

                    break;
            }

            return $stats;
        });
    }

    /**
     * Sync sessions to API.
     *
     * @return bool
     */
    public function sync_sessions() {
        if (!$this->is_configured()) {
            return false;
        }

        $sessions = $this->get_pending_session_stats();
        
        if (empty($sessions)) {
            return true;
        }

        ksort($sessions);

        $response = $this->api_request('POST', 'api/v1/sessions/sync', array(
            'site_token' => $this->site_token,
            'sessions' => array_values($sessions),
        ));

        if ($response['success']) {
            delete_option($this->pending_session_stats_option);
            return true;
        }

        return false;
    }

    /**
     * AJAX endpoint for lightweight analytics tracking.
     *
     * @return void
     */
    public function ajax_track_analytics_event() {
        if (!check_ajax_referer('tg_gdpr_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            return;
        }

        $event_name = isset($_POST['event_name']) ? sanitize_key(wp_unslash($_POST['event_name'])) : '';

        switch ($event_name) {
            case 'banner_shown':
                $this->record_banner_impression();
                wp_send_json_success();
                return;

            case 'consent_saved':
                $consent = isset($_POST['consent']) ? json_decode(wp_unslash($_POST['consent']), true) : array();

                if (!is_array($consent)) {
                    wp_send_json_error(array('message' => 'Invalid consent payload'), 400);
                    return;
                }

                $this->record_consent_interaction($consent);
                wp_send_json_success();
                return;
        }

        wp_send_json_error(array('message' => 'Unsupported analytics event'), 400);
    }

    /**
     * Fetch site settings from API.
     *
     * @param bool $force Force refresh (bypass cache).
     * @return array|null
     */
    public function fetch_settings($force = false) {
        if (!$this->is_configured()) {
            return null;
        }

        $cache_key = 'tg_gdpr_saas_settings';
        
        if (!$force) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $response = $this->api_request('GET', 'api/v1/site/settings', array(
            'site_token' => $this->site_token,
        ));

        if ($response['success']) {
            $settings = array();

            if (isset($response['data']['data']) && is_array($response['data']['data'])) {
                $settings = $response['data']['data'];
            } elseif (isset($response['data']['settings']) && is_array($response['data']['settings'])) {
                $settings = $response['data']['settings'];
            }

            if (!empty($settings)) {
            set_transient($cache_key, $settings, 5 * MINUTE_IN_SECONDS);
            return $settings;
            }
        }

        return null;
    }

    /**
     * Fetch cookies from API database.
     *
     * @return array
     */
    public function fetch_cookies() {
        if (!$this->is_configured()) {
            return array();
        }

        $cache_key = 'tg_gdpr_site_cookies';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->api_request('GET', 'api/v1/cookies/site', array(
            'site_token' => $this->site_token,
        ));

        if ($response['success'] && isset($response['data']['cookies'])) {
            $cookies = $response['data']['cookies'];
            set_transient($cache_key, $cookies, HOUR_IN_SECONDS);
            return $cookies;
        }

        return array();
    }

    /**
     * Submit scanned cookies to API.
     *
     * @param array $cookies Discovered cookies.
     * @return bool
     */
    public function submit_scanned_cookies($cookies) {
        if (!$this->is_configured()) {
            return false;
        }

        $response = $this->api_request('POST', 'api/v1/cookies/scan', array(
            'site_token' => $this->site_token,
            'cookies' => $cookies,
        ));

        return $response['success'];
    }

    /**
     * Submit DSAR request.
     *
     * @param array $data DSAR data.
     * @return array Result with request_id.
     */
    public function submit_dsar($data) {
        if (!$this->is_configured()) {
            return array('success' => false, 'error' => 'API not configured');
        }

        $requester_name = '';

        if (!empty($data['name'])) {
            $requester_name = sanitize_text_field($data['name']);
        } else {
            $requester_name = trim(implode(' ', array_filter(array(
                isset($data['first_name']) ? sanitize_text_field($data['first_name']) : '',
                isset($data['last_name']) ? sanitize_text_field($data['last_name']) : '',
            ))));
        }

        $response = $this->api_request('POST', 'api/v1/dsar/submit', array(
            'site_token' => $this->site_token,
            'request_type' => isset($data['type']) ? sanitize_text_field($data['type']) : '',
            'requester_email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'requester_name' => $requester_name,
            'requester_phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'additional_info' => isset($data['message']) ? sanitize_textarea_field($data['message']) : '',
            'visitor_hash' => $this->normalize_visitor_hash(isset($data['visitor_hash']) ? $data['visitor_hash'] : ''),
        ));

        return $response;
    }

    /**
     * Get usage statistics.
     *
     * @return array
     */
    public function get_usage() {
        if (!$this->is_configured()) {
            return array();
        }

        $response = $this->api_request('GET', 'api/v1/site/usage', array(
            'site_token' => $this->site_token,
        ));

        if ($response['success']) {
            return $response['data'];
        }

        return array();
    }

    /**
     * Make API request.
     *
     * @param string $method HTTP method.
     * @param string $endpoint API endpoint.
     * @param array  $data Request data.
     * @return array Response array.
     */
    private function api_request($method, $endpoint, $data = array()) {
        $url = $this->api_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Site-Token' => $this->site_token,
            ),
        );

        if ($method === 'GET') {
            $url = add_query_arg($data, $url);
        } else {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'data' => $body,
            );
        }

        return array(
            'success' => false,
            'error' => isset($body['message']) ? $body['message'] : 'API error',
            'status' => $status_code,
        );
    }

    /**
     * Generate visitor hash for fingerprinting.
     *
     * @return string
     */
    private function generate_visitor_hash() {
        if (!empty($_COOKIE[$this->visitor_hash_cookie]) && preg_match('/^[a-f0-9]{64}$/i', (string) $_COOKIE[$this->visitor_hash_cookie])) {
            return strtolower(sanitize_text_field(wp_unslash($_COOKIE[$this->visitor_hash_cookie])));
        }

        $components = array(
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '',
            $this->get_anonymized_ip(),
        );
        
        return hash('sha256', implode('|', $components));
    }

    /**
     * Normalize an incoming visitor hash to the canonical stored format.
     *
     * @param string $provided_hash Incoming visitor hash.
     * @return string
     */
    private function normalize_visitor_hash($provided_hash) {
        $provided_hash = sanitize_text_field((string) $provided_hash);

        if (!empty($provided_hash) && preg_match('/^[a-f0-9]{64}$/i', $provided_hash)) {
            return strtolower($provided_hash);
        }

        return $this->generate_visitor_hash();
    }

    /**
     * Normalize consent method to the API contract.
     *
     * @param array $consent Raw consent data.
     * @param array $categories Normalized categories.
     * @return string
     */
    private function normalize_consent_method($consent, $categories) {
        if (!empty($consent['interaction'])) {
            $interaction = sanitize_key($consent['interaction']);

            if ($interaction === 'custom') {
                return 'customize';
            }

            if (in_array($interaction, array('accept_all', 'reject_all', 'customize', 'implicit'), true)) {
                return $interaction;
            }
        }

        if (!empty($categories['functional']) && !empty($categories['analytics']) && !empty($categories['marketing'])) {
            return 'accept_all';
        }

        if (empty($categories['functional']) && empty($categories['analytics']) && empty($categories['marketing'])) {
            return 'reject_all';
        }

        return 'customize';
    }

    /**
     * Normalize policy version to the integer API schema.
     *
     * @param mixed $version Policy version.
     * @return int
     */
    private function normalize_policy_version($version) {
        $normalized = absint($version);

        return $normalized > 0 ? $normalized : 1;
    }

    /**
     * Detect device type from user agent.
     *
     * @param string $user_agent User agent string.
     * @return string
     */
    private function detect_device_type($user_agent) {
        $user_agent = strtolower((string) $user_agent);

        if (preg_match('/tablet|ipad|kindle|playbook/i', $user_agent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $user_agent)) {
            return 'mobile';
        }

        if (preg_match('/mozilla|chrome|safari|firefox|edge|opera/i', $user_agent)) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Detect browser from user agent.
     *
     * @param string $user_agent User agent string.
     * @return string|null
     */
    private function detect_browser($user_agent) {
        if (preg_match('/edg/i', $user_agent)) {
            return 'Edge';
        }

        if (preg_match('/chrome/i', $user_agent)) {
            return 'Chrome';
        }

        if (preg_match('/safari/i', $user_agent)) {
            return 'Safari';
        }

        if (preg_match('/firefox/i', $user_agent)) {
            return 'Firefox';
        }

        if (preg_match('/opera|opr/i', $user_agent)) {
            return 'Opera';
        }

        if (preg_match('/msie|trident/i', $user_agent)) {
            return 'IE';
        }

        return null;
    }

    /**
     * Get anonymized IP address (GDPR compliant).
     *
     * @return string
     */
    private function get_anonymized_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        $ip = filter_var(trim($ip), FILTER_VALIDATE_IP);
        
        if (!$ip) {
            return '0.0.0.0';
        }
        
        // Anonymize: zero out last octet for IPv4, last 80 bits for IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, strrpos($ip, ':')) . ':0000:0000:0000:0000:0000';
        }
        
        return '0.0.0.0';
    }

    /**
     * Get or create session ID.
     *
     * @return string
     */
    private function get_session_id() {
        $session_state = $this->get_session_state();

        return $session_state['id'];
    }

    /**
     * Get current session state.
     *
     * @return array{id:string,is_new:bool}
     */
    private function get_session_state() {
        $raw_cookie = isset($_COOKIE[$this->session_cookie]) ? sanitize_text_field(wp_unslash($_COOKIE[$this->session_cookie])) : '';

        if (!empty($raw_cookie)) {
            $parts = explode('|', $raw_cookie, 2);
            $session_id = isset($parts[0]) ? $parts[0] : '';
            $last_seen = isset($parts[1]) ? absint($parts[1]) : 0;

            if ($this->is_valid_uuid($session_id) && $last_seen > 0 && (time() - $last_seen) < (30 * MINUTE_IN_SECONDS)) {
                return array(
                    'id' => $session_id,
                    'is_new' => false,
                );
            }
        }

        return array(
            'id' => wp_generate_uuid4(),
            'is_new' => true,
        );
    }

    /**
     * Refresh the session cookie.
     *
     * @param string $session_id Session identifier.
     * @return void
     */
    private function refresh_session_cookie($session_id) {
        if (headers_sent() || !$this->is_valid_uuid($session_id)) {
            return;
        }

        $cookie_value = $session_id . '|' . time();

        setcookie(
            $this->session_cookie,
            $cookie_value,
            array(
                'expires' => time() + DAY_IN_SECONDS,
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );

        $_COOKIE[$this->session_cookie] = $cookie_value;
    }

    /**
     * Validate a UUID.
     *
     * @param string $value UUID value.
     * @return bool
     */
    private function is_valid_uuid($value) {
        return is_string($value) && preg_match('/^[a-f0-9-]{36}$/i', $value) === 1;
    }

    /**
     * Ensure a cron event is scheduled with the expected interval.
     *
     * @param string $hook Hook name.
     * @param string $schedule Schedule name.
     * @return void
     */
    private function ensure_scheduled_event($hook, $schedule) {
        if (function_exists('wp_get_scheduled_event')) {
            $scheduled_event = wp_get_scheduled_event($hook);

            if ($scheduled_event && isset($scheduled_event->schedule) && $scheduled_event->schedule === $schedule) {
                return;
            }

            if ($scheduled_event && isset($scheduled_event->timestamp)) {
                wp_unschedule_event($scheduled_event->timestamp, $hook, isset($scheduled_event->args) ? $scheduled_event->args : array());
            }
        } elseif (wp_next_scheduled($hook)) {
            return;
        }

        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, $schedule, $hook);
        }
    }

    /**
     * Get the current stats date.
     *
     * @return string
     */
    private function get_stats_date() {
        return gmdate('Y-m-d');
    }

    /**
     * Get pending session stats.
     *
     * @return array
     */
    private function get_pending_session_stats() {
        $pending = get_option($this->pending_session_stats_option, array());

        return is_array($pending) ? $pending : array();
    }

    /**
     * Mutate the daily session stats for a given date.
     *
     * @param string   $date Session date.
     * @param callable $callback Mutation callback.
     * @return void
     */
    private function mutate_daily_session_stats($date, $callback) {
        $pending = $this->get_pending_session_stats();
        $stats = isset($pending[$date]) && is_array($pending[$date])
            ? array_merge($this->get_empty_session_stats($date), $pending[$date])
            : $this->get_empty_session_stats($date);

        $mutated_stats = call_user_func($callback, $stats);

        if (!is_array($mutated_stats)) {
            return;
        }

        $mutated_stats['date'] = $date;
        $mutated_stats['geo_breakdown'] = isset($mutated_stats['geo_breakdown']) && is_array($mutated_stats['geo_breakdown']) ? $mutated_stats['geo_breakdown'] : array();
        $mutated_stats['device_breakdown'] = isset($mutated_stats['device_breakdown']) && is_array($mutated_stats['device_breakdown']) ? $mutated_stats['device_breakdown'] : array();
        $mutated_stats['no_action'] = max(0, (int) $mutated_stats['banner_shown'] - ((int) $mutated_stats['consent_given'] + (int) $mutated_stats['consent_denied'] + (int) $mutated_stats['consent_customized']));

        $pending[$date] = $mutated_stats;
        $pending = $this->prune_pending_session_stats($pending);

        update_option($this->pending_session_stats_option, $pending, false);
    }

    /**
     * Get an empty daily stats payload.
     *
     * @param string $date Session date.
     * @return array
     */
    private function get_empty_session_stats($date) {
        return array(
            'date' => $date,
            'total_sessions' => 0,
            'banner_shown' => 0,
            'consent_given' => 0,
            'consent_denied' => 0,
            'consent_customized' => 0,
            'no_action' => 0,
            'accepted_functional' => 0,
            'accepted_analytics' => 0,
            'accepted_marketing' => 0,
            'geo_breakdown' => array(),
            'device_breakdown' => array(),
        );
    }

    /**
     * Increment a breakdown bucket.
     *
     * @param array  $breakdown Breakdown values.
     * @param string $key Breakdown key.
     * @return array
     */
    private function increment_breakdown_count($breakdown, $key) {
        $breakdown = is_array($breakdown) ? $breakdown : array();
        $key = sanitize_key((string) $key);

        if (empty($key)) {
            return $breakdown;
        }

        $breakdown[$key] = isset($breakdown[$key]) ? (int) $breakdown[$key] + 1 : 1;

        return $breakdown;
    }

    /**
     * Prune stale pending session stats.
     *
     * @param array $pending Pending stats.
     * @return array
     */
    private function prune_pending_session_stats($pending) {
        $cutoff = gmdate('Y-m-d', strtotime('-14 days'));

        foreach ($pending as $date => $stats) {
            if (!is_string($date) || $date < $cutoff) {
                unset($pending[$date]);
            }
        }

        ksort($pending);

        return $pending;
    }

    /**
     * Clear all caches.
     *
     * @return void
     */
    public function clear_cache() {
        delete_transient('tg_gdpr_saas_settings');
        delete_transient('tg_gdpr_site_cookies');
        delete_transient('tg_gdpr_pending_sessions');
        delete_option($this->pending_session_stats_option);
    }
}

/**
 * Get API sync instance.
 *
 * @return TG_GDPR_API_Sync
 */
function tg_gdpr_api_sync() {
    return TG_GDPR_API_Sync::get_instance();
}
