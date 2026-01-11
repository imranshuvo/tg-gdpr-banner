# TG GDPR Cookie Consent - Licensing API Documentation

## Overview
This is a simple, secure, and scalable Laravel 12 licensing API for the TG GDPR Cookie Consent WordPress plugin. It handles license activation, deactivation, and verification with domain locking.

## Tech Stack
- **Laravel**: 12.33.0
- **PHP**: 8.2+
- **Database**: SQLite (development) / MySQL/PostgreSQL (production)

## Database Schema

### Customers Table
```sql
- id (bigint, primary key)
- name (string)
- email (string, unique)
- company (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### Licenses Table
```sql
- id (bigint, primary key)
- customer_id (bigint, foreign key)
- license_key (string, unique)
- plan (enum: 'single', '3-sites', '10-sites')
- max_activations (integer)
- expires_at (timestamp)
- status (enum: 'active', 'expired', 'suspended')
- created_at (timestamp)
- updated_at (timestamp)
```

### Activations Table
```sql
- id (bigint, primary key)
- license_id (bigint, foreign key)
- domain (string)
- site_url (string)
- last_check_at (timestamp, nullable)
- status (enum: 'active', 'inactive')
- created_at (timestamp)
- updated_at (timestamp)
- UNIQUE(license_id, domain)
```

## API Endpoints

### Base URL
```
https://your-domain.com/api/v1
```

### 1. Activate License

Activates a license for a specific domain.

**Endpoint**: `POST /licenses/activate`

**Request Body**:
```json
{
    "license_key": "ABCD-EFGH-IJKL-MNOP",
    "domain": "example.com",
    "site_url": "https://example.com"
}
```

**Success Response** (200):
```json
{
    "success": true,
    "message": "License activated successfully",
    "data": {
        "license_key": "ABCD-EFGH-IJKL-MNOP",
        "plan": "single",
        "expires_at": "2026-10-08T16:12:30+00:00"
    }
}
```

**Error Responses**:
```json
// Invalid license key
{
    "success": false,
    "message": "Invalid license key"
}

// License expired
{
    "success": false,
    "message": "License has expired"
}

// Maximum activations reached
{
    "success": false,
    "message": "Maximum activations reached for this license"
}

// License not active
{
    "success": false,
    "message": "License is not active"
}
```

### 2. Deactivate License

Deactivates a license for a specific domain, freeing up an activation slot.

**Endpoint**: `POST /licenses/deactivate`

**Request Body**:
```json
{
    "license_key": "ABCD-EFGH-IJKL-MNOP",
    "domain": "example.com"
}
```

**Success Response** (200):
```json
{
    "success": true,
    "message": "License deactivated successfully"
}
```

**Error Responses**:
```json
// Invalid license key
{
    "success": false,
    "message": "Invalid license key"
}

// No activation found
{
    "success": false,
    "message": "No activation found for this domain"
}
```

### 3. Verify License

Verifies if a license is valid and active for a specific domain. This should be called periodically (heartbeat check).

**Endpoint**: `POST /licenses/verify`

**Request Body**:
```json
{
    "license_key": "ABCD-EFGH-IJKL-MNOP",
    "domain": "example.com"
}
```

**Success Response** (200):
```json
{
    "success": true,
    "message": "License is valid",
    "data": {
        "license_key": "ABCD-EFGH-IJKL-MNOP",
        "plan": "single",
        "expires_at": "2026-10-08T16:12:30+00:00",
        "status": "active"
    }
}
```

**Error Responses**:
```json
// Invalid license key
{
    "success": false,
    "message": "Invalid license key"
}

// Not activated for domain
{
    "success": false,
    "message": "License not activated for this domain"
}

// License expired
{
    "success": false,
    "message": "License has expired"
}
```

## License Plans

| Plan | Max Activations | Price |
|------|----------------|-------|
| single | 1 site | $59/year |
| 3-sites | 3 sites | $99/year |
| 10-sites | 10 sites | $199/year |

## Security Features

1. **Domain Locking**: Each license can only be activated on specific domains
2. **Activation Limits**: Enforced based on plan tier
3. **Heartbeat Verification**: Regular license checks with `last_check_at` tracking
4. **Automatic Expiry**: Expired licenses are automatically marked as 'expired'
5. **Unique Constraints**: Prevents duplicate activations on same domain

## Error Handling

All endpoints return appropriate HTTP status codes:
- `200`: Success
- `400`: Bad Request (validation errors, business logic errors)
- `422`: Unprocessable Entity (validation errors)

## WordPress Plugin Integration

The WordPress plugin should:

1. **On Activation**: Call `/licenses/activate` with license key and site domain
2. **Daily Heartbeat**: Call `/licenses/verify` to check license validity
3. **On Deactivation**: Call `/licenses/deactivate` to free up activation slot
4. **Feature Gating**: Check license plan to enable/disable Pro features

## Example cURL Requests

### Activate License
```bash
curl -X POST https://your-domain.com/api/v1/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "ABCD-EFGH-IJKL-MNOP",
    "domain": "example.com",
    "site_url": "https://example.com"
  }'
```

### Verify License
```bash
curl -X POST https://your-domain.com/api/v1/licenses/verify \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "ABCD-EFGH-IJKL-MNOP",
    "domain": "example.com"
  }'
```

### Deactivate License
```bash
curl -X POST https://your-domain.com/api/v1/licenses/deactivate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "ABCD-EFGH-IJKL-MNOP",
    "domain": "example.com"
  }'
```

## Development Setup

1. **Install Dependencies**:
```bash
composer install
```

2. **Environment Setup**:
```bash
cp .env.example .env
php artisan key:generate
```

3. **Run Migrations**:
```bash
php artisan migrate
```

4. **Seed Sample Data** (optional):
```bash
php artisan db:seed
```

5. **Start Development Server**:
```bash
php artisan serve
```

## Production Deployment

1. Set appropriate environment variables in `.env`
2. Use MySQL or PostgreSQL instead of SQLite
3. Enable caching: `php artisan config:cache`
4. Enable route caching: `php artisan route:cache`
5. Set up queue workers for background jobs (if needed)
6. Configure HTTPS/SSL
7. Set up monitoring and logging

## Future Enhancements

- [ ] API authentication for admin panel
- [ ] Webhook notifications for license events
- [ ] Usage analytics tracking
- [ ] Automated renewal reminders
- [ ] License transfer between domains
- [ ] Multi-currency support
- [ ] Admin dashboard for license management
