# ماهرو روی سرور ابری آروان — همان کانتینر تک‌کانتینری، پشت CDN آروان

این راهنما همان image تک‌کانتینری که روی Docker Desktop آزمودید (`docs/deployment/DOCKER_DESKTOP.md`) را روی یک سرور ابری آروان بالا
می‌آورد. image روی ویندوز ساخته و به سرور منتقل می‌شود؛ روی سرور نه git لازم است، نه build، نه رجیستری.

```
کاربر ── HTTPS ──► لبه‌ی CDN آروان (گواهی رایگان، هدایت http→https)
                        │ HTTP
                        ▼
              سرور ابری آروان، پورت ۸۰ (فقط از لبه‌های آروان)
                        ▼
              کانتینر mahru  (MariaDB، PHP-FPM، Nginx، صف‌ها، scheduler، پشتیبان شبانه)
                        ▼
              volume ‏mahru_data (دیتابیس، آپلودها، لاگ، APP_KEY)  +  /srv/mahru/backups (پشتیبان‌ها)
```

**وضعیت این مرحله (تصمیم ۲۰۲۶-۱۰-۲۴):** پیامک واقعی نمی‌رود (`SMS_LOG_ONLY=true` — کد ورود فقط در لاگ سرور)، زرین‌پال sandbox، دیتابیس
خالی (بدون داده‌ی نمونه)، پشتیبان شبانه روی خود سرور و دانلود دستی با WinSCP.

---

## ۰. هزینه‌ها

| چیز | پلن | هزینه |
|---|---|---|
| CDN (DNS، گواهی SSL، پروکسی) | «پایه» | رایگان + مصرف مازاد. طبق پنل: ترافیک تا ۸ گیگابایت روزانه، ۱۰۰ رکورد DNS، ۵ قانون صفحه؛ **بدون** بارگذاری گواهی شخصی، **بدون** راه‌اندازی با CNAME (پس NS دامنه باید آروان شود)، **بدون** محدودیت نرخ درخواست در لبه (محدودیت ورود در خود برنامه هست) |
| سرور ابری | پیشنهاد: **Eco-Small3** (۱ CPU، ۳ گیگ رم، ۳۰ گیگ دیسک) | پولی؛ قیمت روز را در پنل ببینید (اکتبر ۲۰۲۶ حدود ۱٫۱۴ میلیون تومان در ماه) |

CDN رایگان، سرور ندارد؛ برنامه باید روی یک سرور اجرا شود. کانتینر بیکار حدود ۵۵۰ مگابایت رم می‌خواهد و build روی سرور انجام نمی‌شود، پس ۳ گیگ
(به‌علاوه‌ی ۲ گیگ swap) برای پیش از راه‌اندازی رسمی کافی است. Eco-Small2 (۲ گیگ) ارزان‌تر است ولی موقع ساخت گزارش PDF یا شلوغی حاشیه‌ی کمی دارد.
وقتی سالن‌های واقعی آمدند: C2-Medium1 (۲ CPU، ۴ گیگ).

---

## ۱. خرید سرور ابری

پنل آروان ← سرور ابری ← ساخت سرور:

1. **منطقه:** یکی از مناطق ایران (برای کاربران ایرانی و برای زرین‌پال/کاوه‌نگار).
2. **پلن:** بالا.
3. **سیستم‌عامل:** Ubuntu (آخرین نسخه)؛ اگر قالب **Docker** هست همان را بزنید (Docker از قبل نصب است).
4. **شبکه:** IPv4 عمومی.
5. **فایروال:** آروان فایروال‌های «Default» و «CDN» سرور ابری را مرکزی مدیریت می‌کند (اطلاعیه‌ی مهر ۱۴۰۵). اگر هنگام ساخت یا بعداً در
   صفحه‌ی سرور گزینه‌ی فایروال **CDN** هست، آن را بگیرید: ورودی وب فقط از لبه‌های آروان. پورت **۲۲ (SSH)** را برای IP خودتان باز نگه دارید.
   اگر نبود، بخش ۹ همین کار را روی خود سرور می‌کند.
6. **کلید SSH:** یک کلید بسازید و فایل خصوصی را جای امن نگه دارید (WinSCP و ssh با همین وصل می‌شوند).
7. نام سرور: `mahru-prod-1`.

⚠️ سرور تا ۴۸ ساعت بعد از ساخت حذف نمی‌شود؛ پیش از خرید ببینید ارتقای پلن بعداً ممکن است.

IP عمومی سرور را یادداشت کنید (اینجا: `SERVER_IP`).

