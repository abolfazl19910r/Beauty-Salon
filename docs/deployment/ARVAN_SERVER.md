# ماهرو روی سرور (ابری آروان یا VPS دیگر مثل پارس وی دی اس) — همان کانتینر تک‌کانتینری، DNS آروان، CDN اختیاری

این راهنما همان image تک‌کانتینری که روی Docker Desktop آزمودید (`docs/deployment/DOCKER_DESKTOP.md`) را روی یک سرور لینوکسی بالا
می‌آورد. image روی ویندوز ساخته و به سرور منتقل می‌شود؛ روی سرور نه git لازم است، نه build، نه رجیستری.

**سرور production فعلی (از ۲۰۲۶-۱۱-۰۷):** پارس وی دی اس، نه سرور ابری آروان؛ DNS روی آروان با **ابر خاموش** (بی CDN، `TRUSTED_PROXIES`
خالی). هر جا این راهنما «پنل آروان ← سرور» می‌گوید، معادلش در پارس وی دی اس بخش **۲ج** است. تفاوت‌های این سرور (پورت SSH، Docker از مخزن
اوبونتو، کلیدهای میزبان قالب، کنسول VNC، اینترنت بین‌الملل، NTP) همه در ۲ج آمده‌اند.

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
# needrestart بعد از apt گاهی روی ری‌استارت یک سرویس گیر می‌کند و قفل dpkg می‌ماند (بخش ۲ج)
export NEEDRESTART_SUSPEND=1
apt update && apt -y full-upgrade
# Docker از مخزن خود اوبونتو (آینه‌ی داخلی دیتاسنتر کار می‌کند؛ download.docker.com و get.docker.com از ایران معمولاً بسته‌اند)
docker --version && docker compose version || apt install -y docker.io docker-compose-v2
systemctl enable --now docker

# ۲ گیگ swap (رم کم در پیک‌ها)
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

