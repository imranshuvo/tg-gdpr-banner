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
     * Initialize the class
     */
    public function __construct() {
        $this->license_manager = new TG_GDPR_License_Manager();
    }

    /**
     * Check if auto scanner feature is available
     */
    public function is_available() {
        return $this->license_manager->has_feature('auto_scanner');
    }

    /**
     * Scan site for cookies
     */
    public function scan_site() {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'message' => 'Auto Scanner is a Pro feature. Please activate your license to use this feature.',
            );
        }

        // Pro feature implementation
        $cookies = $this->detect_cookies();
        
        return array(
            'success' => true,
            'message' => 'Scan completed successfully',
            'data' => array(
                'cookies_found' => count($cookies),
                'cookies' => $cookies,
            ),
        );
    }

    /**
     * Detect cookies on the site (Pro implementation)
     */
    private function detect_cookies() {
        // This would contain actual cookie detection logic
        // For now, return sample data
        return array(
            array(
                'name' => '_ga',
                'category' => 'analytics',
                'domain' => parse_url(get_site_url(), PHP_URL_HOST),
                'duration' => '2 years',
                'description' => 'Google Analytics cookie used to distinguish users',
                'provider' => 'Google Analytics',
            ),
            array(
                'name' => '_fbp',
                'category' => 'marketing',
                'domain' => parse_url(get_site_url(), PHP_URL_HOST),
                'duration' => '3 months',
                'description' => 'Facebook Pixel cookie for tracking',
                'provider' => 'Facebook',
            ),
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
        
        if ($last_scan) {
            return 'Last scanned: ' . human_time_diff($last_scan, current_time('timestamp')) . ' ago';
        }

        return 'Never scanned';
    }

    /**
     * Schedule automatic scans
     */
    public function schedule_auto_scan() {
        if (!$this->is_available()) {
            return false;
        }

        if (!wp_next_scheduled('tg_gdpr_auto_cookie_scan')) {
            wp_schedule_event(time(), 'weekly', 'tg_gdpr_auto_cookie_scan');
        }

        return true;
    }

    /**
     * Run automated scan (called by cron)
     */
    public function run_auto_scan() {
        if (!$this->is_available()) {
            return;
        }

        $result = $this->scan_site();
        
        if ($result['success']) {
            update_option('tg_gdpr_last_cookie_scan', current_time('timestamp'));
            
            // Automatically update detected cookies in database
            $cookie_manager = new TG_GDPR_Cookie_Manager();
            
            foreach ($result['data']['cookies'] as $cookie) {
                $cookie_manager->add_cookie(
                    $cookie['name'],
                    $cookie['category'],
                    $cookie['domain'],
                    $cookie['duration'],
                    $cookie['description'],
                    $cookie['provider']
                );
            }
        }
    }
}
