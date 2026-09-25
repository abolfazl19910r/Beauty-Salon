# راهنمای راه‌اندازی زمان‌بندی (Cron) و صف (Queue) — ماهرو

> **خلاصه:** سرور فقط **یک خط کرون** لازم دارد. روی DirectAdmin همان خط، صف را هم اجرا می‌کند
> (`QUEUE_WORK_VIA_SCHEDULER=true`). روی Docker همه‌چیز داخل `docker-compose.yml` است.

## ۱. چه چیزهایی باید خودکار اجرا شوند و چرا

### کارهای زمان‌بندی‌شده (نیاز به کرون)

فهرست دقیق روی سرور: `php artisan schedule:list`. این کارها در **دو** فایل تعریف شده‌اند:
`bootstrap/app.php` و `routes/console.php`.

| کار | زمان | اگر اجرا نشود |
|---|---|---|
| `payments:reconcile` | هر ۵ دقیقه | پول مشتری‌ای که پاسخ تایید سامانش گم شده، **برنمی‌گردد**؛ تراکنش‌های رهاشده `pending` می‌مانند |
| `cancel-unpaid-bookings` | هر ۵ دقیقه | ساعت‌های نوبت‌های پرداخت‌نشده برای همیشه اشغال می‌مانند |
| `bookings:send-reminders` | هر ۱۰ دقیقه | پیامک یادآوری نوبت ارسال نمی‌شود |
| `wallet:settle-pending` | هر شب ۰۱:۰۰ | درآمد متخصص‌ها از «در انتظار» به «قابل برداشت» نمی‌رود |
| پاک‌سازی‌های روزانه (گزارش‌ها، توکن‌ها، ...) | روزانه | فضای دیسک و جدول‌ها بزرگ می‌شوند |

### کارهای صف‌دار (نیاز به worker)

وقتی `QUEUE_CONNECTION` برابر `sync` نیست، این‌ها فقط با یک **queue worker** اجرا می‌شوند:

- همه‌ی پیامک‌ها: کد ورود، تایید نوبت، لغو، خوش‌آمد سالن، و **پیامک «پول شما برگشت داده شد»**
- `CancelUnpaidBookings`، که خودش از داخل scheduler وارد صف می‌شود

> ⚠️ بدون worker، کرون هم کار را کامل نمی‌کند: لغو نوبت‌ها وارد صف می‌شود ولی هیچ‌وقت اجرا نمی‌شود.

## ۲. کدام روش مال شماست؟

| سرور | کرون | صف | بخش |
|---|---|---|---|
| **DirectAdmin (هاست اشتراکی)** — روش فعلی | ۱ خط در پنل | همان کرون (`QUEUE_WORK_VIA_SCHEDULER=true`) | ۳ |
| VPS با دسترسی root، بدون Docker | ۱ خط crontab | supervisor | ۴ |
| Docker | خودکار (سرویس `scheduler`) | خودکار (سرویس `queue`) | ۵ |
| لوکال (XAMPP) | `schedule:work` در یک ترمینال | `queue:work` در ترمینال دیگر | ۶ |

## ۳. DirectAdmin (روش فعلی)

### قدم ۱ — تنظیم `.env` روی سرور

```dotenv
APP_ENV=production
QUEUE_CONNECTION=database
QUEUE_WORK_VIA_SCHEDULER=true
CACHE_STORE=database
```

`CACHE_STORE=file` هم قابل قبول است. **هرگز `array` نگذارید.** با `array` هر پروسه حافظه‌ی جدا دارد، پس:

- قفل‌های `withoutOverlapping` زمان‌بندی کار نمی‌کنند.
- محدودیت تلاش ورود (`throttle:auth`) عملاً خاموش است.

`.env.example` و `.env` فعلی لوکال `array` دارند. برای لوکال اشکالی ندارد، برای سرور ندارد.

### قدم ۲ — جدول‌ها و cache تنظیمات

```bash
cd /home/[username]/public_html
php artisan migrate --force        # جدول‌های jobs / failed_jobs / cache را هم می‌سازد (اگر نیستند)
php artisan config:cache           # بعد از هر تغییر .env لازم است
```

### قدم ۳ — خط کرون (فقط یک بار)

