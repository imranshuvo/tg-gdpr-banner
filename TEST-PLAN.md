# Cookiely — Pre-Launch Test Plan

Hands-on verification of every critical path. Sections are ordered cheapest →
most expensive, so a failure early stops you wasting time on later steps.

> **Use this once before launch, then again after any significant change.**
> Three review passes found four ship-blockers each — don't trust "looks right".

---

## A. Automated tests (60 seconds)

```bash
cd tg-gdpr-licensing-api
php artisan test
```

**Expected:** `Tests: 74 passed (219 assertions). Duration: ~3s`.

If anything is red, **stop here**. Don't proceed until green.

---

## B. Local Laravel bring-up (~5 min)

### Prereqs
- PHP 8.2+, Composer, Node 18+, MySQL 8 (or SQLite for the very-quick path).
- A Stripe account in **test mode** with API keys ready.

### Steps
```bash
cd tg-gdpr-licensing-api

# 1. Install + build
composer install
npm ci && npm run build

# 2. Env: copy + key
cp .env.example .env
php artisan key:generate

# 3. DB up + seed plans
php artisan migrate --seed
php artisan db:seed --class=PlanSeeder

# 4. Create a super-admin user for the /admin panel
php artisan tinker --execute="
\$u = App\Models\User::factory()->create([
  'name' => 'Super Admin',
  'email' => 'super@cookiely.local',
  'password' => bcrypt('password'),
  'role' => 'admin',
  'email_verified_at' => now(),
]);
echo 'admin user created: '.\$u->email.PHP_EOL;
"

# 5. Run the app
php artisan serve
```

**Expected:** server up at `http://localhost:8000`. Landing page renders with
the locale switcher.

### Verify
- [ ] `http://localhost:8000/` — modern landing page, no PHP errors
- [ ] `http://localhost:8000/?lang=da` — Danish translations
- [ ] `http://localhost:8000/up` — returns `200` (health check)

---

## C. Super-admin configuration (~10 min)

Log in as `super@cookiely.local / password` at `http://localhost:8000/login`.

### C.1 Plans CRUD
1. Open `/admin/plans` — see Starter / Professional / Agency seeded.
2. Click **Edit** on Professional.
3. Paste a Stripe Price ID into "Stripe Price ID (test)" — go to your Stripe
   Dashboard → Products → create a product if needed → grab the price ID
   (looks like `price_1Abc...`). Save.
4. Refresh `/admin/plans` — the Stripe IDs column should show your test ID.

**If it fails:** check `php artisan migrate` ran. Plans require the `plans`
table.

### C.2 Payment provider settings
1. Open `/admin/settings/payments`.
2. Stripe block:
   - Enable: ON
   - Mode: **Test**
   - Test publishable key: `pk_test_...`
   - Test secret key: `sk_test_...`
   - Test webhook signing secret: leave blank for now (we'll set it after
     starting the Stripe CLI in C.3)
   - Click **Save Stripe**
3. Reopen the page — the page should now show "Active · Test" pill, secrets
   displayed as "already set" (NOT echoed back — verify nothing reveals the
   secret value).

**If it fails:** check `SystemSetting` table exists. Run
`php artisan tinker --execute='echo App\Models\SystemSetting::count();'`.

### C.3 Stripe CLI for webhooks
In a new terminal:
```bash
stripe login
stripe listen --forward-to http://localhost:8000/webhooks/payments/stripe
```

Copy the `whsec_...` it prints. Go back to `/admin/settings/payments`,
paste into "Test webhook signing secret", **Save Stripe** again.

### Verify
- [ ] Plans page lists 3 plans
- [ ] Payment settings shows Stripe as "Active · Test"
- [ ] Stripe CLI is listening
- [ ] Webhook URL `/webhooks/payments/stripe` returns 400 on a curl with no
      signature: `curl -X POST http://localhost:8000/webhooks/payments/stripe`

---

## D. Customer purchase flow (~10 min)

This is the **revenue loop** — the test that mattered most in our reviews.

