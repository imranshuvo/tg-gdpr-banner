<?php
/**
 * Define the internationalization functionality.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_i18n {

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'tg-gdpr-cookie-consent',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