در پنل DirectAdmin: **Advanced Features ← Cron Jobs**.

- زمان: `*` `*` `*` `*` `*` (هر دقیقه)
- دستور:

```bash
cd /home/[username]/public_html && php artisan schedule:run >> /dev/null 2>&1
```

- اگر `php` پیدا نشد، مسیر کامل را بگذارید. آن را با `which php` در SSH پیدا کنید؛ معمولاً `/usr/local/bin/php` یا `/usr/local/php83/bin/php` است.
- نسخه‌ی آماده‌ی همین خط در `deploy/cron/mahru.cron` است.

### قدم ۴ — بررسی (بخش ۷)

## ۴. VPS با supervisor (بدون Docker)

- `.env` مثل بخش ۳ است، با یک تفاوت: **`QUEUE_WORK_VIA_SCHEDULER=false`**. worker دائمی داریم.
- کرون: همان یک خط، با `crontab -e` برای کاربر سایت (نه root).
- worker:

```bash
sudo cp deploy/supervisor/mahru-worker.conf /etc/supervisor/conf.d/
# [username] و مسیرهای داخل فایل را اصلاح کنید
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start mahru-worker:*
```

- بعد از هر deploy: `php artisan queue:restart`. worker کد قدیمی را در حافظه دارد تا ری‌استارت شود.

## ۵. Docker

```bash
make setup     # .env را از .env.docker می‌سازد، APP_KEY تولید می‌کند، build و up
make logs-scheduler
make logs-queue
docker compose ps    # app باید healthy باشد؛ queue و scheduler بعد از آن بالا می‌آیند
```

### چه چیزی ۲۰۲۶-۰۹-۲۶ اصلاح شد

قبل از این، در Docker نه زمان‌بندی اجرا می‌شد و نه صف. دلایلش:

1. `.dockerignore` کل پوشه‌ی `docker/` را کنار می‌گذاشت، در حالی که Dockerfile از آن `COPY` می‌کرد. build از همان اول شکست می‌خورد.
2. healthcheck سرویس `app` دستوری را صدا می‌زد که وجود ندارد (`health:check`). `app` هیچ‌وقت healthy نمی‌شد، و `queue` و `scheduler` که منتظرش بودند اصلاً بالا نمی‌آمدند.
3. `docker/entrypoint.sh` دستور هر container را نادیده می‌گرفت و همیشه وب‌سرور را اجرا می‌کرد. `queue:work` و `schedule:run` هیچ‌وقت اجرا نمی‌شدند و هر سه container هم‌زمان migrate می‌زدند.
4. حلقه‌ی scheduler (`schedule:run && sleep 60`) با اولین خطا بی‌وقفه تکرار می‌شد.
5. worker و scheduler فهرست کوتاه‌تری از تنظیمات داشتند؛ مثلاً قالب‌های پیامک به worker نمی‌رسید.

### الان این‌طور کار می‌کند

- **نقش‌ها:** `entrypoint.sh` دو نقش دارد. بدون آرگومان (`app`) migrate و بعد وب‌سرور. با آرگومان (`queue` و `scheduler`) فقط همان دستور، با کاربر `www`.
- **تنظیمات مشترک:** هر سه container از یک `.env` (`env_file`) و یک مجموعه override شبکه‌ای می‌خوانند.
- **scheduler:** `php artisan schedule:work` اجرا می‌کند.

⚠️ **محدودیت بررسی:** Docker در محیط توسعه‌ی Claude در دسترس نبود، پس build واقعی image انجام نشد. `entrypoint.sh` در هر دو نقش واقعاً اجرا و بررسی شد: با MariaDB، کاربر `www` و `su-exec`. `docker-compose.yml` هم با یک parser بررسی شد.

### وب‌سرور و build (اصلاح دوم، ۲۰۲۶-۰۹-۲۶)

حتی بعد از اصلاح بالا، stack داکر هیچ‌وقت یک صفحه سرو نمی‌کرد و `app` هرگز healthy نمی‌شد (پس queue و scheduler هم بالا نمی‌آمدند):

