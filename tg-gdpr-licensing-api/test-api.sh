#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARTISAN="${ARTISAN:-${ROOT_DIR}/artisan}"
BASE_URL="${BASE_URL:-http://127.0.0.1:8000/api/v1}"
TEST_RUN_ID="${TEST_RUN_ID:-$(date +%s)}"
SINGLE_DOMAIN="single-${TEST_RUN_ID}.example.com"
SINGLE_ACTIVE=0
MULTI_DOMAINS=()

lookup_license_key() {
    local plan="$1"

    php "$ARTISAN" tinker --execute="echo App\\Models\\License::where('plan', '${plan}')->latest('id')->value('license_key');"
}

post_json() {
    local endpoint="$1"
    local payload="$2"

    curl -sS -X POST "${BASE_URL}/${endpoint}" \
        -H "Content-Type: application/json" \
        -d "$payload"
}

assert_contains() {
    local response="$1"
    local expected="$2"
    local label="$3"

    if [[ "$response" == *"$expected"* ]]; then
        return
    fi

    printf '%s failed.\nExpected to find: %s\nActual response: %s\n' "$label" "$expected" "$response" >&2
    exit 1
}

cleanup() {
    if [[ "$SINGLE_ACTIVE" == "1" ]]; then
        post_json "licenses/deactivate" "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"${SINGLE_DOMAIN}\"}" >/dev/null 2>&1 || true
    fi

    if [[ "${#MULTI_DOMAINS[@]}" -eq 0 ]]; then
        return
    fi

    for domain in "${MULTI_DOMAINS[@]}"; do
        post_json "licenses/deactivate" "{\"license_key\":\"${MULTI_KEY}\",\"domain\":\"${domain}\"}" >/dev/null 2>&1 || true
    done
}

SINGLE_KEY="${SINGLE_KEY:-$(lookup_license_key single)}"
MULTI_KEY="${MULTI_KEY:-$(lookup_license_key 3-sites)}"

if [[ -z "$SINGLE_KEY" || -z "$MULTI_KEY" ]]; then
    printf 'Could not find seeded single and 3-sites license keys. Run the local bootstrap first.\n' >&2
    exit 1
fi

trap cleanup EXIT

echo "==================================="
echo "TG GDPR License API Smoke Test"
echo "==================================="
echo ""
echo "Base URL: ${BASE_URL}"
echo "Single license: ${SINGLE_KEY}"
echo "Multi license: ${MULTI_KEY}"
echo ""

echo "1. Testing License Activation..."
echo "-----------------------------------"
ACTIVATION_RESPONSE=$(post_json "licenses/activate" "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"${SINGLE_DOMAIN}\",\"site_url\":\"https://${SINGLE_DOMAIN}\"}")
assert_contains "$ACTIVATION_RESPONSE" '"success":true' 'Single license activation'
echo "Response: ${ACTIVATION_RESPONSE}"
echo ""
SINGLE_ACTIVE=1

echo "2. Testing License Verification..."
echo "-----------------------------------"
VERIFY_RESPONSE=$(post_json "licenses/verify" "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"${SINGLE_DOMAIN}\"}")
assert_contains "$VERIFY_RESPONSE" '"success":true' 'Single license verification'
assert_contains "$VERIFY_RESPONSE" '"status":"active"' 'Single license verification status'
echo "Response: ${VERIFY_RESPONSE}"
echo ""

echo "3. Testing License Deactivation..."
echo "-----------------------------------"
DEACTIVATE_RESPONSE=$(post_json "licenses/deactivate" "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"${SINGLE_DOMAIN}\"}")
assert_contains "$DEACTIVATE_RESPONSE" '"success":true' 'Single license deactivation'
echo "Response: ${DEACTIVATE_RESPONSE}"
echo ""
SINGLE_ACTIVE=0

echo "4. Testing Multi-Site License..."
echo "-----------------------------------"
for i in 1 2 3; do
    domain="site${i}-${TEST_RUN_ID}.example.com"
    echo "Activating site ${i} (${domain})..."
    MULTI_RESPONSE=$(post_json "licenses/activate" "{\"license_key\":\"${MULTI_KEY}\",\"domain\":\"${domain}\",\"site_url\":\"https://${domain}\"}")
    assert_contains "$MULTI_RESPONSE" '"success":true' "Multi-site activation ${i}"
    echo "Response: ${MULTI_RESPONSE}"
    MULTI_DOMAINS+=("$domain")
done
echo ""

echo "5. Testing Activation Limit..."
echo "-----------------------------------"
LIMIT_DOMAIN="site4-${TEST_RUN_ID}.example.com"
echo "Trying to activate 4th site (${LIMIT_DOMAIN})..."
LIMIT_RESPONSE=$(post_json "licenses/activate" "{\"license_key\":\"${MULTI_KEY}\",\"domain\":\"${LIMIT_DOMAIN}\",\"site_url\":\"https://${LIMIT_DOMAIN}\"}")
assert_contains "$LIMIT_RESPONSE" '"success":false' 'Multi-site activation limit response'
assert_contains "$LIMIT_RESPONSE" 'Maximum activations reached' 'Multi-site activation limit message'
echo "Response: ${LIMIT_RESPONSE}"
echo ""

echo "6. Cleaning Up Multi-Site Activations..."
echo "-----------------------------------"
for domain in "${MULTI_DOMAINS[@]}"; do
    DEACTIVATE_MULTI_RESPONSE=$(post_json "licenses/deactivate" "{\"license_key\":\"${MULTI_KEY}\",\"domain\":\"${domain}\"}")
    assert_contains "$DEACTIVATE_MULTI_RESPONSE" '"success":true' "Multi-site deactivation for ${domain}"
    echo "Response: ${DEACTIVATE_MULTI_RESPONSE}"
done
MULTI_DOMAINS=()
echo ""

echo "==================================="
echo "Smoke Test Complete"
echo "==================================="
