# Cookiely — Launch Readiness Status

_Last updated: 2026-04-26 (third review pass)_

Code-checked status. Aspirational docs from earlier development phases are in
[`docs/archive/`](docs/archive/) for git history only — do not use those as a
source of truth.

> **Note on this revision.** Three independent review passes have run in this
> session. The first STATUS.md missed a ship-blocker (no License on payment
> success). The second pass fixed it but the third pass — done across both
> Laravel + WP plugin with three parallel adversarial agents — found four
> more confirmed launch-blockers I'd missed, plus several real gaps. All four
> blockers are now fixed and tests pin them. The honest verdict is below.

---

## ✅ Shipped & verified

### Core CMP

- **Google Consent Mode v2 bootstrap** at `wp_head` priority 0 — pushes
  deny-default for all 7 v2 signals (`ad_storage`, `ad_user_data`,
  `ad_personalization`, `analytics_storage`, `functionality_storage`,
  `personalization_storage`, `security_storage`) **before** any third-party
  Google tag can fire. The external `gcm.js` then refines per-region defaults
  and on-consent updates.
  See [`tg-gdpr-cookie-consent/public/class-tg-gdpr-public.php::inject_critical_inline_script`](tg-gdpr-cookie-consent/public/class-tg-gdpr-public.php).

- **HMAC-SHA256 signed consent records** — every consent gets a signature over
  a deterministic JSON canonicalisation using `APP_KEY`. Tampering with any
  hashed field (including `signed_at`) invalidates the signature. 6 regression
  tests pin tamper detection.
  See [`app/Services/Consent/ConsentSigner.php`](tg-gdpr-licensing-api/app/Services/Consent/ConsentSigner.php).

- **Polite-mode auto-scanner** — async via wp-cron tick. One URL per
  `scan_interval_seconds` (default 60s) with ±15s jitter so the customer's site
  never sees a burst. Configurable, cancellable, with progress reporting via
  `get_scan_progress()`. Fires `tg_gdpr_scan_completed` action so post-scan
  consumers can hook in.
  See [`includes/class-tg-gdpr-auto-scanner.php`](tg-gdpr-cookie-consent/includes/class-tg-gdpr-auto-scanner.php).

- **Scan-driven auto-block** — detected cookies extend the runtime blocker's
  pattern map. Hardcoded base list now covers Google, Meta, X, LinkedIn,
  TikTok, Pinterest, Snapchat, Reddit, Bing, Hotjar, Microsoft Clarity, plus
  HubSpot/Pardot/Marketo. Payment processors (Stripe/PayPal/Klarna) are
  allow-listed as `necessary`. **Default for unknown scripts is `marketing`
  (block-by-default)** — overridable per-script via the
  `tg_gdpr_blocker_patterns` filter.
  See [`build_blocker_patterns()`](tg-gdpr-cookie-consent/public/class-tg-gdpr-public.php).

- **Identifying scanner UA** — `TG GDPR Cookie Scanner/<version> (+https://cookiely.site/scanner)`,
  capped redirects (2), capped response (256 KB), capped URL set (100),
  enumerates only published public posts, **respects `/robots.txt`** Disallow
  rules.

- **Daily retention purge job** — `consents:purge-expired` deletes records past
  their `expires_at`. Scheduled at 03:00.

- **DSAR Article 17 erasure** with audit logging via `ActivityLogger` — every
  erasure records who deleted what, when, and how many records.

- **Banner runtime cleaned** — modern `tg-gdpr-banner.js` is the single source
  of truth. Legacy `public.js`, server-rendered banner partial, and legacy CSS
  all removed from the load path. No jQuery dependency. Both `console.log` and
  `console.warn` gated behind `settings.debug`.

  **Honest wire weight**: 9.78 KB gzipped JS (banner.js + gcm.js) + 3 KB
  gzipped CSS = ~13 KB gzipped total per visitor pageview. The earlier "9.5 KB
  gzipped" claim under-counted CSS — corrected. The landing-page FAQ now says
  "under 10 KB gzipped" referring to the JS only, which is accurate.

### Licensing

- **License activation race condition closed** —
  `LicenseService::activate()` uses `DB::transaction()` + `lockForUpdate()` so
  two concurrent activations on different domains can no longer both pass a
  1-site license check.

- **Public-API rate limits** keyed by `license_key` / `site_token` so
  shared-NAT WP hosts aren't bucketed together. DSAR-submit is per-IP.

- **DB index** on `activations(license_id, status)` — supports the hot
  `canActivate()` count without a partial scan.

### Payments — full purchase loop closed

- **Plans table** + seeded Starter/Pro/Agency with provider price IDs per
  (provider, mode). Hybrid pricing model: DB owns metadata, providers own
  actual prices.

