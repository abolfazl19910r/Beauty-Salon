# ماهرو روی Docker Desktop (ویندوز) — همه‌چیز در یک کانتینر

این راهنما ماهرو را روی سیستم خودتان با **یک کانتینر** بالا می‌آورد. داخل همان کانتینر، با supervisord، این‌ها اجرا می‌شوند:
MariaDB، PHP-FPM، Nginx، worker همه‌ی صف‌ها، worker اختصاصی کد تأیید و scheduler.

همه‌ی داده‌ها روی volume ‏`mahru_data` می‌مانند: دیتابیس، آپلودها، لاگ‌ها و کلید `APP_KEY`. با `down` و دوباره `up` از بین نمی‌روند.

> سرور واقعی با چند سالن: همان `docker-compose.yml` شش‌سرویسی (MySQL و Redis جدا) یا همین حالت تک‌کانتینری روی یک سرور کوچک.
> هر دو از یک `Dockerfile` ساخته می‌شوند (target ‏`production` و `single`).

---

## ۱. نصب Docker Desktop

1. Docker Desktop for Windows را نصب کنید. در نصب گزینه‌ی **Use WSL 2** روشن باشد.
2. بعد از نصب یک بار سیستم را ری‌استارت کنید و Docker Desktop را باز کنید تا آیکونش سبز شود.
3. در `cmd` این را بزنید؛ باید نسخه چاپ شود:
   ```
   docker version
   ```

### دسترسی به Docker Hub از ایران

اگر `build` با خطای `403` یا `timeout` روی `registry-1.docker.io` شکست خورد، یک آینه‌ی رجیستری بگذارید:
Docker Desktop ← Settings ← **Docker Engine**، در JSON این کلید را اضافه کنید و **Apply & restart**:

```json
"registry-mirrors": ["https://docker.arvancloud.ir"]
```

(یا `https://mirror.gcr.io`؛ اگر هیچ‌کدام باز نشد، فقط برای همین build از VPN استفاده کنید.)

build به npm، Packagist/GitHub (کامپوزر) و مخزن Alpine هم وصل می‌شود؛ بار اول ۵ تا ۱۵ دقیقه طول می‌کشد. buildهای بعدی از cache
استفاده می‌کنند و سریع‌ترند.

### اینترنت کند داخل داکر: پکیج‌های کامپوزر را روی خود ویندوز آماده کنید (پیشنهادی)

اولین build روی سیستم ابوالفضل (۲۰۲۶-۱۰-۲۳) در مرحله‌ی کامپوزر شکست خورد: دانلود از GitHub داخل VM داکر چند کیلوبایت بر ثانیه بود
(کامپوزر ویندوز cache و VPN خودش را دارد، داخل داکر ندارد). راه: یک بار پکیج‌ها را **بدون پکیج‌های dev** در پوشه‌ی جدای
`build-vendor` (نه `vendor` خود XAMPP) نصب کنید؛ build آن را برمی‌دارد و دیگر چیزی از GitHub دانلود نمی‌کند.

در `cmd`، ریشه‌ی پروژه — سه خط جدا:

```
set COMPOSER_VENDOR_DIR=build-vendor
composer install --no-dev --no-scripts --no-interaction --ignore-platform-reqs
set COMPOSER_VENDOR_DIR=
```

- `vendor` و `composer.lock` پروژه دست نمی‌خورند؛ XAMPP مثل قبل کار می‌کند. `build-vendor` در git نمی‌رود.
- **بعد از هر پچی که `composer.lock` را عوض کند** این سه خط را دوباره بزنید (وگرنه image پکیج‌های قدیمی را می‌گیرد).
- بدون `build-vendor`، build خودش `composer install` می‌زند؛ cache کامپوزر بین buildها می‌ماند، پس اگر وسط کار قطع شد، دوباره
  اجرا کنید تا از همان‌جا ادامه دهد.

---

## ۲. تنظیمات

در پوشه‌ی پروژه (`C:\xampp\htdocs\Beauty-Salon`):

```
copy .env.single.example .env.single
```

`.env.single` در git نمی‌رود. پیش‌فرض‌هایش برای آزمون لوکال است:

