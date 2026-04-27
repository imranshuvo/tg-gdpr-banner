# Cookiely — Artisan Commands Reference

Ops cookbook for the Laravel app. Run every command from `~/cookiely-app/`
on the server (or `tg-gdpr-licensing-api/` locally).

---

## Table of contents

1. [User management](#1-user-management) — create admins/customers
2. [Deploy + upgrade](#2-deploy--upgrade) — every time you push new code
3. [Scheduled jobs](#3-scheduled-jobs) — what runs daily
4. [Diagnostics](#4-diagnostics) — when something looks off
5. [One-off operations](#5-one-off-operations) — license tools, consents

---

## 1. User management

### `app:create-admin` — bootstrap or promote a super admin

The first super admin on a fresh deploy. Has access to `/admin/*` (manage
plans, payment settings, customers, licenses, DSAR requests).

```bash
# Interactive — prompts for email, generates a random password
php artisan app:create-admin

# Non-interactive
php artisan app:create-admin owner@cookiely.site \
  --name="Owner Name" \
  --password='your-secret'
```

**Behaviour:**
- New email → creates an admin user with `email_verified_at` set.
- Email exists as `customer` → offers to **promote** that user (preserves
  their existing Customer linkage and licenses).
- Email exists as `admin` → no-op success.
- If `--password` omitted, a 16-char password is generated and printed
  once. Save it immediately.

After creation, sign in at `/login` then go to `/admin`.

### `app:create-customer` — create a customer outside the registration form

Atomic `User` + `Customer` creation, same shape as the public registration
flow. Useful for comp accounts, partner onboarding, staging tests.

```bash
php artisan app:create-customer alice@example.com \
  --name="Alice" \
  --company="Acme Ltd" \
  --password='your-secret'
```

If `--password` is omitted, a random password is generated and printed.
Email must be unique — fails if the user already exists.

### `app:list-users` — list users with role + customer link

```bash
php artisan app:list-users
php artisan app:list-users --role=admin
php artisan app:list-users --role=customer
php artisan app:list-users --limit=200
```

Shows: ID, email, name, role, Customer FK, verification status, created.

---

## 2. Deploy + upgrade

The post-deploy sequence. Every time you push new code, run these in order:

```bash
cd ~/cookiely-app

# 1. Pull/extract new code (skip if you used the deploy script)

# 2. Update PHP dependencies
composer install --no-dev --optimize-autoloader --prefer-dist

# 3. Run new migrations (safe — only applies un-applied ones)
php artisan migrate --force

# 4. Re-seed plans if the seeded data has changed
php artisan db:seed --class=PlanSeeder

# 5. Public storage symlink (one-time, but harmless to re-run)
php artisan storage:link

# 6. Permissions
chmod -R 775 storage bootstrap/cache

# 7. Rebuild production caches
php artisan optimize:clear
php artisan optimize          # config + routes + views in one go

# 8. Maintenance mode toggle (optional; only matters if migrations are slow)
php artisan down --render="errors::503" --retry=15
# … run migrations / cache rebuild here …
php artisan up
```

**First deploy only:**

```bash
# Generate APP_KEY (only on first deploy — never re-run on existing data)
php artisan key:generate
```

> ⚠️ Re-running `key:generate` on an existing deploy invalidates every
> consent record's HMAC signature. Don't.

---

## 3. Scheduled jobs

These run automatically via the cron entry from
[DEPLOYMENT-CPANEL.md §2.5](DEPLOYMENT-CPANEL.md). You shouldn't need to
trigger them manually, but useful to know:

| Schedule | Command | What it does |
|---|---|---|
| `* * * * *` | `php artisan schedule:run` | The cron entry; dispatches the below |
| Daily 03:00 | `php artisan consents:purge-expired` | Deletes consent records past `expires_at` |
| Daily 09:00 | `php artisan licenses:monitor --send-alerts` | Emails customers about expiring licenses |
| Daily 00:00 | `php artisan model:prune --model=ActivityLog` | Drops activity logs older than 90 days |

### `consents:purge-expired` — manual run

```bash
# Dry run — shows what would be deleted
php artisan consents:purge-expired --dry-run

# Actually delete
php artisan consents:purge-expired

# Smaller batch size for slow databases
php artisan consents:purge-expired --chunk=200
```

### `licenses:monitor` — manual run

```bash
# Dry run — see what's in scope
php artisan licenses:monitor

# Send the alert emails
php artisan licenses:monitor --send-alerts
```

---

## 4. Diagnostics

### What's actually deployed

```bash
php artisan about | head -30          # Laravel version, env, drivers, cache state
php artisan migrate:status            # which migrations have run
php artisan route:list --path=api     # public API routes
php artisan route:list --path=admin   # admin panel routes
php artisan schedule:list             # registered cron tasks
```

### Did it work

```bash
# Health endpoint — should return 200
curl -s -o /dev/null -w "%{http_code}\n" https://cookiely.site/up

# Log tail (errors and above)
tail -f storage/logs/laravel.log
```

### Is the user there?

```bash
php artisan app:list-users --role=admin
php artisan tinker --execute='echo App\Models\User::count();'
```

### Can the plugin reach us?

From an authenticated WP plugin install, on the WP server:
```bash
curl -X POST https://cookiely.site/api/v1/licenses/verify \
  -H "Content-Type: application/json" \
  -d '{"license_key":"<your-key>","domain":"the-wp-site.com"}'
```

Expected: `{"success": true, ...}` if the license is active for that domain.

---

## 5. One-off operations

### Validate consent record signatures

If you need to spot-check that consent records aren't being tampered with:

```bash
php artisan tinker --execute='
$total = App\Models\ConsentRecord::count();
$invalid = App\Models\ConsentRecord::all()->reject(fn($c) => $c->isSignatureValid())->count();
echo "$total total, $invalid invalid signatures";
'
```

Should print `N total, 0 invalid signatures`. Any non-zero invalid count
means either the DB has been tampered with, or `APP_KEY` was rotated
without re-signing existing rows.

### Suspend a license

```bash
php artisan tinker --execute='
App\Models\License::where("license_key","XXXX-XXXX-XXXX-XXXX")
  ->update(["status" => "suspended"]);
'
```

### Run the test suite (staging)

```bash
php artisan test
```

Expected: 80 passing. Don't run this against a production DB unless
`DB_CONNECTION=sqlite_in_memory` is set for testing — `RefreshDatabase`
will wipe the schema otherwise.

---

## Conventions

- `app:*` — custom application commands (defined in `app/Console/Commands/`).
- `consents:*`, `licenses:*` — domain-scoped maintenance commands.
- `--force` — required on production for any command that's destructive
  (e.g. `migrate:fresh`, `migrate:rollback`). Acts as a "yes I really mean
  it" confirmation.
- All custom commands are auto-discovered — no registration needed.
