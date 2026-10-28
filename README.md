# 💅 ماهرو (Mahru) — پلتفرم نوبت‌دهی آنلاین سالن‌های زیبایی

**فارسی** | [English](README.en.md)

> یک SaaS چندسالنه (multi-tenant): هر سالن پنل مدیریت، پنل متخصص، سایت مشتری، درگاه پرداخت و داده‌ی کاملاً جدای خودش را دارد.
> ساخته‌شده با Laravel 13، Blade، Tailwind CSS و جاوااسکریپت ساده؛ دو اپ اندروید (Expo) برای مشتری و متخصص.
> دامنه‌ی اصلی: `arisheh.ir`

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=flat-square&logo=vite)](https://vitejs.dev)
[![Tests](https://img.shields.io/badge/tests-2363%20passing-brightgreen?style=flat-square)](#-اجرای-تستها)

---

## 📋 فهرست مطالب

- [معرفی پروژه](#-معرفی-پروژه)
- [جداسازی سالن‌ها (Multi-tenancy)](#-جداسازی-سالنها-multi-tenancy)
- [ویژگی‌های اصلی](#-ویژگیهای-اصلی)
- [اپلیکیشن‌های موبایل](#-اپلیکیشنهای-موبایل)
- [معماری و ساختار فنی](#-معماری-و-ساختار-فنی)
- [پیش‌نیازها](#-پیشنیازها)
- [راه‌اندازی لوکال (XAMPP)](#-راهاندازی-لوکال-xampp)
- [راه‌اندازی با Docker](#-راهاندازی-با-docker)
- [تنظیم متغیرهای محیطی](#-تنظیم-متغیرهای-محیطی)
- [اشتراک، پلن و قیمت](#-اشتراک-پلن-و-قیمت)
- [اطلاع‌رسانی](#-اطلاعرسانی)
- [ساختار پروژه](#-ساختار-پروژه)
- [نقش‌های کاربری](#-نقشهای-کاربری)
- [مسیرها و APIها](#-مسیرها-و-apiها)
- [درگاه‌های پرداخت و تسویه](#-درگاههای-پرداخت-و-تسویه)
- [کارهای زمان‌بندی‌شده و صف](#-کارهای-زمانبندیشده-و-صف)
- [اجرای تست‌ها](#-اجرای-تستها)
- [دستورات Artisan پروژه](#-دستورات-artisan-پروژه)
- [جداول دیتابیس](#-جداول-دیتابیس)
- [استقرار روی سرور](#-استقرار-روی-سرور)
- [عیب‌یابی](#-عیبیابی)
- [آخرین تغییرات](#-آخرین-تغییرات)
- [مستندات بیشتر](#-مستندات-بیشتر)

---

## 🎯 معرفی پروژه

**ماهرو** یک پلتفرم نرم‌افزار-به‌عنوان-سرویس برای سالن‌های زیبایی است. هر سالن با **ثبت‌نام آنلاین** از صفحه‌ی فروش (یا توسط مدیر پلتفرم) ساخته می‌شود، یک پلن اشتراک دارد و این بخش‌ها را می‌گیرد:

- **سایت و پنل مشتری** — رزرو نوبت، پرداخت آنلاین یا با کیف پول، تغییر زمان و لغو نوبت، باشگاه مشتریان، دوره‌های درمانی چندجلسه‌ای، نظر دادن
- **پنل متخصص** — برنامه‌ی امروز، نوبت‌ها، تقویم و مرخصی، کیف پول و برداشت، نظرات، گزارش و خروجی Excel
- **پنل مدیریت سالن** — نوبت‌ها، خدمات، متخصص‌ها، مشتری‌ها، درگاه‌های پرداخت، کیف پول و تسویه، گزارش‌ها، وفاداری، کمپین بازگشت مشتری، نقش‌ها، قالب سایت و پنل، تنظیمات
- **پنل مدیر پلتفرم (ابرمدیر)** — سالن‌ها، پلن‌ها و امکانات، اشتراک و فاکتور، تعلیق، تنظیمات پلتفرم و پوش، نسخه‌های اپ، تیکت‌ها، درخواست‌های دمو، وضعیت پشتیبان‌گیری
- **دو اپ اندروید** — «ماهرو» برای مشتری و «ماهرو همکار» برای متخصص (بخش [اپلیکیشن‌های موبایل](#-اپلیکیشنهای-موبایل))

ورود با **شماره موبایل، رمز و کد پیامکی** (کاوه‌نگار) و **احراز هویت دومرحله‌ای (2FA)** انجام می‌شود.

---

## 🧱 جداسازی سالن‌ها (Multi-tenancy)

همه‌ی سالن‌ها در **یک دیتابیس** هستند و جداسازی در سطح برنامه انجام می‌شود:

- **آدرس سالن:** هر سالن یک `slug` دارد (بعد از ساخت قفل است) و از دو راه در دسترس است:
  - زیرمسیر: `https://arisheh.ir/s/{salon_slug}` — همیشه
  - زیردامنه (اگر `CENTRAL_DOMAIN` تنظیم شده باشد): `https://{salon_slug}.arisheh.ir` — راهنما در [`docs/deployment/SUBDOMAINS.md`](docs/deployment/SUBDOMAINS.md)
- **سالن فعلی (`CurrentSalon`):** middlewareها سالن را از آدرس (سایت مشتری) یا از عضویت کاربر (پنل مدیریت و متخصص) پیدا و ثبت می‌کنند؛ پیش از route model binding.
- **`BelongsToSalon`:** مدل‌هایی که ستون `salon_id` دارند به‌صورت خودکار به سالن فعلی محدود می‌شوند و ردیف جدید سالن فعلی را می‌گیرد. حالت سخت‌گیر: کوئری سالن‌دار بدون سالن فعلی خطا می‌دهد مگر صریحاً `allSalons()` باشد.
- **`BelongsToSalonThroughSpecialist`:** برای جدول‌هایی که فقط از طریق متخصص به سالن وصل‌اند (مثل نظرات).
- **کارهای صف و اعلان‌ها:** سالن را صریحاً از خود رکورد می‌گیرند. تنظیمات اعلان از **سالنِ رکوردی که اعلان درباره‌ی آن است** خوانده می‌شود، نه از گیرنده.
- **تست طبقه‌بندی:** هر مدل یا scope سالن دارد یا با دلیل مستند شده است.
- **مختص هر سالن:** جوایز وفاداری، نقش‌ها، تنظیمات اطلاع‌رسانی، امنیتی و کیف پول، درگاه‌های پرداخت، قالب‌ها، متن پیامک‌ها.
- **مشترک در پلتفرم:** نقش‌های سیستمی (`admin`، `specialist`، `super-admin`، …) و فهرست مجوزها — فقط مدیر پلتفرم تغییرشان می‌دهد.

---

## ✨ ویژگی‌های اصلی

### 🔐 احراز هویت و امنیت
- ورود با شماره موبایل، رمز و کد پیامکی؛ مشتری در هر سالن حساب جدا دارد؛ «این دستگاه را به خاطر بسپار»
- کادر (مدیر و متخصص) از `/login` مرکزی وارد می‌شود؛ مشتری از صفحه‌ی سالن خودش؛ «سالن‌های من» در دامنه‌ی مرکزی
- احراز هویت دومرحله‌ای (2FA) با طول کد قابل تنظیم
- RBAC با نقش‌های مختص هر سالن و مجوزهای مشترک (مثلاً منشی بدون دسترسی مالی)
- محدودسازی تلاش ورود، لاگ رویدادهای امنیتی، لاگ کارهای ابرمدیر
- پروکسی‌های مورد اعتماد قابل تنظیم (`TRUSTED_PROXIES`) تا IP واقعی کاربر پشت CDN دیده شود؛ پیش‌فرض: به هیچ پروکسی اعتماد نمی‌شود
- امنیت مستقل از CDN: هدرهای امنیتی و HSTS از خود برنامه، CSP (اول فقط گزارش)، محدودیت نرخ روی مسیرهای حساس، HTTPS روی سرور اصلی، حالت بدون CDN
- نگهداری IP و user agent ورودها و کارهای کلیدی شش ماه (ماده‌ی ۶۶۸)، لاگ دسترسی nginx ماندگار، پاک‌سازی خودکار، بایگانی رمزنگاری‌شده‌ی حساب حذف‌شده
- صفحه‌های حقوقی (حریم خصوصی، قوانین، تماس)، ثبت پذیرش قوانین با نسخه و IP، نشان اینماد، شکایت با مهلت ۷۲ ساعت، احراز هویت و مجوز سالن

### 📅 رزرو نوبت
- رزرو با انتخاب خدمت، متخصص و زمان؛ اسلات‌ها بر اساس برنامه‌ی کاری، مرخصی و تعطیلی؛ جلوگیری از نوبت هم‌پوشان
- نوبت دستی مدیر (تلفنی/حضوری)، تأیید خودکار یا دستی، «نیامد»
- یادآوری پیامکی و پوش
- تغییر زمان و لغو با قانون جریمه‌ی لغو (مشتری و متخصص جدا)
- لغو خودکار نوبت‌های پرداخت‌نشده؛ توقف موقت رزرو سالن با بنر و بازگشایی خودکار
- «نحوه‌ی رزرو» هر سالن: **بدون پیش‌پرداخت** (پیش‌فرض، کل مبلغ در سالن) یا با پیش‌پرداخت آنلاین (فقط با درگاه فعال؛ بی درگاه خودکار برمی‌گردد)
- سقف نوبت آنلاین هر مشتری در روز و کل نوبت‌های آینده؛ مشتری بدقول (پیش‌پرداخت اجباری، یا تأیید دستی و سقف کمتر) و تأیید حضور با لینک

### 🧴 دوره‌های درمانی چندجلسه‌ای
- بسته‌های چندجلسه‌ای و چندخدمتی، پرداخت و پیگیری جلسه‌ها، یادآوری و انقضا
- یادداشت و عکس قبل/بعد هر جلسه **فقط با رضایت ثبت‌شده‌ی مشتری**، روی دیسک خصوصی؛ رضایت را خود مشتری در اپ، سایت سالن یا لینک پیامک می‌دهد (یا کادر حضوری ثبت می‌کند) و سابقه‌اش با زمان، IP، نسخه‌ی متن و منبع می‌ماند

### 💳 پرداخت و کیف پول
- **چند درگاه برای هر سالن:** زرین‌پال، زیبال، وندار، آسان‌پرداخت، سامان، ملت، پارسیان — مشتری درگاه را انتخاب می‌کند و در صورت قطعی، درگاه بعدی امتحان می‌شود؛ کارمزد درگاه به مبلغ مشتری اضافه می‌شود
- پول مشتری فقط با مرچنت **خود سالن** گرفته می‌شود؛ مرچنت پلتفرم فقط برای اشتراک و بسته‌ی پیامک سالن‌هاست
- پیش‌پرداخت درصدی (اختیاری مدیر سالن)، پرداخت کامل یا ترکیبی (کیف پول + درگاه)
- کیف پول مشتری، کیف پول متخصص با کمیسیون سالن، کیف پول سالن
- برداشت متخصص و **تسویه‌ی خودکار** با زرین‌پال، زیبال یا وندار — فقط به شبای تأییدشده؛ تسویه‌ی دستی هم ممکن است
- تطبیق خودکار تراکنش‌های گیرکرده؛ صفحه‌ی «نیاز به بررسی» با اعلان و پیامک به مالک سالن

### 🏆 وفاداری و بازگشت مشتری
- امتیاز بعد از خدمت و نظر، جوایز قابل دریافت و تبدیل به کد تخفیف، انقضای امتیاز
- کمپین بازگشت مشتری (خودکار یا دستی) با کد تخفیف و **لینک انصراف از پیامک تبلیغاتی**

### ⭐ نظرات
- لینک یک‌بارمصرف برای ثبت نظر، امتیاز جزئی، تأیید/رد، نظر ویژه، اعلان نظر منفی

### 📊 گزارش‌گیری
- داشبورد آماری؛ گزارش روزانه، هفتگی و ماهانه؛ خروجی **Excel** و **PDF** در صف؛ گزارش و Excel متخصص

### 📢 اطلاع‌رسانی
- اعلان داخلی، پیامک و **پوش اپ** با «بدون رسید ← پیامک پشتیبان»
- هر سالن برای هر رویداد کانال‌ها را تعیین می‌کند؛ متن پیامک‌ها قابل ویرایش
- سهمیه‌ی پیامک بر حسب قطعه، بسته‌ی پیامک پیش‌پرداخت، لینک کوتاه
- جزئیات در بخش [اطلاع‌رسانی](#-اطلاعرسانی)

### 🧾 اشتراک، پلن و قالب
- پلن‌ها (آزمایشی، پایه، حرفه‌ای، کامل، کلینیک) با دوره‌های ۱، ۳، ۶ و ۱۲ ماهه و **۱۲ امکان قابل روشن/خاموش** برای هر پلن
- قالب‌های قابل انتخاب سایت مشتری و پنل مدیر، بر اساس پلن و دوره
- تعداد متخصص موقع ثبت‌نام پرسیده می‌شود؛ هزینه‌ی متخصص اضافه
- ارتقای میان‌دوره با مابه‌التفاوت، تعلیق خودکار/دستی، فاکتور

### 📝 محتوا و موارد دیگر
- بلاگ، گالری، اعلانیه‌ها (سالن و پلتفرم)، کدهای تخفیف
- تیکت پشتیبانی بین سالن و مدیر پلتفرم؛ درخواست دمو از صفحه‌ی فروش
- QR و لینک سالن، صفحه‌ی دانلود اپ (`/app`)، PWA برای آیفون
- پشتیبان‌گیری شبانه با نمایش وضعیت در داشبورد ابرمدیر

---

## 📱 اپلیکیشن‌های موبایل

- **دو اپ از یک کد Expo** روی برنچ یتیم `mobile-app` همین مخزن: «ماهرو» (مشتری) و «ماهرو همکار» (فقط متخصص؛ پنل مدیر وب می‌ماند).
- **بدون مارکت:** APK در پنل ابرمدیر («نسخه‌های اپ») آپلود و از `https://arisheh.ir/app` دانلود می‌شود. آیفون: PWA.
- مشتری با لینک یا QR سالن وارد اپ می‌شود؛ اپ در هر لحظه یک سالن دارد. پرداخت در مرورگر بیرونی گوشی.
- **پوش:** FCM با رسید؛ حالت‌های «خاموش / آزمایشی / فعال» در تنظیمات پلتفرم؛ در «فعال»، اگر رسید تا مهلت نیامد پیامک پشتیبان می‌رود. ntfy برای قطعی اینترنت بین‌الملل پیش‌بینی شده است.
- API: بخش [مسیرها و APIها](#-مسیرها-و-apiها)؛ ساخت با expo.dev.

---

## 🏗️ معماری و ساختار فنی

| لایه | تکنولوژی |
|------|-----------|
| Backend | Laravel 13 (PHP 8.3 تا 8.5؛ image داکر PHP 8.5) |
| Frontend | Blade + Tailwind CSS 3 + جاوااسکریپت ساده، Vite 8؛ بدون CDN خارجی |
| فونت | Vazirmatn |
| احراز هویت | ورود پیامکی سفارشی، Sanctum برای API اپ‌ها |
| مجوزدهی | RBAC سفارشی (نقش مختص سالن + مجوز مشترک) |
| Multi-tenancy | تک‌دیتابیس، `CurrentSalon` + global scope + چک مالکیت |
| پرداخت | لایه‌ی درایور (`app/Payments`): ۷ درگاه و ۳ درایور تسویه |
| پیامک | کاوه‌نگار |
| پوش | Firebase Cloud Messaging (+ ntfy) |
| PDF / Excel | mPDF، DomPDF / Maatwebsite Excel + PhpSpreadsheet |
| تصویر | Intervention Image |
| تاریخ شمسی | morilog/jalali + hekmatinasser/verta + تقویم خودکفای jcal |
| لاگ فعالیت | Spatie Laravel Activitylog |
| صف | Database (یا Redis) با صف‌های جدا |
| استقرار | Docker تک‌کانتینری (supervisord) روی سرور ابری آروان، پشت CDN آروان |
| معماری کد | Controller ← Service ← Repository (Interface + Eloquent) |

---

## 📦 پیش‌نیازها

| ابزار | نسخه |
|-------|------|
| PHP | 8.3 تا 8.5؛ آزموده با 8.5 (افزونه‌ها: mbstring، xml، pdo_mysql، sqlite3، curl، zip، gd، intl، bcmath) |
| Composer | 2.x |
| MySQL / MariaDB | MySQL 8.0+ (داکر شش‌سرویسی: 8.4) یا MariaDB 10.4+ (XAMPP؛ تک‌کانتینری: 11.8) |
| Node.js | 22 (حداقل 18) |
| Git | — |
| Docker Desktop (اختیاری) | برای اجرای تک‌کانتینری و ساخت image سرور |

---

## 🚀 راه‌اندازی لوکال (XAMPP)

```bash
git clone https://github.com/abolfazl19910r/Beauty-Salon.git
cd Beauty-Salon
git checkout develop
cp .env.example .env                 # پیش از composer install (package:discover به دیتابیس وصل می‌شود)
composer install
npm install
php artisan key:generate
```

دیتابیس بسازید و اطلاعاتش را در `.env` بگذارید:

```sql
CREATE DATABASE beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate:fresh --seed      # سالن نمایشی «راستا» (/s/rasta) و حساب‌های نمایشی (database/seeders/UserSeeder.php)
php artisan superadmin:create
php artisan storage:link              # روی ویندوز با ترمینال Administrator
npm run build                         # یا npm run dev
php artisan serve
php artisan queue:work --queue=otp,push,sms,payments,default,reports   # ترمینال دوم
php artisan schedule:work                                               # ترمینال سوم
```

- صفحه‌ی فروش: **http://127.0.0.1:8000** · ثبت‌نام سالن: `/salon-signup` · سالن: `/s/{salon_slug}` · مدیر سالن: `/admin` · ابرمدیر: `/superadmin/dashboard`
- **داده‌های فعلی فیک‌اند.** وقتی migrationهای سازنده عوض می‌شوند، دیتابیس با `migrate:fresh --seed` از نو ساخته می‌شود (هر بار در «آخرین تغییرات» گفته می‌شود).
- در `local` پیامک واقعی نمی‌رود و متن و کد ورود در `storage/logs` ثبت می‌شود.

---

## 🐳 راه‌اندازی با Docker

دو حالت، هر دو از یک `Dockerfile`:

| حالت | فایل | کاربرد | راهنما |
|---|---|---|---|
| **تک‌کانتینری** (target `single`) | `docker-compose.single.yml` / `docker-compose.server.yml` | آزمون لوکال روی Docker Desktop، و **سرور واقعی آروان** | [`DOCKER_DESKTOP.md`](docs/deployment/DOCKER_DESKTOP.md)، [`ARVAN_SERVER.md`](docs/deployment/ARVAN_SERVER.md) |
| شش‌سرویسی (target `production`) | `docker-compose.yml` + `Makefile` | وقتی بار بیشتر شد (MySQL و Redis جدا) | [`SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md) بخش ۵ |

**تک‌کانتینری:** داخل یک کانتینر با supervisord: MariaDB، PHP-FPM، Nginx، worker صف‌ها، worker کد تأیید، scheduler و پشتیبان شبانه. همه‌ی داده روی volume `/data` می‌ماند.

```bash
copy .env.single.example .env.single                     # ویندوز
docker compose -f docker-compose.single.yml up -d --build
# http://localhost:8080  — ساب‌دامین لوکال با فایل hosts: http://rasta.arisheh.test:8080
```

**شش‌سرویسی:** `make setup`، `make migrate`، `make seed`، `make superadmin`؛ `make help` فهرست کامل.

---

## ⚙️ تنظیم متغیرهای محیطی

نمونه‌ها: `.env.example` (XAMPP)، `.env.single.example` (Docker Desktop)، `.env.server.example` (سرور آروان)، `.env.docker` (شش‌سرویسی).

### پایه و برند
```env
APP_NAME="ماهرو"
BRAND_NAME="ماهرو"
BRAND_NAME_EN=Mahru
BRAND_DOMAIN=arisheh.ir
APP_ENV=local                 # سرور: production
APP_DEBUG=true                # سرور: false
APP_URL=http://localhost      # سرور: https://arisheh.ir
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
CENTRAL_DOMAIN=               # arisheh.ir برای زیردامنه‌ی سالن‌ها؛ خالی = فقط /s/{slug}
SESSION_DOMAIN=               # با زیردامنه: .arisheh.ir
SESSION_SECURE_COOKIE=        # روی HTTPS: true
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
> در حالت تک‌کانتینری `APP_KEY` و `DB_PASSWORD` را خود کانتینر می‌سازد؛ آن‌ها را (حتی خالی) ننویسید.

### اشتراک سالن‌ها
```env
SUBSCRIPTION_TRIAL_DAYS=14
SMS_QUOTA_PER_MONTH=2000                  # سهمیه‌ی ماهانه بر حسب قطعه
TRIAL_SMS_QUOTA=400
SMS_PART_PRICE=320                        # تومان، بسته‌ی پیامک
SMS_PACKS=1000,2500,5000
INCLUDED_SPECIALISTS_COUNT=10
EXTRA_SPECIALIST_PRICE_PER_MONTH=250000
MAX_SIGNUP_SPECIALISTS=50
```
> قیمت پلن‌ها در پنل ابرمدیر («پلن‌ها») تعیین می‌شود.

### درگاه پرداخت پلتفرم (اشتراک و بسته‌ی پیامک)
```env
ZARINPAL_MERCHANT_ID=
ZARINPAL_API_KEY=
ZARINPAL_SANDBOX=true              # در go-live: false
ZARINPAL_PAYOUT_API_KEY=
ZARINPAL_PAYOUT_SANDBOX=true
```
> درگاه‌های **هر سالن** در `.env` نیستند؛ مدیر سالن از پنل خودش وارد می‌کند و رمزنگاری‌شده ذخیره می‌شود.

### پیامک (کاوه‌نگار)
```env
KAVENEGAR_API_KEY=
KAVENEGAR_SENDER=
KAVENEGAR_SEND_IN_LOCAL=false      # در local پیامک فقط در لاگ
SMS_LOG_ONLY=false                 # true = در هیچ محیطی (حتی production) پیامک واقعی نمی‌رود؛ متن و کد فقط در لاگ
KAVENEGAR_TEMPLATE_LOGIN=
KAVENEGAR_TEMPLATE_REGISTER=
KAVENEGAR_TEMPLATE_RESET=
KAVENEGAR_TEMPLATE_2FA=
SMS_LINK_HOST=                     # میزبان لینک کوتاه پیامک؛ خالی = CENTRAL_DOMAIN
```

### پوش و اپ‌ها
```env
PUSH_FCM_CREDENTIALS=storage/app/private/mahru-fcm-sa.json   # خالی = پوش خاموش
PUSH_MODE=shadow                   # off | shadow | live (پیش‌فرض پیش از ذخیره در تنظیمات پلتفرم)
PUSH_FCM_STYLE=data
PUSH_NTFY_SERVER=
CUSTOMER_APP_SHA256_FINGERPRINTS=  # برای /.well-known/assetlinks.json
```

### امنیت، پروکسی و پشتیبان
```env
TWO_FACTOR_CODE_LENGTH=6
MAX_LOGIN_ATTEMPTS=5
LOGIN_THROTTLE_MINUTES=1
TRUSTED_PROXIES=                   # رنج IP لبه‌های CDN (سرور آروان: deploy/single/arvan-ips.sh). هرگز «*»
HSTS_MAX_AGE=                      # فقط روی HTTPS؛ خالی = یک سال، 0 = خاموش
SECURITY_CSP=report               # report (فقط گزارش در storage/logs/csp-*.log)، enforce یا off
RETENTION_DAYS=190                 # نگهداری IP و لاگ‌ها (ماده‌ی ۶۶۸)؛ کمتر از ۱۸۴ پذیرفته نمی‌شود
LEGAL_PAGES_REVIEWED=false         # true بعد از بازبینی حقوق‌دان (برچسب «پیش‌نویس» برداشته می‌شود)
SALON_VERIFIER=manual              # احراز سالن: manual (ابرمدیر)؛ بعداً سامانه‌ی رسمی
BACKUP_AT=03:40                    # فقط تک‌کانتینری: پشتیبان شبانه؛ خالی = خاموش
BACKUP_AGE_RECIPIENT=              # کلید عمومی age (age1…)؛ پشتیبان‌ها رمزنگاری می‌شوند، کلید خصوصی بیرون از سرور
BACKUP_KEEP=3
BACKUP_STALE_HOURS=36
```
> `TRUSTED_PROXIES` خالی یعنی به هیچ پروکسی اعتماد نمی‌شود (حالت بدون CDN، IP مستقیم واقعی است)؛ پشت CDN آروان رنج آروان را بگذارید (`arvan-ips.sh`).
> nginx همان فهرست را برای `set_real_ip_from` می‌خواند (`php artisan security:nginx-real-ip`). امنیت به CDN وابسته نیست و `arvan-ips.sh --no-cdn` سرور را بی CDN بالا می‌آورد.
> هویت حقوقی پلتفرم (نام حقوقی، شناسه‌ی ملی، تلفن ثابت، نشانی، کد پستی، کد نشان اینماد) از پنل ابرمدیر ← تنظیمات ← «اطلاعات حقوقی» تنظیم می‌شود.

### صف، cache و session
```env
QUEUE_CONNECTION=database
QUEUE_WORK_VIA_SCHEDULER=false     # true فقط روی هاست بدون worker (DirectAdmin)
CACHE_STORE=file                   # روی سرور file یا database؛ هرگز array
SESSION_DRIVER=file
TELESCOPE_ENABLED=false            # فقط در لوکال true
BOT_ENABLED=false                  # ربات بله/تلگرام کنار گذاشته شده است
```
> `CACHE_DRIVER` از Laravel 11 به بعد خوانده نمی‌شود؛ فقط `CACHE_STORE`.

---

## 💰 اشتراک، پلن و قیمت

- **پلن‌ها** در پنل ابرمدیر ساخته و ویرایش می‌شوند: قیمت هر دوره (۱، ۳، ۶ و ۱۲ ماهه)، تعداد متخصص شامل، سهمیه‌ی پیامک و **۱۲ امکان** (مثل یادآوری پیامکی، دوره‌های درمانی، کمپین بازگشت، …). امکان می‌تواند برای یک سالن خاص هم جدا روشن/خاموش شود.
- پلن غیرفعال را سالن تازه نمی‌خرد ولی سالن‌هایی که رویش هستند با قیمت فعلی تمدید می‌کنند؛ ابرمدیر هر پلنی را دستی می‌دهد. ارتقای میان‌دوره فوری و با مابه‌التفاوت است.
- **دوره‌ی آزمایشی** ۱۴ روزه؛ خرید در دوره‌ی آزمایشی پلن پولی را همان لحظه شروع می‌کند.
- **ثبت‌نام عمومی** (`/salon-signup`) تعداد متخصص را می‌پرسد؛ همین **سقف متخصص** سالن است. هر متخصص بیشتر از تعداد شامل پلن ماهانه `EXTRA_SPECIALIST_PRICE_PER_MONTH` تومان دارد (با همان تخفیف دوره).
- **قالب‌ها:** قالب پیش‌فرض طلایی ماهرو رایگان است؛ قالب‌های دیگر سایت مشتری و پنل مدیر بر اساس پلن و طول دوره.
- منطق قیمت: `app/Support/Billing/SubscriptionPricing.php`.

---

## 📢 اطلاع‌رسانی

- **کانال‌ها:** اعلان داخلی، پیامک (کاوه‌نگار) و پوش اپ. ربات بله/تلگرام پشت `BOT_ENABLED=false` است و در پنل‌ها دیده نمی‌شود.
- **تنظیمات هر سالن:** برای هر رویداد هر کانال جدا؛ همیشه از سالنِ رکورد خوانده می‌شود.
- **پوش هوشمند:** در حالت «فعال» پیام فوری اگر تا مهلت رسید نیاید، پیامک پشتیبان می‌گیرد؛ دستگاه ناسالم مستقیم پیامک. ابزار: `push:test`، `push:live-check`.
- **هزینه‌ی پیامک:** کدهای تأیید (ورود، 2FA، تأیید تلفن، بازیابی رمز) خرج پلتفرم‌اند و هرگز با تمام شدن سهمیه قطع نمی‌شوند؛ بقیه از سهمیه‌ی ماهانه‌ی سالن (بر حسب قطعه)، بعد از اعتبار خریداری‌شده.
- **«نیاز به بررسی»:** پرداخت یا تسویه‌ای که خودکارسازی رهایش کرده، یک بار به مالک سالن اعلان و پیامک می‌دهد.
- **تبلیغاتی:** پیامک‌های کمپین بازگشت لینک انصراف دارند.

---

## 📁 ساختار پروژه

```
Beauty-Salon/
├── app/
│   ├── Console/Commands/       # دستورات Artisan پروژه
│   ├── Events/ Listeners/      # رویدادها (نوبت، پرداخت، برداشت، …)
│   ├── Exports/                # خروجی‌های Excel
│   ├── Http/
│   │   ├── Controllers/        # Admin، SuperAdmin، Specialist، User، Api/V1، Central، Payment، Auth
│   │   ├── Middleware/         # شناسایی سالن، فعال‌بودن سالن، مجوزها، پروکسی، …
│   │   └── Requests/           # Form Requestها
│   ├── Jobs/                   # کارهای صف (پیامک، پوش، گزارش، …)
│   ├── Models/                 # ۷۶ مدل
│   ├── Notifications/
│   ├── Payments/               # درایورهای درگاه و تسویه + GatewayManager / PayoutManager
│   ├── Repositories/           # Contracts/ + Eloquent/
│   ├── Services/               # منطق کسب‌وکار
│   ├── Support/                # CurrentSalon، Billing، Sms، Features، Queues، …
│   └── Traits/                 # BelongsToSalon، …
├── database/                   # ۶۶ migration، factoryها، seederها
├── deploy/
│   ├── single/                 # ساخت image (ویندوز)، deploy سرور، IPهای آروان
│   ├── backup/                 # اسکریپت پشتیبان
│   ├── cron/ supervisor/       # بدون Docker
├── docker/                     # پیکربندی containerها (single/ برای تک‌کانتینری)
├── docs/
│   ├── deployment/             # آروان، Docker Desktop، زیردامنه، صف و زمان‌بندی، پشتیبان
│   ├── testing/                # تست دستی درگاه‌ها
│   └── brand/                  # کیت برند
├── resources/views/            # Blade: admin، superadmin، specialist، مشتری، salon-home (قالب‌ها)، auth
├── routes/                     # web.php + web/، admin/ (۳۵ فایل)، super-admin.php، salon-auth.php، api/v1.php، console.php
├── tests/                      # Feature و Unit
├── Dockerfile, docker-compose*.yml, Makefile
├── README.md, README.en.md
└── Rasta unified prompt.md     # سند توسعه‌ی پروژه (تاریخچه، تصمیم‌ها، قدم‌های باز)
```

---

## 👥 نقش‌های کاربری

### 🛡️ مدیر پلتفرم (ابرمدیر)
- سالن‌ها: ساخت، ویرایش، تعلیق، تمدید، سقف متخصص، سهمیه‌ی پیامک، امکانات اختصاصی
- پلن‌ها و امکانات، فاکتورها، پرداخت‌های اشتراک، اعطای دستی اعتبار پیامک
- تنظیمات پلتفرم (پوش، درگاه پلتفرم، متن‌ها)، نسخه‌های اپ، اعلانیه‌ی پلتفرم
- تیکت‌ها و شکایت‌ها (مهلت ۷۲ ساعت)، درخواست‌های دمو، لاگ کارها، وضعیت پشتیبان‌گیری
- احراز سالن‌ها (تأیید یا رد مدارک)، اطلاعات حقوقی پلتفرم، جست‌وجوی داده‌ی نگه‌داشته‌ی حساب‌های حذف‌شده برای مرجع قضایی
- نقش‌های سیستمی و فهرست مجوزها

### 🔑 مدیر سالن (مالک / کارمند)
- نوبت‌ها، خدمات، دسته‌ها، متخصص‌ها و برنامه‌ی کاری، مرخصی‌ها، دوره‌های درمانی
- درگاه‌های پرداخت، کیف پول سالن، برداشت‌ها، تأیید شبا، «نیاز به بررسی»
- گزارش‌ها و خروجی، وفاداری، کد تخفیف، کمپین بازگشت مشتری، نظرات
- نقش‌های سالن، تنظیمات اطلاع‌رسانی و امنیتی، قالب‌ها، توقف موقت رزرو، اشتراک و صورت‌حساب، بلاگ، گالری، اعلانیه

### 👩‍💼 متخصص
- برنامه‌ی امروز، نوبت‌ها، تقویم، مرخصی و ساعت کاری هفتگی
- نظرات، کیف پول، شبا و برداشت، گزارش و Excel
- رزرو نوبت برای خودش به‌عنوان مشتری؛ اپ «ماهرو همکار»

### 👤 مشتری
- رزرو، تغییر زمان، لغو، پرداخت با درگاه یا کیف پول یا ترکیبی
- دوره‌های درمانی، باشگاه مشتریان، کد تخفیف، نظر، اعلان‌ها، 2FA، حذف حساب
- اپ «ماهرو» یا PWA

---

## 🛣️ مسیرها و APIها

### مسیرهای اصلی Web

| مسیر | توضیح |
|------|-------|
| `/` | صفحه‌ی فروش پلتفرم |
| `/salon-signup` | ثبت‌نام سالن جدید |
| `/login` | ورود کادر (مدیر و متخصص) |
| `/my-salons` | «سالن‌های من» برای مشتری (شماره ← کد ← فهرست سالن‌ها) |
| `/app` | دانلود اپ‌ها |
| `/b/{code}` | لینک کوتاه پیامک |
| `/s/{salon_slug}` | سایت سالن (یا `https://{salon_slug}.{CENTRAL_DOMAIN}`) |
| `/s/{salon_slug}/login` | ورود مشتری |
| `/s/{salon_slug}/bookings` | نوبت‌های من و رزرو |
| `/s/{salon_slug}/wallet` · `/loyalty` | کیف پول · باشگاه مشتریان |
| `/admin` | پنل مدیریت سالن |
| `/my-dashboard`، `/specialist/*` | پنل متخصص |
| `/superadmin/*` | پنل مدیر پلتفرم |
| `/payments/return/{publicId}` | برگشت از درگاه |
| `/terms` · `/privacy` · `/contact` | قوانین و شرایط · حریم خصوصی · تماس با ما (دامنه‌ی مرکزی) |
| `/s/{salon_slug}/complaints` | شکایت و پیگیری مشتری |
| `/admin/verification` | احراز هویت و مجوز سالن |
| `/up` | سلامت برنامه |

### API اپ‌ها (`/api/v1`، توکن Sanctum)

| گروه | مسیرها |
|---|---|
| پایه | `GET api/v1/status`، `GET api/v1/me`، `POST api/v1/logout`، `api/v1/tokens`، ثبت دستگاه پوش و رسید |
| «ماهرو همکار» | `api/v1/staff/*` — ورود، امروز، نوبت‌ها، تقویم، مرخصی، ساعت کاری، کیف پول و شبا، اعلان‌ها |
| «ماهرو» پیش از ورود | `api/v1/customer/salons/{salon_slug}` — کارت سالن، ورود، ثبت‌نام، بازیابی رمز |
| «ماهرو» | `api/v1/customer/*` — خانه، خدمات، متخصص‌ها، زمان‌های آزاد، نوبت‌ها، پرداخت، کیف پول، باشگاه، دوره‌ها، پروفایل، حذف حساب، اعلان‌ها |
| پرداخت اپ در مرورگر | `/s/{salon_slug}/app/checkout/{tx}` (امضاشده) و callbackها |

فهرست کامل: `php artisan route:list`.

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

- زرین‌پال در sandbox آزموده شده است؛ بقیه با تست‌های خودکار (`Http::fake`)، و تست دستی واقعی‌شان در [`docs/testing/MANUAL_GATEWAY_TESTING.md`](docs/testing/MANUAL_GATEWAY_TESTING.md) مانده است.
- درگاه‌های بانکی (سامان، ملت، پارسیان) معمولاً ثبت IP سرور در پنل بانک را لازم دارند.
- ⚠️ پذیرش «مرچنت هر سالن روی دامنه‌ی پلتفرم» نزد درگاه‌ها هنوز تأیید نشده است (`Rasta unified prompt.md`، کارهای باز 5د).

---

## ⏰ کارهای زمان‌بندی‌شده و صف

در حالت تک‌کانتینری هر دو داخل کانتینرند و کاری لازم نیست. بدون Docker یک خط کرون کافی است:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

| کار | زمان |
|---|---|
| لغو نوبت‌های پرداخت‌نشده، تطبیق تراکنش‌های گیرکرده (`payments:reconcile`) | هر ۵ دقیقه |
| یادآوری نوبت (`bookings:send-reminders`)، تأیید حضور با لینک (`bookings:attendance-checks`)، برگشت خودکار به «بدون پیش‌پرداخت» (`bookings:enforce-payment-mode`) | هر ۱۰ دقیقه |
| کمپین‌های بازگشت (`winback:campaigns`) | هر دقیقه |
| انقضای دوره‌ها (`courses:expire`)، اعمال پلن زمان‌بندی‌شده (`plans:apply-scheduled`) | ساعتی |
| تسویه‌ی درآمدهای معلق (`wallet:settle-pending`) | ۰۱:۰۰ |
| انقضای امتیاز، تمدید توکن وندار، پاک‌سازی‌ها (`model:prune`، توکن‌ها، خروجی گزارش) | شبانه |
| بازگشت مشتری خودکار (`customers:winback`)، یادآوری دوره (`courses:remind`) | صبح |
| یادآوری شکایت‌های بی‌پاسخ (`complaints:remind`) | ساعتی |
| پاک‌سازی داده‌ی نگهداری‌شده پس از مدت قانونی (`retention:prune`) | ۰۳:۵۰ |

**صف‌ها** (به این ترتیب): `otp`، `push`، `sms`، `payments`، `default`، `reports` — به‌اضافه‌ی یک worker اختصاصی `otp`. `queue:work` بدون `--queue` فقط `default` را می‌خواند.

فهرست دقیق: `php artisan schedule:list` · راهنما: [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md)

---

## 🧪 اجرای تست‌ها

```bash
php artisan test                       # SQLite در حافظه (پیش‌فرض phpunit.xml)
```

برای MariaDB/MySQL یک کپی از `phpunit.xml` با `DB_CONNECTION=mysql` و اطلاعات یک دیتابیس خالی بسازید (مثلاً `phpunit.mysql.xml`، در گیت نیست) و `vendor/bin/phpunit -c phpunit.mysql.xml`.
XAMPP (MariaDB پیش از 10.10) `explicit_defaults_for_timestamp=OFF` دارد؛ برای سنجیدن همان رفتار روی سرور تست، آن را در `[mysqld]` خاموش کنید.

| دیتابیس | آخرین اجرای کامل | شکست | skip |
|---|---|---|---|
| SQLite | ۲۳۶۳ (۲۰۲۶-۱۰-۲۷) | ۰ | ۱۱ |
| MariaDB 10.11 (`explicit_defaults_for_timestamp` روشن و خاموش) و MySQL 8.0 | ۲۳۶۳ (۲۰۲۶-۱۰-۲۷) | ۰ | ۱ |

> `Vite manifest not found` در تست‌ها: یک بار `npm run build`.
> تست‌های زیردامنه: `vendor/bin/phpunit -c phpunit.subdomain.xml`.

---

## 🛠️ دستورات Artisan پروژه

```bash
php artisan superadmin:create                 # ساخت مدیر پلتفرم
php artisan bookings:send-reminders           # یادآوری نوبت
php artisan bookings:cleanup                  # نوبت‌های منتظر پرداخت منقضی
php artisan payments:reconcile [--dry-run]    # تطبیق تراکنش‌های گیرکرده
php artisan wallet:settle-pending             # تسویه‌ی درآمدهای معلق
php artisan payouts:refresh-vandar-tokens     # تمدید توکن تسویه‌ی وندار
php artisan loyalty:expire-points             # انقضای امتیاز
php artisan courses:remind | courses:expire   # دوره‌های درمانی
php artisan customers:winback | winback:campaigns   # بازگشت مشتری
php artisan plans:sync-defaults | plans:apply-scheduled
php artisan push:test <شماره> [--repeat=N] [--wait=ثانیه]
php artisan push:live-check <شماره> [--urgency=…]
php artisan reports:cleanup-exports [--days=7]
php artisan review-tokens:cleanup
php artisan salons:analyze-logos
php artisan tenancy:repair-legacy-rows [--dry-run]
php artisan security:nginx-real-ip            # set_real_ip_from از TRUSTED_PROXIES
php artisan retention:prune [--dry-run]       # پاک‌سازی IP و لاگ‌های قدیمی‌تر از مدت نگهداری
php artisan complaints:remind                 # شکایت‌های نزدیک به مهلت ۷۲ ساعت
php artisan perf:seed-load --salons=100       # فقط دیتابیس آزمایشی
```

---

## 🗄️ جداول دیتابیس

۶۶ migration و ۹۴ جدول. هر migration سازنده همه‌ی ستون‌های نهایی جدولش را دارد (migrationهای اصلاحی ادغام شده‌اند).

| حوزه | جداول |
|---|---|
| سالن، پلن و اشتراک | `salons`، `salon_admins`، `plans`، `plan_features`، `salon_feature_overrides`، `invoices`، `salon_sms_usages`، `salon_sms_templates`، `sms_credit_purchases`، `salon_verifications` |
| کاربران و دسترسی | `users`، `roles`، `permissions`، `role_user*`، `permission_role`، `personal_access_tokens`، `password_reset_tokens`، `sessions`، `trusted_devices`، `legal_acceptances`، `deleted_account_archives` |
| خدمات و متخصص‌ها | `categories`، `beauty_services`، `specialists`، `specialist_services`، `specialist_schedules`، `holidays`، `leaves` |
| نوبت و دوره | `bookings`، `booking_policies`، `course_packages`، `customer_courses`، `customer_course_payments`، `course_session_notes`، `course_session_photos`، `course_reminders`، `course_photo_consents`، `course_settings` |
| پرداخت | `payments`، `payment_transactions`، `salon_payment_gateways`، `idempotency_keys` |
| کیف پول و تسویه | `user_wallets`، `user_wallet_transactions`، `specialist_wallets`، `wallet_transactions`، `withdrawal_requests`، `admin_wallet`، `admin_wallet_transactions`، `wallet_settings` |
| تخفیف، وفاداری و بازگشت | `discount_codes`، `discount_usages`، `loyalty_points`، `loyalties`، `loyalty_settings`، `rewards`، `winback_*` |
| نظرات | `reviews`، `review_tokens` |
| اعلان، پوش و تنظیمات | `user_notifications`، `notification_settings`، `security_settings`، `security_logs`، `push_devices`، `push_messages`، `push_deliveries`، `platform_settings`، `platform_announcements` |
| اپ و لینک | `app_releases`، `short_links`، `qr_scans`، `bot_links`، `bot_link_codes` |
| گزارش | `report_exports`، `scheduled_reports`، `scheduled_report_runs`، `user_report_settings` |
| محتوا و پشتیبانی | `blog_posts`، `blog_categories`، `gallery_images`، `announcements`، `support_tickets`، `support_ticket_messages`، `demo_requests` |
| سیستم | `jobs`، `job_batches`، `failed_jobs`، `cache`، `cache_locks`، `activity_log`، `superadmin_audit_logs`، `telescope_*` |

---

## 🌐 استقرار روی سرور

**روش انتخاب‌شده (۲۰۲۶-۱۰-۲۴):** image تک‌کانتینری روی ویندوز ساخته و با WinSCP به سرور ابری آروان منتقل می‌شود؛ روی سرور git، build و رجیستری لازم نیست. HTTPS در لبه‌ی CDN آروان است.

```
ویندوز:  deploy\single\build-image.cmd                   ← build-images\mahru-single-<commit>.tar
WinSCP:  .tar به /srv/mahru/images
سرور:    bash deploy/single/server-deploy.sh images/mahru-single-<commit>.tar
         (پشتیبان پیش از deploy، load، up، انتظار healthy؛ برگشت: --list و --use <نسخه>)
```

راهنمای کامل از خرید سرور تا آزمون: [`docs/deployment/ARVAN_SERVER.md`](docs/deployment/ARVAN_SERVER.md).

نکته‌ها:
- `.env.server`: `APP_ENV=production`، `APP_DEBUG=false`، `TELESCOPE_ENABLED=false`، `MAHRU_SEED=false`، `TRUSTED_PROXIES` از `arvan-ips.sh`.
- تا آماده شدن کاوه‌نگار: `SMS_LOG_ONLY=true` (کد ورود: `docker logs mahru 2>&1 | grep SMS`). تا go-live: `ZARINPAL_SANDBOX=true`.
- پورت ۸۰ فقط برای لبه‌های CDN باز باشد (Docker قوانین ufw را دور می‌زند).
- پشتیبان شبانه در `/srv/mahru/backups` (دیتابیس، آپلودها، `app.key`)؛ دانلود دستی هفتگی — [`docs/deployment/BACKUP.md`](docs/deployment/BACKUP.md).
- زیردامنه‌ها: [`docs/deployment/SUBDOMAINS.md`](docs/deployment/SUBDOMAINS.md).

**بدون Docker (DirectAdmin یا VPS، برای مرجع):**
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
کرون و worker: [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md). `CACHE_STORE=file` یا `database` (نه `array`).

---

## 🩺 عیب‌یابی

| مشکل | علت و راه‌حل |
|---|---|
| `composer install` در `package:discover` خطای دیتابیس می‌دهد | `.env` نیست یا دیتابیس در دسترس نیست؛ `.env` را بسازید و `php artisan package:discover` |
| `Vite manifest not found` | `npm run build` |
| ۴۱۹ Page Expired | `SESSION_DOMAIN` با دامنه یکی نیست (بخش عیب‌یابی `SUBDOMAINS.md`) |
| بعد از `git pull` خطای ستون یا جدول ناموجود، یا `Table … already exists` | migrationهای سازنده عوض شده‌اند؛ `php artisan migrate:fresh --seed` (داده‌ها فیک‌اند) |
| تغییر `.env` اثر نمی‌کند | `php artisan config:clear` و `queue:restart`؛ در Docker `up -d` (نه `restart`) |
| پیامک ارسال نمی‌شود | در `local` یا با `SMS_LOG_ONLY=true` عمدی است؛ متن در لاگ. وگرنه worker صف `sms`/`otp` را چک کنید |
| IP همه‌ی کاربران یکی است / لینک درگاه `http://` | `TRUSTED_PROXIES` |
| تصاویر آپلودی نمایش داده نمی‌شوند | `php artisan storage:link` (روی ویندوز ترمینال Administrator) |
| build داکر کند است یا در کامپوزر می‌شکند | `build-vendor` و آینه‌ی رجیستری — `DOCKER_DESKTOP.md` بخش ۱ |

---

## 🆕 آخرین تغییرات

تاریخچه‌ی کامل، تصمیم‌ها و قدم‌های باز در [`Rasta unified prompt.md`](Rasta%20unified%20prompt.md).

**۲۰۲۶-۱۰-۲۸ — نحوه‌ی رزرو، سقف نوبت مشتری و مشتری بدقول (بسته‌ی 5ه)**
- صفحه‌ی تازه‌ی مالک سالن «نحوه‌ی رزرو» (`/admin/booking-policy`): **بدون پیش‌پرداخت** (پیش‌فرض همه‌ی سالن‌ها، کل مبلغ در سالن، بی‌نیاز از درگاه) یا با پیش‌پرداخت آنلاین (فقط با امکان «پرداخت آنلاین» پلن و درگاه فعال)، با توضیح هر حالت. اگر درگاه یا امکان پلن از دست برود سالن خودکار «بدون پیش‌پرداخت» می‌شود و مالک اعلان و پیامک می‌گیرد. هر نوبت برچسب «با/بدون پیش‌پرداخت» خودش را دارد (`bookings.prepayment_required`) و لغو خودکار، دکمه‌ی پرداخت، پیامک و امتیاز به آن نگاه می‌کنند. رفتن به «بدون پیش‌پرداخت» نوبت‌های در انتظار پرداخت را «در انتظار تأیید» می‌کند و به مشتری و متخصص خبر می‌دهد.
- بدون پیش‌پرداخت: پیامک و اعلان همان لحظه‌ی ثبت؛ کد تخفیف از کل مبلغ کم می‌شود؛ امتیاز باشگاه فقط اگر مدیر روشن کند و دو عددش را خودش وارد کند (بی مقدار پیش‌فرض).
- سقف نوبت آنلاین هر مشتری: در روز (پیش‌فرض ۱، روز خود نوبت) و کل نوبت‌های آینده‌ی فعال (پیش‌فرض ۳)؛ وب، اپ، جابه‌جایی و جلسه‌ی دوره؛ رزرو دستی کادر معاف با هشدار. مشتری بدقول (پیش‌فرض ۲ «نیامد» در ۹۰ روز): با درگاه فقط با پیش‌پرداخت، بی درگاه تأیید دستی اجباری و سقف کمتر؛ برچسب «بدقول» در پنل‌ها و اپ همکار؛ برداشتن محدودیت از صفحه‌ی مشتری. «تأیید حضور با لینک» اختیاری (۲۴ ساعت پیش پیامک لینک‌دار، بی تأیید تا ۳ ساعت مانده لغو).
- دو باگ رفع شد: پیش‌پرداخت صفر نوبت را بعد از ۳۰ دقیقه با پیامک «پرداخت نکردید» لغو می‌کرد؛ متخصص با تأیید خودکار نوبت پرداخت‌نشده را برای همیشه «تأییدشده» نگه می‌داشت.
- اپ مشتری و همکار 0.3.8 (اختیاری؛ اپ‌های قبلی کار می‌کنند). سه migration تازه (`booking_policies`، ستون‌های تازه‌ی `bookings` و `users`): `php artisan migrate` یا `migrate:fresh --seed`. دو کار زمان‌بندی‌شده‌ی تازه، متغیر تازه‌ای ندارد.

**۲۰۲۶-۱۰-۲۸ — ارتقا به Laravel 13 و PHP 8.5**
- Laravel 11 (پایان وصله‌ی امنیتی ۱۲ مارس ۲۰۲۶) ← 12 ← **13** (امنیتی تا ۱۷ مارس ۲۰۲۸)؛ `composer.json`: ‏`php: ^8.3`. کتابخانه‌ها: Sanctum 4.3، Telescope 5.26، Excel **4** با PhpSpreadsheet **5** (تنها راه نصب روی PHP 8.5؛ خروجی Excel بی‌تغییر)، Activitylog 4.12، mPDF 8.3، DomPDF 3.1، Verta 9، Tinker 3، PHPUnit 12، collision 8.9؛ Guzzle روی 7 و Pint روی 1.27 ماند. `composer.lock` روی PHP 8.3 تا 8.5 نصب می‌شود.
- image داکر PHP **8.5** (تولید و تک‌کانتینری/آروان)؛ compose شش‌سرویسی **mysql:8.4** (`docker/mysql/my.cnf` برای 8.4 اصلاح شد؛ volume ‏8.0 در اولین اجرا یک‌طرفه ارتقا می‌یابد — اول پشتیبان).
- رفتارهای تازه (تصمیم‌شده): شناسه‌ی پیام‌های اپ UUID نسخه‌ی ۷ و مرتب‌سازی با شناسه؛ آپلود SVG در فیلدهای تصویر رد می‌شود.
- رفع‌های لازم ارتقا: معافیت CSRF برگشت درگاه‌ها و گزارش CSP در Laravel 13 (بدون آن بانک‌ها ۴۱۹ می‌گرفتند)؛ JSON-LD صفحه‌ی فروش با دستور تازه‌ی `@context` در Blade می‌شکست (۵۰۰)؛ هشدارهای PHP 8.5 کد خود پروژه.
- `phpunit.xml`: ‏`memory_limit=1G`، و `APP_URL` و `PUSH_FCM_CREDENTIALS` ثابت (سوییت دیگر به `.env` و `php.ini` هر سیستم وابسته نیست).
- **بعد از گرفتن پچ‌ها:** `composer install` (روی PHP 8.5)، ‏`php artisan optimize:clear` (نماهای کامپایل‌شده‌ی قدیمی خطای Blade را پنهان می‌کنند)، ‏`npm run build`، و برای Docker ‏`build-vendor` تازه و build دوباره. migration تازه‌ای ندارد؛ چون داده فیک است `migrate:fresh --seed` هم بی‌اشکال است.

**۲۰۲۶-۱۰-۲۸ — پذیرش دوباره‌ی قوانین و رضایت عکس از زبان مشتری**
- `config/legal.php` حالا برای هر نسخه‌ی قوانین، حریم خصوصی و قوانین سالن‌ها خلاصه‌ی تغییر و پرچم «مهم» دارد. فقط تغییر مهم پذیرش دوباره می‌خواهد: مشتری بعد از ورود صفحه‌ی «قوانین به‌روز شد» را می‌بیند و تا نپذیرد نوبت یا دوره‌ی تازه نمی‌گیرد (خروج، حذف حساب، نوبت‌های فعلی و شکایت باز است)؛ پنل مالک سالن تا پذیرش قوانین سالن‌ها قفل است و منشی فقط نوار «منتظر پذیرش مالک» می‌بیند. اپ‌های پیش از 0.3.7 قفل نمی‌شوند و پذیرششان ضمنی ثبت می‌شود. پذیرش‌ها مثل قبل در `legal_acceptances`.
- رضایت عکس دوره: کارمند در اپ همکار یا پنل متخصص «درخواست رضایت» می‌فرستد (پوش و پیامک لینک‌دار از سهمیه‌ی پیامک سالن)؛ مشتری متن کامل را در اپ، «دوره‌های من» یا صفحه‌ی لینک پیامک می‌بیند و خودش موافقت یا مخالفت می‌کند. ثبت حضوری کادر راه دوم است. هر درخواست و تصمیم با زمان، IP، نسخه‌ی متن و منبع در جدول تازه‌ی `course_photo_consents` می‌ماند و در پنل‌ها با برچسب («ثبت حضوری»، «لینک پیامک»…) دیده می‌شود. یک درخواست باز برای هر دوره، ارسال دوباره روزی یک بار، بعد از مخالفت ۲۴ ساعت صبر.
- رفع: صفحه‌ی دوره در پنل مدیر کاربر هر ردیف تاریخچه را جدا می‌خواند (N+1).
- اپ مشتری و همکار 0.3.7. یک migration تازه: `php artisan migrate` (یا `migrate:fresh --seed`). متغیر تازه‌ای ندارد.

**۲۰۲۶-۱۰-۲۷ — خطای 1067 در `migrate:fresh` روی XAMPP**
- روی MariaDB قدیمی‌تر از 10.10 (مثل XAMPP، `explicit_defaults_for_timestamp=OFF`) ساخت `deleted_account_archives` با «1067 Invalid default value for 'purge_after'» می‌شکست و سه ستون دیگر بی‌صدا `ON UPDATE CURRENT_TIMESTAMP` می‌گرفتند. هر پنج ستون timestamp حالا پیش‌فرض صریح دارند یا nullable‌اند، و یک تست همه‌ی migrationها را برای این الگو می‌پاید. دیتابیس لوکال: `php artisan migrate:fresh --seed`.

**۲۰۲۶-۱۰-۲۵ — ساعت کاری در گوشی**
- `/salon-signup` در عرض گوشی دیگر سرریز افقی ندارد (پیش‌تر ۵۸ پیکسل): ردیف ساعت کاری در گوشی دوخطی است. در تنظیمات سالن (پنل مدیر) و فرم سالن ابرمدیر هم ساعت‌ها در گوشی و تبلت کامل دیده می‌شوند (پیش‌تر فقط «09»).

**۲۰۲۶-۱۰-۲۵ — بسته‌ی مجوز پلتفرم (5د)**
- امنیت مستقل از CDN آروان: هدرهای امنیتی و HSTS از خود برنامه، CSP در حالت فقط گزارش، محدودیت نرخ روی کد پیامکی، کد تخفیف، نظر و کارهای مشتری؛ پیش‌فرض `TRUSTED_PROXIES` دیگر Cloudflare نیست؛ nginx با IP واقعی، لاگ دسترسی ماندگار، HTTPS روی سرور اصلی (acme.sh)، حالت `--no-cdn`، سخت‌کردن SSH، fail2ban و پایش بیرونی در `ARVAN_SERVER.md`.
- نگهداری داده‌ی شش‌ماهه (ماده‌ی ۶۶۸): IP و user agent کارهای کلیدی، بایگانی رمزنگاری‌شده‌ی حساب حذف‌شده (پیش‌تر حذف حساب ردی نمی‌گذاشت)، `retention:prune` شبانه، پشتیبان رمزنگاری‌شده با کلید عمومی age.
- صفحه‌های حریم خصوصی، قوانین و تماس با هویت حقوقی از تنظیمات پلتفرم (با برچسب «پیش‌نویس» تا بازبینی حقوق‌دان)؛ ثبت پذیرش قوانین در ثبت‌نام سالن و مشتری؛ نشان اینماد بی‌تغییر؛ `www` با 301 به دامنه‌ی اصلی؛ شکایت با مهلت ۷۲ ساعت؛ هویت ارائه‌دهنده و شرایط لغو پیش از پرداخت؛ حذف محتوای ساختگی صفحه‌ی اصلی سالن؛ متن رضایت عکس دوره و امکان پس گرفتن آن.
- احراز هویت و مجوز سالن (مرحله‌ی دستی با تأیید ابرمدیر)؛ سالن تأییدنشده عمومی دیده نمی‌شود. رابط مرحله‌ی سامانه‌ی رسمی آماده است.
- اپ مشتری 0.3.6: پذیرش قوانین در ثبت‌نام، شرایط خرید پیش از ثبت نوبت، پس گرفتن رضایت عکس؛ اپ همکار: متن رضایت برای خواندن به مشتری.
- ۳ migration تازه و تغییر چند migration سازنده: دیتابیس با `php artisan migrate:fresh --seed` از نو ساخته شود. متغیرهای تازه: `SECURITY_CSP`، `HSTS_MAX_AGE`، `RETENTION_DAYS`، `LEGAL_PAGES_REVIEWED`، `SALON_VERIFIER`، `BACKUP_AGE_RECIPIENT`.

**۲۰۲۶-۱۰-۲۵**
- بررسی شرایط مجوز پلتفرم (اینماد، قانون تجارت الکترونیکی، نگهداری داده طبق قانون جرایم رایانه‌ای، دستورالعمل سکوهای اینترنتی)؛ کمبودها و بسته‌ی کد بعدی در کارهای باز 5د. بدون تغییر کد.
- مستندات: `SCHEDULER_AND_QUEUE.md` تکراری ریشه حذف شد؛ راهنمای زیردامنه به `docs/deployment/SUBDOMAINS.md` و تست دستی درگاه‌ها به `docs/testing/MANUAL_GATEWAY_TESTING.md` رفتند؛ README به‌روز و نسخه‌ی انگلیسی (`README.en.md`) اضافه شد.

**۲۰۲۶-۱۰-۲۴**
- استقرار روی سرور ابری آروان با همان image تک‌کانتینری: ساخت روی ویندوز، `server-deploy.sh` با پشتیبان پیش از deploy و برگشت نسخه، `arvan-ips.sh` برای `TRUSTED_PROXIES` و فایروال، پشتیبان شبانه داخل کانتینر (دیتابیس، آپلودها، `app.key`)، راهنمای `ARVAN_SERVER.md`.
- `SMS_LOG_ONLY`: پیامک در هیچ محیطی واقعاً ارسال نمی‌شود (پیش از آماده شدن کاوه‌نگار). کلید خالی کاوه‌نگار دیگر فرایند PHP را نمی‌کشد.
- حالت تک‌کانتینری روی Docker Desktop آزموده شد: زیردامنه با فایل hosts، ورود مشتری، گوشی و پوش.

**۲۰۲۶-۱۰-۲۳**
- اولین build واقعی Docker و رفع خطاهایش؛ حالت تک‌کانتینری (MariaDB داخلی، بدون Redis)؛ `build-vendor` برای اینترنت کند؛ رازها و آپلودها بیرون از image؛ `make backup-db` درست شد؛ Redis `noeviction`؛ قواعد slug سالن.

**۲۰۲۶-۱۰-۲۲**
- دامنه‌ی اصلی `arisheh.ir`؛ ابزار آمادگی پوش (`push:test --repeat`، `push:live-check`)؛ حساب متخصص تازه و رزرو متخصص برای خودش؛ برنامه‌ی امروز متخصص؛ ربات کنار گذاشته شد.

**۲۰۲۶-۱۰-۱۹ تا ۱۰-۲۱**
- بسته‌ی «ح»: پیامک خوش‌آمد بعد از نتیجه‌ی پرداخت، زیردامنه‌ی لوکال، توقف موقت رزرو سالن، «سالن‌های من».
- بسته‌ی ۵ و ۶: لینک و QR سالن، اپ بدون مارکت (`/app`)، PWA آیفون.

**۲۰۲۶-۱۰-۱۴ تا ۱۰-۱۷**
- برند و صفحه‌ی فروش تازه، درخواست دمو، تنظیمات پلتفرم.
- پلن‌ها و امکانات، دوره‌های درمانی چندجلسه‌ای، کمپین بازگشت مشتری، پشتیبان‌گیری با وضعیت در پنل ابرمدیر.
- قالب‌های قابل انتخاب و چهار پلن عمومی؛ پنل‌ها روی سیستم طراحی ماهرو؛ PDFها بدون CDN.
- بسته‌ی ۴: پوش اپ‌ها (FCM، رسید، «بدون رسید ← پیامک»).

**۲۰۲۶-۱۰-۰۲ تا ۱۰-۱۳**
- اپلیکیشن‌ها: API `/api/v1` با Sanctum، API همکار و مشتری، اپ‌های Expo روی برنچ `mobile-app`، پرداخت اپ در مرورگر بیرونی؛ `/api` قدیمی حذف شد.

**۲۰۲۶-۱۰-۰۱ (ادغام دوم migrationها)**
- هفت migration اصلاحی داخل migration سازنده‌ی جدولشان رفتند. دیتابیس‌های موجود با `php artisan migrate:fresh --seed` از نو ساخته شوند؛ `migrate` معمولی کافی نیست.

**۲۰۲۶-۱۰-۰۱**
- سهمیه‌ی پیامک بر حسب قطعه، بسته‌ی پیامک پیش‌پرداخت، متن کوتاه پیامک‌ها با نام کوتاه و لینک کوتاه؛ ۱۰ متخصص در قیمت پلن؛ قفل‌های هم‌زمانی و idempotency فرم‌ها؛ جلوگیری از نوبت هم‌پوشان؛ حالت سخت‌گیر `BelongsToSalon`.

**۲۰۲۶-۰۹-۳۰**
- صف‌های جدا (`otp`، `sms`، `payments`، `default`، `reports`)؛ همه‌ی پیامک‌های سالن از سهمیه‌ی همان سالن، کدهای تأیید خرج پلتفرم؛ تأیید شبا با رقم کنترلی و تسویه‌ی خودکار فقط به شبای تأییدشده؛ اعلان‌ها مختص سالن؛ «نیاز به بررسی» با اعلان و پیامک به مالک؛ ثبت‌نام عمومی تعداد متخصص را می‌پرسد؛ ادغام اول migrationها.

---

## 📚 مستندات بیشتر

| فایل | محتوا |
|---|---|
| [`Rasta unified prompt.md`](Rasta%20unified%20prompt.md) | سند اصلی توسعه: تاریخچه، تصمیم‌ها، درس‌ها، قدم‌های باز |
| [`docs/deployment/ARVAN_SERVER.md`](docs/deployment/ARVAN_SERVER.md) | سرور ابری آروان: خرید، DNS، HTTPS، فایروال، پشتیبان، نسخه‌ی تازه |
| [`docs/deployment/DOCKER_DESKTOP.md`](docs/deployment/DOCKER_DESKTOP.md) | اجرای تک‌کانتینری روی Docker Desktop ویندوز |
| [`docs/deployment/SUBDOMAINS.md`](docs/deployment/SUBDOMAINS.md) | زیردامنه‌ی سالن‌ها: تنظیمات، سازوکار، تصمیم کوکی |
| [`docs/deployment/SCHEDULER_AND_QUEUE.md`](docs/deployment/SCHEDULER_AND_QUEUE.md) | زمان‌بندی و صف در همه‌ی روش‌ها |
| [`docs/deployment/BACKUP.md`](docs/deployment/BACKUP.md) | پشتیبان‌گیری و بازگردانی |
| [`docs/testing/MANUAL_GATEWAY_TESTING.md`](docs/testing/MANUAL_GATEWAY_TESTING.md) | تست دستی زیبال، وندار و آسان‌پرداخت |

---

## 🤝 مشارکت

این پروژه به‌صورت شخصی-حرفه‌ای توسعه داده می‌شود. روال توسعه: برنچ `develop`، پیام commitها انگلیسی، هر تغییر در commit جدا، تست روی SQLite، MariaDB و MySQL. برای گزارش باگ یا پیشنهاد، Issue باز کنید.

---

<div align="center">
  ساخته شده با ❤️ در ایران
</div>
