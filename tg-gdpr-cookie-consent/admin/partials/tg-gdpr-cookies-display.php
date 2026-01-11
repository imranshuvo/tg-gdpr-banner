<?php
/**
 * Cookies Management Page
 *
 * @package TG_GDPR_Cookie_Consent
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookie_manager = new TG_GDPR_Cookie_Manager();
$cookies = $cookie_manager->get_cookies();

?>

<div class="wrap tg-gdpr-admin-wrap">
    
    <div class="tg-gdpr-admin-header">
        <h1><?php _e('Cookie Management', 'tg-gdpr-cookie-consent'); ?></h1>
        <p><?php _e('Manage cookies detected on your website.', 'tg-gdpr-cookie-consent'); ?></p>
    </div>
    
    <div class="tg-gdpr-notice">
        <p><strong><?php _e('Total Cookies:', 'tg-gdpr-cookie-consent'); ?></strong> <?php echo count($cookies); ?></p>
    </div>
    
    <div class="tg-gdpr-settings-form">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Cookie Name', 'tg-gdpr-cookie-consent'); ?></th>
                    <th><?php _e('Category', 'tg-gdpr-cookie-consent'); ?></th>
                    <th><?php _e('Duration', 'tg-gdpr-cookie-consent'); ?></th>
                    <th><?php _e('Description', 'tg-gdpr-cookie-consent'); ?></th>
                    <th><?php _e('Script Pattern', 'tg-gdpr-cookie-consent'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cookies)) : ?>
                    <?php foreach ($cookies as $cookie) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($cookie->cookie_name); ?></strong></td>
                        <td>
                            <span class="tg-gdpr-category-badge category-<?php echo esc_attr($cookie->category); ?>">
                                <?php echo esc_html(ucfirst($cookie->category)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($cookie->duration); ?></td>
                        <td><?php echo esc_html($cookie->description); ?></td>
                        <td><code><?php echo esc_html($cookie->script_pattern); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:40px;">
                            <?php _e('No cookies found. Default cookies have been added.', 'tg-gdpr-cookie-consent'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">
            <strong><?php _e('Pro Feature:', 'tg-gdpr-cookie-consent'); ?></strong> 
            <?php _e('Auto cookie scanner can detect cookies automatically.', 'tg-gdpr-cookie-consent'); ?>
            <a href="https://techgenesis.com/tg-gdpr-pro/" target="_blank"><?php _e('Upgrade to Pro', 'tg-gdpr-cookie-consent'); ?></a>
        </p>
    </div>
    
</div>

<style>
.tg-gdpr-category-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.category-necessary {
    background: #dbeafe;
    color: #1e40af;
}

.category-functional {
    background: #ddd6fe;
    color: #6d28d9;
}

.category-analytics {
    background: #fef3c7;
    color: #d97706;
}

.category-marketing {
    background: #fce7f3;
    color: #be185d;
}
</style>
