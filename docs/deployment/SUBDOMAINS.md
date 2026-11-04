# زیردامنه‌ی سالن‌ها (`{slug}.arisheh.ir`)

هر سالن از دو آدرس در دسترس است، هم‌زمان:

| آدرس | کی فعال است |
|---|---|
| `https://arisheh.ir/s/{slug}` | همیشه (لینک‌های قدیمی هرگز نمی‌شکنند) |
| `https://{slug}.arisheh.ir` | فقط وقتی `CENTRAL_DOMAIN` تنظیم شده باشد |

دامنه‌ی اصلی بدون زیردامنه (`arisheh.ir`) صفحه‌ی فروش پلتفرم را نشان می‌دهد (`CentralLandingController`).

> راه‌اندازی سرور، DNS و گواهی: [`ARVAN_SERVER.md`](ARVAN_SERVER.md) (بخش ۷ و ۸) · آزمون لوکال: [`DOCKER_DESKTOP.md`](DOCKER_DESKTOP.md) (بخش ۴).
> این فایل فقط سازوکار و تصمیم‌ها را نگه می‌دارد. (پیش‌تر `WILDCARD_SUBDOMAIN_DEPLOYMENT.md` در ریشه‌ی پروژه بود؛ بخش‌های nip.io،
> Cloudflare و certbot دستی آن کنار گذاشته شد: nip.io در ایران باز نمی‌شود و HTTPS حالا در لبه‌ی CDN آروان است.)

---

## ۱. تنظیمات

```env
APP_URL=https://arisheh.ir
CENTRAL_DOMAIN=arisheh.ir      # خالی = فقط /s/{slug}
SESSION_SECURE_COOKIE=true     # روی HTTPS (نام کوکی session خودکار «__Host-…» می‌شود)
```

- **سرور آروان:** `.env.server` (نمونه: `.env.server.example`).
- **Docker Desktop لوکال:** `.env.single` با `CENTRAL_DOMAIN=arisheh.test`. (`SESSION_DOMAIN` از ۲۰۲۶-۱۱-۰۵ خوانده نمی‌شود؛ خط قدیمی‌اش بی‌اثر است.)
- **XAMPP:** `.env` همین پروژه.

⚠️ `CENTRAL_DOMAIN` هنگام بالا آمدن برنامه خوانده می‌شود و در `route:cache` منجمد می‌شود. بعد از هر تغییرش:
`php artisan route:clear` (یا `route:cache` دوباره). در Docker، `up -d` کانتینر را دوباره می‌سازد و کش‌ها را از نو می‌سازد.

---

## ۲. DNS و HTTPS (خلاصه)

- رکوردهای `A` برای `@`، `www` و **`*`** به IP سرور، با پروکسی CDN روشن.
- گواهی باید `*.arisheh.ir` را هم پوشش دهد. اگر CDN گواهی wildcard نداد، `CENTRAL_DOMAIN` را خالی کنید تا آدرس
  سالن‌ها فقط `/s/{slug}` باشد (برنامه لینک‌ها و پیامک‌ها را همان‌طور می‌سازد).
- CDN باید **Host اصلی درخواست** را به سرور بفرستد؛ وگرنه همه‌ی زیردامنه‌ها صفحه‌ی فروش را نشان می‌دهند.
- جزئیات گام‌به‌گام: [`ARVAN_SERVER.md`](ARVAN_SERVER.md).

### لوکال (Docker Desktop یا XAMPP)

فایل hosts ویندوز wildcard نمی‌شناسد: برای هر سالن یک خط (`127.0.0.1 rasta.arisheh.test`). پسوند `.test` برای آزمون رزرو شده و با
دامنه‌ی واقعی قاطی نمی‌شود. برنامه‌های VPN/پروکسی سیستم (مثل FlClash) فایل hosts را دور می‌زنند؛ موقع آزمون خاموش یا Bypass. گوشی
فایل hosts ندارد، پس از گوشی سالن‌ها با `/s/{slug}` باز می‌شوند. جزئیات: [`DOCKER_DESKTOP.md`](DOCKER_DESKTOP.md) بخش ۴.

---

## ۳. سازوکار در کد

- `routes/web.php`: یک گروه مسیر سالن (`$tenantRoutes`) دو بار ثبت می‌شود: با پیشوند `s/{salon_slug}` و، اگر `CENTRAL_DOMAIN` پر است،
  با `Route::domain('{salon_slug}.'.CENTRAL_DOMAIN)`. پارامتر `salon_slug` در هر دو حالت پر می‌شود، پس همان `ResolveSalonFromRoute`
  سالن را پیدا می‌کند.
