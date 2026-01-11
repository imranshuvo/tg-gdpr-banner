<?php
/**
 * Cookie Manager - Manages cookie database and auto-detection
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Cookie_Manager {

    /**
     * Get all cookies.
     *
     * @return array
     */
    public function get_cookies() {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        return $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1 ORDER BY category, cookie_name");
    }

    /**
     * Get cookies by category.
     *
     * @param string $category Category name.
     * @return array
     */
    public function get_cookies_by_category($category) {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE category = %s AND is_active = 1 ORDER BY cookie_name",
            $category
        ));
    }

    /**
     * Add a cookie.
     *
     * @param array $cookie_data Cookie data.
     * @return int|false Insert ID or false on failure.
     */
    public function add_cookie($cookie_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        $defaults = array(
            'cookie_name' => '',
            'category' => 'necessary',
            'description' => '',
            'duration' => '',
            'domain' => '',
            'script_pattern' => '',
            'is_active' => 1,
        );
        
        $cookie_data = wp_parse_args($cookie_data, $defaults);
        
        $result = $wpdb->insert($table, $cookie_data);
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a cookie.
     *
     * @param int $cookie_id Cookie ID.
     * @param array $cookie_data Cookie data.
     * @return bool
     */
    public function update_cookie($cookie_id, $cookie_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        return $wpdb->update(
            $table,
            $cookie_data,
            array('id' => $cookie_id),
            null,
            array('%d')
        );
    }

    /**
     * Delete a cookie.
     *
     * @param int $cookie_id Cookie ID.
     * @return bool
     */
    public function delete_cookie($cookie_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';
        
        return $wpdb->delete($table, array('id' => $cookie_id), array('%d'));
    }

    /**
     * Get cookies grouped by category for frontend.
     *
     * @return array
     */
    public function get_cookies_for_frontend() {
        $cookies = $this->get_cookies();
        $grouped = array();
        
        foreach ($cookies as $cookie) {
            if (!isset($grouped[$cookie->category])) {
                $grouped[$cookie->category] = array();
            }
            
            $grouped[$cookie->category][] = array(
                'name' => $cookie->cookie_name,
                'description' => $cookie->description,
                'duration' => $cookie->duration,
                'domain' => $cookie->domain,
            );
        }
        
        return $grouped;
    }
}
