<?php
/**
 * Consent Manager - Handles user consent preferences
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Consent_Manager {

    /**
     * Cookie name for storing consent.
     */
    const COOKIE_NAME = 'tg_gdpr_consent';

    /**
     * Cookie expiry in days.
     */
    private $cookie_expiry = 365;

    /**
     * Initialize the consent manager.
     */
    public function __construct() {
        $settings = get_option('tg_gdpr_settings', array());
        
        if (isset($settings['advanced']['consent_expiry'])) {
            $this->cookie_expiry = (int) $settings['advanced']['consent_expiry'];
        }
        
        // AJAX handlers
        add_action('wp_ajax_tg_gdpr_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_nopriv_tg_gdpr_save_consent', array($this, 'ajax_save_consent'));
        
        add_action('wp_ajax_tg_gdpr_get_consent', array($this, 'ajax_get_consent'));
        add_action('wp_ajax_nopriv_tg_gdpr_get_consent', array($this, 'ajax_get_consent'));
    }

    /**
     * AJAX handler to save consent.
     */
    public function ajax_save_consent() {
        check_ajax_referer('tg_gdpr_nonce', 'nonce');
        
        $consent = isset($_POST['consent']) ? json_decode(stripslashes($_POST['consent']), true) : array();
        
        if (!is_array($consent)) {
            wp_send_json_error(array('message' => 'Invalid consent data'));
            return;
        }
        
        // Validate categories
        $valid_categories = array('necessary', 'functional', 'analytics', 'marketing');
        $validated_consent = array();
        
        foreach ($valid_categories as $category) {
            $validated_consent[$category] = isset($consent[$category]) ? (bool) $consent[$category] : false;
        }
        
        // Necessary is always true
        $validated_consent['necessary'] = true;

        $normalized_consent = array_merge(
            $validated_consent,
            array(
                'interaction' => isset($consent['interaction']) ? sanitize_key($consent['interaction']) : '',
                'version' => isset($consent['version']) ? absint($consent['version']) : 1,
            )
        );
        
        // Save consent
        $this->save_consent($normalized_consent);

        if (class_exists('TG_GDPR_API_Sync')) {
            $api_sync = TG_GDPR_API_Sync::get_instance();

            if ($api_sync->is_configured()) {
                $api_sync->record_consent_interaction($normalized_consent);
                $api_sync->queue_consent($normalized_consent);
            }
        }
        
        // Log consent (Pro feature)
        if ($this->is_pro_active() && $this->is_consent_logging_enabled()) {
            $this->log_consent($normalized_consent);
        }
        
        wp_send_json_success(array(
            'message' => 'Consent saved successfully',
            'consent' => $normalized_consent
        ));
    }

    /**
     * AJAX handler to get current consent.
     */
    public function ajax_get_consent() {
        check_ajax_referer('tg_gdpr_nonce', 'nonce');
        
        $consent = $this->get_consent();
        
        wp_send_json_success(array(
            'consent' => $consent
        ));
    }

    /**
     * Save consent to cookie.
     *
     * @param array $consent Consent preferences.
     */
    public function save_consent($consent) {
        $expiry = time() + ($this->cookie_expiry * DAY_IN_SECONDS);
        
        $cookie_value = wp_json_encode($consent);
        
        // Set cookie with secure flags
        setcookie(
            self::COOKIE_NAME,
            $cookie_value,
            array(
                'expires' => $expiry,
                'path' => '/',
                'domain' => $this->get_cookie_domain(),
                'secure' => is_ssl(),
                'httponly' => false, // Needs to be accessible via JavaScript
                'samesite' => 'Lax'
            )
        );
        
        // Also set in $_COOKIE for immediate access
        $_COOKIE[self::COOKIE_NAME] = $cookie_value;
    }

    /**
     * Get consent from cookie.
     *
     * @return array Consent preferences.
     */
    public function get_consent() {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $consent_data = json_decode(stripslashes($_COOKIE[self::COOKIE_NAME]), true);
            
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
     * Check if user has given consent.
     *
     * @return bool
     */
    public function has_consent() {
        return isset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Log consent to database (Pro feature).
     *
     * @param array $consent Consent preferences.
     */
    private function log_consent($consent) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'tg_gdpr_consent_log';
        
        // Create user hash (anonymized)
        $user_hash = $this->create_user_hash();
        
        // Anonymize IP
        $ip = $this->anonymize_ip($this->get_client_ip());
        
        // Get consent version (hash of current settings)
        $consent_version = $this->get_consent_version();
        
        // Insert log
        $wpdb->insert(
            $table,
            array(
                'user_hash' => $user_hash,
                'consent_given' => wp_json_encode($consent),
                'ip_address' => $ip,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'consent_version' => $consent_version,
                'page_url' => substr($_SERVER['HTTP_REFERER'] ?? home_url(), 0, 255),
                'expires_at' => date('Y-m-d H:i:s', time() + ($this->cookie_expiry * DAY_IN_SECONDS))
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Create anonymized user hash.
     *
     * @return string
     */
    private function create_user_hash() {
        $ip = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Hash with salt
        return hash('sha256', $ip . $user_agent . wp_salt());
    }

    /**
     * Anonymize IP address (GDPR compliant).
     *
     * @param string $ip IP address.
     * @return string Anonymized IP.
     */
    private function anonymize_ip($ip) {
        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0'; // Remove last octet
            return implode('.', $parts);
        }
        
        // IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts[7] = '0'; // Remove last segment
            return implode(':', $parts);
        }
        
        return '0.0.0.0';
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle multiple IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Get consent version hash.
     *
     * @return string
     */
    private function get_consent_version() {
        $settings = get_option('tg_gdpr_settings', array());
        return substr(md5(wp_json_encode($settings)), 0, 20);
    }

    /**
     * Get cookie domain.
     *
     * @return string
     */
    private function get_cookie_domain() {
        $domain = parse_url(home_url(), PHP_URL_HOST);
        
        // Remove www if present
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, 4);
        }
        
        return apply_filters('tg_gdpr_cookie_domain', $domain);
    }

    /**
     * Check if Pro version is active.
     *
     * @return bool
     */
    private function is_pro_active() {
        $license_manager = new TG_GDPR_License_Manager();

        return $license_manager->is_license_active();
    }

    /**
     * Check if consent logging is enabled.
     *
     * @return bool
     */
    private function is_consent_logging_enabled() {
        $settings = get_option('tg_gdpr_settings', array());
        return isset($settings['pro']['consent_logging']) && $settings['pro']['consent_logging'] === true;
    }

    /**
     * Delete old consent logs (cleanup - called via cron).
     */
    public static function cleanup_old_logs() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'tg_gdpr_consent_log';
        
        // Delete logs older than 36 months (GDPR retention)
        $retention_period = apply_filters('tg_gdpr_log_retention_days', 1095); // 3 years
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_period
            )
        );
    }
}