### Steps
1. Open a **fresh incognito window**. Go to `http://localhost:8000/`.
2. Click **Get Started** on the Pro tier → redirects to `/register`.
3. Register with `customer1@example.test` / `password`.

   **Verify (regression for pass-3 blocker #1):**
   ```bash
   php artisan tinker --execute='
   $u = App\Models\User::where("email","customer1@example.test")->first();
   echo "user_id: ".$u->id." | customer_id: ".$u->customer_id.PHP_EOL;
   echo "customer email: ".$u->customer->email.PHP_EOL;
   '
   ```
   Both lines must print non-null. **If `customer_id` is null, registration is
   broken.** Pre-fix this would have happened.

4. After registration you land on the dashboard.

   **Verify (regression for pass-3 blocker #2):** the page renders 200, NOT
   500. Pre-fix the dashboard threw "Call to a member function on null" on
   `$this->activityLogger`.

5. Go to `/customer/checkout/pro` (or click the pricing CTA from `/`).
   You should be redirected to Stripe Checkout.
6. Use a test card: `4242 4242 4242 4242`, any future expiry, any CVC.
7. Complete the payment.

### Verify the webhook + License creation
8. In the Stripe CLI terminal, watch for `checkout.session.completed`. It
   should print `[200 OK]`.
9. Back in the app:
   ```bash
   php artisan tinker --execute='
   $u = App\Models\User::where("email","customer1@example.test")->first();
   $licenses = $u->customer->licenses;
   foreach ($licenses as $l) {
       echo "License: ".$l->license_key." | plan: ".$l->plan." | provider_sub: ".$l->provider_subscription_id." | max: ".$l->max_activations.PHP_EOL;
   }
   '
   ```
   **Expected:** one License row, plan = `pro`, max_activations = 5,
   provider_subscription_id = `sub_test_...`.

   **If it's missing**, payment loop is broken. Likely causes: webhook
   signing secret wrong (re-paste from `stripe listen` output); plan_id
   metadata missing (check Stripe Dashboard → Events → the
   `checkout.session.completed` event payload should have
   `metadata.plan_id`).

10. Open `/customer/licenses` in the browser — see your License with the
    license key visible.

### Webhook idempotency check
11. Resend the same event from Stripe dashboard or:
    ```bash
    stripe events resend evt_<id_from_step_8>
    ```
12. Re-run the tinker command from step 9. **Should still be exactly ONE
    License row** — not two.

---

## E. WordPress plugin install + activate (~15 min)

### Prereqs
- A local WordPress install (use `wp-env`, Local by Flywheel, MAMP, or
  similar). Site URL: e.g. `http://wp.local`.

### E.1 Install the plugin
```bash
# Package the plugin
cd tg-gdpr-cookie-consent
zip -r ../tg-gdpr-cookie-consent.zip . -x ".git/*" "node_modules/*"
```

In WordPress admin: **Plugins → Add New → Upload Plugin** → upload the zip
→ activate.

### E.2 Point the plugin at your local Laravel
The plugin's default API URL is `https://cookiely.site/...`. For local
testing, override via `wp-config.php`:

```php
// Add this near the top of wp-config.php BEFORE the "happy blogging" line:
define('TG_GDPR_API_URL', 'http://host.docker.internal:8000/api/v1/licenses');
// or http://localhost:8000/api/v1/licenses if running WP natively
```

**Without this, the plugin will hit cookiely.site and fail.** This was
review pass 3 blocker #3 — the placeholder is gone, but you still need the
override for local dev.

### E.3 Activate the license
1. WP admin → **Cookiely → License**.
2. Paste the license key from step D.10.
3. Click **Activate**.

**Expected:** "License activated successfully" + green status indicator.

### Verify
4. Back in Laravel:
   ```bash
   php artisan tinker --execute='
   $a = App\Models\Activation::latest()->first();
   echo "activation domain: ".$a->domain." | status: ".$a->status.PHP_EOL;
   '
   ```
   Should show your local WP domain + status `active`.

### Race-condition spot-check
5. Try activating the **same license** on a second WP install (or in the
   plugin settings, change the site URL temporarily). On a 1-site Starter
   plan it should fail; on a 5-site Pro plan it should succeed up to 5.

---

## F. Visitor consent flow + GCM v2 (~10 min)

### F.1 Banner + GCM v2 deny-default
1. Open the WP site frontend in **incognito** (no consent cookie).
2. Open DevTools → Console → Network → Application.
3. Hard reload.

**Expected:**
- Banner appears at the bottom (or wherever configured).
- DevTools → Application → Cookies: NO `_ga`, `_fbp`, etc. — third-party
  trackers are blocked pre-consent.
- Console (debug mode off): nothing from `[TG GDPR]` (warns/logs gated).
- DevTools → Console → run: `dataLayer.find(x => x[1] === 'consent' && x[2] === 'default')`.
  Returns an entry with `ad_storage: 'denied'` and the other 5 v2 signals
  also denied (only `security_storage: 'granted'`).

### F.2 Auto-block fires
1. Add Google Analytics or any tracker to the WP theme (header.php or via a
   plugin).
2. Reload the visitor page.
3. DevTools → Elements: find the `<script>` tag for GA. Its `type` should
   read `text/plain` and there should be `data-tg-blocked="1"` on it.
   The script element exists in DOM but **has not executed**.

### F.3 Consent → unblock + GCM update
1. Click **Accept all** in the banner.
2. DevTools → Console → run again: `dataLayer.filter(x => x[1] === 'consent')`.
   Now there's a `consent update` entry with `ad_storage: 'granted'`, etc.
3. Application → Cookies — now `_ga`, `_fbp`, etc. should appear (GA
   actually loaded).
4. Application → Cookies → find `tg_gdpr_consent` — it's a JSON cookie with
   the visitor's choices.

### F.4 Consent recorded server-side, signed
On Laravel:
```bash
php artisan tinker --execute='
$c = App\Models\ConsentRecord::latest()->first();
echo "id=".$c->id." | sig=".substr($c->signature, 0, 16)."... | signed_at=".$c->signed_at.PHP_EOL;
echo "sig valid: ".($c->isSignatureValid() ? "YES" : "NO").PHP_EOL;
'
```

**Expected:** signature is 64 hex chars, `signed_at` populated, `isSignatureValid()` returns YES.

### F.5 Tamper detection
```bash
php artisan tinker --execute='
$c = App\Models\ConsentRecord::latest()->first();
DB::table("consent_records")->where("id",$c->id)
  ->update(["consent_categories" => json_encode(["analytics" => true, "marketing" => true])]);
echo "after tamper, sig valid: ".($c->fresh()->isSignatureValid() ? "YES (BUG!)" : "NO (correct)").PHP_EOL;
'
```

**Expected:** `NO (correct)`. If `YES`, the HMAC signing is broken.

---

## G. Auto-scanner polite-mode (~10 min observation)

### G.1 Kick off a scan
1. WP admin → **Cookiely → Cookies** (or wherever the manual-scan button
   lives).
2. Click **Run Cookie Scan**.

**Expected immediate result:** "Scan started: N pages queued. Pages will be
fetched every 60s (~M min total)." The browser does **NOT** hang — the scan
runs in the background.

### G.2 Verify polite cadence
Tail your local web server logs OR your WP server's access logs:
```bash
tail -f /path/to/access.log | grep "TG GDPR Cookie Scanner"
```

**Expected:** one request every ~60s (±15s jitter), NOT a burst. The User-
Agent should be `TG GDPR Cookie Scanner/<version> (+https://cookiely.site/scanner)`.

### G.3 Verify state across ticks
Mid-scan, in WP admin:
```bash
wp option get tg_gdpr_scan_state --format=json
```

You should see `status: running`, `queue: [...remaining URLs...]`,
`detected: [...]` growing.

### G.4 Cancel works
While running:
```bash
wp eval '$s = new TG_GDPR_Auto_Scanner(); var_dump($s->cancel_scan());'
```

The state option should be deleted, no further fetches.

### G.5 Auto-block sees scan results
After a scan completes, in DevTools → Elements on a fresh visitor page:
the inline `<script id="tg-gdpr-critical">` block. Its `var patterns` JSON
should now include any new patterns the scan discovered (e.g. if the scan
found a Hotjar cookie, the patterns object will list its script_pattern).

### G.6 robots.txt is honored
1. Add `Disallow: /private` to the WP site's `/robots.txt`.
2. Visit `http://wp.local/private/whatever` (or any path under it that
   exists in your WP). Trigger another scan.
3. The scan report (`wp option get tg_gdpr_last_cookie_scan_report`) should
   include `errors: ["http://wp.local/private/...: skipped (robots.txt Disallow)"]`.

---

## H. DSAR erasure flow (~5 min)

### H.1 Submit a DSAR
As a visitor, hit your DSAR submission endpoint (typically a form on the
customer site). Submit an erasure request with the email you used in F.

### H.2 Verify
You'll receive an email (in Laravel logs at `storage/logs/laravel.log` since
`MAIL_MAILER=log` locally). Click the verification link.

### H.3 Admin processes
Log in as `super@cookiely.local` → `/admin/dsar` → find the request → mark
as **Process** with action `complete`.

### H.4 Verify deletion + audit log
```bash
php artisan tinker --execute='
$dsar = App\Models\DsarRequest::latest()->first();
echo "DSAR status: ".$dsar->status.PHP_EOL;
echo "consents for visitor_hash: ".App\Models\ConsentRecord::where("visitor_hash", $dsar->visitor_hash)->count().PHP_EOL;
$log = App\Models\ActivityLog::where("event","dsar.erased")->latest()->first();
echo "audit log: ".$log->description." | deleted=".$log->properties["records_deleted"].PHP_EOL;
'
```

**Expected:**
- `consents` count = 0 (deleted)
- audit log entry exists with `event: dsar.erased`, the count, the visitor_hash

---

## I. Regression smoke checks

Quick spot-checks for things three review passes have caught:

### I.1 Race-free activation (multi-domain)
```bash
# Open two terminals
# Terminal 1:
curl -X POST http://localhost:8000/api/v1/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"YOUR-KEY-HERE","domain":"a.test","site_url":"https://a.test"}'

# Terminal 2 (run within 100ms of Terminal 1):
curl -X POST http://localhost:8000/api/v1/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"YOUR-KEY-HERE","domain":"b.test","site_url":"https://b.test"}'
```

For a Starter (1-site) license: exactly ONE should succeed; the other returns
`Maximum activations reached`. Re-run after rolling back to verify the limit.

### I.2 Rate limit fires
```bash
for i in {1..70}; do
  curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8000/api/v1/licenses/verify \
    -H "Content-Type: application/json" \
    -d '{"license_key":"YOUR-KEY-HERE","domain":"a.test"}'
done
```

Expected: first ~60 return `200` (or `200`/`400` depending on validity),
then transitions to `429 Too Many Requests`.

### I.3 Retention purge dry-run
```bash
php artisan tinker --execute='
App\Models\ConsentRecord::create([
  "site_id" => App\Models\Site::first()->id,
  "visitor_hash" => str_repeat("x",64),
  "consent_categories" => ["analytics" => false],
  "consent_method" => "customize",
  "policy_version" => 1,
  "expires_at" => now()->subDay(),
]);
echo "before: ".App\Models\ConsentRecord::count().PHP_EOL;
'

php artisan consents:purge-expired
php artisan tinker --execute='echo "after: ".App\Models\ConsentRecord::count().PHP_EOL;'
```

Should print `before: N+1` and `after: N`.

### I.4 Locale switcher
1. `/?lang=da` → page in Danish.
2. Click globe in nav → choose Suomi.
3. Page reloads in Finnish, session persists across pages.

### I.5 Admin RBAC
Log out and try to hit `/admin/dashboard` as an unauthenticated user — should
redirect to `/login`. Log in as `customer1@example.test` (the customer from
step D) and try again — should `403`.

---

## J. Pre-launch production checklist

These are the things that are NOT engineering-fixable from this codebase
but block the actual launch:

- [ ] `APP_KEY` set on production, non-default. **ConsentSigner refuses to
      sign without it.** Verify with `php artisan tinker --execute='echo strlen(config("app.key"));'`.
- [ ] `APP_DEBUG=false` on production. Critical — debug mode leaks stack
      traces.
- [ ] Production DB has the same migrations applied:
      `php artisan migrate:status` shows all green.
- [ ] DB backups configured per [`DEPLOYMENT-CPANEL.md`](DEPLOYMENT-CPANEL.md) §5.
- [ ] Mail provider switched off `log`:
      `grep MAIL_MAILER .env` should NOT show `log`. Send a test:
      `php artisan tinker --execute='Mail::raw("test", fn($m) => $m->to("you@your.email")->subject("Cookiely test"));'`
- [ ] DKIM, SPF, DMARC records set on `cookiely.site` — verify with
      `dig TXT cookiely.site` and a tool like mail-tester.com.
- [ ] Stripe live keys swapped in `/admin/settings/payments` (Mode = Live).
      Don't forget the live webhook secret.
- [ ] Stripe webhook endpoint configured in Stripe Dashboard:
      `https://cookiely.site/webhooks/payments/stripe`
- [ ] DPA template drafted by privacy lawyer, available for download from
      customer dashboard (see post-launch backlog).
- [ ] `/up` health endpoint pingable from your monitoring (UptimeRobot etc.).
- [ ] Daily cron is actually running (cPanel scheduler, NOT just WP-cron).
      Verify with `php artisan schedule:list` and watch
      `storage/logs/laravel.log` after 03:00 for the purge job.

---

## What "passing" means

If sections **A through I** all pass, the engineering surface is launch-ready.
**J is non-engineering** but blocks the actual go-live.

If anything in B–F fails, the product is broken in a way customers will
hit immediately. Fix before launch.

If something in G–I fails, the product is launchable but with a known
degradation — file it as post-launch and ship. Use judgement.

**One last sanity check before pulling the live trigger:** spin up an
incognito browser, go through D–F as a real first-time customer would.
Click around like an end user, not a developer. The purchase journey
should feel unremarkable. Anything that surprises you is a bug.
