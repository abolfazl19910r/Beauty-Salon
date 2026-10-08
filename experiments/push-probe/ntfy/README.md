# سرور ntfy لوکال — آزمایش راه بدون گوگل (برای قطعی اینترنت بین‌الملل)

در قطعی اینترنت بین‌الملل گوشی به FCM گوگل نمی‌رسد ولی سرورهای داخل ایران در دسترس‌اند. ntfy خودمیزبان این حالت را
می‌سنجد: اپ رسمی ntfy برای هر سروری غیر از ntfy.sh یک اتصال دائمی به همان سرور نگه می‌دارد و FCM در کار نیست.

**این آزمایش روی شبکه‌ی محلی است** (سرور = سیستم شما، گوشی روی همان Wi‑Fi). دو سؤالش:
1. آیا اتصال دائمی ntfy از مدیریت باتری One UI و MIUI جان سالم به در می‌برد (اپ بسته، شب، ریستارت)؟ هزینه‌ی باتری چقدر است؟
2. شبیه‌سازی قطعی: با قطع اینترنت مودم (شبکه‌ی محلی سالم)، FCM نمی‌رسد و ntfy می‌رسد؟

برای استفاده‌ی واقعی (گوشی مشتری روی اینترنت همراه) سرور ntfy باید روی یک سرور مجازی داخل ایران باشد — هاست اشتراکی
DirectAdmin نمی‌تواند برنامه‌ی همیشه‌روشن اجرا کند. این برای بسته‌ی ۴ تصمیم جداست.

## ۱. اجرای سرور روی ویندوز
سرور ntfy روی خود ویندوز اجرا نمی‌شود؛ یکی از این دو:

**الف) Docker Desktop** (اگر دارید):
موتور Docker Desktop به WSL2 نیاز دارد. اگر `docker version` بخش Server نداشت یا خطای `dockerDesktopLinuxEngine` آمد و `wsl --status`
گفت WSL نصب نیست: CMD با Run as administrator ← `wsl --install --no-distribution` ← ریستارت ← باز کردن Docker Desktop تا «Engine running».
(اجرای ۲۰۲۶-۱۰-۰۸: image از Docker Hub بدون mirror و بدون VPN در حدود ۲٫۵ دقیقه گرفته شد.)
```bash
cd experiments/push-probe/ntfy
docker compose up -d
curl http://127.0.0.1:8090/v1/health        # {"healthy":true}
```
کش پیام‌ها (`cache.db`) در volume ‏`ntfy-cache` خود Docker است. پاک کردن کامل آن (مثلاً برای شروع تمیز یک آزمایش):
`docker compose down -v` و دوباره `docker compose up -d`. اگر `push:probe ntfy` خطای 500 داد: `docker compose logs --tail=40`.

**ب) WSL (Ubuntu):** فایل `ntfy_<نسخه>_linux_amd64.deb` را از صفحه‌ی Releases مخزن `binwiederhier/ntfy` در GitHub بگیرید و:
```bash
sudo dpkg -i ntfy_*_linux_amd64.deb
sudo cp server.yml /etc/ntfy/server.yml && sudo sed -i 's/":80"/":8090"/; s#/var/cache/ntfy#/tmp/ntfy#' /etc/ntfy/server.yml
mkdir -p /tmp/ntfy && sudo ntfy serve
```
(در WSL2 ممکن است برای دیده شدن از گوشی، port forwarding ویندوز لازم باشد — Docker Desktop ساده‌تر است.)

## ۲. باز کردن پورت برای گوشی
- IP سیستم در شبکه‌ی محلی: `ipconfig` ← «IPv4 Address» (مثلاً `192.168.1.10`).
- فایروال (CMD با Run as administrator):
  `netsh advfirewall firewall add rule name="ntfy probe" dir=in action=allow protocol=TCP localport=8090`
- از مرورگر گوشی `http://192.168.1.10:8090` را باز کنید؛ باید صفحه‌ی وب ntfy بیاید. اگر نیامد، «AP isolation» مودم را خاموش کنید.

## ۳. گوشی‌ها
1. اپ رسمی **ntfy** را نصب کنید (F-Droid یا APK از Releases مخزن `binwiederhier/ntfy-android`؛ نسخه‌ی Google Play هم برای سرور
   خودمیزبان همان اتصال دائمی را دارد).
2. در اپ آزمایشی ماهرو، بخش ntfy: آدرس `http://192.168.1.10:8090` را بنویسید ← «اشتراک تاپیک در اپ ntfy» ← در ntfy تأیید.
3. ntfy یک اعلان دائمی «instant delivery» نشان می‌دهد — همین اتصال دائمی است؛ خاموشش نکنید.
4. تنظیمات باتری بخش ۳ `FIREBASE_AND_PHONES.md` این‌بار برای **اپ ntfy** است، نه اپ ماهرو.

## ۴. سیستم
در `.env` پروژه: `PUSH_PROBE_NTFY_SERVER=http://127.0.0.1:8090` ← `php artisan config:clear` ← `php artisan push:probe check`
(ردیف «سرور ntfy» باید «موفق» باشد) ← `php artisan push:probe ntfy <تاپیک> --label=A17-بسته`.