mkdir -p /srv/mahru/images /srv/mahru/backups /srv/mahru/deploy/single /srv/mahru/tls
chmod 700 /srv/mahru/tls
```

روی این سرور هیچ image از اینترنت کشیده نمی‌شود؛ پس فقط خود Docker و Compose لازم است و نسخه‌ی مخزن اوبونتو (۲۴.۰۴: `docker.io` 29.x و
`docker-compose-v2` 2.40) کافی است. اگر کرنل تازه نصب شد (`/var/run/reboot-required`)، یک بار `reboot`.

**کلیدهای میزبان SSH قالب:** بعضی قالب‌های VPS کلیدهای میزبان یکسانی برای همه‌ی سرورهای ساخته‌شده از قالب دارند (پارس وی دی اس:
`root@localhost.localdomain` و DSA ‏۱۰۲۴). یک بار عوض کنید (پنجره‌ی SSH باز بماند؛ اتصال بعدی هشدار «REMOTE HOST IDENTIFICATION HAS
CHANGED» می‌دهد و روی ویندوز `ssh-keygen -R "[SERVER_IP]:PORT"` آن را پاک می‌کند):

```bash
ssh-keygen -l -f /etc/ssh/ssh_host_ed25519_key.pub    # اگر root@localhost.localdomain بود ← عوض کنید
rm /etc/ssh/ssh_host_* && ssh-keygen -A && systemctl restart ssh
```

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
SSH_PORT=$(sshd -T | awk '$1=="port"{print $2; exit}')    # پارس وی دی اس: 9011
cat > /etc/fail2ban/jail.d/mahru-sshd.local <<CONF
[sshd]
enabled = true
backend = systemd
# اوبونتو ۲۴: نام سرویس «ssh» است؛ پیش‌فرض fail2ban 1.0 «sshd.service» را می‌پاید و هیچ خطایی نمی‌بیند
journalmatch = _SYSTEMD_UNIT=ssh.service + _COMM=sshd
# پورت SSH غیر ۲۲: بدون این، fail2ban پورت ۲۲ را می‌بندد و حمله‌کننده روی پورت واقعی آزاد است
port = ${SSH_PORT}
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

`fail2ban-client status sshd` باید `Currently banned` و `Total failed` را نشان دهد. آزمون اینکه واقعاً می‌بیند: از ویندوز یک بار با
کاربر غلط وصل شوید (`ssh -p PORT nobody@SERVER_IP`) ← `Total failed` یکی بالا می‌رود. اگر صفر ماند، `journalmatch` درست نیست
(`journalctl -u ssh -n 5`). اگر خودتان قفل شدید: از کنسول پنل `fail2ban-client set sshd unbanip YOUR_IP`. fail2ban فقط SSH را
می‌پاید؛ وب را محدودیت نرخ خود برنامه و nginx کانتینر می‌پایند.

**به‌روزرسانی امنیتی خودکار:** اوبونتو `unattended-upgrades` را از قبل دارد؛ `systemctl status unattended-upgrades` باید `active` باشد.

---

## ۲ج. VPS غیر آروان — پارس وی دی اس (سرور production فعلی)

| چیز | مقدار |
|---|---|
| پلن | `IR_VPS_03_shatel`: ۲ هسته، ۴ گیگ رم، ۳۰ گیگ دیسک، ۳۰۰ گیگ ترافیک ماهانه، KVM، Ubuntu 24.04 |
| شبکه | IPv4 عمومی مستقیم روی `eth0` (بی NAT؛ ۸۰ و ۴۴۳ مستقیم)، IPv6 هم دارد |
| SSH | پورت **`9011`** (نه ۲۲) |
| کنسول نجات | پنل ← تنظیمات ← VNC ← «Launch HTML 5 VNC Client»؛ ورود با **رمز root** |
| اینترنت بین‌الملل | از منوی پنل «فعال‌سازی اینترنت بین‌الملل» (پیش‌فرض بسته؛ از ۲۰۲۶-۱۱-۱۱ باز) |

**`~/.ssh/config` ویندوز** (پوشه‌ی `.ssh` را `ssh-keygen` نمی‌سازد: `New-Item -ItemType Directory $HOME\.ssh`):

```
Host mahru
    HostName SERVER_IP
    Port 9011
    User root
    IdentityFile ~/.ssh/mahru_prod
    ServerAliveInterval 15
```

از آن به بعد `ssh mahru`، `scp … mahru:/srv/mahru/…` و `sftp mahru`. WinSCP: همان میزبان و **پورت ۹۰۱۱**.

**passphrase فراموش‌شده‌ی کلید (SSH فقط با کلید است):** اول روی ویندوز بی شمارش در fail2ban بیازمایید:
`ssh-keygen -y -f $HOME\.ssh\mahru_prod` (passphrase درست ← کلید عمومی چاپ می‌شود). اگر واقعاً فراموش شده، از VNC:

```bash
mv /etc/ssh/sshd_config.d/00-mahru-hardening.conf /root/ && systemctl reload ssh     # موقتاً ورود با رمز
# ویندوز: کلید تازه (ssh-keygen -t ed25519 -f $HOME\.ssh\mahru_prod) و با رمز root:
#   type $HOME\.ssh\mahru_prod.pub | ssh -p 9011 root@SERVER_IP "cat > ~/.ssh/authorized_keys"
mv /root/00-mahru-hardening.conf /etc/ssh/sshd_config.d/ && systemctl reload ssh       # دوباره فقط کلید
```

`Connection closed` بلافاصله بعد از رمز = `Timeout before authentication` (۱۲۰ ثانیه)؛ تند‌تر تایپ کنید یا از VNC بروید.

**اینترنت بین‌الملل:** پیش از هر کاری که به خارج نیاز دارد (acme.sh، Let's Encrypt، FCM، NTP خارجی) بیازمایید:

```bash
for u in https://github.com https://get.acme.sh https://acme-v02.api.letsencrypt.org/directory https://fcm.googleapis.com \
         https://oauth2.googleapis.com https://napi.arvancloud.ir https://api.kavenegar.com https://payment.zarinpal.com; do
  printf '%-50s %s\n' "$u" "$(curl -s -o /dev/null -m 10 -w '%{http_code}' "$u")"
