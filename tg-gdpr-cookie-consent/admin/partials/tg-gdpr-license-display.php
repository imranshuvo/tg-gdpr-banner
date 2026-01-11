<?php
/**
 * License Settings Page
 *
 * @package    TG_GDPR_Cookie_Consent
 * @subpackage TG_GDPR_Cookie_Consent/admin/partials
 */

// Get license manager
$license_manager = new TG_GDPR_License_Manager();
$license_key = $license_manager->get_license_key();
$license_status = $license_manager->get_license_status();
$license_data = $license_manager->get_license_data();

// Handle form submissions
if (isset($_POST['tg_gdpr_activate_license']) && check_admin_referer('tg_gdpr_license_action')) {
    $new_license_key = sanitize_text_field($_POST['license_key']);
    $result = $license_manager->activate_license($new_license_key);
    
    if ($result['success']) {
        echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
    }
    
    // Refresh data
    $license_key = $license_manager->get_license_key();
    $license_status = $license_manager->get_license_status();
    $license_data = $license_manager->get_license_data();
}

if (isset($_POST['tg_gdpr_deactivate_license']) && check_admin_referer('tg_gdpr_license_action')) {
    $result = $license_manager->deactivate_license();
    echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
    
    // Refresh data
    $license_key = '';
    $license_status = 'inactive';
    $license_data = array();
}

if (isset($_POST['tg_gdpr_verify_license']) && check_admin_referer('tg_gdpr_license_action')) {
    $result = $license_manager->verify_license();
    
    if ($result['success']) {
        echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
    }
    
    // Refresh data
    $license_status = $license_manager->get_license_status();
    $license_data = $license_manager->get_license_data();
}

$is_active = $license_status === 'active';
$plan_name = !empty($license_data['plan']) ? ucfirst(str_replace('-', ' ', $license_data['plan'])) : 'Free';
?>

<div class="wrap tg-gdpr-license-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="tg-gdpr-license-container">
        <!-- License Status Card -->
        <div class="tg-gdpr-card">
            <h2>License Status</h2>
            <div class="tg-gdpr-license-status">
                <div class="status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                    <span class="dashicons <?php echo $is_active ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                </div>
                
                <?php if ($is_active): ?>
                    <div class="license-info">
                        <p><strong>Plan:</strong> <?php echo esc_html($plan_name); ?></p>
                        <p><strong>Expires:</strong> <?php echo esc_html($license_manager->get_expiry_date_formatted()); ?></p>
                        
                        <?php
                        $days_until_expiry = $license_manager->get_days_until_expiry();
                        if ($days_until_expiry !== null):
                        ?>
                            <p>
                                <strong>Days Remaining:</strong> 
                                <span class="<?php echo $license_manager->is_expiring_soon() ? 'expiring-soon' : ''; ?>">
                                    <?php echo esc_html($days_until_expiry); ?> days
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="description">You are using the free version with limited features.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- License Activation Form -->
        <div class="tg-gdpr-card">
            <h2><?php echo $is_active ? 'Manage License' : 'Activate License'; ?></h2>
            
            <?php if (!$is_active): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('tg_gdpr_license_action'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="license_key">License Key</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="license_key" 
                                       name="license_key" 
                                       value="<?php echo esc_attr($license_key); ?>" 
                                       class="regular-text"
                                       placeholder="XXXX-XXXX-XXXX-XXXX"
                                       pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                                       required>
                                <p class="description">Enter your license key to activate Pro features.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               name="tg_gdpr_activate_license" 
                               id="submit" 
                               class="button button-primary" 
                               value="Activate License">
                    </p>
                </form>
                
                <div class="tg-gdpr-purchase-box">
                    <h3>Don't have a license?</h3>
                    <p>Upgrade to Pro and unlock powerful features:</p>
                    <ul>
                        <li>✓ Auto Cookie Scanner</li>
                        <li>✓ Analytics Dashboard</li>
                        <li>✓ Advanced Consent Logging</li>
                        <li>✓ Priority Support</li>
                        <li>✓ Custom Branding</li>
                    </ul>
                    <a href="https://your-domain.com/pricing" class="button button-primary" target="_blank">
                        Purchase License
                    </a>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <?php wp_nonce_field('tg_gdpr_license_action'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label>License Key</label>
                            </th>
                            <td>
                                <code class="license-key-display"><?php echo esc_html($license_key); ?></code>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Domain</label>
                            </th>
                            <td>
                                <code><?php echo esc_html(parse_url(get_site_url(), PHP_URL_HOST)); ?></code>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               name="tg_gdpr_verify_license" 
                               class="button" 
                               value="Verify License">
                        <input type="submit" 
                               name="tg_gdpr_deactivate_license" 
                               class="button" 
                               value="Deactivate License"
                               onclick="return confirm('Are you sure you want to deactivate this license?');">
                    </p>
                </form>
            <?php endif; ?>
        </div>

        <!-- Features Comparison -->
        <div class="tg-gdpr-card">
            <h2>Feature Comparison</h2>
            <table class="tg-gdpr-features-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Free</th>
                        <th>Pro</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>GDPR-Compliant Cookie Banner</td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Script Blocking</td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Consent Logging (Basic)</td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Auto Cookie Scanner</td>
                        <td><span class="dashicons dashicons-no"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Analytics Dashboard</td>
                        <td><span class="dashicons dashicons-no"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Advanced Consent Logging</td>
                        <td><span class="dashicons dashicons-no"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Priority Support</td>
                        <td><span class="dashicons dashicons-no"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                    <tr>
                        <td>Custom Branding</td>
                        <td><span class="dashicons dashicons-no"></span></td>
                        <td><span class="dashicons dashicons-yes"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.tg-gdpr-license-page .tg-gdpr-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.tg-gdpr-license-page .tg-gdpr-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.tg-gdpr-license-status {
    padding: 20px 0;
}

.tg-gdpr-license-status .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 20px;
}

.tg-gdpr-license-status .status-badge.active {
    background: #d4edda;
    color: #155724;
}

.tg-gdpr-license-status .status-badge.inactive {
    background: #fff3cd;
    color: #856404;
}

.tg-gdpr-license-status .license-info {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
}

.tg-gdpr-license-status .license-info p {
    margin: 5px 0;
}

.tg-gdpr-license-status .expiring-soon {
    color: #d63638;
    font-weight: 600;
}

.license-key-display {
    background: #f0f0f1;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    letter-spacing: 1px;
}

.tg-gdpr-purchase-box {
    background: #f0f6fc;
    border: 1px solid #0073aa;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.tg-gdpr-purchase-box h3 {
    margin-top: 0;
    color: #0073aa;
}

.tg-gdpr-purchase-box ul {
    list-style: none;
    padding-left: 0;
}

.tg-gdpr-purchase-box ul li {
    padding: 5px 0;
    color: #155724;
}

.tg-gdpr-features-table {
    width: 100%;
    border-collapse: collapse;
}

.tg-gdpr-features-table th,
.tg-gdpr-features-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.tg-gdpr-features-table thead th {
    background: #f0f0f1;
    font-weight: 600;
}

.tg-gdpr-features-table td:nth-child(2),
.tg-gdpr-features-table td:nth-child(3),
.tg-gdpr-features-table th:nth-child(2),
.tg-gdpr-features-table th:nth-child(3) {
    text-align: center;
    width: 100px;
}

.tg-gdpr-features-table .dashicons-yes {
    color: #46b450;
}

.tg-gdpr-features-table .dashicons-no {
    color: #dc3232;
}
</style>
