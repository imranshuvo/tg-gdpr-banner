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
        
        // Register cron hooks
        add_action('tg_gdpr_sync_consents', array($this, 'sync_pending_consents'));
        add_action('tg_gdpr_sync_sessions', array($this, 'sync_sessions'));
        
        // Schedule cron jobs
        if (!wp_next_scheduled('tg_gdpr_sync_consents')) {
            wp_schedule_event(time(), 'five_minutes', 'tg_gdpr_sync_consents');
        }
        if (!wp_next_scheduled('tg_gdpr_sync_sessions')) {
            wp_schedule_event(time(), 'hourly', 'tg_gdpr_sync_sessions');
        }
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
        
        $pending[] = array(
            'consent' => $consent,
            'visitor_hash' => $this->generate_visitor_hash(),
            'ip_address' => $this->get_anonymized_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'page_url' => isset($_SERVER['REQUEST_URI']) ? esc_url_raw(home_url($_SERVER['REQUEST_URI'])) : '',
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
            'timestamp' => current_time('mysql', true),
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
        $session_id = $this->get_session_id();
        $sessions = get_transient('tg_gdpr_pending_sessions') ?: array();
        
        if (!isset($sessions[$session_id])) {
            $sessions[$session_id] = array(
                'session_id' => $session_id,
                'visitor_hash' => $this->generate_visitor_hash(),
                'started_at' => current_time('mysql', true),
                'page_count' => 1,
            );
        } else {
            $sessions[$session_id]['page_count']++;
        }
        
        set_transient('tg_gdpr_pending_sessions', $sessions, HOUR_IN_SECONDS);
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

        $sessions = get_transient('tg_gdpr_pending_sessions') ?: array();
        
        if (empty($sessions)) {
            return true;
        }

        $response = $this->api_request('POST', 'api/v1/sessions/sync', array(
            'site_token' => $this->site_token,
            'sessions' => array_values($sessions),
        ));

        if ($response['success']) {
            delete_transient('tg_gdpr_pending_sessions');
            return true;
        }

        return false;
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

        if ($response['success'] && isset($response['data']['settings'])) {
            $settings = $response['data']['settings'];
            set_transient($cache_key, $settings, 5 * MINUTE_IN_SECONDS);
            return $settings;
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

        $response = $this->api_request('POST', 'api/v1/dsar/submit', array(
            'site_token' => $this->site_token,
            'type' => $data['type'],
            'email' => $data['email'],
            'first_name' => isset($data['first_name']) ? $data['first_name'] : '',
            'last_name' => isset($data['last_name']) ? $data['last_name'] : '',
            'phone' => isset($data['phone']) ? $data['phone'] : '',
            'message' => isset($data['message']) ? $data['message'] : '',
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
        $components = array(
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '',
            $this->get_anonymized_ip(),
        );
        
        return hash('sha256', implode('|', $components));
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
        if (isset($_COOKIE['tg_gdpr_session'])) {
            return sanitize_text_field($_COOKIE['tg_gdpr_session']);
        }
        
        $session_id = wp_generate_uuid4();
        
        // Will be set by JavaScript
        return $session_id;
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
