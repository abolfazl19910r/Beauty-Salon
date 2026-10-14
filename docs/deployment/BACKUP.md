# پشتیبان‌گیری ماهرو

دو چیز باید پشتیبان داشته باشد: **دیتابیس** و **فایل‌های آپلودی** زیر `storage/app`:

| پوشه | چه چیزی | حساسیت |
|---|---|---|
| `storage/app/course-photos` | عکس‌های قبل/بعد جلسه‌های دوره‌ی درمانی (دیسک خصوصی `course_photos`) | **بالا** — چهره/بدن مشتری؛ فقط با رضایت او گرفته شده |
| `storage/app/public` | لوگوی سالن، عکس متخصص‌ها، گالری، تصویر خدمات و بلاگ | عادی |
| `storage/app/private` | خروجی گزارش‌ها و فایل‌های خصوصی دیگر | متوسط |

بدون پشتیبان فایل‌ها، بازگردانی فقط دیتابیس ردیف‌هایی برمی‌گرداند که فایلشان نیست (عکس‌های صفحه‌ی دوره و لوگوها باز نمی‌شوند).

## سرور بدون Docker (DirectAdmin)

اسکریپت `deploy/backup/mahru-backup.sh` هر بار یک پوشه‌ی تاریخ‌دار می‌سازد: `database.sql.gz` و `storage-app.tar.gz`. پشتیبان‌های قدیمی‌تر از
`KEEP_DAYS` (پیش‌فرض ۱۴) روز پاک می‌شوند. رمز دیتابیس از `.env` خوانده می‌شود و در خط فرمان نمی‌آید.

cron (DirectAdmin → Cron Jobs)، هر شب ۰۳:۴۰:

```
40 3 * * * bash /home/[username]/public_html/deploy/backup/mahru-backup.sh /home/[username]/public_html /home/[username]/mahru-backups >> /home/[username]/mahru-backups/backup.log 2>&1
```

- پوشه‌ی پشتیبان **هرگز** زیر `public_html` نباشد. فایل‌ها با دسترسی ۶۰۰ ساخته می‌شوند.
- یک نسخه را بیرون از همان سرور هم نگه دار (مثلاً هفته‌ای یک بار دانلود یا rsync به سرور دیگر) — پشتیبان روی همان دیسک با خرابی دیسک از بین می‌رود.

## Docker

```
make backup        # هر دو
make backup-db     # فقط دیتابیس → backups/beauty_salon_*.sql
make backup-files  # فقط storage/app → backups/storage_app_*.tar.gz
```

`backups/` در `.gitignore` است.

## بازگردانی

```
# دیتابیس
gunzip -c database.sql.gz | mysql -u USER -p DATABASE
# فایل‌ها (از ریشه‌ی برنامه)
tar -xzf storage-app.tar.gz -C /home/[username]/public_html
chmod -R u+rwX,go-rwx storage/app/course-photos
php artisan storage:link   # اگر لینک public/storage نیست
```

بعد از بازگردانی: `php artisan config:cache` و `php artisan queue:restart`.
