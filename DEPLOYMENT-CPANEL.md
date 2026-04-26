# Cookiely — cPanel Deployment Guide

This is the cPanel-specific deployment plan for the Laravel app at
[tg-gdpr-licensing-api/](tg-gdpr-licensing-api/). For Ubuntu/Nginx VPS deployment, see
[DEPLOYMENT-GUIDE.md](DEPLOYMENT-GUIDE.md) — that one does **not** apply on shared cPanel hosting.

---

## 0. Architecture on cPanel

```
/home/<cpuser>/                     ← cPanel home dir
├── cookiely-app/                   ← Laravel app root (NOT web-accessible)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── lang/
│   ├── public/                     ← document root for cookiely.site
│   ├── resources/
│   ├── routes/
│   ├── storage/                    ← keep across deploys
│   │   ├── app/                    (user uploads — preserved)
│   │   ├── framework/              (cache — server-managed)
│   │   └── logs/                   (log files — preserved)
│   ├── vendor/                     (uploaded — composer prod deps)
│   ├── .env                        (created once on server, never deployed)
│   └── …
└── public_html/                    ← (unused or symlinked, see §3.4)
```

**Why outside `public_html`:** any file in `public_html/` is web-reachable. Your `.env`,
`storage/`, `vendor/`, and `app/` MUST NOT be web-reachable. cPanel's "Domains" tool lets you
point a domain's document root at a folder outside `public_html` — that's what we'll do.

If your cPanel plan does NOT allow re-pointing the document root, jump to §3.4 (symlink fallback).

---

## 1. One-time server prep (~20 min)

### 1.1 PHP version

cPanel → **MultiPHP Manager** → set `cookiely.site` to **PHP 8.2 or higher**.

cPanel → **Select PHP Version** → confirm these extensions are enabled:

```
bcmath  ctype  curl  fileinfo  json  mbstring  openssl
pdo_mysql  tokenizer  xml  zip  intl  gd
```

Set the loader to `PHP-FPM` if available (faster than CGI).

### 1.2 Create the database

cPanel → **MySQL® Databases**:

1. **Create New Database**: e.g. `cookiely_app` (cPanel will prefix it with your username, e.g. `mycpuser_cookiely_app`).
2. **Add New User**: e.g. `cookiely_dbuser` with a strong password — store it in a password manager now.
3. **Add User To Database** → grant **ALL PRIVILEGES**.

Note the **full prefixed names** (e.g. `mycpuser_cookiely_app`, `mycpuser_cookiely_dbuser`) — these go into `.env`.

### 1.3 Create the email account (for transactional mail)

cPanel → **Email Accounts** → create `no-reply@cookiely.site`. Save the password.

Then cPanel → **Email Accounts** → click **Connect Devices** on that account → grab the SMTP host, port, encryption — paste into `.env` later.

(Or skip and use Postmark / Resend / SES — recommended for higher deliverability than cPanel SMTP.)

### 1.4 Enable SSH

cPanel → **SSH Access** → **Manage SSH Keys** → **Generate a New Key** (or import your existing public key) → **Authorize**.

Test from your laptop:

```bash
ssh -p <port> <cpuser>@cookiely.site
```

(Many cPanel hosts use a non-22 port. Check cPanel home or ask the host.)

### 1.5 Confirm composer & node availability on the server (optional)

```bash
ssh <cpuser>@cookiely.site
which composer       # if missing: download composer.phar to ~/bin/
which php            # confirm PHP 8.2+
php -v
node -v              # usually NOT available on shared cPanel
```

If `composer` is missing, install it user-local:

```bash
mkdir -p ~/bin && cd ~/bin
curl -sS https://getcomposer.org/installer | php
mv composer.phar composer && chmod +x composer
echo 'export PATH=~/bin:$PATH' >> ~/.bashrc
source ~/.bashrc
```

If `node` is missing, that's expected on shared hosting — we'll build assets on your laptop and ship the `public/build/` output.

### 1.6 Create the app directory

```bash
ssh <cpuser>@cookiely.site
mkdir -p ~/cookiely-app
chmod 750 ~/cookiely-app
exit
```

### 1.7 Point the domain at Laravel's `public/`

cPanel → **Domains** → find `cookiely.site` → **Manage** → set **Document Root** to:

```
cookiely-app/public
```

(The path is relative to your home directory. cPanel will not let you click outside `public_html`
in some skins — type the path into the field directly.)

If your cPanel doesn't allow this, see §3.4 fallback.

### 1.8 Enable AutoSSL

cPanel → **SSL/TLS Status** → check `cookiely.site` and `www.cookiely.site` → **Run AutoSSL**.

cPanel → **Domains** → toggle **Force HTTPS Redirect** ON for `cookiely.site`.

---

## 2. First deploy

### 2.1 On your laptop — build the artifact

