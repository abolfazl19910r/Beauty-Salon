# ماهرو روی سرور ابری آروان — همان کانتینر تک‌کانتینری، پشت CDN آروان

این راهنما همان image تک‌کانتینری که روی Docker Desktop آزمودید (`docs/deployment/DOCKER_DESKTOP.md`) را روی یک سرور ابری آروان بالا
می‌آورد. image روی ویندوز ساخته و به سرور منتقل می‌شود؛ روی سرور نه git لازم است، نه build، نه رجیستری.

```
کاربر ── HTTPS ──► لبه‌ی CDN آروان (گواهی رایگان، هدایت http→https)
                        │ HTTPS (گواهی خود سرور، بخش ۸)
                        ▼
              سرور ابری آروان، پورت ۴۴۳/۸۰ (فقط از لبه‌های آروان، بخش ۹)
                        ▼
              کانتینر mahru  (MariaDB، PHP-FPM، Nginx، صف‌ها، scheduler، پشتیبان شبانه)
                        ▼
              volume ‏mahru_data (دیتابیس، آپلودها، لاگ، APP_KEY)  +  /srv/mahru/backups (پشتیبان‌ها)
```

**⭐ قاعده (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵): امنیت به CDN وابسته نیست.** CDN یک لایه‌ی اضافه است. اگر برداشته یا عوض شود، همه‌ی حفاظت‌ها در
خود سرور و کانتینر برقرار می‌ماند: HTTPS با گواهی خود سرور (بخش ۸)، هدرهای امنیتی، CSP و محدودیت نرخ در برنامه، محدودیت اندازه و زمان
درخواست در nginx کانتینر، IP واقعی در لاگ (بخش ۵ و ۹)، SSH فقط با کلید و fail2ban (بخش ۲ب)، پشتیبان رمزنگاری‌شده (بخش ۱۱) و
مانیتور بیرونی (بخش ۱۰ب). رفتن به حالت «بدون CDN»: بخش ۹ب.

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

## ۲ب. سخت‌سازی سرور (یک بار، بسته‌ی مجوز 5د)

### SSH فقط با کلید

⚠️ **نشست فعلی را تا آخر این بخش نبندید.** اگر چیزی اشتباه شد، از همین نشست برمی‌گردانید.

**مسیر نجات:** پنل آروان ← سرور ← **کنسول** (کنسول وب سرور، بی SSH). ورود در کنسول با **رمز root** است، نه کلید؛ پس اول یک رمز قوی برای
root بگذارید و در مدیر رمزتان نگه دارید (فقط برای کنسول؛ SSH با رمز بسته می‌شود). اگر رمز را هم نداشتید: پنل ← بازنشانی رمز سرور.

```bash
passwd root            # رمز قوی، فقط برای کنسول پنل
```

۱. **در یک پنجره‌ی تازه** (نه همین) ورود با کلید را بیازمایید: `ssh -i path\to\key root@SERVER_IP "echo ok"` ← باید `ok` بدهد، بی پرسیدن رمز.

۲. تنظیم، در فایلی که **پیش از** فایل‌های دیگر خوانده می‌شود:

```bash
cat > /etc/ssh/sshd_config.d/00-mahru-hardening.conf <<'CONF'
# بسته‌ی مجوز 5د — SSH فقط با کلید
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin prohibit-password
PubkeyAuthentication yes
MaxAuthTries 3
CONF
ls /etc/ssh/sshd_config.d/
```

چرا `00-`: در OpenSSH **اولین** مقداری که برای هر گزینه خوانده شود برنده است، و `sshd_config` اوبونتو در خط اولش
`Include /etc/ssh/sshd_config.d/*.conf` دارد (به ترتیب الفبا). روی سرورهای ابری معمولاً `50-cloud-init.conf` با
`PasswordAuthentication yes` هست؛ اگر تنظیم ما بعد از آن یا در خود `sshd_config` بیاید، **بی‌اثر** است.

۳. **تأیید وضعیت مؤثر** (نه خواندن فایل‌ها):

```bash
sshd -t && sshd -T | grep -Ei '^(passwordauthentication|kbdinteractiveauthentication|permitrootlogin|pubkeyauthentication) '
```

باید دقیقاً این‌ها را بدهد (ترتیب مهم نیست):

```
pubkeyauthentication yes
passwordauthentication no
kbdinteractiveauthentication no
permitrootlogin without-password
```

(`without-password` نام قدیمی همان `prohibit-password` است.) اگر `passwordauthentication yes` بود، فایلی در `sshd_config.d` زودتر از `00-`
خوانده می‌شود یا `Include` بالای `sshd_config` نیست؛ درستش کنید و دوباره `sshd -T`.

