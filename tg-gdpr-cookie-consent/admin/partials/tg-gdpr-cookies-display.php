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
$scanner = new TG_GDPR_Auto_Scanner();
$scan_report = get_option('tg_gdpr_last_cookie_scan_report', array());

?>

<div class="wrap tg-gdpr-admin-wrap">
    
    <div class="tg-gdpr-admin-header">
        <h1><?php _e('Cookie Management', 'tg-gdpr-cookie-consent'); ?></h1>
        <p><?php _e('Manage cookies detected on your website.', 'tg-gdpr-cookie-consent'); ?></p>
    </div>

    <?php if (isset($_GET['scan_status'], $_GET['scan_message'])) : ?>
        <div class="notice <?php echo $_GET['scan_status'] === 'success' ? 'notice-success' : 'notice-error'; ?> is-dismissible">
            <p><?php echo esc_html(wp_unslash($_GET['scan_message'])); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="tg-gdpr-notice">
        <p><strong><?php _e('Total Cookies:', 'tg-gdpr-cookie-consent'); ?></strong> <?php echo count($cookies); ?></p>
        <p><strong><?php _e('Scanner Status:', 'tg-gdpr-cookie-consent'); ?></strong> <?php echo esc_html($scanner->get_status()); ?></p>

        <?php if (!empty($scan_report['scanned_urls'])) : ?>
            <p><strong><?php _e('Last Scan Coverage:', 'tg-gdpr-cookie-consent'); ?></strong> <?php echo esc_html(sprintf(__('%1$d URLs scanned, %2$d cookies saved.', 'tg-gdpr-cookie-consent'), count($scan_report['scanned_urls']), intval($scan_report['cookies_saved'] ?? 0))); ?></p>
        <?php endif; ?>
    </div>

    <div class="tg-gdpr-settings-form" style="margin-bottom: 20px;">
        <?php if ($scanner->is_available()) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tg_gdpr_run_cookie_scan">
                <?php wp_nonce_field('tg_gdpr_run_cookie_scan'); ?>
                <button type="submit" class="button button-primary"><?php _e('Run Cookie Scan', 'tg-gdpr-cookie-consent'); ?></button>
                <p class="description"><?php _e('Scans the homepage, privacy policy, and key public pages for known trackers and inline cookie writes.', 'tg-gdpr-cookie-consent'); ?></p>
            </form>
        <?php else : ?>
            <p><strong><?php _e('Pro Feature:', 'tg-gdpr-cookie-consent'); ?></strong>
                <?php _e('Auto cookie scanner can detect cookies automatically.', 'tg-gdpr-cookie-consent'); ?>
                <a href="https://techgenesis.com/tg-gdpr-pro/" target="_blank"><?php _e('Upgrade to Pro', 'tg-gdpr-cookie-consent'); ?></a>
            </p>
        <?php endif; ?>
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
