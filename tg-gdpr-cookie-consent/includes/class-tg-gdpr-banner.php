<?php
/**
 * Banner - Renders the cookie consent banner
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Banner {

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Cookie manager instance.
     *
     * @var TG_GDPR_Cookie_Manager
     */
    private $cookie_manager;

    /**
     * Initialize the banner.
     */
    public function __construct() {
        $this->settings = get_option('tg_gdpr_settings', array());
        $this->cookie_manager = new TG_GDPR_Cookie_Manager();
    }

    /**
     * Render the cookie banner HTML.
     */
    public function render() {
        // Check if banner should be shown
        if (!$this->should_show_banner()) {
            return;
        }
        
        $banner_settings = $this->settings['banner'] ?? array();
        $content = $this->settings['content'] ?? array();
        $categories = $this->settings['categories'] ?? array();
        
        $position = $banner_settings['position'] ?? 'bottom';
        $layout = $banner_settings['layout'] ?? 'bar';
        
        // Get cookies for display
        $cookies_by_category = $this->cookie_manager->get_cookies_for_frontend();
        
        include TG_GDPR_PLUGIN_DIR . 'public/partials/tg-gdpr-banner.php';
    }

    /**
     * Check if banner should be shown.
     *
     * @return bool
     */
    private function should_show_banner() {
        // Don't show if disabled
        if (!isset($this->settings['general']['enabled']) || !$this->settings['general']['enabled']) {
            return false;
        }
        
        // Don't show if user already has consent cookie
        $consent_manager = new TG_GDPR_Consent_Manager();
        if ($consent_manager->has_consent()) {
            return false;
        }
        
        // Check page exclusions
        $show_on = $this->settings['general']['show_on_pages'] ?? 'all';
        $excluded_pages = $this->settings['general']['exclude_pages'] ?? array();
        
        if ($show_on === 'exclude' && !empty($excluded_pages)) {
            $current_page_id = get_the_ID();
            if (in_array($current_page_id, $excluded_pages)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get inline styles for banner.
     *
     * @return string
     */
    public function get_inline_styles() {
        $banner = $this->settings['banner'] ?? array();
        
        $primary_color = $banner['primary_color'] ?? '#1e40af';
        $accent_color = $banner['accent_color'] ?? '#3b82f6';
        $text_color = $banner['text_color'] ?? '#1f2937';
        $bg_color = $banner['bg_color'] ?? '#ffffff';
        
        ob_start();
        ?>
        <style id="tg-gdpr-inline-styles">
            :root {
                --tg-gdpr-primary: <?php echo esc_attr($primary_color); ?>;
                --tg-gdpr-accent: <?php echo esc_attr($accent_color); ?>;
                --tg-gdpr-text: <?php echo esc_attr($text_color); ?>;
                --tg-gdpr-bg: <?php echo esc_attr($bg_color); ?>;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Get category title.
     *
     * @param string $category Category slug.
     * @return string
     */
    public function get_category_title($category) {
        if (isset($this->settings['categories'][$category]['title'])) {
            return $this->settings['categories'][$category]['title'];
        }
        return ucfirst($category);
    }

    /**
     * Get category description.
     *
     * @param string $category Category slug.
     * @return string
     */
    public function get_category_description($category) {
        if (isset($this->settings['categories'][$category]['description'])) {
            return $this->settings['categories'][$category]['description'];
        }
        return '';
    }

    /**
     * Is category locked (always on).
     *
     * @param string $category Category slug.
     * @return bool
     */
    public function is_category_locked($category) {
        return isset($this->settings['categories'][$category]['locked']) && 
               $this->settings['categories'][$category]['locked'] === true;
    }

    /**
     * Is category enabled.
     *
     * @param string $category Category slug.
     * @return bool
     */
    public function is_category_enabled($category) {
        return isset($this->settings['categories'][$category]['enabled']) && 
               $this->settings['categories'][$category]['enabled'] === true;
    }
}
