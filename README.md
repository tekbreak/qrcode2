# QR Code SaaS Platform

A full-featured SaaS platform for creating, managing, and tracking QR codes. Built with Laravel 12, Livewire 4, Alpine.js, and Tailwind CSS 4.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Local Development Setup](#local-development-setup)
- [Demo Accounts](#demo-accounts)
- [QR Code Types](#qr-code-types)
- [Credit System](#credit-system)
- [Subscription Plans](#subscription-plans)
- [Link Proxy & Analytics](#link-proxy--analytics)
- [REST API](#rest-api)
- [Artisan Commands](#artisan-commands)
- [Application Routes](#application-routes)
- [Architecture Overview](#architecture-overview)
- [Environment Variables](#environment-variables)
- [Production Deployment](#production-deployment)

---

## Features

- **14 QR code types** — URL, Text, vCard, WiFi, Email, Phone, SMS, Geo, Calendar Event, Crypto, App Store, Social Media, PDF/File, Restaurant Menu
- **Dynamic links** — Proxy all QR scans through your domain for tracking and real-time destination changes
- **Advanced customization** — Colors, gradients, dot/eye shapes, logo upload or built-in icon picker (Font Awesome Regular SVGs), frames with CTA text, design templates, SVG/EPS export
- **Analytics dashboard** — Scan counts, unique visitors, geo-location (GeoIP), device/OS/browser breakdown, referrer tracking, time-series charts
- **Credit-based billing** — Freemium model with monthly credit allowances and purchasable credit packs
- **Stripe subscriptions** — 4 tiers (Free, Starter, Pro, Enterprise) with monthly/yearly billing via Laravel Cashier
- **Team collaboration** — Invite members, shared QR codes, role-based access (Owner / Admin / Member)
- **REST API** — Full CRUD for QR codes and analytics (Sanctum-authenticated)
- **Bulk generation** — Upload a CSV to generate hundreds of QR codes at once via background jobs
- **Link features** — Expiration dates, password protection, custom slugs, A/B testing, geo/device-based redirects, scheduling, retargeting pixels
- **Custom domains** — Map your own domain for branded short links (paid plans)
- **Authentication** — Email/password, Google OAuth (Socialite), Magic Link login, email verification, password reset
- **Admin panel** — User management, subscription overview, platform-wide QR stats, moderation tools
- **Internationalization** — English and Spanish translations out of the box
- **GDPR compliance** — Cookie consent banner, account data export, account deletion

---

## Tech Stack

| Layer          | Technology                                     |
|----------------|-------------------------------------------------|
| Backend        | PHP 8.2+, Laravel 12                           |
| Frontend       | Livewire 4, Alpine.js, Tailwind CSS 4          |
| Database       | MySQL 8 / MariaDB 10.6+                        |
| Cache/Queue    | Redis (recommended) or Database driver          |
| QR Generation  | `chillerlan/php-qrcode` v5                     |
| Image Handling | `intervention/image` v3, `choowx/rasterize-svg` (SVG→PNG) |
| Payments       | Laravel Cashier (Stripe)                       |
| Auth           | Laravel Socialite, Sanctum                     |
| Permissions    | `spatie/laravel-permission`                    |
| Build Tool     | Vite 7                                         |

---

## Requirements

- PHP 8.2+ (with extensions: `gd`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, `curl`)
- Composer 2+
- Node.js 20.19+ or 22.12+
- npm 10+
- MySQL 8 / MariaDB 10.6+
- Redis (optional but recommended for cache, sessions, and queues)

---

## Installation

```bash
# 1. Clone the repository
git clone <repository-url> qrcode_app
cd qrcode_app

# 2. Install PHP dependencies
composer install

# 3. Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# 4. Configure your .env file (see Environment Variables section below)

# 5. Create the database
mysql -u root -e "CREATE DATABASE qrcode_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Run migrations and seed demo data
php artisan migrate --seed

# 7. Install frontend dependencies and build assets
npm install
npm run build
```

Or use the Composer setup script which runs steps 2-7:

```bash
composer setup
```

---

## Local Development Setup

### Apache Virtual Host

Add to your Apache configuration (e.g., `httpd-vhosts.conf`):

```apache
<VirtualHost *:81>
    ServerName qrcode.local
    ServerAlias go.qrcode.local
    DocumentRoot /path/to/qrcode_app/public

    <Directory /path/to/qrcode_app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Hosts File

Add to `/etc/hosts`:

```
127.0.0.1  qrcode.local
127.0.0.1  go.qrcode.local
```

### Running the Dev Server

For development with hot-reloading:

```bash
npm run dev          # Vite dev server (in a separate terminal)
php artisan serve    # Laravel dev server
```

Or use the all-in-one Composer dev script:

```bash
composer dev         # Starts server, queue worker, log watcher, and Vite concurrently
```

---

## Demo Accounts

After running `php artisan migrate --seed`, the following accounts are available:

### Admin Account

| Field    | Value                |
|----------|----------------------|
| Email    | `admin@example.com`  |
| Password | `password`           |
| Role     | Administrator        |
| Credits  | 5 (Free plan)        |

The admin account has access to the **Admin Panel** at `/admin`.

### Regular User Account

| Field    | Value                |
|----------|----------------------|
| Email    | `test@example.com`   |
| Password | `password`           |
| Role     | Standard user        |
| Credits  | 5 (Free plan)        |

> Both accounts use `password` as the default password (set by Laravel's `UserFactory`).

---

## QR Code Types

| Type              | Key          | Dynamic | Description                                           |
|-------------------|--------------|---------|-------------------------------------------------------|
| Website URL       | `url`        | Yes     | Link to any website with scan tracking                |
| Plain Text        | `text`       | No      | Encode arbitrary text                                 |
| Contact Card      | `vcard`      | No      | Share contact info (vCard format)                     |
| WiFi Network      | `wifi`       | No      | Auto-connect to a WiFi network                       |
| Email Address     | `email`      | No      | Pre-filled email with subject and body                |
| Phone Number      | `phone`      | No      | Dial a phone number                                   |
| SMS Message       | `sms`        | No      | Pre-filled SMS with recipient and message             |
| Location          | `geo`        | No      | Open a map to specific GPS coordinates                |
| Calendar Event    | `event`      | No      | Add an event to the user's calendar                   |
| Cryptocurrency    | `crypto`     | No      | Bitcoin/Ethereum payment request                      |
| App Store         | `app_store`  | Yes     | Link to app on iOS/Android stores                     |
| Social Media      | `social`     | Yes     | Link to social media profiles                         |
| PDF / File        | `pdf`        | Yes     | Share a downloadable file                             |
| Restaurant Menu   | `menu`       | Yes     | Digital restaurant menu                               |

**Dynamic** types (marked "Yes") include scan tracking, analytics, password protection, expiration, and more. Each active dynamic QR code costs **5 credits/month** for maintenance (hosting the redirect, tracking scans). Any edit — changing URL, uploading a new file, updating content — costs an additional **5 credits per edit**. Non-dynamic types are always static and cannot be edited after creation.

---

## Credit System

### Pricing Model: Scenario A (recommended)

**Why this model works:**
- **Clean numbers**: 5 / 50 / 200 / 1,000 credits are easy to understand
- **~€0.10 per credit** at Starter is simple math for users
- **Volume discount**: Starter = €0.10/credit, Pro = €0.075/credit (25% cheaper), Enterprise = €0.05/credit (50% cheaper)
- **Free tier is meaningful**: 1 dynamic QR maintained for free, but edits require an upgrade — a natural conversion trigger
- **Balanced upgrade pressure**: generous enough to demonstrate value, strict enough to drive paid conversions

### Credit Costs

| Action                                   | Credits | EUR value (Starter) |
|------------------------------------------|---------|---------------------|
| Dynamic QR maintenance (per QR/month)    | 5       | €0.50               |
| Edit dynamic QR (per edit)               | 5       | €0.50               |
| Premium customization                    | 2       | €0.20               |
| SVG download                             | 1       | €0.10               |
| EPS download                             | 3       | €0.30               |
| Bulk generation (per QR)                 | 3       | €0.30               |
| API call                                 | 5       | €0.50               |
| Analytics export                         | 5       | €0.50               |
| Scans (per 1,000)                        | 1       | €0.10               |

### How it works

1. **All QR codes start static** — creating any QR code is free (within the static QR limit of your plan).
2. **Converting to dynamic** — when a user edits a dynamic-capable QR code for the first time, it becomes dynamic. The monthly maintenance cost (5 credits/month) is prorated from the conversion date to the end of the billing cycle.
3. **Monthly maintenance** — each active dynamic QR code costs 5 credits/month, charged at the start of each billing cycle. This covers hosting the redirect link, scan tracking, and analytics.
4. **Editing** — each edit to a dynamic QR code (changing URL, uploading a new file, updating menu, etc.) costs 5 credits on top of the maintenance fee.
5. **Credits reset monthly** based on the user's subscription plan.
6. **Credit packs** — users on any non-Enterprise plan can buy additional credits as one-time purchases. Purchased credits stack on top of the monthly allowance and never expire (they persist across billing resets).
7. **Enterprise is unlimited** — Enterprise users bypass the credit system entirely. All actions (dynamic QR maintenance, edits, downloads, API calls, exports) are included at no additional cost. Transactions are still logged for audit purposes.

### Credit Packs (One-Time Purchase)

| Pack        | Credits | Price | EUR/credit |
|-------------|---------|-------|------------|
| 10 credits  | 10      | €2    | €0.20      |
| 50 credits  | 50      | €8    | €0.16      |
| 150 credits | 150     | €20   | €0.13      |
| 500 credits | 500     | €50   | €0.10      |

Credit packs are available on the Billing page. When Stripe is configured, they use Stripe Checkout for secure one-time payments. In development mode (no Stripe price ID), credits are added directly for testing.

Configure Stripe price IDs in `.env`:

```env
STRIPE_CREDIT_PACK_10_PRICE_ID=price_xxx
STRIPE_CREDIT_PACK_50_PRICE_ID=price_xxx
STRIPE_CREDIT_PACK_150_PRICE_ID=price_xxx
STRIPE_CREDIT_PACK_500_PRICE_ID=price_xxx
```

### Real-World Usage Examples

**Free user (5 credits/month):**
- 3 static QR codes (free) + 1 dynamic QR maintained (5 credits) = uses full allowance, no edits possible without upgrading

**Starter user (50 credits/month, €5/mo):**
- 5 dynamic QRs maintained (25 credits) + 3 edits (15 credits) + 2 SVG downloads (2 credits) = 42 credits
- 8 dynamic QRs maintained (40 credits) + 1 edit (5 credits) + 1 analytics export (5 credits) = 50 credits

**Pro user (200 credits/month, €15/mo):**
- 20 dynamic QRs maintained (100 credits) + 10 edits (50 credits) + 10 SVG downloads (10 credits) = 160 credits

**Enterprise user (€50/mo):**
- Unlimited static and dynamic QR codes, unlimited edits, all features included — no credit tracking

---

## Subscription Plans

| Feature               | Free       | Starter          | Pro              | Enterprise         |
|-----------------------|------------|------------------|------------------|--------------------|
| Monthly price         | €0         | €5               | €15              | €50                |
| Yearly price          | €0         | €50 (€4.17/mo)   | €150 (€12.50/mo) | €500 (€41.67/mo)   |
| Monthly credits       | 5          | 50               | 200              | **Unlimited**      |
| Static QR codes       | 3          | 10               | 50               | Unlimited          |
| Dynamic QR codes      | Up to 1    | Up to 10         | Up to 40         | **Unlimited**      |
| Customization         | Basic      | Basic            | Full             | Full               |
| Download formats      | PNG        | PNG, JPG         | All formats      | All formats        |
| Analytics             | Basic      | **Advanced**     | Advanced         | Advanced           |
| Custom domains        | No         | No               | Yes              | Yes                |
| API access            | No         | No               | Yes              | Yes                |
| Bulk operations       | No         | No               | No               | Yes                |
| Teams                 | No         | No               | No               | Yes                |
| Priority support      | No         | No               | No               | Yes                |
| EUR/credit            | —          | €0.10            | €0.075           | N/A (unlimited)    |

Features are defined in the `App\Enums\Feature` enum and assigned to tiers in `App\Enums\PlanTier::features()`. To check if a user has access to a feature:

```php
// In controllers/services
$user->hasFeature(Feature::AdvancedAnalytics);  // true for Starter, Pro, Enterprise
$user->hasFeature(Feature::ExportEps);          // true for Pro, Enterprise only
$user->hasFeature(Feature::BulkOperations);     // true for Enterprise only

// In Blade templates
@if(auth()->user()->hasFeature(\App\Enums\Feature::AdvancedAnalytics))
    {{-- show advanced analytics UI --}}
@endif
```

---

## Link Proxy & Analytics

All QR codes start as static. When a user edits a dynamic-capable QR code (URL, Social, App Store, PDF, Menu), it is upgraded to dynamic and a short link is created. The first conversion prorates the 5 credits/month maintenance fee, and each edit (including the first) costs 5 credits.

Dynamic QR codes are served through a proxy domain (default: `go.qrcode.local:81`). When a user scans a dynamic QR code:

1. The scanner's browser hits `http://go.qrcode.local:81/{slug}`
2. `RedirectController` resolves the short link (with Redis caching in production)
3. A `RecordScanJob` is dispatched to the queue to asynchronously record:
   - IP address and geo-location (GeoIP)
   - Device type, OS, browser (parsed from User-Agent)
   - Referrer URL
   - Timestamp
4. The user is redirected (302) to the target URL

### Link Features

- **Custom slugs** — Use a branded slug instead of the random 7-character default
- **Expiration** — Set a date after which the link returns a 410 Gone response
- **Password protection** — Require a password before redirecting
- **A/B testing** — Split traffic between multiple destination URLs
- **Geo/device redirects** — Route users based on their country or device type
- **Scheduling** — Activate links only during specific date ranges
- **Retargeting pixels** — Attach Facebook/Google tracking pixels to your links

---

## REST API

The API is authenticated via **Laravel Sanctum** (Bearer token). Generate tokens from the Settings page.

Base URL: `http://qrcode.local:81/api`

### Endpoints

| Method   | Endpoint                           | Description                     |
|----------|------------------------------------|---------------------------------|
| `GET`    | `/api/user`                        | Get authenticated user          |
| `GET`    | `/api/qr-codes`                    | List all QR codes               |
| `POST`   | `/api/qr-codes`                    | Create a new QR code            |
| `GET`    | `/api/qr-codes/{id}`               | Get a specific QR code          |
| `PUT`    | `/api/qr-codes/{id}`               | Update a QR code                |
| `DELETE` | `/api/qr-codes/{id}`               | Delete a QR code                |
| `GET`    | `/api/qr-codes/{id}/download`      | Download QR code image          |
| `GET`    | `/api/qr-codes/{id}/analytics`     | Get scan analytics for a QR code|

### Example: Create a QR Code

```bash
curl -X POST http://qrcode.local:81/api/qr-codes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "url",
    "name": "My Website",
    "data": {"url": "https://example.com"},
    "is_dynamic": true
  }'
```

---

## Artisan Commands

| Command                           | Description                                          |
|-----------------------------------|------------------------------------------------------|
| `php artisan migrate --seed`      | Run all migrations and seed demo data                |
| `php artisan analytics:aggregate` | Aggregate raw scan data into hourly summaries        |
| `php artisan credits:reset`       | Reset monthly credit allowances for all users        |
| `php artisan queue:work`          | Start the queue worker for background jobs           |

### Scheduled Tasks

Add the Laravel scheduler to your system crontab:

```
* * * * * cd /path/to/qrcode_app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler handles:
- **Every 15 minutes**: `analytics:aggregate` — Compresses raw scan data into the `scan_aggregates` table
- **Hourly**: `credits:reset` — Checks each user's `resets_at` timestamp and refills credit balances when the monthly reset date has passed

---

## Application Routes

### Public

| URL                         | Description          |
|-----------------------------|----------------------|
| `/`                         | Landing page         |
| `/login`                    | Login page           |
| `/register`                 | Registration page    |
| `/forgot-password`          | Password reset form  |
| `/auth/google`              | Google OAuth redirect|
| `/auth/magic-link`          | Magic link login     |

### Authenticated

| URL                         | Description                 |
|-----------------------------|-----------------------------|
| `/dashboard`                | User dashboard with stats   |
| `/qr-codes`                 | List all QR codes           |
| `/qr-codes/create`          | QR code builder             |
| `/qr-codes/{id}/edit`       | Edit a QR code              |
| `/qr-codes/bulk`            | Bulk CSV upload generator   |
| `/analytics`                | Analytics overview          |
| `/analytics/{qrCode}`       | Detailed analytics per code |
| `/billing`                  | Subscription management     |
| `/settings`                 | User settings & API tokens  |

### Admin (requires `is_admin = true`)

| URL                         | Description                    |
|-----------------------------|--------------------------------|
| `/admin`                    | Admin dashboard                |

---

## Architecture Overview

```
app/
├── Console/Commands/        # Artisan commands (AggregateAnalytics, ResetCredits)
├── Enums/                   # QrCodeType, PlanTier, CreditAction
├── Http/
│   ├── Controllers/
│   │   ├── Api/             # REST API (QrCodeApiController, AnalyticsApiController)
│   │   ├── Auth/            # GoogleController, MagicLinkController
│   │   └── RedirectController.php  # Link proxy handler
│   └── Middleware/           # SetLocale, EnsureUserIsAdmin
├── Jobs/                    # RecordScanJob, BulkGenerateQrCodesJob
├── Livewire/
│   ├── Admin/               # AdminDashboard
│   ├── Analytics/           # AnalyticsIndex
│   ├── Auth/                # Login, Register, ForgotPassword, ResetPassword
│   ├── Billing/             # BillingIndex
│   ├── QrCodes/             # QrCodeBuilder, QrCodeIndex, BulkGenerator
│   ├── Settings/            # SettingsIndex
│   ├── Teams/               # TeamManager
│   └── Dashboard.php
├── Models/                  # User, QrCode, QrDesign, ShortLink, Scan,
│                            # ScanAggregate, Plan, CreditBalance,
│                            # CreditTransaction, Team, CustomDomain
└── Services/                # CreditService, QrCodeGeneratorService,
                             # AnalyticsService
```

---

## Environment Variables

Key variables to configure in your `.env` file:

| Variable               | Description                          | Example                     |
|------------------------|--------------------------------------|-----------------------------|
| `APP_URL`              | Application base URL                 | `http://qrcode.local:81`   |
| `DB_CONNECTION`        | Database driver                      | `mysql`                     |
| `DB_DATABASE`          | Database name                        | `qrcode_app`               |
| `DB_USERNAME`          | Database user                        | `qrcode_app`               |
| `DB_PASSWORD`          | Database password                    | `qrcode_app`               |
| `PROXY_DOMAIN`         | Domain for dynamic QR short links    | `go.qrcode.local:81`       |
| `PROXY_SCHEME`         | Scheme for proxy URLs                | `http` or `https`           |
| `SESSION_DRIVER`       | Session backend                      | `database` or `redis`       |
| `QUEUE_CONNECTION`     | Queue backend                        | `database` or `redis`       |
| `CACHE_STORE`          | Cache backend                        | `database` or `redis`       |
| `STRIPE_KEY`           | Stripe publishable key               | `pk_test_...`               |
| `STRIPE_SECRET`        | Stripe secret key                    | `sk_test_...`               |
| `STRIPE_WEBHOOK_SECRET`| Stripe webhook signing secret        | `whsec_...`                 |
| `GOOGLE_CLIENT_ID`     | Google OAuth client ID               | `123...apps.google...`      |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret           | `GOCSPX-...`                |
| `MAIL_HOST`            | SMTP host                            | `in-v3.mailjet.com`         |
| `MAIL_USERNAME`        | SMTP username (Mailjet API key)      | *(your key)*                |
| `MAIL_PASSWORD`        | SMTP password (Mailjet secret)       | *(your secret)*             |

---

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure a MySQL/MariaDB database and update `DB_*` variables
3. Set up Redis and point `SESSION_DRIVER`, `QUEUE_CONNECTION`, and `CACHE_STORE` to `redis`
4. Configure Stripe keys for live billing
5. Configure Google OAuth credentials with your production callback URL
6. Set `PROXY_DOMAIN` and `PROXY_SCHEME` to your production short-link domain (e.g., `go.yourdomain.com` with `https`)
7. Build frontend assets: `npm run build`
8. Optimize Laravel: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
9. Set up the scheduler cron: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
10. Start the queue worker with Supervisor:

```ini
[program:qrcode-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/qrcode_app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/qrcode_app/storage/logs/worker.log
```

---

## License

This project is proprietary software. All rights reserved.
