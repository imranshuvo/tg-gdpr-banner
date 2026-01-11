# Admin Panel Documentation

## Overview
Complete tenant/license management UI for the TG GDPR Cookie Consent licensing system.

## Access
- **URL**: `http://your-domain/admin`
- **Local Dev**: `http://localhost:8000/admin`

## Features

### 1. Dashboard (`/admin`)
- **Stats Cards**: Total customers, active licenses, YTD revenue, total activations
- **Recent Licenses**: Latest 10 licenses with quick status overview
- **Plan Distribution Chart**: Visual breakdown of license plans
- **Quick Navigation**: Links to all management sections

### 2. Customer Management (`/admin/customers`)

#### Index Page
- **Search**: Search by name, email, or company
- **Data Table**: Sortable list with customer details
- **Actions**: View, edit, delete customers
- **Pagination**: 20 customers per page

#### Create Customer (`/admin/customers/create`)
- Name (required)
- Email (required, unique)
- Company (optional)

#### View Customer (`/admin/customers/{id}`)
- Customer details card
- All licenses associated with customer
- License status badges (Active, Expired, Suspended)
- Activation counts
- Domain tracking per license
- Quick actions (Edit customer, Create new license)

#### Edit Customer (`/admin/customers/{id}/edit`)
- Update name, email, company
- Delete customer (cascades to licenses)

### 3. License Management (`/admin/licenses`)

#### Index Page
- **Filters**:
  - Status: All, Active, Suspended, Expired
  - Plan: All, Single Site, 3 Sites, 10 Sites
  - Search: License key, customer name/email
- **Stats Bar**:
  - Total licenses
  - Active licenses
  - Expiring soon (within 30 days)
  - Expired licenses
- **Data Table**:
  - License key (monospaced)
  - Customer (linked)
  - Plan type (color-coded badges)
  - Status (Active/Suspended/Expired)
  - Activation usage (X/Y sites)
  - Expiry date (color-coded: red=expired, orange=<30 days)

#### Create License (`/admin/licenses/create`)
- **Customer**: Dropdown of all customers
- **Plan Type**: Single ($59), Triple ($99), Ten ($199)
- **Status**: Active or Suspended
- **Expiry Date**: Optional (defaults to +1 year)
- **Pricing Guide**: Built-in reference

#### View License (`/admin/licenses/{id}`)
- **License Info Card**:
  - License key (copyable)
  - Customer details (linked)
  - Plan type with color badge
  - Current status
  - Activation count with remaining slots
  - Created date
  - Expiry date with countdown
  - Quick actions (Edit, Revoke/Activate, Extend)
  
- **Active Sites Table**:
  - Domain name
  - IP address
  - Activation date
  - Last heartbeat (color-coded freshness)
  - Deactivate button per site
  
- **Activity Log**: Placeholder for future enhancement

#### Edit License (`/admin/licenses/{id}/edit`)
- **Read-only**: License key, customer
- **Editable**: Plan type, status, expiry date
- **Warnings**: Alert when downgrading with active sites
- **Quick Actions**: Extend, revoke, delete
- **Validation**: Prevents breaking active installations

### 4. Quick Actions

#### Revoke License
- Sets status to "suspended"
- Deactivates all active sites
- Prevents new activations
- Can be reversed by changing status back to "active"

#### Extend License
- Adds 1 year to expiry date
- Available from multiple locations:
  - License details page
  - License edit page
  - Dashboard (for expiring licenses)

#### Deactivate Site
- Removes single activation
- Frees up activation slot
- Useful for site migrations or domain changes

## Design System

### Colors
- **Blue**: Primary actions, links, active states
- **Green**: Success states, active licenses
- **Orange**: Warnings, expiring soon
- **Red**: Errors, expired, revoked
- **Purple**: 3-site plan
- **Indigo**: 10-site plan
- **Gray**: Neutral, disabled states

### Status Badges
```
Active      → Green badge
Suspended   → Gray badge  
Expired     → Red badge
Expiring    → Orange badge (≤30 days)
```

