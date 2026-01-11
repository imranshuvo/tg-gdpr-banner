# TG GDPR Cookie Consent - Complete Deployment Guide

## 🚀 Quick Deployment Checklist

- [ ] Deploy Laravel API to production server
- [ ] Configure database (MySQL/PostgreSQL)
- [ ] Set up SSL certificate for API
- [ ] Update API URL in WordPress plugin
- [ ] Upload WordPress plugin
- [ ] Create initial license keys
- [ ] Test complete flow
- [ ] Set up monitoring

---

## Part 1: Laravel API Deployment

### 1.1 Server Requirements

- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Nginx or Apache
- **SSL**: Required for production
- **Composer**: Latest version

### 1.2 Deploy to Production Server

```bash
# 1. Upload files to server
cd /var/www/
git clone your-repo.git tg-licensing-api
# OR upload via FTP/SFTP

# 2. Install dependencies
cd tg-licensing-api
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Configure environment
cp .env.example .env
nano .env
```

### 1.3 Environment Configuration

Edit `.env`:

```env
APP_NAME="TG GDPR Licensing API"
APP_ENV=production
APP_KEY=  # Run: php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tg_licensing
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database

# Redis (optional but recommended)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 1.4 Run Migrations

```bash
php artisan migrate --force

# Optionally seed test data
php artisan db:seed --class=LicenseSeeder
```

### 1.5 Nginx Configuration

Create `/etc/nginx/sites-available/tg-licensing-api`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.yourdomain.com;
    
    root /var/www/tg-licensing-api/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
ln -s /etc/nginx/sites-available/tg-licensing-api /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 1.6 SSL Certificate (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d api.yourdomain.com
```

### 1.7 Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize
```

### 1.8 Test API

```bash
curl -X POST https://api.yourdomain.com/api/v1/licenses/verify \
  -H "Content-Type: application/json" \
  -d '{"license_key":"test","domain":"test.com"}'
```

---

## Part 2: WordPress Plugin Deployment

### 2.1 Update API URL

Edit `includes/class-tg-gdpr-license-manager.php`:

```php
private $api_url = 'https://api.yourdomain.com/api/v1/licenses';
```

### 2.2 Package Plugin

```bash
cd /var/www/html/tg-gdpr-banner
zip -r tg-gdpr-cookie-consent.zip tg-gdpr-cookie-consent/ \
  -x "*.git*" "*.DS_Store" "node_modules/*"
```

### 2.3 Install on WordPress

**Option A: WordPress Admin**
1. Go to Plugins → Add New → Upload Plugin
2. Choose `tg-gdpr-cookie-consent.zip`
3. Click Install Now
4. Click Activate

**Option B: FTP/SSH**
```bash
# Upload to WordPress
scp tg-gdpr-cookie-consent.zip user@site.com:/tmp/
ssh user@site.com
cd /var/www/html/wp-content/plugins/
unzip /tmp/tg-gdpr-cookie-consent.zip
chown -R www-data:www-data tg-gdpr-cookie-consent
```

Then activate via WordPress admin.

### 2.4 Verify Installation

1. Check WordPress admin sidebar for "TG GDPR" menu
2. Navigate to TG GDPR → Settings
3. Navigate to TG GDPR → License

---

## Part 3: Create License Keys

### 3.1 Manual Creation (Database)

Connect to your database and run:

```sql
-- Create customer
INSERT INTO customers (name, email, company, created_at, updated_at) 
VALUES ('John Doe', 'john@example.com', 'Example Corp', NOW(), NOW());

-- Create license (get customer_id from above)
INSERT INTO licenses (customer_id, license_key, plan, max_activations, expires_at, status, created_at, updated_at)
VALUES (
    1, 
    'ABCD-EFGH-IJKL-MNOP',
    'single',
    1,
    DATE_ADD(NOW(), INTERVAL 1 YEAR),
    'active',
    NOW(),
    NOW()
);
```

### 3.2 Using Laravel Seeder

```bash
cd /var/www/tg-licensing-api
php artisan db:seed --class=LicenseSeeder
```

This creates 3 test licenses. Check output for license keys.

### 3.3 Programmatic Creation (Future)

Create an admin panel or CLI command:

```bash
php artisan make:command CreateLicense
```

---

## Part 4: Complete Integration Test

### 4.1 Test License Activation

1. **WordPress Admin**:
   - Go to TG GDPR → License
   - Enter a valid license key
   - Click "Activate License"
   - Verify success message

2. **Check Database**:
```sql
-- Laravel database
SELECT * FROM activations WHERE domain = 'your-wordpress-site.com';
```

3. **Check WordPress**:
   - Verify license shows as "Active"
   - Verify plan is displayed correctly
   - Verify expiry date is shown

### 4.2 Test Heartbeat Verification

```bash
# WordPress server
wp cron event run tg_gdpr_daily_license_check --path=/var/www/html

