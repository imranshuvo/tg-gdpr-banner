<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package TG_GDPR_Cookie_Consent
 */

class TG_GDPR_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var TG_GDPR_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = TG_GDPR_VERSION;
        $this->plugin_name = 'tg-gdpr-cookie-consent';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_scanner_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-loader.php';

        // The class responsible for defining internationalization
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-i18n.php';

        // The class responsible for defining all actions in the admin area
        require_once TG_GDPR_PLUGIN_DIR . 'admin/class-tg-gdpr-admin.php';

        // The class responsible for defining all actions on the public-facing side
        require_once TG_GDPR_PLUGIN_DIR . 'public/class-tg-gdpr-public.php';

        // Core functionality classes
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-banner.php';
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-script-blocker.php';
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-consent-manager.php';
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-cookie-manager.php';
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-auto-scanner.php';
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-license-manager.php';
        
        // SaaS API sync handler
        require_once TG_GDPR_PLUGIN_DIR . 'includes/class-tg-gdpr-api-sync.php';

        $this->loader = new TG_GDPR_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new TG_GDPR_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $plugin_admin = new TG_GDPR_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        $this->loader->add_action('admin_post_tg_gdpr_run_cookie_scan', $plugin_admin, 'run_cookie_scan');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new TG_GDPR_Public($this->get_plugin_name(), $this->get_version());
        $api_sync = TG_GDPR_API_Sync::get_instance();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_head', $plugin_public, 'inject_critical_inline_script', 0);
        $this->loader->add_action('wp_footer', $plugin_public, 'render_banner');
        $this->loader->add_action('template_redirect', $api_sync, 'record_session', 0);
        
        // Script blocking
        $this->loader->add_action('template_redirect', $plugin_public, 'start_output_buffering', -9999);
    }

    /**
     * Register auto-scanner hooks.
     */
    private function define_scanner_hooks() {
        $scanner = new TG_GDPR_Auto_Scanner();

        $this->loader->add_action('init', $scanner, 'maybe_schedule_auto_scan', 20);
        $this->loader->add_action('tg_gdpr_auto_cookie_scan', $scanner, 'run_auto_scan');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