done
```

کد `000` یعنی بسته؛ هر کد دیگری (حتی ۴۰۴ ریشه‌ی `fcm.googleapis.com`) یعنی باز. سرویس‌های داخلی (کاوه‌نگار، زرین‌پال، API آروان)
همیشه باز بوده‌اند. در قطعی بین‌الملل: گواهی (تمدید ۶۰ روز پیش از انقضای ۹۰ روزه، بخش ۸الف) و پوش FCM می‌مانند؛ بقیه کار می‌کند.

**needrestart:** بعد از `apt` گاهی روی ری‌استارت یک سرویس (اینجا `fwupd`) گیر می‌کند و قفل dpkg می‌ماند، هرچند نصب تمام شده
(`Log ended` آخر `/var/log/apt/term.log`). پردازه‌ی needrestart را `kill` کنید و از آن به بعد `export NEEDRESTART_SUSPEND=1` پیش از apt.
`fwupd` روی VPS لازم نیست: `systemctl mask fwupd`.

### ۲د. ساعت سرور (NTP)

اعتبار گواهی، توکن‌های پرداخت و امضای JWT حساب سرویس FCM به ساعت درست وابسته‌اند (کانتینر ساعت میزبان را دارد). اوبونتو با
`systemd-timesyncd` همگام می‌شود؛ پیش‌فرضش `ntp.ubuntu.com` (خارج). سرورهای ایرانی pool اول، تا در قطعی بین‌الملل هم همگام بماند:

```bash
mkdir -p /etc/systemd/timesyncd.conf.d
cat > /etc/systemd/timesyncd.conf.d/mahru.conf <<'CONF'
[Time]
NTP=ir.pool.ntp.org ntp.ubuntu.com
FallbackNTP=0.pool.ntp.org 1.pool.ntp.org 2.pool.ntp.org time.cloudflare.com
CONF
systemctl restart systemd-timesyncd
timedatectl set-ntp true
sleep 10; timedatectl timesync-status; timedatectl | grep -E 'synchronized|NTP service'
```

انتظار: `System clock synchronized: yes` و در `timesync-status` یک `Server:` با `Offset` چند میلی‌ثانیه. اگر `Server: n/a` ماند، UDP ‏۱۲۳
بسته است (`journalctl -u systemd-timesyncd -n 20`)؛ در تیکت از دیتاسنتر سرور NTP داخلی بخواهید و همان را اول `NTP=` بگذارید.

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
| `deploy/single/server-deploy.sh`، `deploy/single/arvan-ips.sh` و `deploy/single/tls-reload.sh` | `/srv/mahru/deploy/single/` |
| `build-images\mahru-single-<commit>.tar` | `/srv/mahru/images/` |

هر نسخه‌ی تازه فقط فایل `.tar` (و اگر پچی آن‌ها را عوض کرد، همان فایل‌ها) دوباره منتقل می‌شود.

**بی WinSCP، با `scp`/`sftp` ویندوز** (با `Host mahru` بخش ۲ج):

```
scp build-images\mahru-single-<commit>.tar mahru:/srv/mahru/images/
```

آپلود کند خانه (~۳۰۰ KB/s برای ~۳۰۰ مگابایت) ممکن است وسط کار قطع شود. **ادامه از همان‌جا** (نه از اول):

```
sftp mahru
sftp> cd /srv/mahru/images
sftp> reput build-images\mahru-single-<commit>.tar
sftp> bye
```

بعد درستی فایل: روی ویندوز `certutil -hashfile build-images\mahru-single-<commit>.tar SHA256` و روی سرور
`sha256sum /srv/mahru/images/mahru-single-<commit>.tar` باید یکی باشند.

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
- **`CENTRAL_DOMAIN`:** فقط وقتی گواهی `*.arisheh.ir` در بخش ۸ صادر شد نگهشان دارید. تا آن موقع (یا اگر پلن پایه
  گواهی wildcard نداد) آن را خالی کنید (`CENTRAL_DOMAIN=`؛ `SESSION_DOMAIN` از ۲۰۲۶-۱۱-۰۵ خوانده نمی‌شود) تا آدرس سالن‌ها `https://arisheh.ir/s/{slug}` باشد.
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