---

## ۲. آماده‌سازی سرور (یک بار)

از ویندوز (`cmd` یا PowerShell):

```
ssh -i path\to\key root@SERVER_IP
```

روی سرور:

```bash
apt update && apt -y upgrade
docker --version && docker compose version || (curl -fsSL https://get.docker.com | sh)

# ۲ گیگ swap (رم کم در پیک‌ها)
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

mkdir -p /srv/mahru/images /srv/mahru/backups /srv/mahru/deploy/single
```

اگر `get.docker.com` باز نشد، آینه‌ی رجیستری آروان یا قالب Docker پنل را به کار ببرید؛ روی این سرور هیچ image از اینترنت کشیده نمی‌شود.

---

## ۳. ساخت image روی ویندوز

در پوشه‌ی پروژه، بعد از اعمال آخرین پچ‌ها:

1. اگر `composer.lock` عوض شده: `build-vendor` را دوباره بسازید (`DOCKER_DESKTOP.md` بخش ۱).
2. اجرا:
   ```
   deploy\single\build-image.cmd
   ```
3. خروجی: `build-images\mahru-single-<commit>.tar` (چند صد مگابایت). نام نسخه همان commit است؛ اگر تغییر commit‌نشده دارید، پسوند `-dirty` می‌گیرد.

---

## ۴. انتقال با WinSCP

WinSCP: پروتکل **SFTP**، میزبان `SERVER_IP`، کاربر `root`، در Advanced ← SSH ← Authentication کلید خصوصی (WinSCP خودش به ppk تبدیل می‌کند).

این‌ها را به `/srv/mahru` بفرستید:

| از پروژه | به سرور |
|---|---|
| `docker-compose.server.yml` | `/srv/mahru/docker-compose.server.yml` |
| `.env.server.example` | `/srv/mahru/.env.server` (با همین نام تازه) |
| `deploy/single/server-deploy.sh` و `deploy/single/arvan-ips.sh` | `/srv/mahru/deploy/single/` |
| `build-images\mahru-single-<commit>.tar` | `/srv/mahru/images/` |

هر نسخه‌ی تازه فقط فایل `.tar` (و اگر پچی آن‌ها را عوض کرد، همان فایل‌ها) دوباره منتقل می‌شود.

---

## ۵. `.env.server`

```bash
cd /srv/mahru
nano .env.server
```

پیش‌فرض‌ها برای همین مرحله درست‌اند (production، `SMS_LOG_ONLY=true`، sandbox، `MAHRU_SEED=false`، پشتیبان ۰۳:۴۰ با ۳ نسخه). فقط:

- **`TRUSTED_PROXIES`** را دستی پر نکنید؛ اسکریپت از فهرست رسمی آروان پر می‌کند:
  ```bash
  bash deploy/single/arvan-ips.sh
  ```
  اگر `https://www.arvancloud.ir/en/ips.txt` از سرور باز نشد: فهرست را از پنل یا پشتیبانی آروان بگیرید، هر رنج در یک خط در
  `/srv/mahru/arvan-ips.txt` بگذارید و دوباره اجرا کنید. بدون آن، برنامه IP واقعی کاربر و https را نمی‌بیند: محدودیت ورود بین همه مشترک
  می‌شود و لینک بازگشت درگاه با `http://` ساخته می‌شود.
- **`CENTRAL_DOMAIN` و `SESSION_DOMAIN`:** فقط وقتی گواهی `*.arisheh.ir` در بخش ۸ صادر شد نگهشان دارید. تا آن موقع (یا اگر پلن پایه
  گواهی wildcard نداد) هر دو را خالی کنید (`CENTRAL_DOMAIN=` و `SESSION_DOMAIN=`) تا آدرس سالن‌ها `https://arisheh.ir/s/{slug}` باشد.
- `APP_KEY` و `DB_PASSWORD` را **ننویسید** (حتی خالی)؛ کانتینر بار اول می‌سازد. `.env` لوکال XAMPP را اینجا کپی نکنید.

---

## ۶. اولین بالا آمدن

```bash
cd /srv/mahru
bash deploy/single/server-deploy.sh images/mahru-single-<commit>.tar
```

اسکریپت: image را load می‌کند، نسخه را در `/srv/mahru/.env` می‌نویسد، کانتینر را بالا می‌آورد و تا `healthy` شدن صبر می‌کند (بار اول
migrate چند دقیقه). در آخر `supervisorctl status` باید هفت برنامه را `RUNNING` نشان دهد: mariadb، php-fpm، nginx، queue، queue-otp،
scheduler، backup.

