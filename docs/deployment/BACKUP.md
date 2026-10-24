# پشتیبان‌گیری ماهرو

دو چیز باید پشتیبان داشته باشد: **دیتابیس** و **فایل‌های آپلودی** زیر `storage/app`:

| پوشه | چه چیزی | حساسیت |
|---|---|---|
| `storage/app/course-photos` | عکس‌های قبل/بعد جلسه‌های دوره‌ی درمانی (دیسک خصوصی `course_photos`) | **بالا** — چهره/بدن مشتری؛ فقط با رضایت او گرفته شده |
| `storage/app/public` | لوگوی سالن، عکس متخصص‌ها، گالری، تصویر خدمات و بلاگ | عادی |
| `storage/app/private` | خروجی گزارش‌ها و فایل‌های خصوصی دیگر | متوسط |

بدون پشتیبان فایل‌ها، بازگردانی فقط دیتابیس ردیف‌هایی برمی‌گرداند که فایلشان نیست (عکس‌های صفحه‌ی دوره و لوگوها باز نمی‌شوند).

## رمزنگاری پشتیبان‌ها (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵)

پشتیبان دیتابیس، عکس مشتری‌ها و `APP_KEY` را دارد؛ اگر کسی سرور یا فایل دانلودشده را بردارد، همه چیز باز است. پس پشتیبان با
[age](https://github.com/FiloSottile/age) و **کلید عمومی** رمزنگاری می‌شود. روی سرور فقط کلید عمومی است؛ **کلید خصوصی جدا از سرور و
جدا از `APP_KEY`** فقط پیش شماست. هر فایل در همان جریان رمز می‌شود (نسخه‌ی باز هرگز روی دیسک سرور نوشته نمی‌شود):
`database.sql.gz.age`، `storage-app.tar.gz.age`، `app.key.age`.

**ساخت کلید (یک بار، روی ویندوز خودتان):**
1. `age` را از صفحه‌ی Releases پروژه‌ی age (فایل `age-v…-windows-amd64.zip`) بگیرید و `age.exe` و `age-keygen.exe` را در یک پوشه بگذارید.
2. `age-keygen.exe -o mahru-backup-key.txt` — خروجی خط `Public key: age1…` را بردارید.
3. `mahru-backup-key.txt` (کلید خصوصی) را **دو جا بیرون از سرور** نگه دارید (مثلاً فلش و یک مدیر رمز). گم شدنش یعنی هیچ پشتیبانی باز نمی‌شود.
4. در `.env.server` (یا `.env` روی DirectAdmin): `BACKUP_AGE_RECIPIENT=age1…`. چند کلید (مثلاً دو نفر) با کاما.
   `BACKUP_REQUIRE_ENCRYPTION=true` (پیش‌فرض سرور) یعنی بدون کلید عمومی پشتیبانی ساخته نمی‌شود و داشبورد ابرمدیر «ناموفق» نشان می‌دهد.
   داشبورد کنار «آخرین پشتیبان‌گیری» «رمزنگاری‌شده» یا «بدون رمزنگاری» می‌نویسد.

**باز کردن روی ویندوز:** `age.exe -d -i mahru-backup-key.txt -o database.sql.gz database.sql.gz.age` (برای دو فایل دیگر هم همین).

## سرور بدون Docker (DirectAdmin)

اسکریپت `deploy/backup/mahru-backup.sh` هر بار یک پوشه‌ی تاریخ‌دار می‌سازد: `database.sql.gz` و `storage-app.tar.gz`. پشتیبان‌های قدیمی‌تر از
`KEEP_DAYS` (پیش‌فرض ۱۴) روز پاک می‌شوند. رمز دیتابیس از `.env` خوانده می‌شود و در خط فرمان نمی‌آید.

cron (DirectAdmin → Cron Jobs)، هر شب ۰۳:۴۰:

```
40 3 * * * bash /home/[username]/public_html/deploy/backup/mahru-backup.sh /home/[username]/public_html /home/[username]/mahru-backups >> /home/[username]/mahru-backups/backup.log 2>&1
```

- پوشه‌ی پشتیبان **هرگز** زیر `public_html` نباشد. فایل‌ها با دسترسی ۶۰۰ ساخته می‌شوند.
- هر اجرا (موفق یا ناموفق) `storage/app/backup-status.json` را می‌نویسد؛ داشبورد ابرمدیر «آخرین پشتیبان‌گیری» را از آن نشان می‌دهد و
  اگر ناموفق یا قدیمی‌تر از `BACKUP_STALE_HOURS` (پیش‌فرض ۳۶ ساعت) باشد هشدار می‌دهد. پشتیبان‌گیری Docker (`make backup`) این فایل را نمی‌نویسد.
- یک نسخه را بیرون از همان سرور هم نگه دار (مثلاً هفته‌ای یک بار دانلود یا rsync به سرور دیگر) — پشتیبان روی همان دیسک با خرابی دیسک از بین می‌رود.

## Docker تک‌کانتینری (سرور آروان)

کانتینر خودش هر شب (`BACKUP_AT`، پیش‌فرض سرور ۰۳:۴۰) همین اسکریپت را اجرا می‌کند و در `/data/backups` (روی سرور `/srv/mahru/backups`)
`database.sql.gz`، `storage-app.tar.gz` و `app.key` می‌گذارد؛ فقط `BACKUP_KEEP` نسخه‌ی آخر می‌ماند و `backup-status.json` هم نوشته می‌شود.
دستی: `docker exec -u www mahru mahru-backup now`. دانلود و بازگردانی: `docs/deployment/ARVAN_SERVER.md` بخش ۱۱.

## Docker شش‌سرویسی

```
make backup        # هر دو
make backup-db     # فقط دیتابیس → backups/beauty_salon_*.sql
make backup-files  # فقط storage/app → backups/storage_app_*.tar.gz
```

`backups/` در `.gitignore` است.

## بازگردانی

اگر پشتیبان رمزنگاری‌شده است (`.age`)، اول بازش کنید — روی ویندوز (بالا) یا روی سرور با کلیدی که **موقت** آنجا گذاشته‌اید و بعد پاک
می‌کنید (`age -d -i key.txt database.sql.gz.age | gunzip | mysql …`؛ روی اوبونتو: `apt install age`).

```
# دیتابیس
gunzip -c database.sql.gz | mysql -u USER -p DATABASE
# فایل‌ها (از ریشه‌ی برنامه)
tar -xzf storage-app.tar.gz -C /home/[username]/public_html
chmod -R u+rwX,go-rwx storage/app/course-photos
php artisan storage:link   # اگر لینک public/storage نیست
```

بعد از بازگردانی: `php artisan config:cache` و `php artisan queue:restart`.