گواهی `arisheh.ir` و `*.arisheh.ir` با **DNS-01** (wildcard فقط با DNS-01 صادر می‌شود) و Let's Encrypt. acme.sh افزونه‌ی `dns_arvan` دارد:
رکورد TXT ‏`_acme-challenge` را با API آروان می‌سازد و بعد پاک می‌کند. نیاز: اینترنت بین‌الملل سرور (`get.acme.sh`، GitHub، Let's Encrypt؛
آزمون بخش ۲ج) و API آروان (داخلی).

**کلید API آروان:** فقط برای همین دامنه و فقط DNS — پنل ← IAM ← مدیریت منابع و دسترسی‌ها ← یک «ماشین یوزر» (اینجا `mahru-acme-dns`) با
قانونی روی منبع دامنه‌ی `arisheh.ir` و نقش‌های «مدیر DNS» و «بیننده دامنه» (در توضیحات قانون نقطه و پرانتز ننویسید:
`validation.xss_safe`). متن کپی‌شده از پنل خودش با `apikey ` شروع می‌شود؛ همان را کامل بگذارید (افزونه با یا بی پیشوند می‌پذیرد).
acme.sh کلید را در `/root/.acme.sh/account.conf` (فقط root) نگه می‌دارد تا تمدید خودکار کار کند؛ دسترسی کلید فقط DNS همین دامنه است.

```bash
# ۱) نصب (یک بار) — کرون تمدید روزانه را هم خودش می‌گذارد
curl -fsSL https://get.acme.sh | sh -s email=YOUR_EMAIL
. ~/.bashrc
~/.acme.sh/acme.sh --set-default-ca --server letsencrypt
crontab -l | grep acme          # باید یک خط «acme.sh --cron» باشد

# ۲) کلید API (فاصله‌ی اول خط = در history نمی‌ماند)
 export Arvan_Token="apikey xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"

# ۳) آزمون روی staging (سقف خطای Let's Encrypt را نمی‌خورد؛ گواهی‌اش معتبر نیست)
~/.acme.sh/acme.sh --issue --staging --dns dns_arvan -d arisheh.ir -d '*.arisheh.ir' --keylength ec-256 --dnssleep 120

# ۴) گواهی واقعی (--force: جای گواهی staging همان دامنه)
~/.acme.sh/acme.sh --issue --force --dns dns_arvan -d arisheh.ir -d '*.arisheh.ir' --keylength ec-256 --server letsencrypt --dnssleep 120

# ۵) نصب در /srv/mahru/tls + hook تمدید (بار اول کانتینر را restart می‌کند تا nginx پورت ۴۴۳ را روشن کند)
~/.acme.sh/acme.sh --install-cert -d arisheh.ir --ecc \
    --fullchain-file /srv/mahru/tls/fullchain.pem --key-file /srv/mahru/tls/privkey.pem \
    --reloadcmd "bash /srv/mahru/deploy/single/tls-reload.sh"
unset Arvan_Token

# ۶) آزمون
curl -sI https://arisheh.ir/up | head -1                         # HTTP/2 200 (بی -k: زنجیره‌ی گواهی واقعاً معتبر است)
curl -sI http://arisheh.ir/ | grep -iE '^(HTTP|location)'         # 301 ← https://arisheh.ir/
echo | openssl s_client -connect arisheh.ir:443 -servername demo.arisheh.ir 2>/dev/null | openssl x509 -noout -subject -ext subjectAltName -dates
```

