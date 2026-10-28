# 💅 Mahru — Online Booking Platform for Beauty Salons

[فارسی](README.md) | **English**

> A multi-tenant SaaS: every salon gets its own admin panel, specialist panel, customer site, payment gateways and fully separated data.
> Built with Laravel 13, Blade, Tailwind CSS and plain JavaScript; two Android apps (Expo) for customers and specialists.
> Main domain: `arisheh.ir`. The product UI is Persian (RTL); this file is the English summary of [`README.md`](README.md), which stays the primary version.

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=flat-square&logo=vite)](https://vitejs.dev)
[![Tests](https://img.shields.io/badge/tests-2363%20passing-brightgreen?style=flat-square)](#-running-the-tests)

---

## 📋 Contents

- [Overview](#-overview)
- [Multi-tenancy](#-multi-tenancy)
- [Features](#-features)
- [Mobile apps](#-mobile-apps)
- [Architecture](#-architecture)
- [Requirements](#-requirements)
- [Local setup (XAMPP)](#-local-setup-xampp)
- [Running with Docker](#-running-with-docker)
- [Environment variables](#-environment-variables)
- [Subscriptions, plans and pricing](#-subscriptions-plans-and-pricing)
- [Notifications](#-notifications)
- [Project layout](#-project-layout)
- [Roles](#-roles)
- [Routes and APIs](#-routes-and-apis)
- [Payment and payout gateways](#-payment-and-payout-gateways)
- [Scheduled jobs and queues](#-scheduled-jobs-and-queues)
- [Running the tests](#-running-the-tests)
- [Project Artisan commands](#-project-artisan-commands)
- [Database tables](#-database-tables)
- [Deploying to a server](#-deploying-to-a-server)
- [Troubleshooting](#-troubleshooting)
- [Changelog](#-changelog)
- [Further documentation](#-further-documentation)

---

## 🎯 Overview

**Mahru** is a software-as-a-service platform for beauty salons. A salon is created by **online sign-up** from the sales page (or by the platform admin), has a subscription plan, and gets:

- **Customer site and panel** — booking, online or wallet payment, rescheduling and cancelling, loyalty club, multi-session treatment courses, reviews
- **Specialist panel** — today's schedule, bookings, calendar and leave, wallet and withdrawals, reviews, reports and Excel export
- **Salon admin panel** — bookings, services, specialists, customers, payment gateways, wallet and payouts, reports, loyalty, win-back campaigns, roles, site and panel themes, settings
- **Platform admin (super-admin) panel** — salons, plans and features, subscriptions and invoices, suspension, platform and push settings, app releases, tickets, demo requests, backup status
- **Two Android apps** — "Mahru" for customers and "Mahru Hamkar" (colleague) for specialists (see [Mobile apps](#-mobile-apps))

Sign-in uses **mobile number, password and SMS code** (Kavenegar) plus **two-factor authentication (2FA)**.

---

## 🧱 Multi-tenancy

All salons share **one database**; isolation is done in the application:

- **Salon address:** each salon has a `slug` (locked after creation) and is reachable two ways:
  - path: `https://arisheh.ir/s/{salon_slug}` — always
  - subdomain (when `CENTRAL_DOMAIN` is set): `https://{salon_slug}.arisheh.ir` — see [`docs/deployment/SUBDOMAINS.md`](docs/deployment/SUBDOMAINS.md)
- **Current salon (`CurrentSalon`):** middleware resolves the salon from the URL (customer site) or from the user's membership (admin and specialist panels), before route model binding.
- **`BelongsToSalon`:** models with a `salon_id` column are automatically scoped to the current salon, and new rows get the current salon. Strict mode: a salon-scoped query with no current salon throws unless it explicitly uses `allSalons()`.
- **`BelongsToSalonThroughSpecialist`:** for tables linked to a salon only through a specialist (e.g. reviews).
- **Queued jobs and notifications** take the salon explicitly from the record. Notification settings are read from **the salon of the record the notification is about**, not from the recipient.
- **Classification test:** every model either has a salon scope or a documented reason not to.
- **Per salon:** loyalty rewards, roles, notification/security/wallet settings, payment gateways, themes, SMS texts.
- **Platform-wide:** system roles (`admin`, `specialist`, `super-admin`, …) and the permission list — only the platform admin changes them.

---

## ✨ Features

### 🔐 Authentication and security
- Mobile + password + SMS code; customers have a separate account per salon; "remember this device"
- Staff (admins and specialists) sign in at the central `/login`; customers at their salon's page; "My salons" on the central domain
- 2FA with configurable code length
- RBAC with per-salon roles and shared permissions (e.g. a receptionist without finance access)
- Login throttling, security event log, super-admin action log
- Configurable trusted proxies (`TRUSTED_PROXIES`) so the real client IP is seen behind a CDN; by default no proxy is trusted
- Security independent of the CDN: security headers and HSTS from the app itself, CSP (report-only first), rate limits on sensitive routes, HTTPS on the origin server, a no-CDN mode
- IP and user agent of sign-ins and key actions kept for six months (art. 668), a persistent nginx access log, automatic pruning, an encrypted archive of deleted accounts
- Legal pages (privacy, terms, contact), terms acceptance recorded with version and IP, the eNamad badge, complaints with a 72-hour deadline, salon identity and licence verification

### 📅 Booking
- Pick a service, specialist and time; slots follow working hours, leave and holidays; overlapping bookings are prevented
- Manual bookings by the admin (phone/walk-in), automatic or manual confirmation, "no-show"
- SMS and push reminders
- Rescheduling and cancelling with a cancellation-penalty rule (customer and specialist separately)
- Automatic cancellation of unpaid bookings; salons can pause booking temporarily, with a banner and automatic reopening
- Per-salon "booking mode": **no prepayment** (default, the whole price at the salon) or online prepayment (only with an active gateway; falls back automatically without one)
- Per-customer daily and total future online booking limits; unreliable (no-show) customers (forced prepayment, or manual confirmation and a lower limit) and an attendance-confirmation link

### 🧴 Multi-session treatment courses
- Multi-session, multi-service packages, payment and session tracking, reminders and expiry
- Per-session notes and before/after photos **only with the customer's recorded consent**, stored on a private disk; the customer gives consent in the app, on the salon site or through an SMS link (or staff record it in person), and its history is kept with time, IP, text version and source

### 💳 Payments and wallets
- **Several gateways per salon:** Zarinpal, Zibal, Vandar, AsanPardakht, Saman, Mellat, Parsian — the customer picks one, and on outage the next one is tried; gateway fees are added to the customer's amount
- Customer money is collected only through **the salon's own merchant**; the platform merchant is used only for salon subscriptions and SMS packs
- Percentage deposit (optional, chosen by the salon), full payment, or combined (wallet + gateway)
- Customer wallet, specialist wallet with salon commission, salon wallet
- Specialist withdrawals and **automatic payouts** via Zarinpal, Zibal or Vandar — only to a verified IBAN (Sheba); manual payout is also possible
- Automatic reconciliation of stuck transactions; a "needs review" page with notification and SMS to the salon owner

### 🏆 Loyalty and win-back
- Points after a service and after a review, redeemable rewards and conversion to discount codes, point expiry
- Win-back campaigns (automatic or manual) with discount codes and an **opt-out link for marketing SMS**

### ⭐ Reviews
- One-time review links, detailed ratings, approve/reject, featured reviews, negative-review alerts

### 📊 Reports
- Dashboard; daily, weekly and monthly reports; **Excel** and **PDF** exports in the queue; specialist reports and Excel

### 📢 Notifications
- In-app notifications, SMS and **app push** with "no receipt → fallback SMS"
- Each salon chooses channels per event; SMS texts are editable
- SMS quota counted in message parts, prepaid SMS packs, short links
- Details under [Notifications](#-notifications)

### 🧾 Subscriptions, plans and themes
- Plans (trial, basic, pro, full, clinic) with 1, 3, 6 and 12-month periods and **12 features** that can be toggled per plan
- Selectable customer-site and admin-panel themes, by plan and period
- Specialist count asked at sign-up; extra-specialist fee
- Mid-period upgrade with prorated difference, automatic/manual suspension, invoices

### 📝 Content and more
- Blog, gallery, announcements (salon and platform), discount codes
- Support tickets between salons and the platform admin; demo requests from the sales page
- Salon QR and link, app download page (`/app`), PWA for iPhone
- Nightly backups with status on the super-admin dashboard

---

## 📱 Mobile apps

- **Two apps from one Expo codebase** on the orphan branch `mobile-app` of this repository: "Mahru" (customer) and "Mahru Hamkar" (specialists only; the admin panel stays on the web).
- **No app store:** the APK is uploaded in the super-admin panel ("App releases") and downloaded from `https://arisheh.ir/app`. iPhone: PWA.
- Customers enter the app via the salon's link or QR; the app holds one salon at a time. Payment runs in the phone's external browser.
- **Push:** FCM with delivery receipts; "off / shadow / live" modes in platform settings; in "live", if no receipt arrives within the deadline a fallback SMS is sent. ntfy is planned for international-internet outages.
- API: see [Routes and APIs](#-routes-and-apis); builds via expo.dev.

---

## 🏗️ Architecture

| Layer | Technology |
|------|-----------|
| Backend | Laravel 13 (PHP 8.3-8.5; the Docker image uses PHP 8.5) |
| Frontend | Blade + Tailwind CSS 3 + plain JavaScript, Vite 8; no external CDN |
| Font | Vazirmatn |
| Authentication | custom SMS login, Sanctum for the app API |
| Authorization | custom RBAC (per-salon roles + shared permissions) |
| Multi-tenancy | single database, `CurrentSalon` + global scope + ownership checks |
| Payments | driver layer (`app/Payments`): 7 gateways and 3 payout drivers |
| SMS | Kavenegar |
| Push | Firebase Cloud Messaging (+ ntfy) |
| PDF / Excel | mPDF, DomPDF / Maatwebsite Excel + PhpSpreadsheet |
| Images | Intervention Image |
| Jalali dates | morilog/jalali + hekmatinasser/verta + the self-contained jcal calendar |
| Activity log | Spatie Laravel Activitylog |
| Queue | database (or Redis) with separate queues |
| Deployment | single-container Docker (supervisord) on an ArvanCloud cloud server, behind the Arvan CDN |
| Code structure | Controller → Service → Repository (interface + Eloquent) |

---

## 📦 Requirements

| Tool | Version |
|-------|------|
| PHP | 8.3 to 8.5; tested on 8.5 (extensions: mbstring, xml, pdo_mysql, sqlite3, curl, zip, gd, intl, bcmath) |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8.0+ (six-service Docker: 8.4) or MariaDB 10.4+ (XAMPP; single container: 11.8) |
| Node.js | 22 (18 minimum) |
| Git | — |
| Docker Desktop (optional) | for the single-container mode and building the server image |

---

## 🚀 Local setup (XAMPP)

```bash
git clone https://github.com/abolfazl19910r/Beauty-Salon.git
cd Beauty-Salon
git checkout develop
cp .env.example .env                 # before composer install (package:discover connects to the database)
composer install
npm install
php artisan key:generate
```

Create a database and put its details in `.env`:

```sql
CREATE DATABASE beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate:fresh --seed      # demo salon "Rasta" (/s/rasta) and demo accounts (database/seeders/UserSeeder.php)
php artisan superadmin:create
php artisan storage:link              # on Windows, from an Administrator terminal
npm run build                         # or npm run dev
php artisan serve
php artisan queue:work --queue=otp,push,sms,payments,default,reports   # second terminal
php artisan schedule:work                                               # third terminal
```

- Sales page: **http://127.0.0.1:8000** · salon sign-up: `/salon-signup` · salon: `/s/{salon_slug}` · salon admin: `/admin` · super-admin: `/superadmin/dashboard`
- **Current data is fake.** When the table-creating migrations change, the database is rebuilt with `migrate:fresh --seed` (always announced in the changelog).
- In `local`, no real SMS is sent; the text and login code go to `storage/logs`.

---

## 🐳 Running with Docker

Two modes, both from one `Dockerfile`:

| Mode | File | Use | Guide |
|---|---|---|---|
| **Single container** (target `single`) | `docker-compose.single.yml` / `docker-compose.server.yml` | local testing on Docker Desktop, and **the real Arvan server** | [`DOCKER_DESKTOP.md`](docs/deployment/DOCKER_DESKTOP.md), [`ARVAN_SERVER.md`](docs/deployment/ARVAN_SERVER.md) |
| Six services (target `production`) | `docker-compose.yml` + `Makefile` | when load grows (separate MySQL and Redis) | [`SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md) section 5 |

**Single container:** one container running, under supervisord, MariaDB, PHP-FPM, Nginx, the queue worker, the verification-code worker, the scheduler and the nightly backup. All data lives on the `/data` volume.

```bash
copy .env.single.example .env.single                     # Windows
docker compose -f docker-compose.single.yml up -d --build
# http://localhost:8080  — local subdomain via the hosts file: http://rasta.arisheh.test:8080
```

**Six services:** `make setup`, `make migrate`, `make seed`, `make superadmin`; `make help` for the full list.

---

## ⚙️ Environment variables

Templates: `.env.example` (XAMPP), `.env.single.example` (Docker Desktop), `.env.server.example` (Arvan server), `.env.docker` (six services).

### Base and brand
```env
APP_NAME="ماهرو"
BRAND_NAME="ماهرو"
BRAND_NAME_EN=Mahru
BRAND_DOMAIN=arisheh.ir
APP_ENV=local                 # server: production
APP_DEBUG=true                # server: false
APP_URL=http://localhost      # server: https://arisheh.ir
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
CENTRAL_DOMAIN=               # arisheh.ir for salon subdomains; empty = /s/{slug} only
SESSION_DOMAIN=               # with subdomains: .arisheh.ir
SESSION_SECURE_COOKIE=        # on HTTPS: true
```

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beauty_salon
DB_USERNAME=root
DB_PASSWORD=
```
> In single-container mode the container generates `APP_KEY` and `DB_PASSWORD` itself; do not set them (not even empty).

### Salon subscriptions
```env
SUBSCRIPTION_TRIAL_DAYS=14
SMS_QUOTA_PER_MONTH=2000                  # monthly quota, in message parts
TRIAL_SMS_QUOTA=400
SMS_PART_PRICE=320                        # toman, for SMS packs
SMS_PACKS=1000,2500,5000
INCLUDED_SPECIALISTS_COUNT=10
EXTRA_SPECIALIST_PRICE_PER_MONTH=250000
MAX_SIGNUP_SPECIALISTS=50
```
> Plan prices are set in the super-admin panel ("Plans").

### Platform payment gateway (subscriptions and SMS packs)
```env
ZARINPAL_MERCHANT_ID=
ZARINPAL_API_KEY=
ZARINPAL_SANDBOX=true              # at go-live: false
ZARINPAL_PAYOUT_API_KEY=
ZARINPAL_PAYOUT_SANDBOX=true
```
> **Each salon's** gateways are not in `.env`; the salon admin enters them in their panel and they are stored encrypted.

### SMS (Kavenegar)
```env
KAVENEGAR_API_KEY=
KAVENEGAR_SENDER=
KAVENEGAR_SEND_IN_LOCAL=false      # in local, SMS goes to the log only
SMS_LOG_ONLY=false                 # true = no real SMS in any environment (even production); text and code only in the log
KAVENEGAR_TEMPLATE_LOGIN=
KAVENEGAR_TEMPLATE_REGISTER=
KAVENEGAR_TEMPLATE_RESET=
KAVENEGAR_TEMPLATE_2FA=
SMS_LINK_HOST=                     # host for SMS short links; empty = CENTRAL_DOMAIN
```

### Push and apps
```env
PUSH_FCM_CREDENTIALS=storage/app/private/mahru-fcm-sa.json   # empty = push off
PUSH_MODE=shadow                   # off | shadow | live (default before it is saved in platform settings)
PUSH_FCM_STYLE=data
PUSH_NTFY_SERVER=
CUSTOMER_APP_SHA256_FINGERPRINTS=  # for /.well-known/assetlinks.json
```

### Security, proxies and backups
```env
TWO_FACTOR_CODE_LENGTH=6
MAX_LOGIN_ATTEMPTS=5
LOGIN_THROTTLE_MINUTES=1
TRUSTED_PROXIES=                   # CDN edge IP ranges (Arvan server: deploy/single/arvan-ips.sh). Never "*"
HSTS_MAX_AGE=                      # HTTPS only; empty = one year, 0 = off
SECURITY_CSP=report               # report (log only, storage/logs/csp-*.log), enforce or off
RETENTION_DAYS=190                 # how long IPs and logs are kept (art. 668); below 184 is not accepted
LEGAL_PAGES_REVIEWED=false         # true once a lawyer has reviewed the texts (removes the "draft" label)
SALON_VERIFIER=manual              # salon verification: manual (super-admin); an official service later
BACKUP_AT=03:40                    # single container only: nightly backup; empty = off
BACKUP_AGE_RECIPIENT=              # age public key (age1…); backups are encrypted, the private key stays off the server
BACKUP_KEEP=3
BACKUP_STALE_HOURS=36
```
> An empty `TRUSTED_PROXIES` trusts no proxy (no-CDN mode: the direct IP is the real one); behind the Arvan CDN, set Arvan's ranges (`arvan-ips.sh`).
> nginx reads the same list for `set_real_ip_from` (`php artisan security:nginx-real-ip`). Security does not depend on the CDN, and `arvan-ips.sh --no-cdn` runs the server without it.
> The platform's legal identity (legal name, national ID, landline, address, postal code, eNamad badge code) is set in the super-admin panel → Settings → "Legal information".

### Queue, cache and session
```env
QUEUE_CONNECTION=database
QUEUE_WORK_VIA_SCHEDULER=false     # true only on hosting without a worker (DirectAdmin)
CACHE_STORE=file                   # on a server: file or database; never array
SESSION_DRIVER=file
TELESCOPE_ENABLED=false            # true only locally
BOT_ENABLED=false                  # the Bale/Telegram bot has been shelved
```
> Since Laravel 11, `CACHE_DRIVER` is not read; only `CACHE_STORE`.

---

## 💰 Subscriptions, plans and pricing

- **Plans** are created and edited in the super-admin panel: price per period (1, 3, 6 and 12 months), included specialists, SMS quota and **12 features** (e.g. SMS reminders, treatment courses, win-back campaigns, …). A feature can also be toggled for a single salon.
- An inactive plan cannot be bought by new salons, but salons already on it renew at the current price; the super-admin can assign any plan manually. Mid-period upgrades are immediate and prorated.
- **Trial** is 14 days; buying during the trial starts the paid plan at once.
- **Public sign-up** (`/salon-signup`) asks for the number of specialists; that is the salon's **specialist cap**. Each specialist beyond the plan's included count costs `EXTRA_SPECIALIST_PRICE_PER_MONTH` toman per month (with the same period discount).
- **Themes:** the default gold Mahru theme is free; other customer-site and admin-panel themes depend on the plan and period length.
- Pricing logic: `app/Support/Billing/SubscriptionPricing.php`.

---

## 📢 Notifications

- **Channels:** in-app notifications, SMS (Kavenegar) and app push. The Bale/Telegram bot is behind `BOT_ENABLED=false` and hidden in the panels.
- **Per-salon settings:** each channel per event; always read from the record's salon.
- **Smart push:** in "live" mode, an urgent message with no receipt by the deadline gets a fallback SMS; unhealthy devices get SMS directly. Tools: `push:test`, `push:live-check`.
- **SMS cost:** verification codes (login, 2FA, phone verification, password reset) are paid by the platform and never stop when a quota runs out; everything else comes from the salon's monthly quota (in parts), then from purchased credit.
- **"Needs review":** a payment or payout that automation gave up on notifies the salon owner once, by notification and SMS.
- **Marketing:** win-back SMS include an opt-out link.

---

## 📁 Project layout

```
Beauty-Salon/
├── app/
│   ├── Console/Commands/       # project Artisan commands
│   ├── Events/ Listeners/      # events (booking, payment, withdrawal, …)
│   ├── Exports/                # Excel exports
│   ├── Http/
│   │   ├── Controllers/        # Admin, SuperAdmin, Specialist, User, Api/V1, Central, Payment, Auth
│   │   ├── Middleware/         # salon resolution, salon active, permissions, proxies, …
│   │   └── Requests/           # form requests
│   ├── Jobs/                   # queued jobs (SMS, push, reports, …)
│   ├── Models/                 # 76 models
│   ├── Notifications/
│   ├── Payments/               # gateway and payout drivers + GatewayManager / PayoutManager
│   ├── Repositories/           # Contracts/ + Eloquent/
│   ├── Services/               # business logic
│   ├── Support/                # CurrentSalon, Billing, Sms, Features, Queues, …
│   └── Traits/                 # BelongsToSalon, …
├── database/                   # 66 migrations, factories, seeders
├── deploy/
│   ├── single/                 # image build (Windows), server deploy, Arvan IPs
│   ├── backup/                 # backup script
│   ├── cron/ supervisor/       # without Docker
├── docker/                     # container configuration (single/ for the single container)
├── docs/
│   ├── deployment/             # Arvan, Docker Desktop, subdomains, queue and scheduler, backups
│   ├── testing/                # manual gateway testing
│   └── brand/                  # brand kit
├── resources/views/            # Blade: admin, superadmin, specialist, customer, salon-home (themes), auth
├── routes/                     # web.php + web/, admin/ (35 files), super-admin.php, salon-auth.php, api/v1.php, console.php
├── tests/                      # Feature and Unit
├── Dockerfile, docker-compose*.yml, Makefile
├── README.md, README.en.md
└── Rasta unified prompt.md     # project development record (history, decisions, open items; Persian)
```

---

## 👥 Roles

### 🛡️ Platform admin (super-admin)
- Salons: create, edit, suspend, renew, specialist cap, SMS quota, per-salon features
- Plans and features, invoices, subscription payments, manual SMS credit grants
- Platform settings (push, platform gateway, texts), app releases, platform announcements
- Tickets and complaints (72-hour deadline), demo requests, action log, backup status
- Salon verification (approve or reject documents), the platform legal information, searching data retained from deleted accounts for judicial requests
- System roles and the permission list

### 🔑 Salon admin (owner / staff)
- Bookings, services, categories, specialists and schedules, leave, treatment courses
- Payment gateways, salon wallet, withdrawals, IBAN verification, "needs review"
- Reports and exports, loyalty, discount codes, win-back campaigns, reviews
- Salon roles, notification and security settings, themes, booking pause, subscription and billing, blog, gallery, announcements

### 👩‍💼 Specialist
- Today's schedule, bookings, calendar, leave and weekly hours
- Reviews, wallet, IBAN and withdrawals, reports and Excel
- Booking for themselves as a customer; the "Mahru Hamkar" app

### 👤 Customer
- Book, reschedule, cancel, pay by gateway, wallet or both
- Treatment courses, loyalty club, discount codes, reviews, notifications, 2FA, account deletion
- The "Mahru" app or the PWA

---

## 🛣️ Routes and APIs

### Main web routes

| Route | Description |
|------|-------|
| `/` | platform sales page |
| `/salon-signup` | new salon sign-up |
| `/login` | staff sign-in (admins and specialists) |
| `/my-salons` | "My salons" for customers (number → code → list of salons) |
| `/app` | app downloads |
| `/b/{code}` | SMS short link |
| `/s/{salon_slug}` | salon site (or `https://{salon_slug}.{CENTRAL_DOMAIN}`) |
| `/s/{salon_slug}/login` | customer sign-in |
| `/s/{salon_slug}/bookings` | my bookings and booking |
| `/s/{salon_slug}/wallet` · `/loyalty` | wallet · loyalty club |
| `/admin` | salon admin panel |
| `/my-dashboard`, `/specialist/*` | specialist panel |
| `/superadmin/*` | platform admin panel |
| `/payments/return/{publicId}` | gateway return |
| `/terms` · `/privacy` · `/contact` | terms · privacy policy · contact (central domain) |
| `/s/{salon_slug}/complaints` | customer complaints and follow-up |
| `/admin/verification` | salon identity and licence verification |
| `/up` | health check |

### App API (`/api/v1`, Sanctum token)

| Group | Routes |
|---|---|
| Base | `GET api/v1/status`, `GET api/v1/me`, `POST api/v1/logout`, `api/v1/tokens`, push device registration and receipts |
| "Mahru Hamkar" | `api/v1/staff/*` — sign-in, today, bookings, calendar, leave, working hours, wallet and IBAN, notifications |
| "Mahru" before sign-in | `api/v1/customer/salons/{salon_slug}` — salon card, sign-in, sign-up, password reset |
| "Mahru" | `api/v1/customer/*` — home, services, specialists, free slots, bookings, payment, wallet, loyalty, courses, profile, account deletion, notifications |
| App payment in the browser | `/s/{salon_slug}/app/checkout/{tx}` (signed) and callbacks |

Full list: `php artisan route:list`.

---

## 💳 Payment and payout gateways

| Gateway | Payment | Automatic payout |
|---|:---:|:---:|
| Zarinpal | ✅ | ✅ |
| Zibal | ✅ | ✅ |
| Vandar | ✅ | ✅ |
| AsanPardakht | ✅ | — |
| Saman | ✅ | — |
| Mellat | ✅ | — |
| Parsian | ✅ | — |

- Zarinpal has been tried in sandbox; the others are covered by automated tests (`Http::fake`), and their real manual test is pending in [`docs/testing/MANUAL_GATEWAY_TESTING.md`](docs/testing/MANUAL_GATEWAY_TESTING.md).
- Bank gateways (Saman, Mellat, Parsian) usually require the server IP to be registered in the bank panel.
- ⚠️ Whether gateways accept "each salon's merchant on the platform's domain" is not confirmed yet (`Rasta unified prompt.md`, open item 5d).

---

## ⏰ Scheduled jobs and queues

In single-container mode both run inside the container; nothing to do. Without Docker, one cron line is enough:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

| Job | When |
|---|---|
| cancel unpaid bookings, reconcile stuck transactions (`payments:reconcile`) | every 5 minutes |
| booking reminders (`bookings:send-reminders`), attendance confirmation links (`bookings:attendance-checks`), automatic return to no prepayment (`bookings:enforce-payment-mode`) | every 10 minutes |
| win-back campaigns (`winback:campaigns`) | every minute |
| course expiry (`courses:expire`), scheduled plan changes (`plans:apply-scheduled`) | hourly |
| settle pending earnings (`wallet:settle-pending`) | 01:00 |
| point expiry, Vandar token refresh, clean-ups (`model:prune`, tokens, report exports) | nightly |
| automatic win-back (`customers:winback`), course reminders (`courses:remind`) | morning |
| reminders for unanswered complaints (`complaints:remind`) | hourly |
| prune retained data after the legal period (`retention:prune`) | 03:50 |

**Queues** (in this order): `otp`, `push`, `sms`, `payments`, `default`, `reports` — plus a dedicated `otp` worker. `queue:work` without `--queue` reads only `default`.

Exact list: `php artisan schedule:list` · guide: [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md)

---

## 🧪 Running the tests

```bash
php artisan test                       # in-memory SQLite (phpunit.xml default)
```

For MariaDB/MySQL, copy `phpunit.xml` with `DB_CONNECTION=mysql` and an empty database's details (e.g. `phpunit.mysql.xml`, not in git) and run `vendor/bin/phpunit -c phpunit.mysql.xml`.
XAMPP (MariaDB before 10.10) runs with `explicit_defaults_for_timestamp=OFF`; to test that behaviour, turn it off under `[mysqld]` on the test server.

| Database | Last full run | Failures | Skipped |
|---|---|---|---|
| SQLite | 2363 (2026-10-27) | 0 | 11 |
| MariaDB 10.11 (`explicit_defaults_for_timestamp` on and off) and MySQL 8.0 | 2363 (2026-10-27) | 0 | 1 |

> `Vite manifest not found` in tests: run `npm run build` once.
> Subdomain tests: `vendor/bin/phpunit -c phpunit.subdomain.xml`.

---

## 🛠️ Project Artisan commands

```bash
php artisan superadmin:create                 # create a platform admin
php artisan bookings:send-reminders           # booking reminders
php artisan bookings:cleanup                  # expire bookings awaiting payment
php artisan payments:reconcile [--dry-run]    # reconcile stuck transactions
php artisan wallet:settle-pending             # settle pending earnings
php artisan payouts:refresh-vandar-tokens     # refresh Vandar payout tokens
php artisan loyalty:expire-points             # expire loyalty points
php artisan courses:remind | courses:expire   # treatment courses
php artisan customers:winback | winback:campaigns   # win-back
php artisan plans:sync-defaults | plans:apply-scheduled
php artisan push:test <mobile> [--repeat=N] [--wait=seconds]
php artisan push:live-check <mobile> [--urgency=…]
php artisan reports:cleanup-exports [--days=7]
php artisan review-tokens:cleanup
php artisan salons:analyze-logos
php artisan tenancy:repair-legacy-rows [--dry-run]
php artisan security:nginx-real-ip            # set_real_ip_from lines from TRUSTED_PROXIES
php artisan retention:prune [--dry-run]       # prune IPs and logs older than the retention period
php artisan complaints:remind                 # complaints close to the 72-hour deadline
php artisan perf:seed-load --salons=100       # test database only
```

---

## 🗄️ Database tables

66 migrations and 94 tables. Each creating migration holds all final columns of its table (fix-up migrations have been merged in).

| Area | Tables |
|---|---|
| Salons, plans, subscriptions | `salons`, `salon_admins`, `plans`, `plan_features`, `salon_feature_overrides`, `invoices`, `salon_sms_usages`, `salon_sms_templates`, `sms_credit_purchases`, `salon_verifications` |
| Users and access | `users`, `roles`, `permissions`, `role_user*`, `permission_role`, `personal_access_tokens`, `password_reset_tokens`, `sessions`, `trusted_devices`, `legal_acceptances`, `deleted_account_archives` |
| Services and specialists | `categories`, `beauty_services`, `specialists`, `specialist_services`, `specialist_schedules`, `holidays`, `leaves` |
| Bookings and courses | `bookings`, `booking_policies`, `course_packages`, `customer_courses`, `customer_course_payments`, `course_session_notes`, `course_session_photos`, `course_reminders`, `course_photo_consents`, `course_settings` |
| Payments | `payments`, `payment_transactions`, `salon_payment_gateways`, `idempotency_keys` |
| Wallets and payouts | `user_wallets`, `user_wallet_transactions`, `specialist_wallets`, `wallet_transactions`, `withdrawal_requests`, `admin_wallet`, `admin_wallet_transactions`, `wallet_settings` |
| Discounts, loyalty, win-back | `discount_codes`, `discount_usages`, `loyalty_points`, `loyalties`, `loyalty_settings`, `rewards`, `winback_*` |
| Reviews | `reviews`, `review_tokens` |
| Notifications, push, settings | `user_notifications`, `notification_settings`, `security_settings`, `security_logs`, `push_devices`, `push_messages`, `push_deliveries`, `platform_settings`, `platform_announcements` |
| Apps and links | `app_releases`, `short_links`, `qr_scans`, `bot_links`, `bot_link_codes` |
| Reports | `report_exports`, `scheduled_reports`, `scheduled_report_runs`, `user_report_settings` |
| Content and support | `blog_posts`, `blog_categories`, `gallery_images`, `announcements`, `support_tickets`, `support_ticket_messages`, `demo_requests` |
| System | `jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks`, `activity_log`, `superadmin_audit_logs`, `telescope_*` |

---

## 🌐 Deploying to a server

**Chosen method (2026-10-24):** the single-container image is built on Windows and copied with WinSCP to an ArvanCloud cloud server; the server needs no git, build or registry. HTTPS terminates at the Arvan CDN edge.

```
Windows:  deploy\single\build-image.cmd                   → build-images\mahru-single-<commit>.tar
WinSCP:   .tar to /srv/mahru/images
Server:   bash deploy/single/server-deploy.sh images/mahru-single-<commit>.tar
          (backup before deploy, load, up, wait for healthy; rollback: --list and --use <version>)
```

Full guide from buying the server to testing: [`docs/deployment/ARVAN_SERVER.md`](docs/deployment/ARVAN_SERVER.md) (Persian).

Notes:
- `.env.server`: `APP_ENV=production`, `APP_DEBUG=false`, `TELESCOPE_ENABLED=false`, `MAHRU_SEED=false`, `TRUSTED_PROXIES` from `arvan-ips.sh`.
- Until Kavenegar is ready: `SMS_LOG_ONLY=true` (login code: `docker logs mahru 2>&1 | grep SMS`). Until go-live: `ZARINPAL_SANDBOX=true`.
- Port 80 should be open only to the CDN edges (Docker bypasses ufw rules).
- Nightly backups in `/srv/mahru/backups` (database, uploads, `app.key`); weekly manual download — [`docs/deployment/BACKUP.md`](docs/deployment/BACKUP.md).
- Subdomains: [`docs/deployment/SUBDOMAINS.md`](docs/deployment/SUBDOMAINS.md).

**Without Docker (DirectAdmin or a VPS, for reference):**
```bash
php artisan down
git pull origin develop
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan queue:restart
php artisan up
```
Cron and workers: [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md). `CACHE_STORE=file` or `database` (not `array`).

---

## 🩺 Troubleshooting

| Problem | Cause and fix |
|---|---|
| `composer install` fails in `package:discover` with a database error | `.env` is missing or the database is unreachable; create `.env` and run `php artisan package:discover` |
| `Vite manifest not found` | `npm run build` |
| 419 Page Expired | `SESSION_DOMAIN` does not match the domain (troubleshooting in `SUBDOMAINS.md`) |
| After `git pull`: missing column/table, or `Table … already exists` | the creating migrations changed; `php artisan migrate:fresh --seed` (data is fake) |
| `.env` changes have no effect | `php artisan config:clear` and `queue:restart`; in Docker `up -d` (not `restart`) |
| SMS is not sent | intentional in `local` or with `SMS_LOG_ONLY=true`; the text is in the log. Otherwise check the `sms`/`otp` queue worker |
| All users share one IP / gateway link is `http://` | `TRUSTED_PROXIES` |
| Uploaded images do not show | `php artisan storage:link` (Administrator terminal on Windows) |
| Docker build is slow or breaks in Composer | `build-vendor` and the registry mirror — `DOCKER_DESKTOP.md` section 1 |

---

## 🆕 Changelog

Full history, decisions and open items in [`Rasta unified prompt.md`](Rasta%20unified%20prompt.md) (Persian).

**2026-10-28 — booking mode, per-customer limits and unreliable customers (package 5e)**
- New owner page "booking mode" (`/admin/booking-policy`): **no prepayment** (default for every salon, the whole price at the salon, no gateway needed) or online prepayment (only with the plan's online-payment feature and an active gateway), with an explanation of each. If the gateway or the plan feature is lost the salon falls back to no prepayment automatically and the owner gets a notification and an SMS. Every booking keeps its own "with/without prepayment" label (`bookings.prepayment_required`) that auto-cancel, the pay button, SMS and loyalty points follow. Switching to no prepayment moves awaiting-payment bookings to awaiting confirmation and tells the customer and the specialist.
- Without prepayment: SMS and notifications at booking time; a discount comes off the full price; loyalty points only if the owner turns them on and enters both numbers (no defaults).
- Per-customer online limits: per day (default 1, the booking's own day) and total active future bookings (default 3); web, app, rescheduling and course sessions; manual staff bookings are exempt with a warning. Unreliable customers (default 2 no-shows in 90 days): with a gateway they must prepay, without one forced manual confirmation and a lower limit; an "unreliable" label in the panels and the staff app; the restriction is lifted from the customer page. Optional attendance-confirmation link (SMS link 24 hours before, cancelled if not confirmed 3 hours before).
- Two bugs fixed: a zero prepayment cancelled the booking after 30 minutes with a "you did not pay" SMS; an auto-confirming specialist kept an unpaid booking "confirmed" forever.
- Customer and staff apps 0.3.8 (optional; older apps keep working). Three new migrations (`booking_policies`, new columns on `bookings` and `users`): `php artisan migrate` or `migrate:fresh --seed`. Two new scheduled commands, no new variables.

**2026-10-28 — upgrade to Laravel 13 and PHP 8.5**
- Laravel 11 (security fixes ended 2026-03-12) -> 12 -> **13** (security fixes until 2028-03-17); `composer.json`: `php: ^8.3`. Libraries: Sanctum 4.3, Telescope 5.26, Excel **4** with PhpSpreadsheet **5** (the only line that installs on PHP 8.5; Excel output unchanged), Activitylog 4.12, mPDF 8.3, DomPDF 3.1, Verta 9, Tinker 3, PHPUnit 12, collision 8.9; Guzzle stays on 7 and Pint on 1.27. `composer.lock` installs on PHP 8.3 to 8.5.
- Docker image on PHP **8.5** (production and single/ArvanCloud); six-service compose on **mysql:8.4** (`docker/mysql/my.cnf` fixed for 8.4; an 8.0 volume is upgraded one-way on first start — back it up first).
- New behaviour (decided): app push message ids are UUIDv7 and are ordered by id; SVG uploads are rejected by image fields.
- Fixes the upgrade needed: CSRF exemption of the gateway return URL and CSP reports on Laravel 13 (banks would have got 419); the sales page's JSON-LD broke on the new Blade `@context` directive (500); the project's own PHP 8.5 deprecations.
- `phpunit.xml`: `memory_limit=1G`, fixed `APP_URL` and `PUSH_FCM_CREDENTIALS` (the suite no longer depends on each machine's `.env` / `php.ini`).
- **After applying:** `composer install` (on PHP 8.5), `php artisan optimize:clear` (stale compiled views hide Blade errors), `npm run build`; for Docker a fresh `build-vendor` and a rebuild. No new migration; `migrate:fresh --seed` is fine since the data is fake.

**2026-10-28 — terms re-acceptance and photo consent from the customer**
- `config/legal.php` now has a change summary and a "major" flag for every version of the terms, privacy policy and salon terms. Only a major change requires re-acceptance: after login the customer sees a "terms updated" page and cannot book a new appointment or course until accepting (logout, account deletion, current bookings and complaints stay open); the salon owner's panel is locked until the salon terms are accepted, and secretaries only see an "awaiting the owner" banner. Apps older than 0.3.7 are not locked; their acceptance is recorded as implicit. Acceptances are stored in `legal_acceptances` as before.
- Course photo consent: staff send a "consent request" from the staff app or the specialist panel (push plus an SMS link taken from the salon's SMS quota); the customer reads the full text in the app, in "my courses" or on the SMS-link page and agrees or declines themselves. The in-person record by staff is the second way. Every request and decision is kept with time, IP, text version and source in the new `course_photo_consents` table and shown in the panels with labels ("in-person record", "SMS link", ...). One open request per course, "send again" once a day, a 24-hour wait after a decline.
- Fix: the admin course page loaded the user of every activity row separately (N+1).
- Customer and staff apps 0.3.7. One new migration: `php artisan migrate` (or `migrate:fresh --seed`). No new variables.

**2026-10-27 — error 1067 in `migrate:fresh` on XAMPP**
- On MariaDB older than 10.10 (like XAMPP, `explicit_defaults_for_timestamp=OFF`) creating `deleted_account_archives` failed with "1067 Invalid default value for 'purge_after'", and three other columns silently got `ON UPDATE CURRENT_TIMESTAMP`. All five timestamp columns now have an explicit default or are nullable, and a test checks every migration for this pattern. Local database: `php artisan migrate:fresh --seed`.

**2026-10-25 — working hours on phones**
- `/salon-signup` no longer scrolls sideways on phones (it overflowed by 58 px): the working-hours row wraps onto two lines. In the salon settings (admin panel) and the super-admin salon form, the times are now fully visible on phones and tablets (previously only "09").

**2026-10-25 — platform licensing package (5d)**
- Security independent of the Arvan CDN: security headers and HSTS from the app itself, a report-only CSP, rate limits on SMS codes, discount codes, reviews and customer actions; the `TRUSTED_PROXIES` default is no longer Cloudflare; nginx with the real IP, a persistent access log, HTTPS on the origin (acme.sh), a `--no-cdn` mode, SSH hardening, fail2ban and external uptime monitoring in `ARVAN_SERVER.md`.
- Six-month data retention (art. 668): IP and user agent of key actions, an encrypted archive of deleted accounts (account deletion used to leave no trace), nightly `retention:prune`, backups encrypted with an age public key.
- Privacy, terms and contact pages with the legal identity from the platform settings (labelled "draft" until a lawyer reviews them); terms acceptance recorded at salon and customer signup; the eNamad badge placed verbatim; `www` redirects with 301 to the bare domain; complaints with a 72-hour deadline; provider identity and cancellation terms before payment; fabricated content removed from the default salon home page; course photo consent text and withdrawal.
- Salon identity and licence verification (manual stage, approved by the super-admin); unverified salons are not public. The interface for an official verification service is in place.
- Customer app 0.3.6: terms acceptance at signup, purchase terms before booking, photo consent withdrawal; staff app: consent text to read to the customer.
- 3 new migrations and changes to several creating migrations: rebuild the database with `php artisan migrate:fresh --seed`. New variables: `SECURITY_CSP`, `HSTS_MAX_AGE`, `RETENTION_DAYS`, `LEGAL_PAGES_REVIEWED`, `SALON_VERIFIER`, `BACKUP_AGE_RECIPIENT`.

**2026-10-25**
- Review of the platform licensing requirements (eNamad, the E-Commerce Law, data retention under the Computer Crimes Law, the internet-platforms directive); gaps and the next code package recorded as open item 5d. No code changes.
- Docs: the duplicate root `SCHEDULER_AND_QUEUE.md` was removed; the subdomain guide moved to `docs/deployment/SUBDOMAINS.md` and manual gateway testing to `docs/testing/MANUAL_GATEWAY_TESTING.md`; README updated and this English version (`README.en.md`) added.

**2026-10-24**
- Deployment to an ArvanCloud cloud server with the same single-container image: build on Windows, `server-deploy.sh` with pre-deploy backup and version rollback, `arvan-ips.sh` for `TRUSTED_PROXIES` and the firewall, nightly in-container backups (database, uploads, `app.key`), the `ARVAN_SERVER.md` guide.
- `SMS_LOG_ONLY`: no SMS is really sent in any environment (until Kavenegar is ready). An empty Kavenegar key no longer kills the PHP process.
- Single-container mode tested on Docker Desktop: subdomains via the hosts file, customer sign-in, phone and push.

**2026-10-23**
- First real Docker build and its fixes; single-container mode (internal MariaDB, no Redis); `build-vendor` for slow internet; secrets and uploads outside the image; `make backup-db` fixed; Redis `noeviction`; salon slug rules.

**2026-10-22**
- Main domain `arisheh.ir`; push readiness tools (`push:test --repeat`, `push:live-check`); fresh specialist accounts and specialists booking for themselves; specialist "today" schedule; the bot was shelved.

**2026-10-19 to 10-21**
- Package "H": welcome SMS after the payment result, local subdomains, salon booking pause, "My salons".
- Packages 5 and 6: salon link and QR, app without a store (`/app`), iPhone PWA.

**2026-10-14 to 10-17**
- New brand and sales page, demo requests, platform settings.
- Plans and features, multi-session treatment courses, win-back campaigns, backups with status in the super-admin panel.
- Selectable themes and four public plans; panels on the Mahru design system; PDFs without a CDN.
- Package 4: app push (FCM, receipts, "no receipt → SMS").

**2026-10-02 to 10-13**
- Apps: `/api/v1` API with Sanctum, staff and customer APIs, Expo apps on the `mobile-app` branch, app payment in the external browser; the old `/api` was removed.

**2026-10-01 (second migration consolidation)**
- Seven fix-up migrations were merged into their tables' creating migrations. Rebuild existing databases with `php artisan migrate:fresh --seed`; a plain `migrate` is not enough.

**2026-10-01**
- SMS quota in message parts, prepaid SMS packs, shorter SMS texts with short names and short links; 10 specialists included in the plan price; concurrency locks and form idempotency; overlapping bookings prevented; strict `BelongsToSalon` mode.

**2026-09-30**
- Separate queues (`otp`, `sms`, `payments`, `default`, `reports`); all salon SMS from that salon's quota, verification codes paid by the platform; IBAN verification with check digits and automatic payouts only to verified IBANs; per-salon notifications; "needs review" with notification and SMS to the owner; public sign-up asks for the specialist count; first migration consolidation.

---

## 📚 Further documentation

Most project documents are in Persian.

| File | Content |
|---|---|
| [`Rasta unified prompt.md`](Rasta%20unified%20prompt.md) | main development record: history, decisions, lessons, open items |
| [`docs/deployment/ARVAN_SERVER.md`](docs/deployment/ARVAN_SERVER.md) | ArvanCloud server: purchase, DNS, HTTPS, firewall, backups, new versions |
| [`docs/deployment/DOCKER_DESKTOP.md`](docs/deployment/DOCKER_DESKTOP.md) | single container on Docker Desktop for Windows |
| [`docs/deployment/SUBDOMAINS.md`](docs/deployment/SUBDOMAINS.md) | salon subdomains: settings, mechanism, cookie decision |
| [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md) | scheduler and queues for every deployment method |
| [`docs/deployment/BACKUP.md`](docs/deployment/BACKUP.md) | backup and restore |
| [`docs/testing/MANUAL_GATEWAY_TESTING.md`](docs/testing/MANUAL_GATEWAY_TESTING.md) | manual testing of Zibal, Vandar and AsanPardakht |

---

## 🤝 Contributing

This is a personal-professional project. Workflow: the `develop` branch, English commit messages, one change per commit, tests on SQLite, MariaDB and MySQL. Open an issue for bugs or suggestions.

---

<div align="center">
  Made with ❤️ in Iran
</div>
