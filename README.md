# dynQR

A full-featured SaaS platform for creating, managing, and tracking dynamic QR codes. Built with Laravel 12, Livewire 4, Alpine.js, and Tailwind CSS 4.

**Website:** [dynqr.com](https://dynqr.com)

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Local Development Setup](#local-development-setup)
- [Demo Accounts](#demo-accounts)
- [QR Code Types](#qr-code-types)
- [Pricing Model](#pricing-model)
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
- **Subscription billing** — Three tiers (Starter, Pro, Enterprise) with feature gating via Laravel Cashier
- **Pay-per-action** — €1 one-time Stripe payments for dynamic QR edits on Starter and Pro plans
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
git clone <repository-url> dynqr
cd dynqr

# 2. Install PHP dependencies
composer install

# 3. Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# 4. Configure your .env file (see Environment Variables section below)

# 5. Create the database
mysql -u root -e "CREATE DATABASE dynqr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

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
    ServerName dynqr.local
    ServerAlias go.dynqr.local
    DocumentRoot /path/to/dynqr/public

    <Directory /path/to/dynqr/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Hosts File

Add to `/etc/hosts`:

```
127.0.0.1  dynqr.local
127.0.0.1  go.dynqr.local
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
| Plan     | Starter (free)       |

The admin account has access to the **Admin Panel** at `/admin`.

### Regular User Account

| Field    | Value                |
|----------|----------------------|
| Email    | `test@example.com`   |
| Password | `password`           |
| Role     | Standard user        |
| Plan     | Starter (free)       |

### Demo User Account

| Field    | Value                |
|----------|----------------------|
| Email    | `demo@example.com`   |
| Password | `password`           |
| Role     | Standard user        |
| Plan     | Starter (free)       |

> All mock accounts use `password` as the default password. Re-seed anytime with `php artisan db:seed --class=MockUserSeeder`.

> On local (`APP_ENV=local`), the login page shows one-click buttons for each mock account.

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

**Dynamic** types (marked "Yes") include scan tracking, analytics, password protection, expiration, and more. Creating your first dynamic QR is included in your plan quota. Subsequent edits to URL, password, expiration, or scan limits cost **€1 per action** on Starter and Pro (Enterprise includes unlimited edits).

---

## Pricing Model

The platform uses a **subscription + pay-per-action** model:

- **Subscriptions** define capacity (static/dynamic QR limits) and feature access (downloads, API, bulk, teams, etc.)
- **€1 one-time payments** apply to dynamic QR mutations after the initial activation (Starter & Pro only)
- **No credits, no credit packs, no monthly resets**

### €1 Paid Actions (Starter & Pro)

| Action | Price |
|--------|-------|
| Edit dynamic QR destination URL | €1 |
| Enable / change password protection | €1 |
| Set or extend expiration date | €1 |
| Update max scan limit | €1 |
| Re-activate an expired or paused dynamic QR | €1 |

Enterprise users are not charged for these actions.

Configure the Stripe price ID in `.env`:

```env
STRIPE_PAID_ACTION_PRICE_ID=price_xxx
```

Paid actions are handled by `PaidActionService` → Stripe Checkout → `PaidActionController` success callback.

---

## Subscription Plans

| Feature               | Starter    | Pro              | Enterprise         |
|-----------------------|------------|------------------|--------------------|
| Monthly price         | €0         | €10              | €39                |
| Yearly price          | €0         | €99 (~17% off)   | €389 (~17% off)    |
| Static QR codes       | 5          | Unlimited        | Unlimited          |
| Dynamic QR codes      | 1          | 10               | Unlimited          |
| Dynamic QR edits      | €1 each    | €1 each          | **Included**       |
| Customization         | Basic      | Full             | Full               |
| Download formats      | PNG        | PNG, JPG, SVG, EPS | All formats      |
| Analytics             | Basic (30d)| Advanced (1yr)   | Advanced (unlimited)|
| Analytics export      | No         | Yes              | Yes                |
| API access            | No         | Yes (1k/mo)      | Unlimited          |
| Bulk operations       | No         | Yes (500/mo)     | Unlimited          |
| Custom domains        | No         | No               | Yes                |
| Teams                 | No         | No               | Yes (10 members)   |
| Priority support      | No         | No               | Yes                |

Features are defined in `App\Enums\Feature` and assigned to tiers in `App\Enums\PlanTier::features()`. Feature gating is enforced via `$user->hasFeature()`:

```php
// In controllers/services
$user->hasFeature(Feature::AdvancedAnalytics);  // true for Pro, Enterprise
$user->hasFeature(Feature::ExportSvg);          // true for Pro, Enterprise
$user->hasFeature(Feature::BulkOperations);    // true for Pro, Enterprise
$user->hasFeature(Feature::Teams);              // true for Enterprise only

// In Blade templates
@if(auth()->user()->hasFeature(\App\Enums\Feature::ExportSvg))
    {{-- show SVG download button --}}
@endif
```

Plan configuration lives in `config/qrcode.php` and is seeded via `PlanSeeder`.

---

## Link Proxy & Analytics

All QR codes start as static. When a user edits a dynamic-capable QR code (URL, Social, App Store, PDF, Menu), it is upgraded to dynamic and a short link is created. The first activation is free within plan limits. Subsequent edits trigger a €1 Stripe payment (except on Enterprise).

Dynamic QR codes are served through a proxy domain (default: `go.dynqr.local:81`). When a user scans a dynamic QR code:

1. The scanner's browser hits `http://go.dynqr.local:81/{slug}`
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

Base URL: `http://dynqr.local:81/api`

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
curl -X POST http://dynqr.local:81/api/qr-codes \
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
| `php artisan queue:work`          | Start the queue worker for background jobs           |

### Scheduled Tasks

Add the Laravel scheduler to your system crontab:

```
* * * * * cd /path/to/dynqr && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler handles:
- **Every 15 minutes**: `analytics:aggregate` — Compresses raw scan data into the `scan_aggregates` table

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
| `/paid-actions/{id}/success`| Paid action checkout success|
| `/paid-actions/{id}/cancel` | Paid action checkout cancel |
| `/settings`                 | User settings & API tokens  |

### Admin (requires `is_admin = true`)

| URL                         | Description                    |
|-----------------------------|--------------------------------|
| `/admin`                    | Admin dashboard                |

---

## Architecture Overview

```
app/
├── Console/Commands/        # Artisan commands (AggregateAnalytics)
├── Enums/                   # QrCodeType, PlanTier, Feature, PaidActionType
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
│                            # ScanAggregate, Plan, PaidAction, Team, CustomDomain
└── Services/                # PaidActionService, SubscriptionService,
                             # QrCodeGeneratorService, AnalyticsService
```

---

## Environment Variables

Key variables to configure in your `.env` file:

| Variable               | Description                          | Example                     |
|------------------------|--------------------------------------|-----------------------------|
| `APP_NAME`             | Application display name               | `dynQR`                     |
| `APP_URL`              | Application base URL                 | `http://dynqr.local:81`    |
| `DB_CONNECTION`        | Database driver                      | `mysql`                     |
| `DB_DATABASE`          | Database name                        | `dynqr`                     |
| `DB_USERNAME`          | Database user                        | `dynqr`                     |
| `DB_PASSWORD`          | Database password                    | *(your password)*           |
| `PROXY_DOMAIN`         | Domain for dynamic QR short links    | `go.dynqr.local:81`        |
| `PROXY_SCHEME`         | Scheme for proxy URLs                | `http` or `https`           |
| `SESSION_DRIVER`       | Session backend                      | `database` or `redis`       |
| `QUEUE_CONNECTION`     | Queue backend                        | `database` or `redis`       |
| `CACHE_STORE`          | Cache backend                        | `database` or `redis`       |
| `STRIPE_KEY`           | Stripe publishable key               | `pk_test_...`               |
| `STRIPE_SECRET`        | Stripe secret key                    | `sk_test_...`               |
| `STRIPE_WEBHOOK_SECRET`| Stripe webhook signing secret        | `whsec_...`                 |
| `STRIPE_PRO_MONTHLY_PRICE_ID` | Pro plan monthly price ID     | `price_...`                 |
| `STRIPE_PRO_YEARLY_PRICE_ID` | Pro plan yearly price ID (€99) | `price_...`                 |
| `STRIPE_ENTERPRISE_MONTHLY_PRICE_ID` | Enterprise monthly price ID | `price_...`          |
| `STRIPE_ENTERPRISE_YEARLY_PRICE_ID` | Enterprise yearly price ID  | `price_...`                 |
| `STRIPE_PAID_ACTION_PRICE_ID` | €1 paid action price ID      | `price_...`                 |
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
6. Set `PROXY_DOMAIN` and `PROXY_SCHEME` to your production short-link domain (e.g., `go.dynqr.com` with `https`)
7. Build frontend assets: `npm run build`
8. Optimize Laravel: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
9. Set up the scheduler cron: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
10. Start the queue worker with Supervisor:

```ini
[program:dynqr-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/dynqr/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/dynqr/storage/logs/worker.log
```

---

## License

This project is proprietary software. All rights reserved.