| کلید | پیش‌فرض | توضیح |
|---|---|---|
| `APP_ENV` | `local` | پیامک واقعی نمی‌رود؛ کد ورود در لاگ ثبت می‌شود (بخش ۵) |
| `APP_URL` | `http://arisheh.test:8080` | از گوشی: `http://192.168.1.2:8080` (بخش ۷) |
| `CENTRAL_DOMAIN` | `arisheh.test` | ساب‌دامین سالن‌ها: `{slug}.arisheh.test` (بخش ۴) |
| `APP_KEY`، `DB_PASSWORD` | (در فایل نیستند) | کانتینر بار اول می‌سازد و در `/data` نگه می‌دارد؛ خالی ننویسیدشان |
| `MAHRU_SEED` | `true` | بار اول داده‌ی نمونه (سالن «راستا»، متخصص‌ها، خدمات، حساب‌های آزمایشی) |

پورت پیش‌فرض **8080** است (پورت ۸۰ دست Apache زمپ است). برای پورت دیگر، قبل از `up` در همان `cmd`:
`set MAHRU_PORT=8081`.

---

## ۳. بالا آوردن

```
docker compose -f docker-compose.single.yml up -d --build
```

وضعیت و لاگ زنده (تا ببینید migrate تمام شد و سرویس‌ها بالا آمدند):

```
docker compose -f docker-compose.single.yml ps
docker compose -f docker-compose.single.yml logs -f
```

در `ps` ستون STATUS بعد از حدود یک دقیقه باید `healthy` شود. بعد در مرورگر:

- صفحه‌ی فروش: `http://localhost:8080`
- سالن نمونه با مسیر: `http://localhost:8080/s/rasta`
- سلامت: `http://localhost:8080/up`

---

## ۴. ساب‌دامین سالن‌ها (فایل hosts ویندوز)

فایل hosts ویندوز آدرس wildcard (`*.arisheh.test`) را نمی‌شناسد، پس **برای هر سالن یک خط** اضافه کنید.

**مسیر فایل:**

```
C:\Windows\System32\drivers\etc\hosts
```

**ویرایش:** منوی Start ← «Notepad» را جست‌وجو کنید ← راست‌کلیک ← **Run as administrator** ← File ← Open ← مسیر بالا را بدهید
(در پنجره‌ی Open نوع فایل را روی **All Files** بگذارید تا `hosts` دیده شود).

این خطوط را به آخر فایل اضافه و ذخیره کنید:

```
127.0.0.1 arisheh.test
127.0.0.1 rasta.arisheh.test
```

برای هر سالن تازه که می‌سازید (مثلاً با slug ‏`almas`) یک خط دیگر:

```
127.0.0.1 almas.arisheh.test
```

بعد در مرورگر:

- دامنه‌ی اصلی: `http://arisheh.test:8080`
- سالن راستا: `http://rasta.arisheh.test:8080`

اگر بعد از ذخیره باز نشد:

- برنامه‌ی VPN/پروکسی (مثل FlClash با System Proxy یا TUN) درخواست `arisheh.test` را به پروکسی می‌فرستد و فایل hosts دیده
  نمی‌شود. موقع آزمون خاموشش کنید، یا `*.test`، `localhost` و `127.0.0.1` را در Bypass آن بگذارید. (آزموده‌ی ابوالفضل ۲۰۲۶-۱۰-۲۴: با
  FlClash روشن باز نشد، با خاموش کردنش باز شد.)
- `ipconfig /flushdns` در `cmd` **با Run as administrator** (بدون آن: «The requested operation requires elevation»).

> چرا `.test`؟ این پسوند برای آزمون رزرو شده و هیچ‌وقت روی اینترنت واقعی وجود ندارد؛ با دامنه‌ی واقعی `arisheh.ir` قاطی نمی‌شود.
> مسیر `/s/{slug}` هم‌زمان کار می‌کند.

---

## ۵. ورود و کد پیامکی

با `MAHRU_SEED=true` حساب‌های نمونه‌ی seeder ساخته می‌شوند (همان‌هایی که روی XAMPP دارید؛ `database/seeders/UserSeeder.php`).
یا سوپرادمین تازه بسازید:

```
docker compose -f docker-compose.single.yml exec -u www app php artisan superadmin:create
```

ورود: شماره + رمز، بعد **کد پیامکی**. در `APP_ENV=local` پیامک نمی‌رود و متنش در لاگ است:

```
docker compose -f docker-compose.single.yml exec app sh -c "tail -n 50 storage/logs/laravel-*.log"
```

دنبال خط `SMS: در حال ارسال` بگردید.

---

## ۶. پوش (فایربیس)

فایل حساب سرویس فایربیس را داخل volume کپی کنید (یک بار؛ با `down`/`up` می‌ماند):