**چرا `--dnssleep 120`:** بی آن acme.sh پیش از درخواست به Let's Encrypt رکورد TXT را با DoH (`cloudflare-dns.com`، `dns.google`) می‌پرسد؛ از سرور
پارس وی دی اس DoH حتی با بین‌الملل باز بسته است (`curl` خطای ۲۸، «No DOH») و حلقه تمام نمی‌شود (۲۰۲۶-۱۱-۱۱). با `--dnssleep` فقط دو دقیقه
صبر می‌کند و خود Let's Encrypt از NSهای آروان می‌خواند؛ acme.sh این گزینه را برای تمدید نگه می‌دارد. پیش از صدور:
`dig +short TXT _acme-challenge.arisheh.ir @i.ns.arvancdn.ir`. اگر Let's Encrypt خطای `Verify error` داد، `--dnssleep 300`. با `--debug 2` جزئیات API آروان دیده می‌شود. رکوردهای TXT را خود افزونه پاک می‌کند؛ اگر نیمه‌کاره ماند، از
پنل DNS آروان رکوردهای `_acme-challenge` را دستی بردارید.

**تمدید:** کرون acme.sh هر روز سر می‌زند و ۶۰ روز پس از صدور (۳۰ روز پیش از انقضای ۹۰ روزه) تمدید می‌کند؛ بعد `tls-reload.sh` با
`nginx -t` و `nginx -s reload` بی قطعی جایگزین می‌کند. آزمون بی صدور تازه: `~/.acme.sh/acme.sh --list` (ستون Renew)، و
`~/.acme.sh/acme.sh --cron --home ~/.acme.sh` (چیزی تمدید نمی‌شود مگر موعدش رسیده باشد). در قطعی بین‌الملل تمدید شکست می‌خورد ولی
۳۰ روز فرصت هست؛ کرون هر روز دوباره تلاش می‌کند.

**`tls-reload.sh` (`deploy/single/`، با WinSCP/scp کنار `server-deploy.sh`):** دسترسی‌ها را درست می‌کند (`privkey.pem` ‏۶۰۰)؛ اگر nginx
کانتینر هنوز HTTPS ندارد (اولین گواهی) `docker restart mahru`، وگرنه `nginx -t` و reload. چرا restart: `docker/nginx-setup.sh` فقط هنگام
شروع کانتینر `tls.conf` و هدایت ۸۰ ← ۴۴۳ را روشن می‌کند؛ `up -d` بی تغییر کانتینر را دوباره نمی‌سازد (پس nginx-setup هم دوباره اجرا
نمی‌شود)، و `up -d --force-recreate` تغییرهای `docker cp` لایه‌ی کانتینر را دور می‌ریزد (بخش ۱۳).

وابستگی به آروان فقط در **صدور و تمدید** است؛ اگر DNS جای دیگری رفت، همان دستور با افزونه‌ی DNS تازه (`--dns dns_…`). بدون API DNS
می‌شود فقط `arisheh.ir` و `www` را با HTTP-01 گرفت (`--webroot`؛ نیاز به mount ‏`./acme:/data/acme:ro` در `docker-compose.server.yml` که
فعلاً نیست)، ولی ساب‌دامین سالن‌ها پوشش ندارند (بخش ۹ب).

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

**اگر گواهی `*.arisheh.ir` صادر نشد:** ساب‌دامین‌ها خطای گواهی می‌دهند. در `.env.server` ‏`CENTRAL_DOMAIN=` را خالی کنید و
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
| `https://arisheh.ir/<نام>.txt` (فایل تأیید اینماد) | بعد از بارگذاری در «تنظیمات پلتفرم ← فایل تأیید مالکیت دامنه»: ۲۰۰، `text/plain`، همان متن (بخش ۱۰ج) |
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

| هدرهای امنیتی | `curl -sI https://arisheh.ir/ \| grep -iE 'strict-transport\|x-frame\|referrer-policy\|permissions-policy\|content-security'` — همه هستند (CSP اجرایی از ۲۰۲۶-۱۰-۳۱؛ اگر صفحه‌ای شکست: `storage/logs/csp-*.log` و موقتاً `SECURITY_CSP_STRICT_SCRIPTS=report` (فقط اسکریپت‌ها؛ از ۲۰۲۶-۱۱-۰۵ بی `'unsafe-inline'`) یا `SECURITY_CSP=report`) |
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


