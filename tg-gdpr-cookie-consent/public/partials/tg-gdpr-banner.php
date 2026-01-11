<?php
/**
 * Cookie Banner Template - CookieYes-inspired design
 *
 * @package TG_GDPR_Cookie_Consent
 * 
 * Available variables:
 * @var array $banner_settings
 * @var array $content
 * @var array $categories
 * @var array $cookies_by_category
 * @var string $position
 * @var string $layout
 */

if (!defined('ABSPATH')) {
    exit;
}

$heading = $content['heading'] ?? __('We value your privacy', 'tg-gdpr-cookie-consent');
$message = $content['message'] ?? __('We use cookies to enhance your browsing experience.', 'tg-gdpr-cookie-consent');
$accept_all = $content['accept_all_text'] ?? __('Accept All', 'tg-gdpr-cookie-consent');
$reject_all = $content['reject_all_text'] ?? __('Reject All', 'tg-gdpr-cookie-consent');
$settings_text = $content['settings_text'] ?? __('Cookie Settings', 'tg-gdpr-cookie-consent');
$privacy_text = $content['privacy_policy_text'] ?? __('Privacy Policy', 'tg-gdpr-cookie-consent');
$privacy_url = $content['privacy_policy_url'] ?? get_privacy_policy_url();
?>

