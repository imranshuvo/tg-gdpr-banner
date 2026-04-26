<?php
/**
 * Admin Settings Page
 *
 * @package TG_GDPR_Cookie_Consent
 */

if (!defined('ABSPATH')) {
    exit;
}

if (! current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'tg-gdpr-cookie-consent'));
}

$settings = get_option('tg_gdpr_settings', array());
$license_manager = new TG_GDPR_License_Manager();
$is_pro_active = $license_manager->is_license_active();

// Handle form submission
if (isset($_POST['tg_gdpr_save_settings']) && check_admin_referer('tg_gdpr_settings_nonce')) {
    $admin = new TG_GDPR_Admin('tg-gdpr-cookie-consent', defined('TG_GDPR_VERSION') ? TG_GDPR_VERSION : '1.0.0');
    $clean = $admin->sanitize_settings(wp_unslash($_POST['tg_gdpr_settings'] ?? array()));

    update_option('tg_gdpr_settings', $clean);
    echo '<div class="tg-gdpr-notice success"><p>' . esc_html__('Settings saved successfully!', 'tg-gdpr-cookie-consent') . '</p></div>';
    $settings = get_option('tg_gdpr_settings');
}

?>

<div class="wrap tg-gdpr-admin-wrap">
    
    <div class="tg-gdpr-admin-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p><?php _e('Configure your Cookiely cookie banner and settings.', 'tg-gdpr-cookie-consent'); ?></p>
    </div>
    
    <form method="post" action="" class="tg-gdpr-settings-form">
        <?php wp_nonce_field('tg_gdpr_settings_nonce'); ?>
        
        <!-- General Settings -->
        <div class="tg-gdpr-form-section">
            <h2><?php _e('General Settings', 'tg-gdpr-cookie-consent'); ?></h2>
            
            <div class="tg-gdpr-form-row">
                <label>
                    <input type="checkbox" name="tg_gdpr_settings[general][enabled]" value="1" <?php checked(isset($settings['general']['enabled']) && $settings['general']['enabled']); ?>>
                    <?php _e('Enable Cookie Consent Banner', 'tg-gdpr-cookie-consent'); ?>
                </label>
            </div>
            
            <div class="tg-gdpr-form-row">
                <label>
                    <input type="checkbox" name="tg_gdpr_settings[general][auto_block]" value="1" <?php checked(isset($settings['general']['auto_block']) && $settings['general']['auto_block']); ?>>
                    <?php _e('Auto-block scripts (recommended)', 'tg-gdpr-cookie-consent'); ?>
                </label>
                <p class="description"><?php _e('Automatically block tracking scripts until consent is given.', 'tg-gdpr-cookie-consent'); ?></p>
            </div>
        </div>
        
        <!-- Banner Appearance -->
        <div class="tg-gdpr-form-section">
            <h2><?php _e('Banner Appearance', 'tg-gdpr-cookie-consent'); ?></h2>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('Banner Position', 'tg-gdpr-cookie-consent'); ?></label>
                <select name="tg_gdpr_settings[banner][position]">
                    <option value="bottom" <?php selected($settings['banner']['position'] ?? 'bottom', 'bottom'); ?>><?php _e('Bottom', 'tg-gdpr-cookie-consent'); ?></option>
                    <option value="top" <?php selected($settings['banner']['position'] ?? 'bottom', 'top'); ?>><?php _e('Top', 'tg-gdpr-cookie-consent'); ?></option>
                    <option value="bottom-left" <?php selected($settings['banner']['position'] ?? 'bottom', 'bottom-left'); ?>><?php _e('Bottom Left', 'tg-gdpr-cookie-consent'); ?></option>
                    <option value="bottom-right" <?php selected($settings['banner']['position'] ?? 'bottom', 'bottom-right'); ?>><?php _e('Bottom Right', 'tg-gdpr-cookie-consent'); ?></option>
                </select>
            </div>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('Primary Color', 'tg-gdpr-cookie-consent'); ?></label>
                <input type="text" name="tg_gdpr_settings[banner][primary_color]" value="<?php echo esc_attr($settings['banner']['primary_color'] ?? '#1e40af'); ?>" class="tg-gdpr-color-picker">
            </div>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('Accent Color', 'tg-gdpr-cookie-consent'); ?></label>
                <input type="text" name="tg_gdpr_settings[banner][accent_color]" value="<?php echo esc_attr($settings['banner']['accent_color'] ?? '#3b82f6'); ?>" class="tg-gdpr-color-picker">
            </div>
        </div>
        
        <!-- Banner Content -->
        <div class="tg-gdpr-form-section">
            <h2><?php _e('Banner Content', 'tg-gdpr-cookie-consent'); ?></h2>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('Heading', 'tg-gdpr-cookie-consent'); ?></label>
                <input type="text" name="tg_gdpr_settings[content][heading]" value="<?php echo esc_attr($settings['content']['heading'] ?? __('We value your privacy', 'tg-gdpr-cookie-consent')); ?>">
            </div>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('Message', 'tg-gdpr-cookie-consent'); ?></label>
                <textarea name="tg_gdpr_settings[content][message]"><?php echo esc_textarea($settings['content']['message'] ?? __('We use cookies to enhance your browsing experience.', 'tg-gdpr-cookie-consent')); ?></textarea>
            </div>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('Privacy Policy URL', 'tg-gdpr-cookie-consent'); ?></label>
                <input type="url" name="tg_gdpr_settings[content][privacy_policy_url]" value="<?php echo esc_url($settings['content']['privacy_policy_url'] ?? get_privacy_policy_url()); ?>">
            </div>
        </div>
        
        <!-- Pro Features -->
        <div class="tg-gdpr-form-section">
            <h2><?php _e('Pro Features', 'tg-gdpr-cookie-consent'); ?> 
                <span style="background: #10b981; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 10px;">PRO</span>
            </h2>
            
            <div class="tg-gdpr-form-row">
                <label><?php _e('License Key', 'tg-gdpr-cookie-consent'); ?></label>
                <input type="text" name="tg_gdpr_settings[pro][license_key]" value="<?php echo esc_attr($settings['pro']['license_key'] ?? ''); ?>" placeholder="XXXX-XXXX-XXXX-XXXX">
                <p class="description"><?php _e('Enter your Cookiely Pro license key to unlock advanced features.', 'tg-gdpr-cookie-consent'); ?> <a href="https://techgenesis.com/tg-gdpr-pro/" target="_blank"><?php _e('Get Pro', 'tg-gdpr-cookie-consent'); ?></a></p>
            </div>
            
            <?php if ($is_pro_active) : ?>
                <div class="tg-gdpr-notice success">
                    <p><strong><?php _e('✓ Pro License Active', 'tg-gdpr-cookie-consent'); ?></strong></p>
                </div>
                
                <div class="tg-gdpr-form-row">
                    <label>
                        <input type="checkbox" name="tg_gdpr_settings[pro][consent_logging]" value="1" <?php checked(isset($settings['pro']['consent_logging']) && $settings['pro']['consent_logging']); ?>>
                        <?php _e('Enable Consent Logging', 'tg-gdpr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Record user consent for GDPR compliance.', 'tg-gdpr-cookie-consent'); ?></p>
                </div>
                
                <div class="tg-gdpr-form-row">
                    <label>
                        <input type="checkbox" name="tg_gdpr_settings[pro][auto_scan_enabled]" value="1" <?php checked(isset($settings['pro']['auto_scan_enabled']) && $settings['pro']['auto_scan_enabled']); ?>>
                        <?php _e('Enable Auto Cookie Scanner', 'tg-gdpr-cookie-consent'); ?>
                    </label>
                    <p class="description"><?php _e('Automatically detect and categorize cookies on your site.', 'tg-gdpr-cookie-consent'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tg-gdpr-submit">
            <button type="submit" name="tg_gdpr_save_settings" class="button button-primary button-large">
                <?php _e('Save Settings', 'tg-gdpr-cookie-consent'); ?>
            </button>
        </div>
        
    </form>
    
</div>