## ۱۰ج. اینماد: تأیید مالکیت دامنه با فایل txt

سرویس ایمیل روی سرور نصب نمی‌شود، پس تأیید با ایمیل `info@arisheh.ir` ممکن نیست؛ روش **«بارگذاری فایل»** پنل اینماد:

1. پیش‌نیاز (اینماد سایت کارا می‌خواهد): HTTPS (بخش ۸الف)، صفحه‌های «قوانین»، «حریم خصوصی» و «تماس با ما» با تلفن ثابت، ایمیل و نشانی
   (پنل ابرمدیر ← «تنظیمات پلتفرم ← اطلاعات حقوقی و اینماد»)، قیمت پلن‌ها روی صفحه‌ی فروش، و **چرخه‌ی کامل خرید پلن** (انتخاب ← ثبت‌نام با
   کد پیامکی ← پرداخت) که یعنی کاوه‌نگار واقعی (`SMS_LOG_ONLY=false`).
2. پنل اینماد ← کسب‌وکار ← دامنه‌ی `arisheh.ir` ← تأیید مالکیت ← «فایل»: فایل txt را دانلود کنید (نامش را عوض نکنید).
3. پنل ابرمدیر ← «تنظیمات پلتفرم» ← کارت **«فایل تأیید مالکیت دامنه (اینماد)»** ← بارگذاری ← ذخیره. اگر اینماد فقط نام و متن را نشان داد،
   «دستی» همان کارت. در یک زمان فقط یک فایل؛ فایل تازه جای قبلی را می‌گیرد.
4. آزمون از ویندوز: `curl.exe -si https://arisheh.ir/<نام>.txt` ← `HTTP/1.1 200`، `Content-Type: text/plain`، همان متن (بی redirect).
5. پنل اینماد ← «بررسی». بعد از تأیید فایل را برندارید.

فایل در جدول `platform_settings` است (نه در image و نه در `public/`)، پس با deploy تازه نمی‌رود و با پشتیبان دیتابیس برمی‌گردد.
---

## ۱۱. پشتیبان و دانلود دستی

هر شب ۰۳:۴۰ (به وقت تهران) کانتینر در `/srv/mahru/backups/<تاریخ>/` می‌سازد:

| فایل | چه چیزی |
|---|---|
| `database.sql.gz.age` | کل دیتابیس |
| `storage-app.tar.gz.age` | آپلودها: لوگو، عکس متخصص، گالری، **عکس‌های قبل/بعد دوره‌ها (داده‌ی حساس چهره)**، خروجی گزارش‌ها؛ **بی APKهای «نسخه‌های اپ»** (اصلشان روی ویندوز؛ پس از بازگردانی دوباره بارگذاری شوند، `BACKUP.md`) |
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

بعد پنل ابرمدیر ← «نسخه‌های اپ»: ردیف‌ها را حذف و APK آخر هر اپ را دوباره بارگذاری کنید (APKها در پشتیبان نیستند).

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
| اعمال تغییر `.env.server` | `docker compose -f docker-compose.server.yml up -d` (`restart` کافی نیست؛ کانتینر از نو ساخته می‌شود) |
| فضای دیسک (هفته‌ای یک بار) | `df -h /` — بالای ۷۰٪: پشتیبان‌ها و imageهای قدیمی (بخش ۱۲)؛ بالای ۸۵٪: دیسک بیشتر |
| پشتیبان دستی | `docker exec -u www mahru mahru-backup now` |
| خاموش (داده می‌ماند) | `docker compose -f docker-compose.server.yml down` |

⚠️ هرگز `down -v` نزنید: volume دیتابیس و آپلودها پاک می‌شود.

**وصله‌ی فوری یک فایل بی build تازه** (مثلاً یک view؛ فقط تا deploy بعدی):

