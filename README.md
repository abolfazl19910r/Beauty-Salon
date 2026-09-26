# 💅 ماهرو (Mahru) — پلتفرم نوبت‌دهی آنلاین سالن‌های زیبایی

> یک SaaS چندسالنه (multi-tenant): هر سالن پنل مدیریت، پنل متخصص، سایت مشتری، درگاه پرداخت و داده‌ی کاملاً جدای خودش را دارد.
> ساخته‌شده با Laravel 11، Blade، Tailwind CSS و جاوااسکریپت ساده.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=flat-square&logo=vite)](https://vitejs.dev)
[![Tests](https://img.shields.io/badge/tests-1472%20passing-brightgreen?style=flat-square)](#-اجرای-تستها)

---

## 📋 فهرست مطالب

- [معرفی پروژه](#-معرفی-پروژه)
- [جداسازی سالن‌ها (Multi-tenancy)](#-جداسازی-سالنها-multi-tenancy)
- [ویژگی‌های اصلی](#-ویژگیهای-اصلی)
- [معماری و ساختار فنی](#-معماری-و-ساختار-فنی)
- [پیش‌نیازها](#-پیشنیازها)
- [راه‌اندازی از صفر تا صد](#-راهاندازی-از-صفر-تا-صد)
- [راه‌اندازی با Docker](#-راهاندازی-با-docker)
- [تنظیم متغیرهای محیطی](#-تنظیم-متغیرهای-محیطی)
- [ساختار پروژه](#-ساختار-پروژه)
- [نقش‌های کاربری](#-نقشهای-کاربری)
- [مسیرها و APIها](#-مسیرها-و-apiها)
- [درگاه‌های پرداخت و تسویه](#-درگاههای-پرداخت-و-تسویه)
- [کارهای زمان‌بندی‌شده و صف](#-کارهای-زمانبندیشده-و-صف)
- [اجرای تست‌ها](#-اجرای-تستها)
- [دستورات Artisan پروژه](#-دستورات-artisan-پروژه)
- [جداول دیتابیس](#-جداول-دیتابیس)
- [استقرار روی سرور](#-استقرار-روی-سرور)
- [مستندات بیشتر](#-مستندات-بیشتر)

---

## 🎯 معرفی پروژه

**ماهرو** یک پلتفرم نرم‌افزار-به‌عنوان-سرویس برای سالن‌های زیبایی است. هر سالن با **ثبت‌نام آنلاین** (یا توسط مدیر پلتفرم) ساخته می‌شود، اشتراک ماهانه/سالانه دارد و این بخش‌ها را در اختیار می‌گیرد:

- **سایت و پنل مشتری** — رزرو نوبت، پرداخت آنلاین یا با کیف پول، تغییر زمان و لغو نوبت، امتیاز وفاداری و جوایز، نظر دادن
- **پنل متخصص** — نوبت‌ها، برنامه‌ی کاری، مرخصی و تعطیلی، کیف پول و درخواست برداشت، نظرات، گزارش عملکرد و خروجی Excel
- **پنل مدیریت سالن** — کنترل کامل سالن: نوبت‌ها، خدمات، متخصص‌ها، مشتری‌ها، درگاه‌های پرداخت، کیف پول و تسویه، گزارش‌ها، وفاداری، نقش‌ها، تنظیمات
- **پنل مدیر پلتفرم (سوپرادمین)** — ساخت و مدیریت سالن‌ها، تمدید اشتراک، فاکتورها، تعلیق سالن، پرداخت‌های اشتراک، تیکت‌های پشتیبانی

ورود با **شماره موبایل و کد یک‌بارمصرف (کاوه‌نگار)** و **احراز هویت دومرحله‌ای (2FA)** انجام می‌شود.

---

## 🧱 جداسازی سالن‌ها (Multi-tenancy)

همه‌ی سالن‌ها در **یک دیتابیس** هستند و جداسازی در سطح برنامه انجام می‌شود:

- **آدرس سالن:** هر سالن یک `slug` دارد و از دو راه در دسترس است:
  - زیرمسیر: `https://example.com/s/{salon_slug}`
  - زیردامنه (اگر `CENTRAL_DOMAIN` تنظیم شده باشد): `https://{salon_slug}.example.com` — راهنما در [`WILDCARD_SUBDOMAIN_DEPLOYMENT.md`](WILDCARD_SUBDOMAIN_DEPLOYMENT.md)
- **سالن فعلی (`CurrentSalon`):** middlewareها سالن را از آدرس (سایت مشتری) یا از عضویت کاربر (پنل مدیریت و متخصص) پیدا و ثبت می‌کنند.
- **`BelongsToSalon`:** مدل‌هایی که ستون `salon_id` دارند (نوبت، خدمت، متخصص، کد تخفیف، جایزه‌ی وفاداری، تنظیمات امنیتی و …) به‌صورت خودکار به سالن فعلی محدود می‌شوند و ردیف جدید سالن فعلی را می‌گیرد.
- **`BelongsToSalonThroughSpecialist`:** برای جدول‌هایی که فقط از طریق متخصص به سالن وصل‌اند (مثل نظرات).
- **چک مالکیت صریح:** چون اتصال مدل در route قبل از شناسایی سالن اجرا می‌شود، هر اکشن مدیریت که مدلی را از آدرس می‌گیرد `ensureSalonOwnership()` را صدا می‌زند.
- **کارهای صف و اعلان‌ها:** سالن فعلی ندارند، پس سالن را صریحاً از خود رکورد یا گیرنده می‌گیرند (مثلاً تنظیمات اعلان از سالنِ گیرنده خوانده می‌شود).
- **مختص هر سالن:** جوایز وفاداری، نقش‌ها، تنظیمات اطلاع‌رسانی، تنظیمات امنیتی، تنظیمات کیف پول و درگاه‌های پرداخت.
- **مشترک در پلتفرم:** نقش‌های سیستمی (`admin`، `specialist`، `super-admin`، …) و فهرست مجوزها — چون کد با نامشان چک می‌کند — فقط توسط مدیر پلتفرم تغییر می‌کنند.

---

## ✨ ویژگی‌های اصلی

### 🔐 احراز هویت و امنیت
- ثبت‌نام و ورود با شماره موبایل (OTP از طریق کاوه‌نگار)؛ مشتری در هر سالن حساب جدا دارد
- احراز هویت دومرحله‌ای (2FA) با طول کد قابل تنظیم (۴ تا ۱۰ رقم)
- RBAC با نقش‌های مختص هر سالن و مجوزهای مشترک
- محدودسازی تلاش ورود، لاگ رویدادهای امنیتی، مدت اعتبار رمز عبور (مختص هر سالن)
- لاگ فعالیت‌ها با Spatie Activity Log

### 📅 رزرو نوبت
- رزرو با انتخاب خدمت، متخصص و زمان؛ اسلات‌ها بر اساس برنامه‌ی کاری، مرخصی و تعطیلی متخصص
- تأیید خودکار یا دستی نوبت توسط متخصص
- یادآوری پیامکی نوبت
- تغییر زمان و لغو نوبت توسط مشتری، با قانون جریمه‌ی لغو
- لغو خودکار نوبت‌های پرداخت‌نشده

### 💳 پرداخت و کیف پول
- **چند درگاه برای هر سالن:** زرین‌پال، زیبال، وندار، آسان‌پرداخت، سامان، ملت، پارسیان — مشتری درگاه را انتخاب می‌کند و در صورت قطعی، درگاه بعدی امتحان می‌شود
- پیش‌پرداخت نوبت، پرداخت کامل یا ترکیبی (کیف پول + درگاه)
- کیف پول مشتری، کیف پول متخصص با کمیسیون سالن، کیف پول سالن
- درخواست برداشت متخصص و **تسویه‌ی خودکار** (Payout) از طریق زرین‌پال، زیبال یا وندار
- تطبیق خودکار تراکنش‌های گیرکرده و برگشت وجه پرداختی که نوبتش از دست رفته
- صفحه‌ی «نیاز به بررسی» برای پرداخت‌ها و تسویه‌هایی که دخالت مدیر لازم دارند

### 🏆 وفاداری
- امتیاز بعد از هر خدمت و بعد از ثبت نظر
- جوایز قابل دریافت (مختص هر سالن) و تبدیل به کد تخفیف
- تاریخچه‌ی امتیازها و اعلان کسب امتیاز

### ⭐ نظرات
- لینک یک‌بارمصرف (ReviewToken) برای ثبت نظر بعد از نوبت
- امتیاز جزئی (کیفیت، برخورد، نظافت، سرعت)، تأیید/رد، نظر ویژه
- اعلان نظر منفی به مدیرهای همان سالن

### 📊 گزارش‌گیری
- داشبورد آماری مدیریت
- گزارش روزانه، هفتگی و ماهانه
- خروجی **Excel** (چند شیت) و **PDF** در صف، با دانلود بعد از آماده‌شدن
- گزارش و خروجی Excel متخصص

### 📢 اطلاع‌رسانی
- اعلان داخلی، پیامک و تلگرام
- هر سالن برای هر رویداد تعیین می‌کند کدام کانال فعال باشد
- سهمیه‌ی پیامک ماهانه برای هر سالن

### 🧾 اشتراک و صورت‌حساب
- پلن‌های ۱، ۳، ۶ و ۱۲ ماهه، دوره‌ی آزمایشی رایگان
- محدودیت تعداد متخصص و سهمیه‌ی پیامک بر اساس پلن
- پرداخت و تمدید اشتراک، فاکتور، تعلیق خودکار/دستی سالن

### 📝 محتوا و موارد دیگر
- بلاگ، گالری تصاویر، اعلانیه‌ها
- کدهای تخفیف (عمومی یا شخصی، محدودیت استفاده و انقضا)
- تیکت پشتیبانی بین سالن و مدیر پلتفرم
- جست‌وجوی سراسری در پنل مدیریت

---

## 🏗️ معماری و ساختار فنی

| لایه | تکنولوژی |
|------|-----------|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Blade + Tailwind CSS 3 + جاوااسکریپت ساده، Vite 8 |
| فونت | Vazirmatn |
| احراز هویت | Laravel Breeze + ورود پیامکی سفارشی، Sanctum برای API |
| مجوزدهی | RBAC سفارشی (نقش مختص سالن + مجوز مشترک) |
| Multi-tenancy | تک‌دیتابیس، `CurrentSalon` + global scope + چک مالکیت |
| پرداخت | لایه‌ی درایور درگاه (`app/Payments`) با ۷ درگاه و ۳ درایور تسویه |
| پیامک | کاوه‌نگار |
| PDF | DomPDF / mPDF / Snappy |
| Excel | Maatwebsite Excel + PhpSpreadsheet |
| تصویر | Intervention Image |
| تاریخ شمسی | morilog/jalali + hekmatinasser/verta |
| لاگ فعالیت | Spatie Laravel Activitylog |
| دیباگ | Laravel Telescope |
| صف | Database (یا Redis) |
| معماری کد | Controller ← Service ← Repository (Interface + Eloquent) |

> React از پروژه حذف شده؛ فایل‌های `resources/js/app.jsx` و `admin.jsx` فقط به‌عنوان entry point برای Vite باقی مانده‌اند.

---

## 📦 پیش‌نیازها

| ابزار | نسخه |
|-------|------|
| PHP | 8.2 یا بالاتر (افزونه‌ها: mbstring، xml، pdo_mysql، sqlite3، curl، zip، gd، intl، bcmath) |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8.0+ یا MariaDB 10.11+ |
| Node.js | 18.x یا بالاتر |
| npm | 9.x یا بالاتر |
| Git | — |

> **XAMPP:** از نسخه‌ی 8.2 به بالا استفاده کنید.
> **Docker:** برای راه‌اندازی با Docker فقط Docker و Docker Compose لازم است (بخش [راه‌اندازی با Docker](#-راهاندازی-با-docker)).

---

## 🚀 راه‌اندازی از صفر تا صد

### مرحله ۱ — Clone
```bash
git clone https://github.com/abolfazl19910r/Beauty-Salon.git
cd Beauty-Salon
git checkout develop
```

### مرحله ۲ — وابستگی‌های PHP
```bash
composer install
```
> ⚠️ اگر به خطای timeout برخوردید (فیلترینگ)، از VPN یا mirror ایرانی استفاده کنید:
> ```bash
> composer config --global repos.packagist composer https://packagist.ir
> composer install
> ```

### مرحله ۳ — وابستگی‌های JavaScript
```bash
npm install
```

### مرحله ۴ — فایل `.env` و کلید برنامه
```bash
cp .env.example .env
php artisan key:generate
```

### مرحله ۵ — دیتابیس
```sql
CREATE DATABASE beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
سپس اطلاعات اتصال را در `.env` وارد کنید ([تنظیم متغیرهای محیطی](#-تنظیم-متغیرهای-محیطی)).

### مرحله ۶ — Migration و Seeder
```bash
php artisan migrate
php artisan db:seed            # نقش‌ها، مجوزها و داده‌های اولیه
```
برای reset کامل: `php artisan migrate:fresh --seed`

### مرحله ۷ — ساخت مدیر پلتفرم
```bash
php artisan superadmin:create
```

### مرحله ۸ — storage link
```bash
php artisan storage:link
```

### مرحله ۹ — Build فایل‌های Frontend
```bash
npm run dev      # توسعه
npm run build    # production
```

### مرحله ۱۰ — صف و زمان‌بندی
```bash
php artisan queue:work --tries=3     # یک ترمینال
php artisan schedule:work            # ترمینال دیگر (در توسعه)
```
در production به بخش [کارهای زمان‌بندی‌شده و صف](#-کارهای-زمانبندیشده-و-صف) مراجعه کنید.

### مرحله ۱۱ — اجرای سرور
```bash
php artisan serve
```
- صفحه‌ی اصلی پلتفرم: **http://127.0.0.1:8000**
- ثبت‌نام سالن جدید: **http://127.0.0.1:8000/salon-signup**
- سایت یک سالن: **http://127.0.0.1:8000/s/{salon_slug}**
- پنل مدیریت سالن: **http://127.0.0.1:8000/admin**
- پنل مدیر پلتفرم: **http://127.0.0.1:8000/superadmin/dashboard**

---

## 🐳 راه‌اندازی با Docker

`Makefile` همه‌ی کارها را ساده کرده است (`make help` فهرست کامل را نشان می‌دهد):

```bash
make setup         # کپی .env.docker، ساخت APP_KEY، build و بالا آوردن containerها
make migrate       # اجرای migrationها
make seed          # اجرای seederها
make superadmin    # ساخت مدیر پلتفرم
```

دستورهای پرکاربرد دیگر:

| دستور | کار |
|---|---|
| `make up` / `make down` / `make restart` | بالا/پایین آوردن و ری‌استارت |
| `make logs` / `make logs-app` / `make logs-queue` / `make logs-scheduler` | لاگ‌ها |
| `make shell` / `make db-shell` / `make redis-shell` | ورود به container |
| `make queue-restart` | ری‌استارت صف بعد از deploy |
| `make cache-clear` / `make cache-optimize` | cache |
| `make backup-db` | پشتیبان دیتابیس |
| `make status` | وضعیت سرویس‌ها |

---

## ⚙️ تنظیم متغیرهای محیطی

### پایه و برند
```env
APP_NAME="ماهرو"
BRAND_NAME="ماهرو"
BRAND_NAME_EN=Mahru
BRAND_DOMAIN=mahru.ir
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
CENTRAL_DOMAIN=              # مثلاً mahru.ir برای فعال‌شدن زیردامنه‌ی سالن‌ها؛ خالی = فقط /s/{slug}
```

### دیتابیس
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beauty_salon
DB_USERNAME=root
DB_PASSWORD=
```

### اشتراک سالن‌ها
```env
SUBSCRIPTION_PRICE_1M=1500000       # تومان
SUBSCRIPTION_PRICE_3M=4150000
SUBSCRIPTION_PRICE_6M=7650000
SUBSCRIPTION_PRICE_12M=13850000
SUBSCRIPTION_TRIAL_DAYS=14
SMS_QUOTA_PER_MONTH=1500
TRIAL_SMS_QUOTA=300
DEFAULT_MAX_SPECIALISTS_COUNT=3
```

### درگاه پرداخت اشتراک (زرین‌پال پلتفرم)
```env
ZARINPAL_MERCHANT_ID=
ZARINPAL_API_KEY=
ZARINPAL_SANDBOX=true              # در production: false
ZARINPAL_PAYOUT_API_KEY=
ZARINPAL_PAYOUT_SANDBOX=true
```
> درگاه‌های **هر سالن** در `.env` نیستند؛ مدیر هر سالن آن‌ها را از پنل خودش (بخش درگاه‌های پرداخت) وارد می‌کند.

### کاوه‌نگار (پیامک)
```env
KAVENEGAR_API_KEY=
KAVENEGAR_SENDER=
KAVENEGAR_SEND_IN_LOCAL=false      # در local پیامک ارسال نمی‌شود و در log ثبت می‌شود
KAVENEGAR_TEMPLATE_LOGIN=
KAVENEGAR_TEMPLATE_REGISTER=
KAVENEGAR_TEMPLATE_RESET=
KAVENEGAR_TEMPLATE_2FA=
```

### امنیت
```env
TWO_FACTOR_TIMEOUT=300              # اعتبار کد 2FA (ثانیه)
TWO_FACTOR_CODE_LENGTH=6            # بین ۴ تا ۱۰
MAX_LOGIN_ATTEMPTS=5
LOGIN_THROTTLE_MINUTES=15
VERIFICATION_CODE_EXPIRE_MINUTES=5
RESET_CODE_EXPIRE_MINUTES=5
PAYMENT_EXPIRY_MINUTES=30
SECURITY_LOG_LEVEL=
PAYMENTS_LOG_LEVEL=
```

### صف، cache و session
```env
QUEUE_CONNECTION=database
QUEUE_WORK_VIA_SCHEDULER=false      # true = صف از همان کرون scheduler اجرا می‌شود (مناسب DirectAdmin)
CACHE_STORE=file
SESSION_DRIVER=file
```

### تلگرام و Telescope
```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope
```

---

## 📁 ساختار پروژه

```
Beauty-Salon/
├── app/
│   ├── Console/Commands/       # دستورات Artisan پروژه
│   ├── Events/ Listeners/      # رویدادها (نوبت، پرداخت، برداشت، …)
│   ├── Exports/                # خروجی‌های Excel
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # پنل مدیریت سالن
│   │   │   ├── SuperAdmin/     # پنل مدیر پلتفرم
│   │   │   ├── Specialist/     # پنل متخصص
│   │   │   ├── User/           # سایت و پنل مشتری
│   │   │   ├── Payment/        # برگشت از درگاه‌ها
│   │   │   └── Auth/           # ورود پیامکی، 2FA، …
│   │   ├── Middleware/         # شناسایی سالن، فعال‌بودن سالن، مجوزها، …
│   │   └── Requests/           # Form Requestها
│   ├── Jobs/                   # کارهای صف (پیامک، گزارش، لغو نوبت پرداخت‌نشده، …)
│   ├── Models/                 # ۴۶ مدل Eloquent
│   ├── Notifications/          # اعلان‌ها (با تنظیمات کانال هر سالن)
│   ├── Observers/
│   ├── Payments/               # درایورهای درگاه و تسویه + GatewayManager / PayoutManager
│   ├── Repositories/           # Contracts/ (Interface) + Eloquent/
│   ├── Rules/                  # قوانین اعتبارسنجی سفارشی
│   ├── Services/               # منطق کسب‌وکار
│   ├── Support/                # CurrentSalon، SalonOfNotifiable، رویدادهای اعلان، …
│   └── Traits/                 # BelongsToSalon، BelongsToSalonThroughSpecialist، …
├── database/
│   ├── factories/
│   ├── migrations/             # ۵۱ فایل
│   └── seeders/
├── deploy/                     # cron و supervisor
├── docker/                     # پیکربندی containerها
├── docs/                       # مستندات استقرار و برند
├── resources/
│   ├── css/ js/                # Tailwind و JS ساده
│   └── views/                  # Blade: admin، superadmin، specialist، مشتری، auth
├── routes/
│   ├── web.php                 # ساختار کلی (پلتفرم، سالن‌ها با /s/{slug} یا زیردامنه)
│   ├── web/                    # مسیرهای سایت مشتری و ثبت‌نام سالن
│   ├── admin/                  # ۳۰ فایل مسیر پنل مدیریت
│   ├── super-admin.php         # پنل مدیر پلتفرم
│   ├── salon-auth.php          # ورود مشتری در هر سالن
│   ├── api.php + api/          # API
│   └── console.php
├── tests/                      # Feature و Unit
├── Dockerfile, docker-compose.yml, Makefile
└── Rasta unified prompt.md     # سند توسعه‌ی پروژه (تاریخچه، تصمیم‌ها، قدم‌های باز)
```

---

## 👥 نقش‌های کاربری

### 🛡️ مدیر پلتفرم (Super Admin)
- ساخت، ویرایش، تعلیق و تمدید اشتراک سالن‌ها؛ فاکتورها
- مشاهده و خروجی پرداخت‌های اشتراک
- پاسخ به تیکت‌های پشتیبانی سالن‌ها
- مدیریت نقش‌های سیستمی و فهرست مجوزها

### 🔑 مدیر سالن (Owner / Staff)
- نوبت‌ها، خدمات، دسته‌بندی‌ها، متخصص‌ها و برنامه‌ی کاری، مرخصی‌ها
- درگاه‌های پرداخت، کیف پول سالن، درخواست‌های برداشت و تسویه، «نیاز به بررسی»
- گزارش‌ها و خروجی Excel/PDF
- وفاداری (امتیازها و جوایز)، کدهای تخفیف، نظرات
- نقش‌های سالن و تخصیص نقش به کاربران همان سالن
- تنظیمات اطلاع‌رسانی، امنیتی و سالن؛ بلاگ، گالری، اعلانیه‌ها؛ اشتراک و صورت‌حساب
- دسترسی هر کارمند با نقش‌ها و مجوزها محدود می‌شود (مثلاً منشی بدون دسترسی مالی)

### 👩‍💼 متخصص (Specialist)
- نوبت‌های خود، تأیید یا رد نوبت
- برنامه‌ی کاری، مرخصی و تعطیلی
- نظرات و آمار نظرات
- کیف پول، تراکنش‌ها و درخواست برداشت
- گزارش عملکرد و خروجی Excel

### 👤 مشتری
- رزرو، تغییر زمان و لغو نوبت
- پرداخت با درگاه، کیف پول یا ترکیبی
- تاریخچه‌ی نوبت‌ها، کیف پول و تراکنش‌ها
- امتیاز وفاداری، دریافت جایزه، کدهای تخفیف
- ثبت نظر، اعلان‌ها، 2FA

---

## 🛣️ مسیرها و APIها

### مسیرهای اصلی Web

| مسیر | توضیح |
|------|-------|
| `/` | صفحه‌ی اصلی پلتفرم |
| `/salon-signup` | ثبت‌نام سالن جدید |
| `/s/{salon_slug}` | سایت سالن (یا `https://{salon_slug}.{CENTRAL_DOMAIN}`) |
| `/s/{salon_slug}/login` | ورود مشتری |
| `/s/{salon_slug}/services` | خدمات سالن |
| `/s/{salon_slug}/bookings` | نوبت‌های من و رزرو |
| `/s/{salon_slug}/wallet` | کیف پول |
| `/s/{salon_slug}/loyalty` | وفاداری و جوایز |
| `/s/{salon_slug}/security/2fa` | احراز هویت دومرحله‌ای |
| `/admin` | پنل مدیریت سالن |
| `/my-dashboard`، `/specialist/*` | پنل متخصص |
| `/superadmin/*` | پنل مدیر پلتفرم |
| `/payments/return/{publicId}` | برگشت از درگاه پرداخت |

### API

| گروه | مسیرها |
|---|---|
| اعلانیه‌های عمومی سالن | `GET api/s/{salon_slug}/announcements` (و `/active`، `/top`، `/{id}`) |
| نوبت‌ها (Sanctum) | `api/bookings` — فهرست، ثبت، آینده/گذشته/آخرین، جزئیات، لغو، تغییر زمان، امتیازدهی، کد تخفیف، متخصص‌های یک خدمت، روزها و اسلات‌های خالی |
| پرداخت امن (Sanctum) | `POST api/payments/secure/initiate/{booking}`، `GET api/payments/secure/{reference}/status` |
| امنیت حساب (Sanctum) | `api/security` — تاریخچه‌ی ورود، لاگ‌ها، نشست‌های فعال و خاتمه‌ی آن‌ها، بررسی قدرت رمز |

فهرست کامل با `php artisan route:list` قابل مشاهده است.

---

## 💳 درگاه‌های پرداخت و تسویه

| درگاه | پرداخت | تسویه‌ی خودکار (Payout) |
|---|:---:|:---:|
| زرین‌پال | ✅ | ✅ |
| زیبال | ✅ | ✅ |
| وندار | ✅ | ✅ |
| آسان‌پرداخت | ✅ | — |
| سامان | ✅ | — |
| ملت | ✅ | — |
| پارسیان | ✅ | — |

- هر سالن درگاه‌های خودش را با اولویت از پنل مدیریت ثبت می‌کند؛ اطلاعات محرمانه رمزنگاری‌شده ذخیره می‌شود.
- مشتری هنگام پرداخت درگاه را انتخاب می‌کند؛ اگر درگاه در دسترس نبود، درگاه بعدی امتحان می‌شود.
- کارمزد درگاه به مبلغ مشتری اضافه می‌شود.
- درگاه‌های بانکی (سامان، ملت، پارسیان) معمولاً ثبت IP سرور را در پنل بانک لازم دارند.

---

## ⏰ کارهای زمان‌بندی‌شده و صف

یک خط کرون کافی است:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

| کار | زمان |
|---|---|
| لغو نوبت‌های پرداخت‌نشده (`CancelUnpaidBookings`) | هر ۵ دقیقه |
| تطبیق تراکنش‌های گیرکرده (`payments:reconcile`) | هر ۵ دقیقه |
| یادآوری نوبت (`bookings:send-reminders`) | هر ۱۰ دقیقه |
| تسویه‌ی درآمدهای معلق کیف پول (`wallet:settle-pending`) | روزانه ۰۱:۰۰ |
| تمدید توکن تسویه‌ی وندار (`payouts:refresh-vandar-tokens`) | روزانه ۰۳:۳۰ |
| پاک‌سازی توکن‌های نظر (`review-tokens:cleanup`) | روزانه |
| پاک‌سازی خروجی‌های گزارش (`reports:cleanup-exports`) | روزانه |

**صف:** یا یک worker دائمی (`php artisan queue:work --tries=3` با supervisor — نمونه در `deploy/supervisor`)، یا روی هاست‌هایی مثل DirectAdmin با `QUEUE_WORK_VIA_SCHEDULER=true` صف هر دقیقه از همان کرون اجرا می‌شود.

راهنمای کامل: [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md)

---

## 🧪 اجرای تست‌ها

```bash
php artisan test                           # SQLite در حافظه (پیش‌فرض phpunit.xml)
```

برخی باگ‌ها فقط روی MySQL/MariaDB دیده می‌شوند (کلید خارجی، طول ستون، شمارنده‌ی AUTOINCREMENT). برای اجرای کل سوییت روی MariaDB یک کپی از `phpunit.xml` با اتصال `mysql` بسازید (مثلاً `phpunit.mysql.xml`، در گیت نیست) و:

```bash
vendor/bin/phpunit -c phpunit.mysql.xml
```

وضعیت فعلی:

| دیتابیس | تست | شکست | skip |
|---|---|---|---|
| SQLite | ۱۴۷۲ | ۰ | ۲ |
| MariaDB 10.11 | ۱۴۷۲ | ۰ | ۱ |

> اگر تست‌ها با خطای `Vite manifest not found` شکست خوردند، یک بار `npm run build` بزنید.
> تست‌های زیردامنه جداگانه با `phpunit.subdomain.xml` اجرا می‌شوند.

---

## 🛠️ دستورات Artisan پروژه

```bash
php artisan superadmin:create                 # ساخت مدیر پلتفرم
php artisan bookings:cleanup                  # پاک‌سازی نوبت‌های منتظر پرداخت منقضی
php artisan bookings:send-reminders           # ارسال یادآوری نوبت
php artisan payments:reconcile [--dry-run]    # تطبیق تراکنش‌های گیرکرده با درگاه
php artisan wallet:settle-pending             # تسویه‌ی درآمدهای معلق کیف پول
php artisan payouts:refresh-vandar-tokens     # تمدید توکن تسویه‌ی وندار
php artisan review-tokens:cleanup             # پاک‌سازی توکن‌های نظر منقضی
php artisan reports:cleanup-exports [--days=7]  # پاک‌سازی خروجی‌های قدیمی گزارش
php artisan tenancy:repair-legacy-rows [--rewards-salon=<slug>] [--dry-run]
                                              # اصلاح یک‌باره‌ی ردیف‌های قدیمی با مالکیت اشتباه
```

دستورات عمومی:
```bash
php artisan route:list
php artisan pail                              # مشاهده‌ی زنده‌ی لاگ
php artisan optimize:clear                    # پاک‌سازی همه‌ی cacheها
```

---

## 🗄️ جداول دیتابیس

۵۱ migration، ۶۳ جدول:

| حوزه | جداول |
|---|---|
| سالن و اشتراک | `salons`، `salon_admins`، `invoices`، `salon_sms_usages` |
| کاربران و دسترسی | `users`، `roles`، `permissions`، `role_user`، `permission_role`، `personal_access_tokens`، `password_reset_tokens`، `sessions` |
| خدمات و متخصص‌ها | `categories`، `beauty_services`، `specialists`، `specialist_services`، `specialist_schedules`، `holidays`، `leaves` |
| نوبت | `bookings` |
| پرداخت | `payments`، `payment_transactions`، `salon_payment_gateways` |
| کیف پول و تسویه | `user_wallets`، `user_wallet_transactions`، `specialist_wallets`، `wallet_transactions`، `withdrawal_requests`، `admin_wallet`، `admin_wallet_transactions`، `wallet_settings` |
| تخفیف | `discount_codes`، `discount_usages` |
| وفاداری | `loyalty_points`، `loyalties`، `loyalty_settings`، `rewards` |
| نظرات | `reviews`، `review_tokens` |
| اعلان و تنظیمات | `user_notifications`، `notification_settings`، `security_settings`، `security_logs` |
| گزارش | `report_exports`، `scheduled_reports`، `scheduled_report_runs`، `user_report_settings` |
| محتوا | `blog_posts`، `blog_categories`، `gallery_images`، `announcements` |
| پشتیبانی | `support_tickets`، `support_ticket_messages` |
| سیستم | `jobs`، `job_batches`، `failed_jobs`، `cache`، `cache_locks`، `activity_log`، `migrations`، `telescope_*` |

---

## 🌐 استقرار روی سرور

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

نکته‌ها:
- `APP_ENV=production`، `APP_DEBUG=false`، `ZARINPAL_SANDBOX=false`
- اگر برنامه پشت nginx یا پراکسی است، `TRUSTED_PROXIES` را تنظیم کنید.
- برای زیردامنه‌ی سالن‌ها: DNS و گواهی wildcard — [`WILDCARD_SUBDOMAIN_DEPLOYMENT.md`](WILDCARD_SUBDOMAIN_DEPLOYMENT.md)
- کرون scheduler و worker صف — [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md)
- بعد از بسته‌ی ۲۰۲۶-۰۹-۲۷ یک بار: `php artisan tenancy:repair-legacy-rows --dry-run` و سپس بدون `--dry-run`

---

## 📚 مستندات بیشتر

| فایل | محتوا |
|---|---|
| [`Rasta unified prompt.md`](Rasta%20unified%20prompt.md) | سند اصلی توسعه: تاریخچه، تصمیم‌ها، درس‌ها، قدم‌های باز |
| [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md) | scheduler و صف روی DirectAdmin، VPS و Docker |
| [`WILDCARD_SUBDOMAIN_DEPLOYMENT.md`](WILDCARD_SUBDOMAIN_DEPLOYMENT.md) | راه‌اندازی زیردامنه‌ی سالن‌ها |
| [`MANUAL_GATEWAY_TESTING_PROMPT.md`](MANUAL_GATEWAY_TESTING_PROMPT.md) | راهنمای تست دستی درگاه‌ها |

---

## 🤝 مشارکت

این پروژه به‌صورت شخصی-حرفه‌ای توسعه داده می‌شود. روال توسعه: برنچ `develop`، پیام commitها انگلیسی، هر تغییر در commit جدا، تست روی SQLite و MariaDB. برای گزارش باگ یا پیشنهاد، Issue باز کنید.

---

<div align="center">
  ساخته شده با ❤️ در ایران
</div>