<!-- TG GDPR Cookie Consent Banner -->
<div id="tg-gdpr-banner" class="tg-gdpr-banner tg-gdpr-<?php echo esc_attr($position); ?> tg-gdpr-<?php echo esc_attr($layout); ?>" role="dialog" aria-label="<?php esc_attr_e('Cookie Consent', 'tg-gdpr-cookie-consent'); ?>" aria-modal="true">
    
    <!-- Banner Backdrop -->
    <div class="tg-gdpr-backdrop"></div>
    
    <!-- Banner Container -->
    <div class="tg-gdpr-container">
        
        <!-- Main View -->
        <div class="tg-gdpr-main-view" id="tg-gdpr-main-view">
            
            <div class="tg-gdpr-content">
                
                <?php if (!empty($heading)) : ?>
                <h2 class="tg-gdpr-heading"><?php echo esc_html($heading); ?></h2>
                <?php endif; ?>
                
                <p class="tg-gdpr-message"><?php echo wp_kses_post($message); ?></p>
                
                <?php if (!empty($privacy_url)) : ?>
                <a href="<?php echo esc_url($privacy_url); ?>" class="tg-gdpr-privacy-link" target="_blank" rel="noopener">
                    <?php echo esc_html($privacy_text); ?>
                </a>
                <?php endif; ?>
                
            </div>
            
            <div class="tg-gdpr-actions">
                <button type="button" class="tg-gdpr-btn tg-gdpr-btn-primary" id="tg-gdpr-accept-all">
                    <?php echo esc_html($accept_all); ?>
                </button>
                <button type="button" class="tg-gdpr-btn tg-gdpr-btn-secondary" id="tg-gdpr-reject-all">
                    <?php echo esc_html($reject_all); ?>
                </button>
                <button type="button" class="tg-gdpr-btn tg-gdpr-btn-link" id="tg-gdpr-settings-btn">
                    <?php echo esc_html($settings_text); ?>
                </button>
            </div>
            
        </div>
        
        <!-- Settings View -->
        <div class="tg-gdpr-settings-view" id="tg-gdpr-settings-view" style="display:none;">
            
            <div class="tg-gdpr-settings-header">
                <button type="button" class="tg-gdpr-back-btn" id="tg-gdpr-back-btn" aria-label="<?php esc_attr_e('Back', 'tg-gdpr-cookie-consent'); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <h3 class="tg-gdpr-settings-title"><?php esc_html_e('Cookie Settings', 'tg-gdpr-cookie-consent'); ?></h3>
            </div>
            
            <div class="tg-gdpr-settings-content">
                
                <?php
                // Get enabled categories
                $enabled_categories = array('necessary', 'functional', 'analytics', 'marketing');
                
                foreach ($enabled_categories as $category) :
                    if (!$this->is_category_enabled($category)) {
                        continue;
                    }
                    
                    $title = $this->get_category_title($category);
                    $description = $this->get_category_description($category);
                    $is_locked = $this->is_category_locked($category);
                    $category_cookies = $cookies_by_category[$category] ?? array();
                ?>
                
                <div class="tg-gdpr-category">
                    
                    <div class="tg-gdpr-category-header">
                        <div class="tg-gdpr-category-title-wrap">
                            <h4 class="tg-gdpr-category-title"><?php echo esc_html($title); ?></h4>
                            <?php if ($is_locked) : ?>
                            <span class="tg-gdpr-always-active"><?php esc_html_e('Always Active', 'tg-gdpr-cookie-consent'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <label class="tg-gdpr-toggle">
                            <input 
                                type="checkbox" 
                                name="tg_gdpr_category[]" 
                                value="<?php echo esc_attr($category); ?>" 
                                class="tg-gdpr-category-checkbox"
                                data-category="<?php echo esc_attr($category); ?>"
                                <?php checked($is_locked); ?>
                                <?php disabled($is_locked); ?>
                            >
                            <span class="tg-gdpr-toggle-slider"></span>
                        </label>
                    </div>
                    
                    <?php if (!empty($description)) : ?>
                    <p class="tg-gdpr-category-description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($category_cookies)) : ?>
                    <div class="tg-gdpr-cookies-list">
                        <button type="button" class="tg-gdpr-cookies-toggle" data-category="<?php echo esc_attr($category); ?>">
                            <?php esc_html_e('View Cookies', 'tg-gdpr-cookie-consent'); ?>
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        
                        <div class="tg-gdpr-cookies-details" data-category="<?php echo esc_attr($category); ?>" style="display:none;">
                            <table class="tg-gdpr-cookies-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Name', 'tg-gdpr-cookie-consent'); ?></th>
                                        <th><?php esc_html_e('Duration', 'tg-gdpr-cookie-consent'); ?></th>
                                        <th><?php esc_html_e('Description', 'tg-gdpr-cookie-consent'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_cookies as $cookie) : ?>
                                    <tr>
                                        <td><?php echo esc_html($cookie['name']); ?></td>
                                        <td><?php echo esc_html($cookie['duration']); ?></td>
                                        <td><?php echo esc_html($cookie['description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php endforeach; ?>
                
            </div>
            
            <div class="tg-gdpr-settings-actions">
                <button type="button" class="tg-gdpr-btn tg-gdpr-btn-primary" id="tg-gdpr-save-settings">
                    <?php esc_html_e('Save Settings', 'tg-gdpr-cookie-consent'); ?>
                </button>
                <button type="button" class="tg-gdpr-btn tg-gdpr-btn-secondary" id="tg-gdpr-accept-all-settings">
                    <?php echo esc_html($accept_all); ?>
                </button>
            </div>
            
        </div>
        
        <!-- Close Button -->
        <button type="button" class="tg-gdpr-close" id="tg-gdpr-close" aria-label="<?php esc_attr_e('Close', 'tg-gdpr-cookie-consent'); ?>">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>
        
    </div>
    
</div>

<!-- Revisit Consent Button (appears after consent is given) -->
<?php
$advanced = $this->settings['advanced'] ?? array();
if (isset($advanced['show_revisit_button']) && $advanced['show_revisit_button']) :
    $revisit_position = $advanced['revisit_button_position'] ?? 'left';
?>
<button type="button" id="tg-gdpr-revisit" class="tg-gdpr-revisit tg-gdpr-revisit-<?php echo esc_attr($revisit_position); ?>" style="display:none;" aria-label="<?php esc_attr_e('Cookie Settings', 'tg-gdpr-cookie-consent'); ?>">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
    </svg>
</button>
<?php endif; ?>