```bash
docker cp <فایل> mahru:/var/www/html/<مسیر همان فایل>
docker exec mahru chown www:www /var/www/html/<مسیر همان فایل>
docker exec -u www mahru php artisan view:cache      # اگر view است (یا config:cache / route:cache)
docker exec mahru supervisorctl restart php-fpm      # ⚠️ لازم: OPcache زمان فایل را نمی‌سنجد
```

`docker/php/php.ini` در image ‏`opcache.validate_timestamps = 0` دارد؛ بی ری‌استارت php-fpm، PHP همان نسخه‌ی قدیمی را از حافظه می‌دهد (درس
۲۰۲۶-۱۱-۱۰). این تغییر در لایه‌ی خود کانتینر است: `docker restart mahru` آن را نگه می‌دارد، ولی ساختن دوباره‌ی کانتینر
(`server-deploy.sh`، `up -d --force-recreate` یا تغییر `.env.server` + `up -d`) آن را دور می‌ریزد. پس همان تغییر باید پچ و در image بعدی باشد.

---

## ۱۴. عیب‌یابی

| نشانه | علت و راه |
|---|---|
| `Restarting (1)` و در لاگ `can't create /var/www/html/.env: Permission denied` | image پیش از `0383` روی میزبان با `fs.protected_regular=1` (اوبونتو): هر `docker restart` یا ری‌استارت سرور. راه فوری: `docker compose -f docker-compose.server.yml up -d --force-recreate` (کانتینر تازه)؛ راه اصلی: image تازه |
| `server-deploy.sh`: «healthy نشد» | لاگ چاپ‌شده؛ معمولاً migrate یا `.env.server` (خط خالی `APP_KEY=`/`DB_PASSWORD=`) |
| ۵۲۰ یا ۵۲۲ از آروان | کانتینر بالا نیست یا فایروال پورت ۸۰ را برای لبه‌ی آروان هم بسته (`docker ps`، بخش ۹) |
| خطای گواهی روی ساب‌دامین | گواهی `*.arisheh.ir` نیست (بخش ۸) |
| ۴۱۹ Page Expired | فرم از صفحه‌ی کهنه‌ی میزبان دیگر آمده (صفحه را تازه کنید)، یا صفحه از کش CDN آمده (بخش ۸ «کش») |
| لاگ امنیتی IP آروان را نشان می‌دهد | `TRUSTED_PROXIES` (بخش ۵)، بعد `up -d` |
| پیامک واقعی رفت | `SMS_LOG_ONLY=true` نبود؛ `server-deploy.sh` هشدار می‌دهد |
| «آخرین پشتیبان‌گیری» قرمز | `docker logs mahru 2>&1 \| grep backup` و `/data/storage/app/backup-status.json` |

---

## ۱۵. آنچه تأییدنشده است (روی سرور واقعی بسنجید)

- (بسته‌ی مجوز 5د) صدور گواهی `*.arisheh.ir` با `dns_arvan` روی پلن پایه (بخش ۸الف، ۲۰۲۶-۱۱-۱۱ در حال اجرا روی پارس وی دی اس) و
  اتصال «HTTPS به مبدا» لبه‌ی آروان به گواهی خود سرور (فقط اگر CDN روشن شد)؛ HTTPS و هدایت ۸۰ در sandbox با گواهی خودامضا و nginx و
  php-fpm واقعی آزموده شد، خود image آلپاین نه. `tls-reload.sh` با docker ساختگی آزموده شد (`TlsReloadTest`).
- (بسته‌ی مجوز 5د) نام دقیق کنسول وب و بازنشانی رمز در پنل سرور ابری آروان.

- آدرس فهرست IP آروان (`/en/ips.txt`) — ابزارهای متن‌باز همین را استفاده می‌کنند؛ از بیرون ایران برای Claude بسته بود.
- پروکسی روی رکورد `*` و صدور گواهی `*.arisheh.ir` در پلن پایه.
- هدرهایی که آروان به مبدا می‌فرستد (`X-Forwarded-For`، `X-Forwarded-Proto`، Host اصلی) — با آزمون‌های بخش ۱۰.
- نام دقیق گزینه‌ها در پنل.
