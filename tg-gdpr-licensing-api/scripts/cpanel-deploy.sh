#!/usr/bin/env bash
#
# cpanel-deploy.sh — deploy the Cookiely Laravel app to a cPanel host.
#
# See ../../DEPLOYMENT-CPANEL.md for the full plan and one-time server prep.
#
# Required env vars (set in your shell or in a .env.deploy file you `source`):
#   CPANEL_USER       cPanel username (e.g. mycpuser)
#   CPANEL_HOST       SSH host (e.g. cookiely.site or server IP)
#   CPANEL_PORT       SSH port (often non-22 on cPanel — confirm with your host)
#   REMOTE_APP_PATH   Absolute path on server (e.g. /home/mycpuser/cookiely-app)
#
# Optional env vars:
#   PHP_BIN           Server PHP binary path (default: php). Common cPanel paths:
#                     /usr/local/bin/php  or  /opt/cpanel/ea-php82/root/usr/bin/php
#   SKIP_ASSETS=1     Skip `npm ci && npm run build` (PHP-only changes).
#   SKIP_COMPOSER=1   Skip local composer install (vendor already current).
#   SKIP_MIGRATE=1    Skip `php artisan migrate --force` on remote.
#   DRY_RUN=1         Skip local build + rsync writes + remote tasks; show
#                     what rsync would transfer, then exit.
#   NO_MAINT=1        Don't toggle maintenance mode (down/up).
#
# Behavior:
#   1. Local: composer install --no-dev, npm run build (unless SKIP_*).
#   2. Remote: php artisan down (unless NO_MAINT=1).
#   3. rsync source tree to REMOTE_APP_PATH (excludes .env, storage, dev files).
#   4. Remote: migrate, optimize (config/route/view/event cache), chmod storage.
#   5. Remote: php artisan up.

set -euo pipefail

# ─── Resolve script + project paths ────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${PROJECT_DIR}"

# ─── Validate config ───────────────────────────────────────────────────────────
: "${CPANEL_USER:?Set CPANEL_USER (e.g. mycpuser)}"
: "${CPANEL_HOST:?Set CPANEL_HOST (e.g. cookiely.site)}"
: "${CPANEL_PORT:=22}"
: "${REMOTE_APP_PATH:?Set REMOTE_APP_PATH (e.g. /home/${CPANEL_USER}/cookiely-app)}"
: "${PHP_BIN:=php}"

SSH="ssh -p ${CPANEL_PORT} ${CPANEL_USER}@${CPANEL_HOST}"
RSYNC_REMOTE="${CPANEL_USER}@${CPANEL_HOST}:${REMOTE_APP_PATH}/"

# ─── Helpers ───────────────────────────────────────────────────────────────────
log()  { printf '\033[36m▶\033[0m %s\n' "$*"; }
ok()   { printf '\033[32m✓\033[0m %s\n' "$*"; }
warn() { printf '\033[33m!\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[31m✗\033[0m %s\n' "$*" >&2; exit 1; }

remote() {
    # Run a command on the server and stream its output back.
    # shellcheck disable=SC2029
    ${SSH} "cd ${REMOTE_APP_PATH} && $*"
}

# ─── 1. Local pre-flight ───────────────────────────────────────────────────────
log "Pre-flight checks"

[[ -f composer.json ]] || die "Run from the Laravel project root (no composer.json found)"
[[ -f artisan ]]       || die "No artisan file — wrong directory?"
command -v rsync >/dev/null || die "rsync not installed locally"
command -v ssh   >/dev/null || die "ssh not installed locally"

# Don't ship .env even if someone tries to. Catch it loudly.
if grep -q '^APP_DEBUG=true' .env 2>/dev/null; then
    warn "Local .env has APP_DEBUG=true — that's fine; we never deploy .env."
fi

ok "Pre-flight passed"

# ─── 2. Local build (skipped on DRY_RUN — local build is destructive) ──────────
if [[ "${DRY_RUN:-0}" == "1" ]]; then
    warn "DRY_RUN=1 — skipping local composer/npm build (rsync preview only)"
