<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Admin {

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
     * Initialize the class.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            TG_GDPR_PLUGIN_URL . 'admin/css/tg-gdpr-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            TG_GDPR_PLUGIN_URL . 'admin/js/tg-gdpr-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script(
            $this->plugin_name,
            'TG_GDPR_ADMIN',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tg_gdpr_admin_nonce'),
            )
        );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Cookiely', 'tg-gdpr-cookie-consent'),
            __('Cookiely', 'tg-gdpr-cookie-consent'),
            'manage_options',
            'tg-gdpr-cookie-consent',
            array($this, 'display_admin_page'),
            'dashicons-shield',
            80
        );
        
        // Settings submenu
        add_submenu_page(
            'tg-gdpr-cookie-consent',
            __('Settings', 'tg-gdpr-cookie-consent'),
            __('Settings', 'tg-gdpr-cookie-consent'),
            'manage_options',
            'tg-gdpr-cookie-consent',
            array($this, 'display_admin_page')
        );
        
        // Cookies submenu
        add_submenu_page(
            'tg-gdpr-cookie-consent',
            __('Cookies', 'tg-gdpr-cookie-consent'),
            __('Cookies', 'tg-gdpr-cookie-consent'),
            'manage_options',
            'tg-gdpr-cookies',
            array($this, 'display_cookies_page')
        );
        
        // License submenu
        add_submenu_page(
            'tg-gdpr-cookie-consent',
            __('License', 'tg-gdpr-cookie-consent'),
            __('License', 'tg-gdpr-cookie-consent'),
            'manage_options',
            'tg-gdpr-license',
            array($this, 'display_license_page')
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'tg_gdpr_settings_group',
            'tg_gdpr_settings',
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Sanitize the tg_gdpr_settings option.
     *
     * Whitelist-based: only known keys survive. Each value runs through the
     * appropriate WordPress sanitizer (sanitize_text_field, sanitize_hex_color,
     * esc_url_raw, wp_kses_post). Unknown keys are dropped — a writer cannot
     * smuggle extra fields through.
     *
     * @param mixed $input Raw POST data (untrusted).
     * @return array Sanitized settings, safe to store.
     */
    public function sanitize_settings($input) {
        if (! is_array($input)) {
            return array();
        }

        $allowed_positions = array('top', 'bottom', 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'center');

        return array(
            'general' => array(
                'enabled'    => ! empty($input['general']['enabled']) ? 1 : 0,
                'auto_block' => ! empty($input['general']['auto_block']) ? 1 : 0,
            ),
            'banner' => array(
                'position'      => in_array(($input['banner']['position'] ?? 'bottom'), $allowed_positions, true)
                    ? $input['banner']['position']
                    : 'bottom',
                'primary_color' => sanitize_hex_color($input['banner']['primary_color'] ?? '') ?: '#1e40af',
                'accent_color'  => sanitize_hex_color($input['banner']['accent_color'] ?? '') ?: '#3b82f6',
            ),
            'content' => array(
                'heading'            => sanitize_text_field($input['content']['heading'] ?? ''),
                'message'            => wp_kses_post($input['content']['message'] ?? ''),
                'privacy_policy_url' => esc_url_raw($input['content']['privacy_policy_url'] ?? ''),
            ),
            'pro' => array(
                'license_key'       => $this->sanitize_license_key($input['pro']['license_key'] ?? ''),
                'consent_logging'   => ! empty($input['pro']['consent_logging']) ? 1 : 0,
                'auto_scan_enabled' => ! empty($input['pro']['auto_scan_enabled']) ? 1 : 0,
            ),
        );
    }

    /**
     * Strip license keys to A-Z, 0-9 and dashes; uppercase.
     */
    private function sanitize_license_key($value) {
        return preg_replace('/[^A-Z0-9-]/', '', strtoupper(sanitize_text_field((string) $value)));
    }

    /**
     * Display admin page.
     */
    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include TG_GDPR_PLUGIN_DIR . 'admin/partials/tg-gdpr-admin-display.php';
    }

    /**
     * Display cookies management page.
     */
    public function display_cookies_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include TG_GDPR_PLUGIN_DIR . 'admin/partials/tg-gdpr-cookies-display.php';
    }
    
    /**
     * Display license page.
     */
    public function display_license_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include TG_GDPR_PLUGIN_DIR . 'admin/partials/tg-gdpr-license-display.php';
    }

    /**
     * Run a manual cookie scan from the admin area.
     */
    public function run_cookie_scan() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to run a cookie scan.', 'tg-gdpr-cookie-consent'));
        }

        check_admin_referer('tg_gdpr_run_cookie_scan');

        $scanner = new TG_GDPR_Auto_Scanner();
        $result = $scanner->scan_site(true);

        $redirect_args = array(
            'page' => 'tg-gdpr-cookies',
            'scan_status' => !empty($result['success']) ? 'success' : 'error',
            'scan_message' => rawurlencode($result['message'] ?? __('Cookie scan completed.', 'tg-gdpr-cookie-consent')),
        );

        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }
}