From the repo root:

```bash
cd tg-gdpr-licensing-api

# Production composer deps (no dev/test packages)
composer install --no-dev --optimize-autoloader --prefer-dist

# Production frontend bundle (CSS + JS to public/build/)
npm ci
npm run build
```

### 2.2 Push the code to the server

Use the included script (recommended) — see §4 — or do it manually:

```bash
rsync -avz --delete \
  --exclude '.git/' \
  --exclude 'node_modules/' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude '/storage/app/' \
  --exclude '/storage/logs/' \
  --exclude '/storage/framework/' \
  --exclude 'tests/' \
  --exclude 'phpunit.xml' \
  --exclude '.DS_Store' \
  -e "ssh -p <port>" \
  ./ <cpuser>@cookiely.site:cookiely-app/
```

### 2.3 Create `.env` on the server (one time only)

```bash
ssh <cpuser>@cookiely.site
cd ~/cookiely-app
cp .env.cpanel.example .env
nano .env       # fill in DB creds, APP_URL=https://cookiely.site, mail creds, etc.
chmod 600 .env

php artisan key:generate
```

Walk through every line of `.env`. Critical values:

| Key                | Value                                                                |
| ------------------ | -------------------------------------------------------------------- |
| `APP_NAME`         | `Cookiely`                                                           |
| `APP_ENV`          | `production`                                                         |
| `APP_DEBUG`        | `false` ← **never `true` in prod**                                   |
| `APP_URL`          | `https://cookiely.site`                                              |
| `APP_LOCALE`       | `en` (the per-visitor locale is set by middleware)                   |
| `LOG_CHANNEL`      | `daily` (rotates, won't blow up your inode quota)                    |
| `DB_CONNECTION`    | `mysql`                                                              |
| `DB_HOST`          | `localhost`                                                          |
| `DB_DATABASE`      | `<cpuser>_cookiely_app` (the prefixed name from §1.2)                |
| `DB_USERNAME`      | `<cpuser>_cookiely_dbuser`                                           |
| `DB_PASSWORD`      | the password from §1.2                                               |
| `SESSION_DRIVER`   | `database`                                                           |
| `CACHE_STORE`      | `database`                                                           |
| `QUEUE_CONNECTION` | `database`                                                           |
| `MAIL_*`           | from §1.3 or your external provider                                  |

### 2.4 Initialize the app

Still SSH'd in:

```bash
cd ~/cookiely-app

# Storage subfolders + writable perms
mkdir -p storage/{app,framework/{cache/data,sessions,testing,views},logs}
chmod -R 775 storage bootstrap/cache

# DB schema
php artisan migrate --force

# Cache & session tables (if you didn't run all migrations above, run these explicitly)
# php artisan session:table
# php artisan cache:table
# php artisan queue:table
# php artisan migrate --force

# Public-storage symlink (so user uploads served from public/storage)
php artisan storage:link

# Production caches (do this LAST, after .env is final)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

If `storage:link` fails with "symlink() has been disabled" (some cPanel hosts disable it), do
it manually:

```bash
ln -s ~/cookiely-app/storage/app/public ~/cookiely-app/public/storage
```

### 2.5 Configure the cron for the Laravel scheduler

cPanel → **Cron Jobs** → add:

```
* * * * * cd /home/<cpuser>/cookiely-app && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

(Confirm the exact PHP path with `which php` over SSH — on some hosts it's
`/opt/cpanel/ea-php82/root/usr/bin/php` instead.)

The scheduler is what fires queued jobs, license expiry checks, etc.

### 2.6 Bootstrap the first super admin

Once the app is running, you need at least one admin user to log in to
`/admin` and configure Stripe keys, plans, etc.

```bash
cd ~/cookiely-app
php artisan app:create-admin
```

You'll be prompted for an email; the command generates a random password and
prints it once. Or pass everything non-interactively:

```bash
php artisan app:create-admin imran@cookiely.site \
  --name="Imran Khan" \
  --password='<your-secret>'
```

If the email already exists as a `customer`, the command offers to **promote**
that user to admin (preserving their existing Customer linkage). If they're
already an admin, it's a no-op.

Other useful CLI commands:

```bash
# Create a customer outside the registration form (atomic User + Customer):
php artisan app:create-customer alice@example.com --name="Alice" --company="Acme Ltd"

# List users (filterable by role):
php artisan app:list-users
php artisan app:list-users --role=admin
php artisan app:list-users --role=customer
```

**Terminology note**: today the role column has two values, `admin` (=
super admin, has access to `/admin/*` panel) and `customer`. The planned
post-launch refactor renames `admin → super_admin` and adds a per-site
`owner|admin` pivot for multi-admin-per-site, but the MVP single-tier
model is what's deployed.

### 2.7 Verify

Open `https://cookiely.site/` — landing page should render with the locale switcher in the nav.

Smoke tests over SSH:

```bash
cd ~/cookiely-app
php artisan about | head -30          # shows Laravel version, env, drivers
php artisan migrate:status            # shows all migrations RAN
php artisan route:list --path=api     # shows your API routes
php artisan app:list-users --role=admin   # confirms your super admin is in
tail -f storage/logs/laravel.log      # leave running while you click around
```

Then sign in at `https://cookiely.site/login` with the admin you just
created and walk through:

1. `/admin/settings/payments` — paste Stripe live (or test) keys
2. `/admin/plans` — paste matching Stripe Price IDs onto each plan
3. Stripe Dashboard → Webhooks → add endpoint `https://cookiely.site/webhooks/payments/stripe`
   with the secret you also entered into the admin UI

---

## 3. Subsequent deploys

### 3.1 The simple flow

From your laptop, after merging to `main`:

```bash
cd tg-gdpr-licensing-api
./scripts/cpanel-deploy.sh
```

What the script does (see [scripts/cpanel-deploy.sh](tg-gdpr-licensing-api/scripts/cpanel-deploy.sh) for the source of truth):

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. `php artisan down --render="errors::503"` on the server (maintenance mode)
4. `rsync` the built tree (excluding `.env`, user storage, logs, dev files)
5. Remote: `php artisan migrate --force`
6. Remote: `php artisan optimize` (re-caches config, routes, views)
7. Remote: re-run `chmod -R 775 storage bootstrap/cache`
8. Remote: `php artisan up`

Average deploy time: 30–90 seconds, depending on connection and how much changed.

### 3.2 Rolling back

The deploy is **not** atomic by default — files are rsynced in place. If a deploy goes bad:

```bash
ssh <cpuser>@cookiely.site
cd ~/cookiely-app
php artisan down                      # stop traffic
git -C <if you maintain a git checkout> reset --hard <previous-sha>
# OR re-run the deploy script from your laptop on the previous git commit:
#   git checkout <previous-sha> && ./scripts/cpanel-deploy.sh
php artisan up
```

For *real* atomic rollback you'd need a `releases/` + symlink-current scheme — see §6.1 if you
need that later.

### 3.3 Database migrations that fail mid-deploy

```bash
php artisan migrate:rollback --step=1
# fix the migration
# redeploy
```

If a migration partially applied and rollback can't undo it, restore from the latest backup
(see §5.1) and re-run.

### 3.4 Fallback: cPanel won't let you change document root

Some discount cPanel plans pin the document root to `~/public_html`. In that case:

```bash
# After §1.6, instead of repointing the domain:
cd ~
rm -rf public_html                                   # only if empty / safe
ln -s ~/cookiely-app/public ~/public_html
```

If symlinks aren't allowed either, use the "two-file shim" pattern: copy the contents of
`~/cookiely-app/public/` into `~/public_html/`, then edit `~/public_html/index.php` to require
`__DIR__.'/../cookiely-app/vendor/autoload.php'` and
`__DIR__.'/../cookiely-app/bootstrap/app.php'` instead of the relative `../`. The deploy script
has a `PUBLIC_HTML_SHIM=1` mode for this — read its top comment.

### 3.5 No-SSH fallback (zip upload)

If your cPanel plan has no SSH access at all:

1. On your laptop: `composer install --no-dev --optimize-autoloader && npm run build`
2. Zip the whole `tg-gdpr-licensing-api/` directory (excluding `.git`, `node_modules`, `.env*`, `tests`).
3. cPanel → **File Manager** → upload the zip into `~/cookiely-app/`, extract.
4. Use cPanel's **Cron Jobs** to run a one-shot post-deploy command, e.g.:
   ```
   cd /home/<cpuser>/cookiely-app && php artisan migrate --force && php artisan optimize && rm /tmp/run-deploy
   ```
   set to fire once and gate it on a `/tmp/run-deploy` flag file you create before deploys.
   This is hacky — push for SSH.

---

## 4. The deploy script

[scripts/cpanel-deploy.sh](tg-gdpr-licensing-api/scripts/cpanel-deploy.sh) drives the full local-build → upload → remote-tasks pipeline.

Configure it once via env vars (in your shell or a `.env.deploy` file you `source`):

```bash
export CPANEL_USER=mycpuser
export CPANEL_HOST=cookiely.site         # or the server IP
export CPANEL_PORT=22                    # or the host's SSH port
export REMOTE_APP_PATH=/home/mycpuser/cookiely-app
```

Then:

```bash
./scripts/cpanel-deploy.sh
```

Skip the asset rebuild (when you're only changing PHP):

```bash
SKIP_ASSETS=1 ./scripts/cpanel-deploy.sh
```

Skip migrations (e.g. config-only change):

```bash
SKIP_MIGRATE=1 ./scripts/cpanel-deploy.sh
```

Dry-run rsync (see what *would* upload without writing):

```bash
DRY_RUN=1 ./scripts/cpanel-deploy.sh
```

---

## 5. Backups & monitoring

### 5.1 Database backups

cPanel → **Cron Jobs** → daily 03:00 dump:

```bash
0 3 * * * mysqldump -u <cpuser>_cookiely_dbuser -p'<DB_PASSWORD>' <cpuser>_cookiely_app | gzip > /home/<cpuser>/backups/db-$(date +\%Y\%m\%d).sql.gz && find /home/<cpuser>/backups -name 'db-*.sql.gz' -mtime +30 -delete
```

(Note the escaped `\%` — cPanel's cron strips unescaped `%`.)

Create the `~/backups` dir first; chmod it `700`.

For offsite backups, cPanel → **Backup** lets you pull a full account archive on demand;
schedule one weekly to your laptop or to S3 via [rclone](https://rclone.org/).

### 5.2 Log rotation

`LOG_CHANNEL=daily` in `.env` already rotates Laravel logs daily and keeps 14. To be safe, also
add a monthly truncate cron:

```bash
0 4 1 * * find /home/<cpuser>/cookiely-app/storage/logs -name '*.log' -mtime +30 -delete
```

### 5.3 Uptime + health check

The `/up` endpoint is already wired (Laravel default health route, see [bootstrap/app.php](tg-gdpr-licensing-api/bootstrap/app.php)).

Point UptimeRobot / BetterStack / Pingdom at `https://cookiely.site/up` with a 5-minute interval
and alerts on non-200.

### 5.4 Error visibility

Two options, in order of preference:

- **Sentry** (recommended): `composer require sentry/sentry-laravel`, set `SENTRY_LARAVEL_DSN`
  in `.env`, free tier is 5k events/month — plenty for a small SaaS.
- **Plain log tail**: `tail -f ~/cookiely-app/storage/logs/laravel.log` over SSH when
  diagnosing.

---

## 6. Hardening (do these once)

### 6.1 Atomic releases (optional, when you're ready)

When the simple in-place rsync starts feeling risky, switch to a `releases/` scheme:

```
~/cookiely-releases/
  20260426143000/      ← new release
  20260426120000/      ← previous
~/cookiely-current     → symlink to active release
~/cookiely-shared/
  .env
  storage/
```

Each release is a fresh rsync target; deploy ends with `ln -snf ~/cookiely-releases/<new>
~/cookiely-current` and the document root points at `~/cookiely-current/public`. Rollback is one
symlink flip. The deploy script ships a `RELEASES_MODE=1` flag for this — leave it off until you
need it.

### 6.2 Secure file permissions

```bash
ssh <cpuser>@cookiely.site
cd ~/cookiely-app
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

### 6.3 `.htaccess` deny on sensitive dirs (defense in depth)

Laravel's default `public/.htaccess` already routes everything through `index.php`, so files
outside `public/` are unreachable via the document-root setup. Belt-and-braces: add this to
`~/.htaccess` (cPanel home dir):

```
<FilesMatch "\.(env|log|sql|sqlite)$">
    Require all denied
</FilesMatch>
```

### 6.4 Rate-limit the licensing API

Already handled by Laravel's `throttle:60,1` middleware on the API group. Confirm in
[routes/api.php](tg-gdpr-licensing-api/routes/api.php). For abusive IPs, cPanel → **IP Blocker**.

### 6.5 Disable `APP_DEBUG` in `.env`

It's already `false` per the template — verify after every deploy:

```bash
ssh <cpuser>@cookiely.site "grep '^APP_DEBUG' ~/cookiely-app/.env"
```

---

## 7. First-deploy checklist

- [ ] PHP 8.2+ selected for `cookiely.site`
- [ ] All required PHP extensions enabled
- [ ] MySQL DB + user created and linked
- [ ] SSH key authorized
- [ ] Composer reachable on server (or local-build flow chosen)
- [ ] `~/cookiely-app/` created
- [ ] Document root for `cookiely.site` → `cookiely-app/public`
- [ ] AutoSSL active, Force HTTPS on
- [ ] First `rsync` completed
- [ ] `.env` filled and `APP_KEY` generated
- [ ] `php artisan migrate --force` completed
- [ ] `php artisan storage:link` completed (or manual symlink)
- [ ] `php artisan config:cache route:cache view:cache event:cache` completed
- [ ] Scheduler cron added (`* * * * * php artisan schedule:run`)
- [ ] DB backup cron added
- [ ] Log retention cron added
- [ ] UptimeRobot pinging `/up`
- [ ] Smoke test: visit `/`, switch language, register a test account, log in

---

**Status**: Ready for cPanel.
**Updated**: 2026-04-26.
