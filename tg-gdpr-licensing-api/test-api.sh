#!/bin/bash

# TG GDPR Licensing API - Test Script

BASE_URL="http://localhost:8000/api/v1"

echo "==================================="
echo "TG GDPR License API Test Script"
echo "==================================="
echo ""

# Test license keys from seeder
SINGLE_KEY="XZVS-HZ0R-LUZU-0HW1"
MULTI_KEY="0GLR-U8DX-WBP8-S15Z"

echo "1. Testing License Activation..."
echo "-----------------------------------"
ACTIVATION_RESPONSE=$(curl -s -X POST ${BASE_URL}/licenses/activate \
  -H "Content-Type: application/json" \
  -d "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"testsite.com\",\"site_url\":\"https://testsite.com\"}")

echo "Response: ${ACTIVATION_RESPONSE}"
echo ""

echo "2. Testing License Verification..."
echo "-----------------------------------"
VERIFY_RESPONSE=$(curl -s -X POST ${BASE_URL}/licenses/verify \
  -H "Content-Type: application/json" \
  -d "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"testsite.com\"}")

echo "Response: ${VERIFY_RESPONSE}"
echo ""

echo "3. Testing License Deactivation..."
echo "-----------------------------------"
DEACTIVATE_RESPONSE=$(curl -s -X POST ${BASE_URL}/licenses/deactivate \
  -H "Content-Type: application/json" \
  -d "{\"license_key\":\"${SINGLE_KEY}\",\"domain\":\"testsite.com\"}")

echo "Response: ${DEACTIVATE_RESPONSE}"
echo ""

echo "4. Testing Multi-Site License..."
echo "-----------------------------------"
for i in 1 2 3; do
  echo "Activating site ${i}..."
  MULTI_RESPONSE=$(curl -s -X POST ${BASE_URL}/licenses/activate \
    -H "Content-Type: application/json" \
    -d "{\"license_key\":\"${MULTI_KEY}\",\"domain\":\"site${i}.com\",\"site_url\":\"https://site${i}.com\"}")
  echo "Response: ${MULTI_RESPONSE}"
done
echo ""

echo "5. Testing Activation Limit..."
echo "-----------------------------------"
echo "Trying to activate 4th site (should fail)..."
LIMIT_RESPONSE=$(curl -s -X POST ${BASE_URL}/licenses/activate \
  -H "Content-Type: application/json" \
  -d "{\"license_key\":\"${MULTI_KEY}\",\"domain\":\"site4.com\",\"site_url\":\"https://site4.com\"}")

echo "Response: ${LIMIT_RESPONSE}"
echo ""

echo "==================================="
echo "Test Complete!"
echo "==================================="