# Check if license is still active
wp option get tg_gdpr_license_status --path=/var/www/html
```

### 4.3 Test Pro Features

1. Go to TG GDPR → Cookies
2. Look for "Auto Scan" button (should be available with Pro license)
3. Test the feature

### 4.4 Test Deactivation

1. Go to TG GDPR → License
2. Click "Deactivate License"
3. Verify license status changes to "Inactive"
4. Verify Pro features are no longer accessible

### 4.5 Test Activation Limits

Try activating the same license key on multiple sites:
- Single plan: Should allow 1 activation only
- 3-Sites plan: Should allow 3 activations
- 10-Sites plan: Should allow 10 activations

---

## Part 5: Monitoring & Maintenance

### 5.1 Laravel Logging

Check logs:
```bash
tail -f /var/www/tg-licensing-api/storage/logs/laravel.log
```

### 5.2 Monitor API Requests

Track activation requests:
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as activations
FROM activations
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### 5.3 Monitor License Expiry

Find expiring licenses:
```sql
SELECT 
    c.email,
    l.license_key,
    l.plan,
    l.expires_at,
    DATEDIFF(l.expires_at, NOW()) as days_remaining
FROM licenses l
JOIN customers c ON l.customer_id = c.id
WHERE l.status = 'active'
  AND l.expires_at < DATE_ADD(NOW(), INTERVAL 30 DAY)
ORDER BY l.expires_at ASC;
```

### 5.4 Automated Backups

```bash
# Database backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u user -p tg_licensing > /backups/tg_licensing_$DATE.sql
find /backups -name "tg_licensing_*.sql" -mtime +30 -delete
```

Add to crontab:
```bash
0 2 * * * /root/backup-licensing-db.sh
```

### 5.5 Health Check Endpoint

Add to Laravel routes:

```php
Route::get('/health', function() {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

Monitor with uptime service (e.g., UptimeRobot, Pingdom).

---

## Part 6: Troubleshooting

### Issue: License activation fails

**Check**:
1. API URL is correct in WordPress plugin
2. API is accessible (test with curl)
3. SSL certificate is valid
4. License key exists in database
5. License hasn't expired
6. Activation limit not reached

**Debug**:
```bash
# WordPress
wp option get tg_gdpr_license_key --path=/var/www/html

# Laravel
tail -f storage/logs/laravel.log
```

### Issue: Heartbeat verification fails

**Check**:
1. WordPress cron is running
2. API is accessible from WordPress server
3. License key is stored in WordPress options

**Debug**:
```bash
# List cron events
wp cron event list --path=/var/www/html

# Run manually
wp cron event run tg_gdpr_daily_license_check --path=/var/www/html
```

### Issue: Pro features not working

**Check**:
1. License is active (`tg_gdpr_license_status` = 'active')
2. License plan is correct in license data
3. Feature check logic in Pro feature classes

**Debug**:
```php
// In WordPress
$lm = new TG_GDPR_License_Manager();
var_dump($lm->is_license_active());
var_dump($lm->get_license_plan());
var_dump($lm->has_feature('auto_scanner'));
```

---

## Part 7: Security Hardening

### 7.1 Rate Limiting

Add to Laravel routes:

```php
Route::middleware('throttle:60,1')->prefix('v1')->group(function () {
    // License routes
});
```

### 7.2 IP Whitelisting (Optional)

For admin endpoints only:

```php
// In middleware
if (!in_array($request->ip(), config('app.allowed_ips'))) {
    abort(403);
}
```

### 7.3 API Key Authentication (Future Enhancement)

Add API key validation for added security.

---

## Part 8: Performance Optimization

### 8.1 Enable Redis Caching

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### 8.2 Enable OPcache

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### 8.3 Database Indexing

Indexes are already added in migrations:
- `licenses.license_key` (unique)
- `customers.email` (unique)
- `activations(license_id, domain)` (unique composite)

---

## ✅ Deployment Verification Checklist

- [ ] Laravel API accessible via HTTPS
- [ ] SSL certificate valid and auto-renewing
- [ ] Database migrations completed
- [ ] Test license keys created
- [ ] WordPress plugin installed and activated
- [ ] License activation works from WordPress
- [ ] Heartbeat verification works
- [ ] Pro features accessible with active license
- [ ] License deactivation works
- [ ] Activation limits enforced
- [ ] Logs being written
- [ ] Backups configured
- [ ] Monitoring/health checks setup
- [ ] Error handling tested
- [ ] Documentation updated with production URLs

---

**Deployment Status**: ✅ Ready for Production  
**Date**: October 8, 2025  
**Version**: 1.0.0
