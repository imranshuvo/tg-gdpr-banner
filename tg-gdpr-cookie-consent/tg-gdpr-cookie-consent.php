<?php
/**
 * Plugin Name: TG GDPR Cookie Consent
 * Plugin URI: https://techgenesis.com/tg-gdpr-cookie-consent
 * Description: Performance-first GDPR cookie consent plugin. Fully compliant, works with all optimization plugins, beautiful UI inspired by CookieYes.
 * Version: 1.0.0
 * Author: TechGenesis
 * Author URI: https://techgenesis.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tg-gdpr-cookie-consent
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package TG_GDPR_Cookie_Consent
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('TG_GDPR_VERSION', '1.0.0');
define('TG_GDPR_PLUGIN_FILE', __FILE__);
define('TG_GDPR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TG_GDPR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TG_GDPR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_tg_gdpr_cookie_consent() {
    require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-activator.php';
    TG_GDPR_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_tg_gdpr_cookie_consent() {
    require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-deactivator.php';
    TG_GDPR_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_tg_gdpr_cookie_consent');
register_deactivation_hook(__FILE__, 'deactivate_tg_gdpr_cookie_consent');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-core.php';

/**
 * Begins execution of the plugin.
 */
function run_tg_gdpr_cookie_consent() {
    $plugin = new TG_GDPR_Core();
    $plugin->run();
}

run_tg_gdpr_cookie_consent();