- **PaymentProvider abstraction**:
  - **StripeProvider** — full implementation against Cashier 16. Webhook
    signature verified.
  - **FrisbiiProvider** — structurally complete scaffold. Reads admin-managed
    credentials, verifies HMAC-SHA256 webhooks. `startCheckout` /
    `cancelSubscription` throw a clear "awaiting endpoint mapping" message
    until sandbox creds + a sample request are supplied.

- **Critical post-payment fix (caught in re-review)** — Stripe's
  `checkout.session.completed` now calls `LicenseService::provisionForCheckout()`
  which creates a usable License from the matching Plan, with `max_activations`
  read from `plan.max_sites`. Idempotent via the unique
  `licenses.provider_subscription_id` column — webhook re-delivery returns the
  existing License instead of duplicating. **4 regression tests** pin the full
  pay → license → activate flow:
  see [`tests/Feature/Payments/StripeWebhookFulfillmentTest.php`](tg-gdpr-licensing-api/tests/Feature/Payments/StripeWebhookFulfillmentTest.php).

- **Super-admin Plans CRUD** at `/admin/plans`.

- **Super-admin Payment Settings** at `/admin/settings/payments` —
  per-provider test/live key blocks, mode toggle, encrypted secrets,
  blank-input preserves existing secret, "Test connection" button. Webhook
  URLs displayed inline.

- **Customer checkout** at `/customer/checkout/{plan-slug}` — drives whichever
  provider the super admin has enabled. Anonymous → `/register`; authed →
  redirected to provider's hosted checkout. Activity-logged.

- **Webhook entrypoint** at `POST /webhooks/payments/{stripe|frisbii}` —
  CSRF-exempt, signature-verified, returns 200 even on internal errors so
  providers don't retry-storm.

### WordPress plugin security

- `sanitize_settings()` rewritten as a whitelist sanitizer + capability check
  on the partial. Closes a stored-XSS vector in admin settings.

### Operations

- **GitHub Actions CI** — runs PHP lint + 70-test suite on every push/PR.

- **Tailwind landing page** with locale switcher (en, da, sv, fi, nb, de, nl).
  Vapor metrics removed. IAB TCF v2.2 claim removed pending real
  implementation. JS-size claim corrected to "under 10 KB" across all locales.

### Tests

**74 passing, 219 assertions.** Coverage hot-spots:

- License activate / deactivate / verify (incl. limit-enforcement regression)
- License provisioning from payment webhook (incl. idempotency, end-to-end
  pay-to-activate)
- Consent signing + tamper detection
- Retention purge command
- Admin payments UI (renders, encryption-at-rest, blank-preserves-secret, RBAC)
- Customer checkout (no-provider, unknown-plan, inactive-plan, anonymous)
- **Registration creates a linked Customer** (regression for the silent-License-fail bug)
- **Customer dashboard renders without 500** (regression for the missing-DI bug)
- Site settings, DSAR flow, session sync, consent recording, geo targeting

---

## ⚠ Required for genuine launch (NOT engineering)

- **DPA template** — needs lawyer drafting. Once drafted, add a customer-
  downloadable route. Legal blocker for selling to EU customers as a data
  processor.
- **Mail provider configuration** — `MAIL_MAILER=log` today. Need a Postmark /
  Resend / SES decision, then DKIM/SPF/DMARC for `cookiely.site`.
- **Frisbii sandbox creds + sample request** — to unblock the driver. Stripe
  alone is sufficient for launch.
- **Production `APP_KEY`** — non-empty, non-default. ConsentSigner refuses to
  sign without it.
- **Production database backups + log retention** — see
  [`DEPLOYMENT-CPANEL.md`](DEPLOYMENT-CPANEL.md) §5.

---

## 🚧 Post-launch backlog (priority order)

1. **Purchase-confirmation email** — currently no email is sent on Stripe
   success. Customer must log in to the dashboard to find their license. UX,
   not blocker.
2. **Sentry error tracking** — currently relying on
   `storage/logs/laravel.log`.
3. **Roles refactor** — current single-`customer`-role-per-Customer is fine
   for MVP. Multi-admin-per-site is v1.1: rename `admin → super_admin`, add
   `site_user` pivot (owner/admin), policies, Team tab.
4. **API-driven cookie categorisation** — augment the heuristic categoriser
   with a lookup against the central `cookie_definitions` table for unknown
   cookies. Currently the scanner uses local heuristic patterns + falls back
   to `functional`; combined with the runtime's default-deny, unknowns are
   still blocked. The lookup would improve labels in the customer dashboard.
5. **IAB TCF v2.2 generation** — schema exists; TC string encoding + vendor
   list management is ~2 weeks of work.
6. **Frisbii driver completion** — full hosted checkout + cancel + webhook
   handler once sandbox creds arrive.
7. **App-level encryption on `consent_categories` + `tcf_string`** — HMAC
   protects integrity; encryption protects confidentiality at rest.
