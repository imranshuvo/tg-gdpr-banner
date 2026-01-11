<?php
/**
 * Fired during plugin activation.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Activator {

    /**
     * Activate the plugin.
     */
    public static function activate() {
        // Create database tables for Pro features (consent logging)
        self::create_tables();
        
        // Set default settings
        self::set_default_settings();
        
        // Set activation flag
        update_option('tg_gdpr_activated', true);
        update_option('tg_gdpr_activation_time', time());
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables for consent logging (Pro feature).
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'tg_gdpr_consent_log';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_hash varchar(64) NOT NULL,
            consent_given text NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            consent_version varchar(20) DEFAULT NULL,
            page_url varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_hash (user_hash),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Cookies table
        $cookies_table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        $sql_cookies = "CREATE TABLE IF NOT EXISTS $cookies_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cookie_name varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            description text DEFAULT NULL,
            duration varchar(100) DEFAULT NULL,
            domain varchar(255) DEFAULT NULL,
            script_pattern text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY cookie_name (cookie_name),
            KEY category (category)
        ) $charset_collate;";
        
        dbDelta($sql_cookies);
        
        // Insert default cookies
        self::insert_default_cookies();
    }

    /**
     * Insert default cookies into the database.
     */
    private static function insert_default_cookies() {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        // Check if already populated
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }
        
        $default_cookies = array(
            // Necessary
            array(
                'cookie_name' => 'tg_gdpr_consent',
                'category' => 'necessary',
                'description' => 'Stores user cookie consent preferences',
                'duration' => '12 months',
                'domain' => get_site_url(),
                'script_pattern' => '',
            ),
            array(
                'cookie_name' => 'PHPSESSID',
                'category' => 'necessary',
                'description' => 'PHP session cookie for maintaining user session',
                'duration' => 'Session',
                'domain' => get_site_url(),
                'script_pattern' => '',
            ),
            // Analytics
            array(
                'cookie_name' => '_ga',
                'category' => 'analytics',
                'description' => 'Google Analytics - Used to distinguish users',
                'duration' => '2 years',
                'domain' => '.google-analytics.com',
                'script_pattern' => 'google-analytics.com/analytics.js|googletagmanager.com/gtag/js',
            ),
            array(
                'cookie_name' => '_gid',
                'category' => 'analytics',
                'description' => 'Google Analytics - Used to distinguish users',
                'duration' => '24 hours',
                'domain' => '.google-analytics.com',
                'script_pattern' => 'google-analytics.com/analytics.js|googletagmanager.com/gtag/js',
            ),
            // Marketing
            array(
                'cookie_name' => '_fbp',
                'category' => 'marketing',
                'description' => 'Facebook Pixel - Used for tracking conversions',
                'duration' => '3 months',
                'domain' => '.facebook.com',
                'script_pattern' => 'connect.facebook.net/en_US/fbevents.js',
            ),
        );
        
        foreach ($default_cookies as $cookie) {
            $wpdb->insert($table, $cookie);
        }
    }

    /**
     * Set default plugin settings.
     */
    private static function set_default_settings() {
        $default_settings = array(
            'general' => array(
                'enabled' => true,
                'auto_block' => true,
                'show_on_pages' => 'all',
                'exclude_pages' => array(),
            ),
            'banner' => array(
                'position' => 'bottom',
                'layout' => 'bar',
                'primary_color' => '#1e40af',
                'accent_color' => '#3b82f6',
                'text_color' => '#1f2937',
                'bg_color' => '#ffffff',
                'show_logo' => false,
                'logo_url' => '',
            ),
            'content' => array(
                'heading' => __('We value your privacy', 'tg-gdpr-cookie-consent'),
                'message' => __('We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'tg-gdpr-cookie-consent'),
                'accept_all_text' => __('Accept All', 'tg-gdpr-cookie-consent'),
                'reject_all_text' => __('Reject All', 'tg-gdpr-cookie-consent'),
                'settings_text' => __('Cookie Settings', 'tg-gdpr-cookie-consent'),
                'privacy_policy_text' => __('Privacy Policy', 'tg-gdpr-cookie-consent'),
                'privacy_policy_url' => get_privacy_policy_url(),
            ),
            'categories' => array(
                'necessary' => array(
                    'enabled' => true,
                    'locked' => true,
                    'title' => __('Necessary', 'tg-gdpr-cookie-consent'),
                    'description' => __('Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas of the website.', 'tg-gdpr-cookie-consent'),
                ),
                'functional' => array(
                    'enabled' => true,
                    'locked' => false,
                    'title' => __('Functional', 'tg-gdpr-cookie-consent'),
                    'description' => __('Functional cookies enable the website to provide enhanced functionality and personalization, such as videos and live chats.', 'tg-gdpr-cookie-consent'),
                ),
                'analytics' => array(
                    'enabled' => true,
                    'locked' => false,
                    'title' => __('Analytics', 'tg-gdpr-cookie-consent'),
                    'description' => __('Analytics cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.', 'tg-gdpr-cookie-consent'),
                ),
                'marketing' => array(
                    'enabled' => true,
                    'locked' => false,
                    'title' => __('Marketing', 'tg-gdpr-cookie-consent'),
                    'description' => __('Marketing cookies are used to track visitors across websites to display relevant advertisements.', 'tg-gdpr-cookie-consent'),
                ),
            ),
            'advanced' => array(
                'consent_expiry' => 365, // days
                'auto_hide' => true,
                'auto_hide_delay' => 0,
                'show_revisit_button' => true,
                'revisit_button_position' => 'left',
            ),
            'pro' => array(
                'license_key' => '',
                'license_status' => 'inactive',
                'auto_scan_enabled' => false,
                'consent_logging' => false,
                'geolocation' => false,
            ),
        );
        
        update_option('tg_gdpr_settings', $default_settings);
    }
}