۴. اعمال: `systemctl reload ssh` (اوبونتو ۲۴: نام سرویس `ssh`).

۵. **در یک پنجره‌ی تازه** دوباره با کلید وارد شوید، و ببینید رمز رد می‌شود:
`ssh -o PubkeyAuthentication=no -o PreferredAuthentications=password root@SERVER_IP` ← باید `Permission denied (publickey)` بدهد.
فقط حالا نشست اول را ببندید.

### fail2ban برای SSH

```bash
apt install -y fail2ban
cat > /etc/fail2ban/jail.d/mahru-sshd.local <<'CONF'
[sshd]
enabled = true
backend = systemd
maxretry = 5
findtime = 10m
bantime = 1h
bantime.increment = true
bantime.maxtime = 1w
# IP ثابت خودتان را می‌توانید اضافه کنید
ignoreip = 127.0.0.1/8 ::1
CONF
systemctl enable --now fail2ban
fail2ban-client status sshd
```

`fail2ban-client status sshd` باید `Currently banned` و `Total failed` را نشان دهد. اگر خودتان قفل شدید: از کنسول پنل
`fail2ban-client set sshd unbanip YOUR_IP`. fail2ban فقط SSH را می‌پاید؛ وب را محدودیت نرخ خود برنامه و nginx کانتینر می‌پایند.

**به‌روزرسانی امنیتی خودکار:** اوبونتو `unattended-upgrades` را از قبل دارد؛ `systemctl status unattended-upgrades` باید `active` باشد.

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

دو لایه (بسته‌ی مجوز 5د، تصمیم ۲۰۲۶-۱۰-۲۵: **خود سرور هم HTTPS دارد**): لبه‌ی آروان گواهی رایگان خودش را دارد، و کانتینر با گواهی خود
سرور روی ۴۴۳ گوش می‌دهد. با CDN، لبه با HTTPS به سرور وصل می‌شود؛ بدون CDN، کاربر مستقیم. برداشتن CDN فقط یک تغییر DNS است.

### ۸الف. گواهی خود سرور (acme.sh روی میزبان)

گواهی `arisheh.ir` و `*.arisheh.ir` با **DNS-01** (wildcard فقط با DNS-01 صادر می‌شود). acme.sh برای DNS آروان افزونه‌ی `dns_arvan` دارد:

```bash
curl -fsSL https://get.acme.sh | sh -s email=you@example.com      # یا از GitHub: acmesh-official/acme.sh
# پنل آروان ← کلیدهای API ← کلید فقط با دسترسی DNS
export Arvan_Token="apikey xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
~/.acme.sh/acme.sh --issue --dns dns_arvan -d arisheh.ir -d '*.arisheh.ir' --server letsencrypt
~/.acme.sh/acme.sh --install-cert -d arisheh.ir \
    --fullchain-file /srv/mahru/tls/fullchain.pem --key-file /srv/mahru/tls/privkey.pem \
    --reloadcmd "chmod 600 /srv/mahru/tls/privkey.pem; docker exec mahru nginx -s reload"
docker compose -f docker-compose.server.yml up -d      # بار اول: nginx با دیدن گواهی ۴۴۳ را روشن می‌کند
curl -skI https://127.0.0.1/up -H 'Host: arisheh.ir' | head -1   # HTTP/2 200
```

acme.sh خودش کرون تمدید می‌گذارد (`crontab -l`). وابستگی به آروان فقط در **صدور** است؛ اگر DNS جای دیگری رفت، همان دستور با افزونه‌ی
DNS تازه (`--dns dns_…`). بدون API DNS می‌شود فقط `arisheh.ir` و `www` را با HTTP-01 گرفت (`--webroot /srv/mahru/acme`)، ولی
ساب‌دامین سالن‌ها پوشش ندارند (بخش ۹ب).

وقتی `./tls` گواهی دارد: پورت ۸۰ به HTTPS هدایت می‌شود (جز `/up` و چالش ACME)، و درخواستی که لبه‌ی آروانِ مورد اعتماد خودش با HTTPS گرفته
هدایت نمی‌شود (حلقه ساخته نمی‌شود). برنامه روی HTTPS هدر **HSTS** (یک سال، با ساب‌دامین‌ها) می‌فرستد.

### ۸ب. لبه‌ی آروان

پنل آروان ← دامنه ← **تنظیمات SSL/TLS** (نام‌ها ممکن است کمی فرق کنند):

