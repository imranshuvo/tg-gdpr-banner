<?php
/**
 * Fired during plugin deactivation.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        $timestamp = wp_next_scheduled('tg_gdpr_cookie_scan');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'tg_gdpr_cookie_scan');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