- **نام‌های مسیر:** وقتی زیردامنه فعال است، نسخه‌ی `/s/{slug}` نام‌هایش را با پیشوند `legacy.` می‌گیرد؛ `route('home')` و بقیه به
  زیردامنه اشاره می‌کنند. (بدون این، `route:cache` با `Another route has already been assigned name` می‌شکست — رفع ۲۰۲۶-۰۹-۲۳.)
- `RedirectLegacySalonPathToSubdomain` (M5، ۲۰۲۶-۱۱-۰۵): با `CENTRAL_DOMAIN` پر، `GET` روی `arisheh.ir/s/{slug}/…` با ۳۰۲ به `{slug}.arisheh.ir/…`
  می‌رود (مسیر، query و پورت حفظ؛ slug ناموجود همان ۴۰۴؛ POST دست نمی‌خورد؛ روی `127.0.0.1` انتقالی نیست).
- `MatchSessionCookieDomainToHost` (از قبل؛ با domain همیشه null دیگر کاری نمی‌کند): اگر میزبان درخواست زیرمجموعه‌ی `SESSION_DOMAIN` نباشد (مثلاً `127.0.0.1`)، دامنه‌ی کوکی را برای همان
  درخواست خالی می‌کند تا ورود نشکند.
- قواعد slug (`App\Support\SalonSubdomain`): فقط `[a-z0-9-]`، اول و آخر حرف یا عدد، حداکثر ۶۳ نویسه، فهرست رزرو (`www`، `admin`، `api`، …).
  slug بعد از ساخت سالن قفل است.
- تست‌ها: `tests/Feature/Middleware/SubdomainRoutingTest.php` (با `phpunit.subdomain.xml`).

---

## ۴. تصمیم: کوکی session فقط مال همان میزبان (بررسی امنیتی M5، ۲۰۲۶-۱۱-۰۵ — جایگزین «کوکی مشترک» ۲۰۲۶-۰۹-۲۰)

- **قبلاً** `SESSION_DOMAIN=.arisheh.ir` بود: کوکی بین دامنه‌ی اصلی و همه‌ی زیردامنه‌ها مشترک، چون صفحه‌ی قدیمی `/s/{slug}` روی دامنه‌ی اصلی
  فرم‌هایی می‌ساخت که action‌شان زیردامنه بود (۴۱۹). ریسک: اسکریپتی روی زیردامنه‌ی یک سالن (مثل H1) می‌توانست برای کل دامنه کوکی بگذارد
  (تثبیت نشست / cookie tossing).
- **حالا:** `config/session.php` ‏`domain` همیشه `null` است (`SESSION_DOMAIN` خوانده نمی‌شود) و روی HTTPS نام کوکی `__Host-…` است — مرورگر
  چنین کوکی‌ای را فقط از خود همان میزبان و بی Domain می‌پذیرد. `/s/{slug}` روی دامنه‌ی اصلی به زیردامنه منتقل می‌شود (بخش ۳)، پس هر صفحه‌ی
  سالن و فرم‌هایش روی یک میزبان‌اند و ۴۱۹ پیش نمی‌آید.
- پنل‌های مدیر، متخصص و ابرمدیر و ورود کادر روی دامنه‌ی اصلی‌اند؛ مشتری روی زیردامنه‌ی همان سالن وارد می‌شود. مرز سالن‌ها همچنان
  `EnsureCustomerBelongsToSalon`/`EnsureAdminSalonActive` (از آدرس هر درخواست) است.
- با تغییر نام کوکی، همه یک بار از حساب خارج می‌شوند.

---

## ۵. عیب‌یابی

| نشانه | علت و راه |
|---|---|
| زیردامنه صفحه‌ی فروش را نشان می‌دهد | CDN Host اصلی را نمی‌فرستد، یا `CENTRAL_DOMAIN` با دامنه یکی نیست، یا (لوکال) خط آن سالن در hosts نیست |
| ۴۱۹ Page Expired | فرم از میزبان دیگری آمده (صفحه‌ی کهنه‌ی `/s/{slug}` پیش از انتقال — صفحه را تازه کنید)؛ یا صفحه از کش CDN آمده (فقط `/build/` و `/storage/` کش شوند) |
| خطای گواهی روی زیردامنه | گواهی `*.arisheh.ir` صادر نشده (بخش ۲) |
| `route:cache`: `Another route has already been assigned name` | کد قدیمی‌تر از ۲۰۲۶-۰۹-۲۳ |
| تغییر `CENTRAL_DOMAIN` اثر نکرد | `php artisan route:clear` (یا در Docker `up -d`) |