```bash
curl -sI http://127.0.0.1/up        # 200
```

**سوپرادمین:**

```bash
docker exec -it -u www mahru php artisan superadmin:create
```

**کلید فایربیس (پوش):** فایل `mahru-fcm-sa.json` را با WinSCP به `/srv/mahru` بفرستید، بعد:

```bash
docker cp /srv/mahru/mahru-fcm-sa.json mahru:/data/storage/app/private/mahru-fcm-sa.json
docker exec mahru chown www:www /data/storage/app/private/mahru-fcm-sa.json
rm /srv/mahru/mahru-fcm-sa.json
```

---

## ۷. DNS: انتقال `arisheh.ir` به آروان (ir-nic)

پلن پایه راه‌اندازی با CNAME ندارد؛ NS دامنه باید آروان شود.

1. پنل آروان ← **شبکه‌ی توزیع محتوا** ← افزودن دامنه `arisheh.ir` ← پلن **پایه**. آروان دو نام‌سرور (NS) نشان می‌دهد.
2. پنل **ir-nic** (nic.ir) ← دامنه‌ی `arisheh.ir` ← ویرایش «سرورهای نام» ← دو NS آروان را جایگزین کنید.
3. تا ۲۴ (گاهی ۴۸) ساعت. بررسی از ویندوز: `nslookup -type=ns arisheh.ir` باید NSهای آروان را بدهد.
4. در پنل آروان ← DNS این رکوردها را بسازید، با **پروکسی (ابر) روشن**:

| نوع | نام | مقدار |
|---|---|---|
| A | `@` | `SERVER_IP` |
| A | `www` | `SERVER_IP` |
| A | `*` | `SERVER_IP` |

`*` همه‌ی ساب‌دامین‌های سالن‌هاست (`rasta.arisheh.ir`، …). اگر پنل برای `*` پروکسی را اجازه نداد، این را یادداشت کنید (بخش ۸).

---

## ۸. HTTPS

پنل آروان ← دامنه ← **تنظیمات SSL/TLS** (نام‌ها ممکن است کمی فرق کنند):

