<?php
/**
 * License Manager Class
 *
 * Handles license activation, verification, and feature gating
 *
 * @package    TG_GDPR_Cookie_Consent
 * @subpackage TG_GDPR_Cookie_Consent/includes
 */

class TG_GDPR_License_Manager {

    /**
     * License API endpoint
     */
    private $api_url = 'https://your-domain.com/api/v1/licenses';

    /**
     * Option names
     */
    private $license_key_option = 'tg_gdpr_license_key';
    private $license_data_option = 'tg_gdpr_license_data';
    private $license_status_option = 'tg_gdpr_license_status';

    /**
     * Initialize the class
     */
    public function __construct() {
        // Daily license verification cron
        add_action('tg_gdpr_daily_license_check', array($this, 'verify_license_cron'));
        
        // Schedule cron if not scheduled
        if (!wp_next_scheduled('tg_gdpr_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'tg_gdpr_daily_license_check');
        }
    }

    /**
     * Get the current license key
     */
    public function get_license_key() {
        return get_option($this->license_key_option, '');
    }

    /**
     * Get license data
     */
    public function get_license_data() {
        return get_option($this->license_data_option, array());
    }

    /**
     * Get license status
     */
    public function get_license_status() {
        return get_option($this->license_status_option, 'inactive');
    }

    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $domain = $this->get_domain();
        $site_url = get_site_url();

        $response = wp_remote_post($this->api_url . '/activate', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'license_key' => $license_key,
                'domain' => $domain,
                'site_url' => $site_url,
            )),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to connect to license server: ' . $response->get_error_message(),
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['success'])) {
            update_option($this->license_key_option, $license_key);
            update_option($this->license_data_option, $body['data']);
            update_option($this->license_status_option, 'active');
            
            return array(
                'success' => true,
                'message' => $body['message'],
                'data' => $body['data'],
            );
        }

        return array(
            'success' => false,
            'message' => $body['message'] ?? 'License activation failed',
        );
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_key = $this->get_license_key();
        
        if (empty($license_key)) {
            return array('success' => true, 'message' => 'No license to deactivate');
        }

        $domain = $this->get_domain();

        $response = wp_remote_post($this->api_url . '/deactivate', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'license_key' => $license_key,
                'domain' => $domain,
            )),
            'timeout' => 30,
        ));

        // Clear local license data regardless of API response
        delete_option($this->license_key_option);
        delete_option($this->license_data_option);
        delete_option($this->license_status_option);

        if (is_wp_error($response)) {
            return array(
                'success' => true,
                'message' => 'License removed locally (API unreachable)',
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return array(
            'success' => true,
            'message' => $body['message'] ?? 'License deactivated',
        );
    }

    /**
     * Verify license (heartbeat check)
     */
    public function verify_license() {
        $license_key = $this->get_license_key();

        if (empty($license_key)) {
            update_option($this->license_status_option, 'inactive');
            return array('success' => false, 'message' => 'No license key found');
        }

        $domain = $this->get_domain();

        $response = wp_remote_post($this->api_url . '/verify', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'license_key' => $license_key,
                'domain' => $domain,
            )),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            // Don't deactivate on network error, keep last known status
            return array(
                'success' => false,
                'message' => 'Failed to verify license: ' . $response->get_error_message(),
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['success'])) {
            update_option($this->license_data_option, $body['data']);
            update_option($this->license_status_option, 'active');
            
            return array(
                'success' => true,
                'message' => 'License verified',
                'data' => $body['data'],
            );
        } else {
            update_option($this->license_status_option, 'inactive');
            
            return array(
                'success' => false,
                'message' => $body['message'] ?? 'License verification failed',
            );
        }
    }

    /**
     * Verify license via cron
     */
    public function verify_license_cron() {
        $this->verify_license();
    }

    /**
     * Check if license is active
     */
    public function is_license_active() {
        return $this->get_license_status() === 'active';
    }

    /**
     * Get license plan
     */
    public function get_license_plan() {
        $data = $this->get_license_data();
        return $data['plan'] ?? 'free';
    }

    /**
     * Check if feature is available based on license
     */
    public function has_feature($feature) {
        if (!$this->is_license_active()) {
            return false;
        }

        $plan = $this->get_license_plan();

        // All paid plans have Pro features
        $pro_features = array(
            'auto_scanner',
            'analytics_dashboard',
            'advanced_logging',
            'priority_support',
            'custom_branding',
        );

        if (in_array($feature, $pro_features)) {
            return in_array($plan, array('single', '3-sites', '10-sites'));
        }

        return false;
    }

    /**
     * Get current domain
     */
    private function get_domain() {
        return parse_url(get_site_url(), PHP_URL_HOST);
    }

    /**
     * Get days until expiry
     */
    public function get_days_until_expiry() {
        $data = $this->get_license_data();
        
        if (empty($data['expires_at'])) {
            return null;
        }

        $expires = strtotime($data['expires_at']);
        $now = time();
        $diff = $expires - $now;

        return floor($diff / (60 * 60 * 24));
    }

    /**
     * Is license expiring soon (within 30 days)
     */
    public function is_expiring_soon() {
        $days = $this->get_days_until_expiry();
        return $days !== null && $days > 0 && $days <= 30;
    }

    /**
     * Format expiry date for display
     */
    public function get_expiry_date_formatted() {
        $data = $this->get_license_data();
        
        if (empty($data['expires_at'])) {
            return 'N/A';
        }

        return date('F j, Y', strtotime($data['expires_at']));
    }
}