elif [[ "${SKIP_COMPOSER:-0}" == "1" ]]; then
    warn "SKIP_COMPOSER=1 — using existing vendor/"
else
    log "composer install (no-dev, optimized)"
    composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
    ok "Composer dependencies installed"
fi

if [[ "${DRY_RUN:-0}" != "1" ]]; then
    if [[ "${SKIP_ASSETS:-0}" == "1" ]]; then
        warn "SKIP_ASSETS=1 — using existing public/build/"
    else
        log "npm ci && npm run build"
        npm ci
        npm run build
        ok "Frontend assets built"
    fi
fi

# ─── 3. Maintenance mode ON ────────────────────────────────────────────────────
if [[ "${NO_MAINT:-0}" != "1" && "${DRY_RUN:-0}" != "1" ]]; then
    log "Enabling maintenance mode on remote"
    remote "${PHP_BIN} artisan down --render='errors::503' --retry=15" || warn "down failed (first deploy?)"
fi

# ─── 4. rsync ──────────────────────────────────────────────────────────────────
log "Syncing tree to ${RSYNC_REMOTE}"

RSYNC_FLAGS=(-avz --human-readable --delete --delete-excluded)
[[ "${DRY_RUN:-0}" == "1" ]] && RSYNC_FLAGS+=(--dry-run --itemize-changes)

# Anchored excludes (`/foo`) match only at the source root.
# Unanchored excludes (`foo`) match anywhere in the tree.
EXCLUDES=(
    # Includes must come BEFORE the matching excludes — rsync stops at first match.
    --include '.env.cpanel.example'        # ship the template
    --exclude '.env'
    --exclude '.env.*'
    --exclude '.git/'
    --exclude '.github/'
    --exclude '.idea/'
    --exclude '.vscode/'
    --exclude 'node_modules/'
    --exclude '/storage/app/'
    --exclude '/storage/logs/'
    --exclude '/storage/framework/'
    --exclude 'tests/'
    --exclude 'phpunit.xml'
    --exclude '.phpunit.*'
    --exclude '*.log'
    --exclude '.DS_Store'
    --exclude 'Thumbs.db'
    --exclude '/scripts/local-bootstrap.sh'
)

rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" \
    -e "ssh -p ${CPANEL_PORT}" \
    ./ "${RSYNC_REMOTE}"

if [[ "${DRY_RUN:-0}" == "1" ]]; then
    ok "Dry run complete — no files written"
    exit 0
fi
ok "Files synced"

# ─── 5. Remote: migrate + cache + permissions ──────────────────────────────────
log "Setting storage permissions"
remote "mkdir -p storage/{app,framework/{cache/data,sessions,testing,views},logs} && chmod -R 775 storage bootstrap/cache"

if [[ "${SKIP_MIGRATE:-0}" != "1" ]]; then
    log "Running migrations"
    remote "${PHP_BIN} artisan migrate --force"
    ok "Migrations applied"
else
    warn "SKIP_MIGRATE=1 — skipping migrate"
fi

log "Rebuilding production caches"
remote "${PHP_BIN} artisan optimize:clear && \
        ${PHP_BIN} artisan config:cache && \
        ${PHP_BIN} artisan route:cache && \
        ${PHP_BIN} artisan view:cache && \
        ${PHP_BIN} artisan event:cache"
ok "Caches rebuilt"

# ─── 6. Maintenance mode OFF ───────────────────────────────────────────────────
if [[ "${NO_MAINT:-0}" != "1" ]]; then
    log "Disabling maintenance mode"
    remote "${PHP_BIN} artisan up"
fi

# ─── 7. Quick smoke check ──────────────────────────────────────────────────────
log "Smoke check: HTTP HEAD https://${CPANEL_HOST}/up"
if command -v curl >/dev/null; then
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "https://${CPANEL_HOST}/up" || echo "000")
    if [[ "${code}" == "200" ]]; then
        ok "Health check returned 200"
    else
        warn "Health check returned ${code} — investigate"
    fi
fi

ok "Deploy complete: https://${CPANEL_HOST}"