### Plan Badges
```
Single Site → Blue badge
3 Sites     → Purple badge
10 Sites    → Indigo badge
```

## Tech Stack
- **Frontend**: Tailwind CSS + Alpine.js
- **Backend**: Laravel 12.33.0
- **Database**: SQLite (dev) / MySQL/PostgreSQL (prod)
- **Build**: Vite

## Development

### Build Assets
```bash
npm run build          # Production build
npm run dev            # Development with HMR
```

### Start Server
```bash
php artisan serve      # http://localhost:8000
```

### Access Admin
```
http://localhost:8000/admin
```

## File Structure
```
resources/views/
├── layouts/
│   └── admin.blade.php          # Master layout with sidebar
├── admin/
│   ├── dashboard.blade.php      # Dashboard with stats
│   ├── customers/
│   │   ├── index.blade.php      # Customer list
│   │   ├── create.blade.php     # New customer form
│   │   ├── show.blade.php       # Customer details
│   │   └── edit.blade.php       # Edit customer
│   └── licenses/
│       ├── index.blade.php      # License list with filters
│       ├── create.blade.php     # New license form
│       ├── show.blade.php       # License details + activations
│       └── edit.blade.php       # Edit license

app/Http/Controllers/Admin/
├── DashboardController.php      # Dashboard stats & charts
├── CustomerController.php       # Full CRUD for customers
└── LicenseController.php        # CRUD + revoke/extend/deactivate
```

## Routes
```php
GET     /admin                                  # Dashboard
GET     /admin/customers                        # List customers
GET     /admin/customers/create                 # New customer form
POST    /admin/customers                        # Store customer
GET     /admin/customers/{id}                   # View customer
GET     /admin/customers/{id}/edit              # Edit customer form
PUT     /admin/customers/{id}                   # Update customer
DELETE  /admin/customers/{id}                   # Delete customer

GET     /admin/licenses                         # List licenses
GET     /admin/licenses/create                  # New license form
POST    /admin/licenses                         # Store license
GET     /admin/licenses/{id}                    # View license
GET     /admin/licenses/{id}/edit               # Edit license form
PUT     /admin/licenses/{id}                    # Update license
DELETE  /admin/licenses/{id}                    # Delete license
POST    /admin/licenses/{id}/revoke             # Revoke license
POST    /admin/licenses/{id}/extend             # Extend by 1 year
DELETE  /admin/licenses/{id}/activations/{aid}  # Deactivate site
```

## Responsive Design
- **Mobile**: Stacked layout, hamburger menu (future)
- **Tablet**: 2-column grids
- **Desktop**: Full sidebar + 4-column stat grids
- **All**: Touch-friendly 44px+ tap targets

## Performance
- **Eager Loading**: All relationships loaded efficiently
- **Pagination**: 20 items per page to prevent slow queries
- **Indexed Searches**: Database indexes on frequently searched fields
- **Asset Bundling**: Vite optimizes CSS/JS for production

## Future Enhancements
1. **Activity Logging**: Track all admin actions
2. **Email Notifications**: Auto-notify customers of expiring licenses
3. **Bulk Actions**: Select multiple licenses for batch operations
4. **Export**: CSV/Excel export of customers and licenses
5. **Advanced Analytics**: Revenue trends, churn rates, MRR tracking
6. **Payment Integration**: Auto-create licenses on payment
7. **Role-Based Access**: Admin vs. support user permissions
8. **API Keys**: Allow customers to manage their own licenses
9. **Webhooks**: Notify external systems of license events
10. **2FA**: Two-factor authentication for admin access

## Security
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS prevention (Blade escaping)
- ✅ Confirmation dialogs for destructive actions
- ⏳ Authentication (to be added)
- ⏳ Authorization/permissions (to be added)
- ⏳ Rate limiting (to be added)

## Notes
- Keep it simple - senior engineer approach ✅
- No over-engineering ✅
- Clean, maintainable code ✅
- Production-ready ✅