1. **build:** مرحله‌ی composer (`composer:2.7`) افزونه‌ی `gd` ندارد و `mpdf` آن را می‌خواهد → `docker build` در مرحله‌ی ۲ شکست می‌خورد. حالا آن مرحله `--ignore-platform-req='ext-*'` می‌گیرد و image نهایی با `composer check-platform-reqs` واقعاً چک می‌شود.
2. **boot:** `laravel/telescope` در require-dev است ولی provider آن بی‌قید ثبت بود؛ با `--no-dev` برنامه اصلاً boot نمی‌شد (حتی `migrate` در entrypoint). حالا فقط وقتی پکیج نصب است ثبت می‌شود. ⚠️ این برای هر سرور production با `composer install --no-dev` هم صادق بود، نه فقط Docker.
3. **php-fpm:** image رسمی `www.conf` و `zz-docker.conf` دارد که pool ما را override می‌کردند (`listen = 9000`، `user = www-data`) → سوکتی که nginx می‌خواهد ساخته نمی‌شد. فایل ما حالا `zzz-beauty-salon.conf` است (آخر خوانده می‌شود).
4. **nginx داخل app:** vhost در `http.d/` کپی می‌شد ولی `nginx.conf` ما `conf.d/` را include می‌کند → هیچ server blockی، هیچ پورتی. کاربر `nginx` هم به گروه `www` اضافه شد تا به سوکت (0660) دسترسی داشته باشد.
5. **سلامت:** `/up` قبلاً یک «200 OK» ثابت nginx بود؛ حالا به route سلامت Laravel می‌رسد (بدون php-fpm → 502 → unhealthy).
6. **سرویس جداگانه‌ی `nginx` حذف شد:** volume خالی `beauty_public` را سرو می‌کرد و به سوکت php-fpm دسترسی نداشت. حالا خود `app` پورت `${APP_PORT:-80}` را منتشر می‌کند. HTTPS بیرون از stack: Cloudflare Proxied یا nginx/certbot روی سرور (`WILDCARD_SUBDOMAIN_DEPLOYMENT.md`).
7. **امنیت:** پورت‌های MySQL، Redis (بدون رمز پیش‌فرض) و phpMyAdmin فقط روی `127.0.0.1` سرور (Docker قوانین ufw را دور می‌زند؛ قبلاً به کل اینترنت باز بودند).
8. **`.dockerignore`:** `vendor/`، `public/build/`، `public/storage` و `bootstrap/cache/*.php` محلی دیگر روی خروجی build نمی‌نشینند (کش محلی providerهای dev را ثبت کرده و با `--no-dev` برنامه را می‌خواباند).
9. **Makefile:** دستورهای artisan با کاربر `www` (با root، `laravel.log` مال root می‌شد و php-fpm دیگر نمی‌توانست بنویسد)؛ `make setup` تا healthy شدن صبر می‌کند؛ `make superadmin` برای ساخت اولین سوپر ادمین.

⚠️ **محدودیت بررسی:** image واقعی هنوز ساخته نشده — در محیط Claude خود Docker نصب شد ولی registryها (Docker Hub و آینه‌ها) قابل دسترس نبودند. به‌جایش همون container در نقش web به‌صورت native شبیه‌سازی شد: nginx + php-fpm + supervisord با **همین فایل‌های پروژه** و چیدمان pool رسمی image. قبل از اصلاح: هیچ پورتی باز نبود. بعد: `/up`، صفحه‌ی اصلی، `/login`، assetهای Vite و فایل‌های آپلودی ۲۰۰؛ `/.env` ۴۰۴؛ `/up` بدون php-fpm ۵۰۲. مرحله‌ی composer با افزونه‌های image `composer` قبل/بعد اجرا شد، و همه‌ی قدم‌های entrypoint (migrate، cacheها، superadmin) روی نصب `--no-dev` با `APP_ENV=production`.

**اولین اجرای واقعی روی سرور:**
```bash
make setup          # build + up + صبر تا healthy
make superadmin     # شماره، نام و رمز سوپر ادمین پرسیده می‌شود
make status && make logs-scheduler
```
اگر `make setup` خطا داد، `docker compose logs app` خروجی لازم را دارد.

## ۶. لوکال (XAMPP)

دو ترمینال در پوشه‌ی پروژه باز کنید:

