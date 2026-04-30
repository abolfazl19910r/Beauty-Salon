# 💅 Beauty Salon Management System

> سیستم جامع مدیریت سالن زیبایی — ساخته شده با Laravel 11، React 18، Blade و Tailwind CSS

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)](https://php.net)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=flat-square&logo=react)](https://reactjs.org)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

---

## 📋 فهرست مطالب

- [معرفی پروژه](#-معرفی-پروژه)
- [ویژگی‌های اصلی](#-ویژگیهای-اصلی)
- [معماری و ساختار فنی](#-معماری-و-ساختار-فنی)
- [پیش‌نیازها](#-پیشنیازها)
- [راه‌اندازی از صفر تا صد](#-راهاندازی-از-صفر-تا-صد)
- [تنظیم متغیرهای محیطی](#-تنظیم-متغیرهای-محیطی)
- [ساختار پروژه](#-ساختار-پروژه)
- [نقش‌های کاربری](#-نقشهای-کاربری)
- [APIها و مسیرها](#-apiها-و-مسیرها)
- [سرویس‌های خارجی](#-سرویسهای-خارجی)
- [اجرای تست‌ها](#-اجرای-تستها)
- [دستورات مفید Artisan](#-دستورات-مفید-artisan)

---

## 🎯 معرفی پروژه

سیستم مدیریت سالن زیبایی یک نرم‌افزار **Full-Stack** کامل برای مدیریت تمام جنبه‌های یک سالن زیبایی است. این پروژه شامل:

- **پنل مشتری** — رزرو نوبت، پرداخت آنلاین، کیف پول، سیستم وفاداری
- **پنل متخصص (Specialist)** — مدیریت نوبت‌ها، برنامه کاری، مرخصی، کیف پول
- **پنل مدیریت (Admin)** — کنترل کامل سیستم، گزارش‌گیری، مدیریت کاربران و سرویس‌ها

احراز هویت بر پایه **SMS (Kavenegar)** و **2FA** بوده و پرداخت از طریق **زرین‌پال** انجام می‌شود.

---

## ✨ ویژگی‌های اصلی

### 🔐 احراز هویت و امنیت
- ثبت‌نام و ورود با شماره موبایل (OTP از طریق Kavenegar)
- احراز هویت دو مرحله‌ای (2FA)
- RBAC کامل (Role-Based Access Control) با Permission های جداگانه
- Middleware امنیتی (Security، Session، CSRF)
- لاگ‌گذاری فعالیت‌ها با Spatie Activity Log

### 📅 سیستم رزرو (Booking)
- رزرو نوبت با انتخاب متخصص، سرویس و زمان
- مدیریت اسلات‌های زمانی بر اساس برنامه کاری متخصص
- تایید خودکار یا دستی نوبت توسط متخصص
- یادآور نوبت از طریق SMS
- لغو و تغییر زمان نوبت
- تاریخ‌های تعطیل و مرخصی متخصص

### 💳 سیستم پرداخت
- پرداخت آنلاین با **زرین‌پال** (sandbox و production)
- کیف پول کاربر (شارژ، برداشت، تراکنش‌ها)
- کیف پول متخصص با سیستم کمیسیون
- کیف پول ادمین
- درخواست برداشت (Withdrawal)
- پیش‌پرداخت (Prepayment) نوبت

### 🏆 سیستم وفاداری (Loyalty)
- امتیازدهی به مشتریان پس از هر خدمت
- تبدیل امتیاز به کد تخفیف
- جوایز قابل بازخرید
- تاریخچه کامل امتیازات
- اعلان هنگام کسب امتیاز

### ⭐ سیستم نظرات (Reviews)
- دریافت نظر از مشتریان بعد از نوبت
- توکن یکبار مصرف برای ارسال نظر (ReviewToken)
- نمایش آمار نظرات به متخصص
- اعلان نظر منفی به ادمین
- تنظیم توکن‌های منقضی‌شده با Artisan Command

### 📊 گزارش‌گیری
- داشبورد آماری با نمودارهای React (Recharts)
- گزارش‌های مالی (PDF با DomPDF + Excel با Maatwebsite)
- گزارش متخصص
- گزارش مشتری
- گزارش‌های زمان‌بندی شده (Scheduled Reports)
- Export به PDF و Excel

### 📢 اطلاع‌رسانی
- سیستم اعلان داخلی (UserNotification)
- اعلان لحظه‌ای ادمین از ثبت نوبت جدید
- SMS به مشتری و متخصص
- اعلان تغییر وضعیت نوبت

### 📝 بلاگ و گالری
- سیستم بلاگ با دسته‌بندی
- گالری تصاویر سالن
- اعلانیه‌ها (Announcements)

### 🎟️ کدهای تخفیف
- ایجاد و مدیریت کدهای تخفیف
- محدودیت تعداد استفاده و تاریخ انقضا
- ردیابی استفاده از کد تخفیف

---

## 🏗️ معماری و ساختار فنی

| لایه | تکنولوژی |
|------|-----------|
| Backend Framework | Laravel 11 |
| Frontend (Admin Dashboard) | React 18 + Vite |
| Frontend (Public/User) | Blade + Tailwind CSS |
| UI Components | Shadcn UI + Radix UI + Lucide React |
| Charts | Recharts |
| Authentication | Laravel Breeze + Custom SMS Auth |
| Authorization | Custom RBAC (Role + Permission) |
| Payment Gateway | زرین‌پال (Zarinpal) |
| SMS Provider | Kavenegar |
| PDF Generation | DomPDF + mPDF |
| Excel Export | Maatwebsite Excel + PhpSpreadsheet |
| Image Processing | Intervention Image |
| Jalali Calendar | morilog/jalali + hekmatinasser/verta |
| Activity Logging | Spatie Laravel Activitylog |
| Debugging | Laravel Telescope |
| Queue | Database Driver |
| Cache | File / Array |

---

## 📦 پیش‌نیازها

قبل از راه‌اندازی، مطمئن شوید که موارد زیر نصب هستند:

| ابزار | نسخه مورد نیاز |
|-------|----------------|
| PHP | 8.2 یا بالاتر |
| Composer | 2.x |
| MySQL | 8.0 یا بالاتر |
| Node.js | 18.x یا بالاتر |
| npm | 9.x یا بالاتر |
| Git | هر نسخه‌ای |

> **برای کاربران XAMPP:** از XAMPP 8.2+ استفاده کنید.

---

## 🚀 راه‌اندازی از صفر تا صد

### مرحله ۱ — Clone کردن پروژه

```bash
git clone https://github.com/abolfazl19910r/Beauty-Salon.git
cd Beauty-Salon
```

---

### مرحله ۲ — نصب وابستگی‌های PHP

```bash
composer install
```

> ⚠️ اگر به خطای timeout یا access block برخوردید (به‌خاطر فیلترینگ)، با VPN/proxy اجرا کنید یا از آدرس mirror ایرانی استفاده کنید:
> ```bash
> composer config --global repos.packagist composer https://packagist.ir
> composer install
> ```

---

### مرحله ۳ — نصب وابستگی‌های JavaScript

```bash
npm install
```

---

### مرحله ۴ — ساخت فایل `.env`

```bash
cp .env.example .env
```

---

### مرحله ۵ — تولید Application Key

```bash
php artisan key:generate
```

---

### مرحله ۶ — تنظیم دیتابیس

در phpMyAdmin یا MySQL CLI یک دیتابیس جدید بسازید:

```sql
CREATE DATABASE beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

سپس اطلاعات دیتابیس را در `.env` تنظیم کنید (بخش بعدی را ببینید).

---

### مرحله ۷ — اجرای Migration و Seeder

```bash
# اجرای تمام migration ها
php artisan migrate

# اجرای Seeder برای داده‌های اولیه
php artisan db:seed
```

> **یا برای reset کامل:**
> ```bash
> php artisan migrate:fresh --seed
> ```

---

### مرحله ۸ — ساخت storage link

```bash
php artisan storage:link
```

---

### مرحله ۹ — Build کردن فایل‌های Frontend

برای محیط توسعه:
```bash
npm run dev
```

برای محیط production:
```bash
npm run build
```

---

### مرحله ۱۰ — راه‌اندازی Queue Worker

سیستم از Queue برای Job های پس‌زمینه (ارسال SMS، یادآوری نوبت، ...) استفاده می‌کند:

```bash
php artisan queue:work --tries=3
```

---

### مرحله ۱۱ — راه‌اندازی Scheduler (اختیاری)

برای اجرای دستورات زمان‌بندی شده (cleanup، یادآوری، گزارش):

```bash
# Linux/Mac — اضافه کردن به crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# برای تست در محیط توسعه:
php artisan schedule:work
```

---

### مرحله ۱۲ — اجرای سرور

```bash
php artisan serve
```

سپس در مرورگر باز کنید: **http://127.0.0.1:8000**

---

### 🏃‍♂️ راه‌اندازی سریع (یک دستور)

اگر composer این script را ساپورت کند، می‌توانید همه چیز را با یک دستور اجرا کنید:

```bash
composer run dev
```

این دستور به صورت موازی اجرا می‌کند:
- `php artisan serve` — سرور PHP
- `php artisan queue:listen` — Queue Worker
- `php artisan pail` — Log Viewer
- `npm run dev` — Vite Dev Server

---

## ⚙️ تنظیم متغیرهای محیطی

فایل `.env` را با مقادیر واقعی خود ویرایش کنید:

### تنظیمات پایه

```env
APP_NAME="Beauty Salon"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
```

### دیتابیس

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beauty_salon
DB_USERNAME=root
DB_PASSWORD=           # رمز MySQL خود را وارد کنید
```

### زرین‌پال (درگاه پرداخت)

```env
ZARINPAL_MERCHANT_ID=your-merchant-id    # از پنل زرین‌پال دریافت کنید
ZARINPAL_SANDBOX=true                    # در production روی false تنظیم کنید
```

### Kavenegar (سرویس SMS)

```env
KAVENEGAR_API_KEY=your-api-key
KAVENEGAR_SENDER=your-sender-number
KAVENEGAR_SEND_IN_LOCAL=false            # در local, SMS ارسال نمی‌شود (در log ذخیره می‌شود)

# Template های SMS
KAVENEGAR_TEMPLATE_LOGIN=your-login-template
KAVENEGAR_TEMPLATE_REGISTER=your-register-template
KAVENEGAR_TEMPLATE_RESET=your-reset-template
KAVENEGAR_TEMPLATE_2FA=your-2fa-template
```

### امنیت و احراز هویت

```env
TWO_FACTOR_TIMEOUT=300                   # مدت اعتبار کد 2FA (ثانیه)
TWO_FACTOR_CODE_LENGTH=6                 # طول کد OTP
MAX_LOGIN_ATTEMPTS=5                     # حداکثر تلاش برای ورود
LOGIN_THROTTLE_MINUTES=15                # مدت قفل شدن پس از تلاش ناموفق
VERIFICATION_CODE_EXPIRE_MINUTES=5       # انقضای کد تأیید
PAYMENT_EXPIRY_MINUTES=30                # انقضای لینک پرداخت
```

### Laravel Telescope (برای debugging)

```env
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope
```

### Queue و Cache

```env
QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
```

---

## 📁 ساختار پروژه

```
Beauty-Salon/
├── app/
│   ├── Broadcasting/          # Channels برای Broadcast
│   ├── Channels/              # SMS Channel
│   ├── Console/Commands/      # Artisan Commands (cleanup، reminder، ...)
│   ├── Events/                # Booking، Registration Events
│   ├── Exports/               # Excel Export (Maatwebsite)
│   ├── Helpers/               # JalaliDate Helper
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/         # 20+ Admin Controller
│   │   │   ├── Api/V1/        # API Controllers
│   │   │   ├── Auth/          # احراز هویت (SMS، 2FA، ...)
│   │   │   └── Specialist/    # پنل متخصص
│   │   ├── Middleware/        # Security، Role، Permission، ...
│   │   ├── Requests/          # Form Requests
│   │   └── Resources/         # API Resources
│   ├── Jobs/                  # Queue Jobs
│   ├── Listeners/             # Event Listeners
│   ├── Models/                # 30+ Eloquent Model
│   ├── Notifications/         # 12+ Notification Class
│   ├── Observers/             # Booking، DiscountCode Observer
│   ├── Policies/              # Authorization Policies
│   ├── Providers/             # Service Providers
│   └── Services/              # 15+ Service Class
│       ├── BookingService.php
│       ├── PaymentService.php
│       ├── SMSService.php
│       ├── LoyaltyService.php
│       ├── ReviewService.php
│       ├── SecurityLogService.php
│       └── ...
├── database/
│   ├── migrations/            # 26 Migration File
│   └── seeders/               # 20+ Seeder
├── resources/
│   ├── js/
│   │   ├── Components/        # React Components
│   │   │   ├── Admin/         # داشبورد ادمین (React)
│   │   │   ├── booking/       # فرم رزرو
│   │   │   └── Loyalty/       # سیستم وفاداری
│   │   ├── layouts/           # AdminLayout
│   │   ├── services/          # HTTP Client، DashboardService
│   │   └── Utils/             # DateUtils، toast، error-handler
│   └── views/
│       ├── admin/             # Blade Views ادمین
│       ├── auth/              # صفحات احراز هویت
│       ├── bookings/          # مدیریت نوبت‌ها
│       ├── specialist/        # پنل متخصص
│       └── user/wallet/       # کیف پول کاربر
├── routes/
│   ├── web.php                # مسیرهای اصلی
│   ├── api.php                # مسیرهای API
│   ├── admin/                 # 22 فایل route ادمین
│   ├── api/                   # Route های API (admin، user، public)
│   └── web/                   # Route های Web (bookings، payments، ...)
└── storage/
    └── fonts/                 # فونت Vazirmatn برای PDF
```

---

## 👥 نقش‌های کاربری

### 🔑 Admin
- دسترسی کامل به تمام بخش‌های سیستم
- مدیریت کاربران، متخصصین، سرویس‌ها، دسته‌بندی‌ها
- تأیید یا رد مرخصی متخصصین
- مشاهده و مدیریت تمام نوبت‌ها
- گزارش‌گیری مالی و عملکردی
- مدیریت Role ها و Permission ها
- مدیریت کیف پول ادمین و درخواست‌های برداشت
- تنظیمات سیستم وفاداری

### 👩‍💼 Specialist (متخصص)
- مشاهده نوبت‌های خود
- تأیید یا رد نوبت (در صورت عدم تأیید خودکار)
- ثبت مرخصی و مشاهده برنامه کاری
- مشاهده نظرات مشتریان
- مدیریت کیف پول و درخواست برداشت
- مشاهده گزارش عملکرد

### 👤 User (مشتری)
- رزرو نوبت آنلاین
- پرداخت آنلاین (زرین‌پال یا کیف پول)
- مشاهده تاریخچه نوبت‌ها
- ارسال نظر پس از دریافت خدمت
- مدیریت کیف پول
- مشاهده و استفاده از امتیازات وفاداری
- استفاده از کدهای تخفیف

---

## 🛣️ APIها و مسیرها

### Public API (بدون احراز هویت)

```
GET  /api/v1/services          — لیست سرویس‌ها
GET  /api/v1/specialists        — لیست متخصصین
GET  /api/v1/gallery            — گالری تصاویر
GET  /api/v1/blog               — پست‌های بلاگ
GET  /api/v1/announcements      — اعلانیه‌ها
```

### User API (نیاز به احراز هویت)

```
GET  /api/v1/bookings           — نوبت‌های کاربر
POST /api/v1/bookings           — ثبت نوبت جدید
GET  /api/v1/loyalty            — امتیازات وفاداری
GET  /api/v1/payments           — تراکنش‌های پرداخت
```

### Admin API

```
GET  /api/v1/admin/dashboard    — آمار داشبورد
GET  /api/v1/admin/bookings     — مدیریت نوبت‌ها
GET  /api/v1/admin/specialists  — مدیریت متخصصین
GET  /api/v1/admin/reports      — گزارش‌ها
GET  /api/v1/admin/loyalty      — تنظیمات وفاداری
```

### مسیرهای اصلی Web

| مسیر | توضیح |
|------|-------|
| `/` | صفحه اصلی |
| `/register` | ثبت‌نام |
| `/login` | ورود |
| `/bookings` | رزرو نوبت |
| `/services` | لیست سرویس‌ها |
| `/payment/*` | فرآیند پرداخت |
| `/wallet` | کیف پول کاربر |
| `/loyalty` | سیستم وفاداری |
| `/profile` | پروفایل کاربر |
| `/admin` | پنل ادمین |
| `/specialist/*` | پنل متخصص |

---

## 🔌 سرویس‌های خارجی

### زرین‌پال (Zarinpal)
برای دریافت Merchant ID به [زرین‌پال](https://www.zarinpal.com) مراجعه کنید. در محیط توسعه، `ZARINPAL_SANDBOX=true` را تنظیم کنید تا از Sandbox استفاده شود.

### Kavenegar
برای دریافت API Key به [کاوه‌نگار](https://www.kavenegar.com) مراجعه کنید. Template های SMS را از پنل کاوه‌نگار ایجاد و نام آن‌ها را در `.env` وارد کنید.

---

## 🧪 اجرای تست‌ها

```bash
php artisan test
```

یا با PHPUnit مستقیم:

```bash
./vendor/bin/phpunit
```

---

## 🛠️ دستورات مفید Artisan

```bash
# پاک‌سازی نوبت‌های منتظر پرداخت که منقضی شده‌اند
php artisan bookings:cleanup-pending

# ارسال یادآور نوبت به مشتریان
php artisan bookings:send-reminders

# پاک‌سازی توکن‌های نظر منقضی‌شده
php artisan reviews:cleanup-tokens

# تسویه درآمدهای معلق کیف پول
php artisan wallet:settle-pending

# مشاهده تمام route ها
php artisan route:list

# مشاهده log ها با Pail
php artisan pail

# پاک‌سازی cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

---

## 🗄️ جداول دیتابیس (۲۶ Migration)

| Migration | جداول |
|-----------|-------|
| users | کاربران سیستم |
| roles_and_permissions | نقش‌ها و مجوزها |
| categories | دسته‌بندی سرویس‌ها |
| beauty_services | سرویس‌های زیبایی |
| specialists | متخصصین |
| specialist_services | رابطه متخصص-سرویس |
| specialist_schedules + work_schedules + holidays + leaves | برنامه زمانی |
| bookings | رزرو نوبت‌ها |
| payments | تراکنش‌های پرداخت |
| discount_codes + discount_usages | سیستم تخفیف |
| loyalty_points + rewards + loyalties + loyalty_settings | سیستم وفاداری |
| blog_posts + blog_categories | بلاگ |
| gallery_images | گالری |
| announcements | اعلانیه‌ها |
| support_tickets + support_ticket_messages | تیکت پشتیبانی |
| scheduled_reports + scheduled_report_runs | گزارش‌های خودکار |
| user_notifications | اعلان‌های درونی |
| wallet_transactions + user_wallets + specialist_wallets + admin_wallets | کیف پول‌ها |
| reviews + review_tokens | نظرات |
| telescope_entries | Laravel Telescope |

---

## 🤝 مشارکت

این پروژه به عنوان یک پروژه شخصی-حرفه‌ای توسعه یافته است. برای گزارش باگ یا پیشنهاد ویژگی جدید، یک Issue باز کنید.

---

## 📄 لایسنس

این پروژه تحت لایسنس [MIT](LICENSE) منتشر شده است.

---

<div align="center">
  ساخته شده با ❤️ در ایران
</div>