8. **Admin audit logging** on Site / Customer mutations (DSAR is already
   covered).
9. **Sub-processor disclosure page** in the customer dashboard.
10. **ETag + cache headers on `/sites/settings`** (plugin polls every 5 min).
11. **Batch consent sync** to a queued job + dedup on `consent_id`.
12. **Soft-delete Customer** with a retention grace window + hard-purge job.
13. **Dead-code cleanup** — physically remove the now-unused
    [`public/js/tg-gdpr-public.js`](tg-gdpr-cookie-consent/public/js/tg-gdpr-public.js),
    [`public/css/tg-gdpr-public.css`](tg-gdpr-cookie-consent/public/css/tg-gdpr-public.css),
    [`public/partials/tg-gdpr-banner.php`](tg-gdpr-cookie-consent/public/partials/tg-gdpr-banner.php),
    [`includes/class-tg-gdpr-banner.php`](tg-gdpr-cookie-consent/includes/class-tg-gdpr-banner.php).
    Unhooked from runtime but still on disk.

---

## What changed across the review passes

For transparency about what was caught when:

### Re-review pass 2 (after first STATUS.md)

| # | Issue | Severity | Status |
|---|---|---|---|
| 1 | **No License created on payment success.** Customer paid → got nothing they could activate. | CRITICAL ship-blocker, missed in first pass | ✅ `LicenseService::provisionForCheckout` + idempotency column + 4 regression tests |
| 2 | **Synchronous scanner** that hammered the customer's site with 100 URLs at once. | Real risk, raised by user | ✅ async tick architecture, configurable interval+jitter |
| 3 | **`console.warn` not gated** — production sites still got 5 warning lines on errors. | Polish | ✅ `warn()` helper alongside `log()` |
| 4 | **Page-weight claim was wrong** — said 9.5 KB, actually 13 KB combined (JS+CSS). | Honesty | ✅ legacy CSS removed (-2.5 KB), claim corrected to "under 10 KB JS" across all 7 locales |
| 5 | **Inline blocker had sparse coverage** — TikTok, LinkedIn, Pinterest, Hotjar all defaulted to "necessary" (= unblocked). | Real CMP correctness | ✅ expanded base patterns + scan-driven extension + default-deny for unknowns |

### Third review pass (full system, both Laravel + WP plugin)

Three parallel adversarial review agents found four more confirmed
launch-blockers I had missed:

| # | Issue | Severity | Status |
|---|---|---|---|
| 1 | **Registration didn't create a Customer.** Every newly-registered user had `customer_id = null`, so Stripe checkout silently failed to provision a License (the webhook bailed on null customer_id and only logged a warning). Same end-state as pass 2's blocker, different root cause. | CRITICAL ship-blocker | ✅ atomic `User + Customer` creation in registration. 2 regression tests. |
| 2 | **`DashboardController` referenced `$this->activityLogger` with no constructor or property** — every authed customer hitting the dashboard would 500. | CRITICAL ship-blocker | ✅ added DI constructor. 2 regression tests confirm 200 + 403 paths. |
| 3 | **WordPress plugin `class-tg-gdpr-license-manager.php` had `private $api_url = 'https://your-domain.com/api/v1/licenses';`** — a literal placeholder. The plugin would 404 against `your-domain.com` for every license activation, verification, and heartbeat. | CRITICAL ship-blocker | ✅ replaced with `DEFAULT_API_URL = 'https://cookiely.site/api/v1/licenses'`, override-able via `TG_GDPR_API_URL` constant or `tg_gdpr_api_url` filter for staging/dev. |
| 4 | **`LicenseService::createLicense` matched on legacy enum values** (`single`, `3-sites`, `10-sites`) that don't match the seeded Plan slugs (`starter`, `pro`, `agency`); the admin license-create form used yet a third vocabulary (`single`, `triple`, `ten`). | Admin path broken | ✅ refactored to take a `Plan` model; admin form now reads `Plan::active()`. |

The third review also surfaced gaps that are real but **not launch-blockers**
(documented for post-launch backlog rather than fixed now): license-key
collision retry, no `invoice.payment_failed` webhook handler, no Cashier
webhook mounted (so `subscriptions` table is sparsely populated and customer
dashboard subscription panel may show empty), 30s timeout on plugin license
heartbeat, scanner state race on concurrent wp-cron ticks. None of these
break the core purchase → license → activate loop, which is now
end-to-end-test-pinned.

### Honest meta-observation

Three review passes caught four ship-blockers across the session. That's
worth being explicit about: a single "I confirm it's ready" pass is not
sufficient for a production launch — adversarial parallel review found
issues that single-pass writing missed every time. The codebase is now
in a defensible state, but the pattern says: another pass after any
significant change before launch is wise.