1. **گواهی رایگان آروان** را فعال کنید (Let's Encrypt؛ سه‌ماهه و خودکار تمدید می‌شود). ببینید `*.arisheh.ir` هم پوشش داده می‌شود.
2. **HTTPS پیش‌فرض** (هدایت همه‌ی HTTP به HTTPS) را روشن کنید.
3. **پروتکل اتصال به سرور اصلی (مبدا):** **HTTPS** (بعد از ۸الف). تا گواهی سرور نیامده: **HTTP**.
   اگر مبدا HTTPS دارد و لبه روی «HTTP» مانده، باز هم کار می‌کند (درخواست لبه‌ی مورد اعتماد با `X-Forwarded-Proto: https` هدایت نمی‌شود)،
   ولی مسیر لبه تا سرور رمزنگاری‌نشده می‌ماند.
4. **HSTS لبه:** خاموش بگذارید؛ خود برنامه HSTS را روی HTTPS می‌فرستد. اگر گواهی `*.arisheh.ir` ندارید، در `.env.server`
   `HSTS_INCLUDE_SUBDOMAINS=false` بگذارید (وگرنه مرورگر ساب‌دامین بی گواهی را هم فقط HTTPS می‌خواهد).

**اگر گواهی `*.arisheh.ir` صادر نشد:** ساب‌دامین‌ها خطای گواهی می‌دهند. در `.env.server` ‏`CENTRAL_DOMAIN=` و `SESSION_DOMAIN=` را خالی کنید و
`docker compose -f docker-compose.server.yml up -d`؛ آدرس سالن‌ها `/s/{slug}` می‌شود و برنامه همین را در لینک‌ها و پیامک‌ها می‌سازد.

**کش:** فقط فایل‌های ثابت کش شوند. اگر پنل «احترام به هدر سرور اصلی» دارد آن را بزنید؛ وگرنه با قانون صفحه: `/build/*` کش طولانی،
`/storage/*` یک روز، و بقیه بدون کش. صفحه‌های دارای فرم و ورود هرگز نباید کش شوند (۴۱۹ و نمایش صفحه‌ی کاربر دیگر).

---

## ۹. بستن IP مستقیم سرور (با CDN)

Docker پورت‌های ۸۰ و ۴۴۳ را مستقیم باز می‌کند و ufw روی آن‌ها اثری ندارد. هر کس IP سرور را بداند، CDN را دور می‌زند.

- **ترجیح:** فایروال CDN پنل آروان (بخش ۱).
- **اگر پنل نداشت:** روی سرور (IP خودتان را برای آزمون مستقیم بدهید، اختیاری):
  ```bash
  sudo bash deploy/single/arvan-ips.sh --firewall YOUR_HOME_IP
  ```
  ۸۰ و ۴۴۳ فقط برای لبه‌های آروان (IPv4؛ کانتینر روی IPv6 منتشر نمی‌شود). این قاعده با ری‌استارت سرور پاک می‌شود؛ یک بار:
  ```bash
  (crontab -l 2>/dev/null; echo '@reboot sleep 60 && cd /srv/mahru && bash deploy/single/arvan-ips.sh --firewall YOUR_HOME_IP') | crontab -
  ```

آزمون از جایی غیر از IP خودتان (مثلاً دیتای گوشی): `http://SERVER_IP/up` نباید باز شود؛ `https://arisheh.ir/up` باید باز شود.

بعد از هر تغییر فهرست آروان: `bash deploy/single/arvan-ips.sh` (و `--firewall` اگر استفاده می‌کنید)، بعد
`docker compose -f docker-compose.server.yml up -d`.

**SSH:** پورت ۲۲ مال میزبان است (نه Docker)، پس ufw یا فایروال پنل رویش اثر دارد. اگر IP ثابت دارید، فقط همان:
`ufw allow from YOUR_IP to any port 22 proto tcp && ufw enable` (اول مطمئن شوید IP درست است؛ وگرنه فقط کنسول پنل می‌ماند). اگر IP ثابت
ندارید، SSH باز بماند: کلید و fail2ban (بخش ۲ب) کافی‌اند.

## ۹ب. حالت «بدون CDN» (برداشتن یا عوض کردن CDN)

همه‌ی حفاظت‌ها در خود سرور است؛ فقط این چند قدم:

1. **گواهی خود سرور** باید باشد (بخش ۸الف) و تمدیدش به DNS تازه وصل باشد (اگر DNS هم عوض شد، افزونه‌ی `--dns` تازه).
2. ```bash
   cd /srv/mahru
   sudo bash deploy/single/arvan-ips.sh --no-cdn
   docker compose -f docker-compose.server.yml up -d
   ```
   `TRUSTED_PROXIES` خالی می‌شود (به هیچ پروکسی اعتماد نمی‌شود؛ IP مستقیم کاربر همان IP واقعی است)، قاعده‌ی `DOCKER-USER` برداشته و ۸۰/۴۴۳
   برای همه باز می‌شود. خط `@reboot … --firewall` را از `crontab -e` پاک کنید.
3. **DNS:** رکوردهای `@`، `www` و `*` مستقیم به `SERVER_IP`، بدون پروکسی (یا در DNS تازه).
4. آزمون: `https://arisheh.ir/up` با قفل سبز؛ پنل ابرمدیر ← «داده‌های نگهداری‌شده» با شماره‌ی خودتان: IP **خودتان**.

**CDN دیگر به‌جای آروان:** همان `TRUSTED_PROXIES` با رنج‌های لبه‌ی آن CDN (با کاما)، بعد `up -d`. nginx کانتینر هنگام شروع همین فهرست را
برای IP واقعی لاگ دسترسی می‌خواند (`php artisan security:nginx-real-ip`).

---

## ۱۰. آزمون‌ها

| آزمون | انتظار |
|---|---|
| `https://arisheh.ir/up` | ۲۰۰، با قفل سبز مرورگر |
| `http://arisheh.ir` | به `https://` هدایت می‌شود |
| `https://arisheh.ir` | صفحه‌ی فروش |
| `https://www.arisheh.ir/x` | 301 به `https://arisheh.ir/x` (یک نسخه‌ی اصلی دامنه برای اینماد؛ خود برنامه) |
| لوگوی اینماد پایین صفحه‌ی فروش | بعد از گذاشتن کد در «تنظیمات پلتفرم ← اطلاعات حقوقی و اینماد»؛ کلیک ← شناسنامه‌ی اینماد |
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

| هدرهای امنیتی | `curl -sI https://arisheh.ir/ \| grep -iE 'strict-transport\|x-frame\|referrer-policy\|permissions-policy\|content-security'` — همه هستند (CSP اجرایی از ۲۰۲۶-۱۰-۳۱؛ اگر صفحه‌ای شکست: `storage/logs/csp-*.log` و موقتاً `SECURITY_CSP=report`) |
| لاگ دسترسی ماندگار | `docker exec mahru ls /data/logs/nginx` — فایل امروز؛ `tail -1` آن IP **خودتان** را دارد |
| SSH با رمز | `ssh -o PubkeyAuthentication=no root@SERVER_IP` ← `Permission denied (publickey)` (بخش ۲ب) |

**اگر ساب‌دامین صفحه‌ی فروش را نشان داد** یا لینک‌ها `http://` بودند: آروان Host یا `X-Forwarded-Proto` را آن‌طور که برنامه انتظار دارد
نمی‌فرستد — تنظیم «هدر Host» مبدا را در پنل ببینید و نتیجه را گزارش کنید (نیاز به تغییر کد را همان‌جا می‌سنجیم).

## ۱۰ب. مانیتور بیرونی uptime (بسته‌ی مجوز 5د)

خود سرور نمی‌تواند خبر دهد که خاموش است؛ پس یک سرویس **بیرون از همین سرور** هر چند دقیقه `https://arisheh.ir/up` را بزند و اگر جواب ۲۰۰
نیامد هشدار بدهد (ایمیل، پیامک یا تلگرام/بله).

- **ساده‌ترین:** یک سرویس مانیتور رایگان (مثلاً UptimeRobot یا Better Stack): نوع HTTP(S)، آدرس `https://arisheh.ir/up`، فاصله‌ی ۵ دقیقه،
  هشدار به ایمیل و موبایل خودتان. فقط کد پاسخ را بسنجد (صفحه‌ی `/up` فونت و Tailwind را از CDN بیرونی می‌گیرد و از ایران ممکن است
  ناقص دیده شود؛ برای کد پاسخ مهم نیست).
- **اگر سرویس خارجی به سرور ایران نرسید** (قطعی بین‌الملل): یک سرور کوچک دیگر (جدا از این) با کرون:
  `*/5 * * * * curl -fsS -m 20 https://arisheh.ir/up >/dev/null || <دستور هشدار>`.
- دو آدرس بهتر از یکی: `https://arisheh.ir/up` (از راه CDN) و در صورت امکان `https://SERVER_IP/up` با Host درست (خود مبدا)، تا معلوم
  شود خرابی از CDN است یا سرور.
- آزمون هشدار: یک بار `docker compose -f docker-compose.server.yml stop` (در ساعت خلوت) ← هشدار باید ظرف چند دقیقه برسد ← `up -d`.

---

## ۱۱. پشتیبان و دانلود دستی

هر شب ۰۳:۴۰ (به وقت تهران) کانتینر در `/srv/mahru/backups/<تاریخ>/` می‌سازد:

| فایل | چه چیزی |
|---|---|
| `database.sql.gz.age` | کل دیتابیس |
| `storage-app.tar.gz.age` | آپلودها: لوگو، عکس متخصص، گالری، **عکس‌های قبل/بعد دوره‌ها (داده‌ی حساس چهره)**، خروجی گزارش‌ها |
| `app.key.age` | کلید `APP_KEY` — بدون آن داده‌ی رمزشده‌ی دیتابیس قابل بازگردانی نیست |

⭐ همه با **age و کلید عمومی** `BACKUP_AGE_RECIPIENT` رمزنگاری شده‌اند (بسته‌ی مجوز 5د)؛ کلید خصوصی فقط روی ویندوز شماست، جدا از سرور و جدا
از `APP_KEY`. ساخت کلید و باز کردن فایل‌ها: `docs/deployment/BACKUP.md` بخش «رمزنگاری». بدون `BACKUP_AGE_RECIPIENT` سرور پشتیبان
نمی‌گیرد (`BACKUP_REQUIRE_ENCRYPTION=true`) و `server-deploy.sh` پیش از نسخه‌ی تازه می‌ایستد.

فقط **۳ پشتیبان آخر** روی سرور می‌ماند (`BACKUP_KEEP`). **دست‌کم هفته‌ای یک بار** پوشه‌ی تازه را با WinSCP دانلود کنید؛ پشتیبان روی همان دیسک با
خرابی سرور از بین می‌رود. روی ویندوز هم جای امن نگهش دارید (عکس مشتری‌ها و کلید).

پشتیبان دستی (مثلاً پیش از کار پرخطر): `docker exec -u www mahru mahru-backup now`. `server-deploy.sh` پیش از هر نسخه‌ی تازه خودش یکی می‌گیرد.

فضای دیسک: `df -h /` و `du -sh /srv/mahru/backups`. اگر عکس‌ها زیاد شد، دیسک اضافه از پنل یا `BACKUP_KEEP=2`.

### بازگردانی

کلید خصوصی را **موقت** با WinSCP در `/root/mahru-backup-key.txt` بگذارید (`chmod 600`)، و بعد از بازگردانی پاکش کنید:

```bash
apt install -y age          # یک بار
cd /srv/mahru
D=backups/<تاریخ>
K=/root/mahru-backup-key.txt
age -d -i $K $D/database.sql.gz.age | gunzip | docker exec -i mahru mariadb mahru
age -d -i $K $D/storage-app.tar.gz.age | docker exec -i mahru tar -xzf - -C /data
docker exec mahru chown -R www:www /data/storage/app
docker compose -f docker-compose.server.yml restart
shred -u $K
```

**سرور تازه با پشتیبان قدیمی:** پیش از اولین `server-deploy.sh` در `.env.server` بنویسید `APP_KEY=<محتوای app.key>` (کامل، نه خالی؛
`age -d -i $K $D/app.key.age`)، بعد بالا بیاورید و دستورهای بالا را بزنید. پشتیبان‌های قدیمی بی `.age` (پیش از این بسته) همان
`gunzip -c … | …` قبلی‌اند.

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

- (بسته‌ی مجوز 5د) صدور گواهی `*.arisheh.ir` با `dns_arvan` روی پلن پایه و اتصال «HTTPS به مبدا» لبه‌ی آروان به گواهی خود سرور؛
  HTTPS و هدایت ۸۰ در sandbox با گواهی خودامضا و nginx و php-fpm واقعی آزموده شد، خود image آلپاین نه.
- (بسته‌ی مجوز 5د) نام دقیق کنسول وب و بازنشانی رمز در پنل سرور ابری آروان.

- آدرس فهرست IP آروان (`/en/ips.txt`) — ابزارهای متن‌باز همین را استفاده می‌کنند؛ از بیرون ایران برای Claude بسته بود.
- پروکسی روی رکورد `*` و صدور گواهی `*.arisheh.ir` در پلن پایه.
- هدرهایی که آروان به مبدا می‌فرستد (`X-Forwarded-For`، `X-Forwarded-Proto`، Host اصلی) — با آزمون‌های بخش ۱۰.
- نام دقیق گزینه‌ها در پنل.