1. **گواهی رایگان آروان** را فعال کنید (Let's Encrypt؛ سه‌ماهه و خودکار تمدید می‌شود). ببینید `*.arisheh.ir` هم پوشش داده می‌شود.
2. **HTTPS پیش‌فرض** (هدایت همه‌ی HTTP به HTTPS) را روشن کنید.
3. **پروتکل اتصال به سرور اصلی (مبدا):** **HTTP**. «HTTPS» یا «خودکار» نزنید: کانتینر فقط پورت ۸۰ دارد.
4. **HSTS:** فعلاً خاموش. روشن کردنش تا پایان max-age برگشت‌پذیر نیست؛ بعد از چند هفته‌ی بی‌مشکل.

**اگر گواهی `*.arisheh.ir` صادر نشد:** ساب‌دامین‌ها خطای گواهی می‌دهند. در `.env.server` ‏`CENTRAL_DOMAIN=` و `SESSION_DOMAIN=` را خالی کنید و
`docker compose -f docker-compose.server.yml up -d`؛ آدرس سالن‌ها `/s/{slug}` می‌شود و برنامه همین را در لینک‌ها و پیامک‌ها می‌سازد.

**کش:** فقط فایل‌های ثابت کش شوند. اگر پنل «احترام به هدر سرور اصلی» دارد آن را بزنید؛ وگرنه با قانون صفحه: `/build/*` کش طولانی،
`/storage/*` یک روز، و بقیه بدون کش. صفحه‌های دارای فرم و ورود هرگز نباید کش شوند (۴۱۹ و نمایش صفحه‌ی کاربر دیگر).

---

## ۹. بستن IP مستقیم سرور

Docker پورت ۸۰ را مستقیم باز می‌کند و ufw روی آن اثری ندارد. هر کس IP سرور را بداند، CDN را دور می‌زند.

- **ترجیح:** فایروال CDN پنل آروان (بخش ۱).
- **اگر پنل نداشت:** روی سرور (IP خودتان را برای آزمون مستقیم بدهید، اختیاری):
  ```bash
  sudo bash deploy/single/arvan-ips.sh --firewall YOUR_HOME_IP
  ```
  این قاعده با ری‌استارت سرور پاک می‌شود؛ یک بار:
  ```bash
  (crontab -l 2>/dev/null; echo '@reboot sleep 60 && cd /srv/mahru && bash deploy/single/arvan-ips.sh --firewall YOUR_HOME_IP') | crontab -
  ```

آزمون از جایی غیر از IP خودتان (مثلاً دیتای گوشی): `http://SERVER_IP/up` نباید باز شود؛ `https://arisheh.ir/up` باید باز شود.

بعد از هر تغییر فهرست آروان: `bash deploy/single/arvan-ips.sh` (و `--firewall` اگر استفاده می‌کنید)، بعد
`docker compose -f docker-compose.server.yml up -d`.

---

## ۱۰. آزمون‌ها

| آزمون | انتظار |
|---|---|
| `https://arisheh.ir/up` | ۲۰۰، با قفل سبز مرورگر |
| `http://arisheh.ir` | به `https://` هدایت می‌شود |
| `https://arisheh.ir` | صفحه‌ی فروش |
| ورود سوپرادمین (`/login`) | کد را از لاگ بردارید (پایین) |
| ساخت سالن آزمایشی (مثلاً `demo`) از پنل ابرمدیر | — |
| `https://demo.arisheh.ir` (اگر wildcard دارید) | صفحه‌ی سالن، **نه** صفحه‌ی فروش |
| `https://arisheh.ir/s/demo` | صفحه‌ی سالن |
| ورود مشتری روی سالن | بدون ۴۱۹ |
| پنل ابرمدیر ← لاگ امنیتی ورودها | IP **خودتان**، نه IP آروان (درستی `TRUSTED_PROXIES`) |
| پرداخت sandbox زرین‌پال | بازگشت به `https://…/payments/return/…` و «موفق» |
| `push:test` | `docker exec -u www mahru php artisan push:test 09xxxxxxxxx --wait=30` |
| داشبورد ابرمدیر ← «آخرین پشتیبان‌گیری» | بعد از اولین پشتیبان سبز |

**کد ورود (پیامک فقط در لاگ):**

```bash
docker logs --since 10m mahru 2>&1 | grep "SMS"
```

خط `SMS Template: در حال ارسال` ← `tokens` اولی کد است. تا وقتی `SMS_LOG_ONLY=true` است، **هیچ کاربر دیگری** نمی‌تواند وارد شود (کد فقط
پیش شماست). وقتی خط و قالب‌های کاوه‌نگار آماده شد: در `.env.server` ‏`KAVENEGAR_API_KEY=…` و `SMS_LOG_ONLY=false`، بعد
`docker compose -f docker-compose.server.yml up -d`.

**اگر ساب‌دامین صفحه‌ی فروش را نشان داد** یا لینک‌ها `http://` بودند: آروان Host یا `X-Forwarded-Proto` را آن‌طور که برنامه انتظار دارد
نمی‌فرستد — تنظیم «هدر Host» مبدا را در پنل ببینید و نتیجه را گزارش کنید (نیاز به تغییر کد را همان‌جا می‌سنجیم).

---

## ۱۱. پشتیبان و دانلود دستی

هر شب ۰۳:۴۰ (به وقت تهران) کانتینر در `/srv/mahru/backups/<تاریخ>/` می‌سازد:

| فایل | چه چیزی |
|---|---|
| `database.sql.gz` | کل دیتابیس |
| `storage-app.tar.gz` | آپلودها: لوگو، عکس متخصص، گالری، **عکس‌های قبل/بعد دوره‌ها (داده‌ی حساس چهره)**، خروجی گزارش‌ها |
| `app.key` | کلید `APP_KEY` — بدون آن داده‌ی رمزشده‌ی دیتابیس قابل بازگردانی نیست |

فقط **۳ پشتیبان آخر** روی سرور می‌ماند (`BACKUP_KEEP`). **دست‌کم هفته‌ای یک بار** پوشه‌ی تازه را با WinSCP دانلود کنید؛ پشتیبان روی همان دیسک با
خرابی سرور از بین می‌رود. روی ویندوز هم جای امن نگهش دارید (عکس مشتری‌ها و کلید).

پشتیبان دستی (مثلاً پیش از کار پرخطر): `docker exec -u www mahru mahru-backup now`. `server-deploy.sh` پیش از هر نسخه‌ی تازه خودش یکی می‌گیرد.

فضای دیسک: `df -h /` و `du -sh /srv/mahru/backups`. اگر عکس‌ها زیاد شد، دیسک اضافه از پنل یا `BACKUP_KEEP=2`.

### بازگردانی

```bash
cd /srv/mahru
D=backups/<تاریخ>
gunzip -c $D/database.sql.gz | docker exec -i mahru mariadb mahru
docker exec -i mahru tar -xzf - -C /data < $D/storage-app.tar.gz
docker exec mahru chown -R www:www /data/storage/app
docker compose -f docker-compose.server.yml restart
```

**سرور تازه با پشتیبان قدیمی:** پیش از اولین `server-deploy.sh` در `.env.server` بنویسید `APP_KEY=<محتوای app.key>` (کامل، نه خالی)،
بعد بالا بیاورید و دو دستور بالا را بزنید.

---

## ۱۲. نسخه‌ی تازه و برگشت

1. ویندوز: پچ‌ها ← `deploy\single\build-image.cmd`.
2. WinSCP: فایل `.tar` تازه به `/srv/mahru/images/`.
3. سرور:
   ```bash
   cd /srv/mahru
   bash deploy/single/server-deploy.sh images/mahru-single-<commit>.tar
   ```
   اسکریپت اول پشتیبان می‌گیرد (اگر شکست بخورد deploy انجام نمی‌شود)، بعد نسخه‌ی تازه و migrate.

**برگشت:**

```bash
bash deploy/single/server-deploy.sh --list
bash deploy/single/server-deploy.sh --use <نسخه‌ی قبلی>
```

⚠️ برگشت migrationهای نسخه‌ی تازه را برنمی‌گرداند. اگر نسخه‌ی تازه migration داشت و برنامه خراب شد، دیتابیس را از پشتیبانِ همان deploy
(تازه‌ترین پوشه‌ی پیش از آن) بازگردانی کنید (بخش ۱۱).

فایل‌های `.tar` و imageهای قدیمی: `rm images/<قدیمی>.tar` و `docker image rm mahru:single-<قدیمی>` (دو نسخه‌ی آخر را نگه دارید).

---

## ۱۳. دستورهای روزمره (از `/srv/mahru`)

| کار | دستور |
|---|---|
| وضعیت | `docker ps` و `docker exec mahru supervisorctl status` |
| لاگ زنده | `docker logs -f --tail 100 mahru` |
| کد ورود | `docker logs --since 10m mahru 2>&1 \| grep SMS` |
| artisan | `docker exec -u www mahru php artisan …` |
| شل دیتابیس | `docker exec -it mahru mariadb mahru` |
| اعمال تغییر `.env.server` | `docker compose -f docker-compose.server.yml up -d` (`restart` کافی نیست) |
| پشتیبان دستی | `docker exec -u www mahru mahru-backup now` |
| خاموش (داده می‌ماند) | `docker compose -f docker-compose.server.yml down` |

⚠️ هرگز `down -v` نزنید: volume دیتابیس و آپلودها پاک می‌شود.

---

## ۱۴. عیب‌یابی

| نشانه | علت و راه |
|---|---|
| `server-deploy.sh`: «healthy نشد» | لاگ چاپ‌شده؛ معمولاً migrate یا `.env.server` (خط خالی `APP_KEY=`/`DB_PASSWORD=`) |
| ۵۲۰ یا ۵۲۲ از آروان | کانتینر بالا نیست یا فایروال پورت ۸۰ را برای لبه‌ی آروان هم بسته (`docker ps`، بخش ۹) |
| خطای گواهی روی ساب‌دامین | گواهی `*.arisheh.ir` نیست (بخش ۸) |
| ۴۱۹ Page Expired | `SESSION_DOMAIN` با دامنه یکی نیست، یا صفحه از کش CDN آمده (بخش ۸ «کش») |
| لاگ امنیتی IP آروان را نشان می‌دهد | `TRUSTED_PROXIES` (بخش ۵)، بعد `up -d` |
| پیامک واقعی رفت | `SMS_LOG_ONLY=true` نبود؛ `server-deploy.sh` هشدار می‌دهد |
| «آخرین پشتیبان‌گیری» قرمز | `docker logs mahru 2>&1 \| grep backup` و `/data/storage/app/backup-status.json` |

---

## ۱۵. آنچه تأییدنشده است (روی سرور واقعی بسنجید)

- آدرس فهرست IP آروان (`/en/ips.txt`) — ابزارهای متن‌باز همین را استفاده می‌کنند؛ از بیرون ایران برای Claude بسته بود.
- پروکسی روی رکورد `*` و صدور گواهی `*.arisheh.ir` در پلن پایه.
- هدرهایی که آروان به مبدا می‌فرستد (`X-Forwarded-For`، `X-Forwarded-Proto`، Host اصلی) — با آزمون‌های بخش ۱۰.
- نام دقیق گزینه‌ها در پنل.
