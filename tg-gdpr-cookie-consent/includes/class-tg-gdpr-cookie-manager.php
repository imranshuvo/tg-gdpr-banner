<?php
/**
 * Cookie Manager - Manages cookie database and auto-detection
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Cookie_Manager {

    /**
     * Default cookie fields.
     *
     * @return array
     */
    private function get_cookie_defaults() {
        return array(
            'cookie_name' => '',
            'category' => 'necessary',
            'description' => '',
            'duration' => '',
            'domain' => '',
            'script_pattern' => '',
            'is_active' => 1,
        );
    }

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

        $cookie_data = wp_parse_args($cookie_data, $this->get_cookie_defaults());
        
        $result = $wpdb->insert($table, $cookie_data);
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a cookie by name.
     *
     * @param string $cookie_name Cookie name.
     * @return object|null
     */
    public function get_cookie_by_name($cookie_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'tg_gdpr_cookies';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE cookie_name = %s LIMIT 1",
            $cookie_name
        ));
    }

    /**
     * Insert or update an automatically detected cookie.
     *
     * @param array $cookie_data Cookie data.
     * @return int|false Cookie ID or false on failure.
     */
    public function save_detected_cookie($cookie_data) {
        $cookie_data = wp_parse_args($cookie_data, $this->get_cookie_defaults());
        $cookie_data['cookie_name'] = sanitize_text_field($cookie_data['cookie_name']);

        if (empty($cookie_data['cookie_name'])) {
            return false;
        }

        $existing = $this->get_cookie_by_name($cookie_data['cookie_name']);

        if (!$existing) {
            return $this->add_cookie($cookie_data);
        }

        $merged_pattern = $this->merge_script_patterns($existing->script_pattern, $cookie_data['script_pattern']);

        $updated_data = array(
            'category' => !empty($cookie_data['category']) ? $cookie_data['category'] : $existing->category,
            'description' => !empty($cookie_data['description']) ? $cookie_data['description'] : $existing->description,
            'duration' => !empty($cookie_data['duration']) ? $cookie_data['duration'] : $existing->duration,
            'domain' => !empty($cookie_data['domain']) ? $cookie_data['domain'] : $existing->domain,
            'script_pattern' => $merged_pattern,
            'is_active' => 1,
        );

        $updated = $this->update_cookie($existing->id, $updated_data);

        return $updated !== false ? (int) $existing->id : false;
    }

    /**
     * Merge stored script patterns without duplicates.
     *
     * @param string $existing Existing script pattern string.
     * @param string $incoming Incoming script pattern string.
     * @return string
     */
    private function merge_script_patterns($existing, $incoming) {
        $patterns = array_filter(array_map('trim', explode('|', (string) $existing)));
        $incoming_patterns = array_filter(array_map('trim', explode('|', (string) $incoming)));

        foreach ($incoming_patterns as $pattern) {
            if (!in_array($pattern, $patterns, true)) {
                $patterns[] = $pattern;
            }
        }

        return implode('|', $patterns);
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