```
docker compose -f docker-compose.single.yml cp storage\app\private\mahru-fcm-sa.json app:/data/storage/app/private/mahru-fcm-sa.json
docker compose -f docker-compose.single.yml exec app chown www:www /data/storage/app/private/mahru-fcm-sa.json
docker compose -f docker-compose.single.yml restart
```

`.env.single` از قبل `PUSH_FCM_CREDENTIALS=storage/app/private/mahru-fcm-sa.json` دارد. آزمون:

```
docker compose -f docker-compose.single.yml exec -u www app php artisan push:test 09xxxxxxxxx --wait=30
```

---

## ۷. آزمون با گوشی

1. در `.env.single`: `APP_URL=http://192.168.1.2:8080` (IP سیستم شما؛ با `ipconfig` ببینید).
2. `docker compose -f docker-compose.single.yml up -d` (تغییر `.env.single` با همین دستور اعمال می‌شود؛ `restart` کافی نیست).
3. در اپ (فیلد مخفی آدرس سرور در صفحه‌ی ورود): `http://192.168.1.2:8080`.
4. اگر گوشی وصل نشد: فایروال ویندوز ← اجازه‌ی ورودی برای پورت 8080 (Docker Desktop معمولاً خودش می‌پرسد).

گوشی فایل hosts سیستم شما را ندارد، پس از گوشی سالن‌ها با `/s/{slug}` باز می‌شوند، نه ساب‌دامین.

---

## ۸. دستورهای روزمره

همه از پوشه‌ی پروژه؛ `-f docker-compose.single.yml` لازم است:

| کار | دستور |
|---|---|
| خاموش کردن (داده می‌ماند) | `docker compose -f docker-compose.single.yml down` |
| روشن کردن | `docker compose -f docker-compose.single.yml up -d` |
| بعد از اعمال پچ تازه | `docker compose -f docker-compose.single.yml up -d --build` |
| وضعیت سرویس‌های داخل کانتینر | `docker compose -f docker-compose.single.yml exec app supervisorctl status` |
| artisan | `docker compose -f docker-compose.single.yml exec -u www app php artisan …` |
| شل دیتابیس | `docker compose -f docker-compose.single.yml exec app mariadb mahru` |
| بکاپ دیتابیس | `docker compose -f docker-compose.single.yml exec -T app mariadb-dump --single-transaction mahru > backup.sql` |
| پاک کردن همه‌چیز و شروع از صفر | `docker compose -f docker-compose.single.yml down -v` ⚠️ دیتابیس و آپلودها پاک می‌شوند |

`supervisorctl status` باید هر شش برنامه را `RUNNING` نشان دهد: mariadb، php-fpm، nginx، queue، queue-otp، scheduler.

---

## ۹. عیب‌یابی

| نشانه | علت و راه |
|---|---|
| `exec /usr/local/bin/entrypoint-single.sh: no such file or directory` | پایان خط CRLF؛ image تازه این را خودش درست می‌کند — با `--build` دوباره بسازید |
| `port is already allocated` | پورت 8080 گرفته است: `set MAHRU_PORT=8081` و دوباره `up` |
| صفحه‌ی ۵۰۰ | `docker compose -f docker-compose.single.yml logs --tail=100` و `storage/logs/laravel-*.log` (بخش ۵) |
| ساب‌دامین صفحه‌ی فروش را نشان می‌دهد | خط آن سالن در hosts نیست، یا `CENTRAL_DOMAIN` با دامنه‌ی hosts یکی نیست |
| ۴۱۹ Page Expired روی ساب‌دامین | `SESSION_DOMAIN=.arisheh.test` (با نقطه‌ی اول) و آدرس را از `arisheh.test` باز کنید، نه `localhost` |
| build در مرحله‌ی کامپوزر یا npm گیر می‌کند | اینترنت/آینه (بخش ۱)؛ دوباره اجرا کنید — مراحل تمام‌شده از cache می‌آیند |
| کامپوزر: `Failed to download … from dist` و بعد clone کند، یا `exceeded the timeout` | اینترنت کند داخل داکر یا سقف درخواست بی‌نام GitHub — `build-vendor` (بخش ۱) |
| دانلود imageهای پایه (php، node، composer) ده‌ها دقیقه | آینه‌ی رجیستری (بخش ۱)؛ فقط بار اول است |
| `docker compose exec … artisan` خطای `Access denied for user 'mahru'` | در `.env.single` خطی مثل `DB_PASSWORD=` یا `APP_KEY=` خالی هست؛ پاکش کنید و `up -d` |