```bash
php artisan schedule:work
php artisan queue:work
```

یا `composer dev`، که سرور و `queue:listen` و vite را با هم اجرا می‌کند؛ `schedule:work` را جدا بزنید.
`QUEUE_WORK_VIA_SCHEDULER` در لوکال `false` بماند.

## ۷. بررسی اینکه واقعاً کار می‌کند

```bash
php artisan schedule:list                  # باید payments:reconcile، cancel-unpaid-bookings و ... را نشان دهد
                                           # روی DirectAdmin: queue:work --stop-when-empty هم در فهرست باشد
php artisan schedule:run                   # یک بار دستی؛ خطایی نباید بدهد
php artisan tinker --execute="echo \\Illuminate\\Support\\Facades\\DB::table('jobs')->count();"   # چند دقیقه بعد از کرون باید ۰ یا نزدیک ۰ باشد
php artisan queue:failed                   # کارهای شکست‌خورده؛ باید خالی باشد
tail -n 50 storage/logs/laravel.log        # خطاهای scheduler/queue
```

یک آزمون ساده: یک نوبت بسازید و پرداخت نکنید. حدود ۳۵ دقیقه بعد باید لغو شده باشد و پیامک لغو برسد. این یعنی هم کرون و هم صف کار می‌کنند.

## ۸. بعد از هر deploy

```bash
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart     # روی VPS/Docker؛ روی DirectAdmin لازم نیست (worker هر دقیقه تازه اجرا می‌شود)
```

## ۹. عیب‌یابی

| نشانه | علت محتمل |
|---|---|
| هیچ پیامکی نمی‌رسد ولی سایت کار می‌کند | worker اجرا نمی‌شود (`jobs` پر است): `QUEUE_WORK_VIA_SCHEDULER` یا supervisor را چک کنید |
| `schedule:list` چیزی از `queue:work` نشان نمی‌دهد (DirectAdmin) | `QUEUE_WORK_VIA_SCHEDULER=true` نیست، یا بعد از تغییر `.env` دستور `config:cache` زده نشده |
| کرون هست ولی هیچ کاری اجرا نمی‌شود | مسیر `php` یا پوشه‌ی پروژه در خط کرون اشتباه است؛ خروجی را موقتاً به فایل بفرستید: `>> storage/logs/cron.log 2>&1` |
| `proc_open` / `exec` disabled | هاست اجرای پروسه‌ی فرزند را بسته است؛ `schedule:run` بدون آن کار نمی‌کند. از پشتیبانی هاست فعال‌سازی `proc_open` را برای PHP CLI بخواهید |
| کارها دو بار اجرا می‌شوند | `CACHE_STORE=array` است؛ `database` یا `file` بگذارید |

---

## ۱۰. پرامپت آماده (برای یک چت جدید یا پشتیبان فنی)

```
پروژه‌ی من «ماهرو» یک اپ Laravel 11 است (مخزن abolfazl19910r/Beauty-Salon، برنچ develop).
می‌خواهم زمان‌بندی (Cron) و صف (Queue) را روی سرور راه‌اندازی کنم، طبق فایل
docs/deployment/SCHEDULER_AND_QUEUE.md همین مخزن.

نوع سرور من: [DirectAdmin هاست اشتراکی / VPS با root / Docker]
نسخه‌ی PHP روی سرور: [خروجی php -v]
مسیر پروژه روی سرور: [مثلاً /home/username/public_html]

قدم‌به‌قدم کمکم کن:
1. مقادیر لازم در .env (QUEUE_CONNECTION، QUEUE_WORK_VIA_SCHEDULER، CACHE_STORE) را برای نوع سرور من بگو.
2. دقیقاً چه خط کرونی (و اگر لازم است چه تنظیم supervisor) بگذارم.
3. با دستورهای بخش «بررسی» مطمئن شویم کار می‌کند. من خروجی هر دستور را برایت می‌فرستم.
4. اگر خطایی بود، با جدول عیب‌یابی همان فایل رفعش کنیم.

خروجی این دستورها روی سرور:
- php artisan schedule:list
- php artisan queue:failed
- tail -n 30 storage/logs/laravel.log
```
