# واسط تلگرام روی Cloudflare Workers

سرور ماهرو داخل ایران به `api.telegram.org` دسترسی ندارد. این Worker بیرون از ایران درخواست‌های Bot API را به تلگرام می‌رساند.
کد Laravel تغییری لازم ندارد؛ فقط `TELEGRAM_API_BASE` عوض می‌شود.

```
سرور ماهرو (ایران) ──> https://tg.mahru.ir/<PROXY_SECRET>/bot<TOKEN>/sendMessage ──> api.telegram.org
```

## پیش‌نیاز
- حساب Cloudflare و دامنه‌ای که DNS آن روی Cloudflare است (در `wrangler.toml` زیردامنه‌ی `tg.mahru.ir` فرض شده — عوضش کنید).
- Node.js 18+ روی سیستم خودتان (نه روی سرور).

## راه‌اندازی
```bash
cd deploy/cloudflare/telegram-proxy
npx wrangler login
npx wrangler secret put TELEGRAM_BOT_TOKEN     # توکن ربات تلگرام از BotFather
npx wrangler secret put PROXY_SECRET           # رشته‌ی تصادفی، دست‌کم ۱۶ حرف (فقط حروف و اعداد)
npx wrangler deploy
```

## تنظیم سرور ماهرو
در `.env`:
```
TELEGRAM_BOT_TOKEN=<همان توکن>
TELEGRAM_BOT_USERNAME=<نام کاربری ربات بدون @>
TELEGRAM_API_BASE=https://tg.mahru.ir/<PROXY_SECRET>
```

تست از خود سرور ایران:
```bash
curl https://tg.mahru.ir/<PROXY_SECRET>/bot<TOKEN>/getMe      # باید "ok":true برگردد
php artisan config:cache
php artisan bot:webhook telegram
```

## نکته‌ها
- **workers.dev استفاده نکنید** — از داخل ایران معمولاً فیلتر است (`workers_dev = false`).
- **مسیر برگشت:** پیام کاربران از تلگرام مستقیم به `https://<دامنه‌ی ماهرو>/api/bot/webhook/telegram/...` می‌آید، نه از این Worker.
  اگر سرور از بیرون در دسترس نباشد (اختلال اینترنت بین‌الملل)، اتصال با «/start» و «/stop» تلگرام کار نمی‌کند؛ بله این مشکل را ندارد.
- **امنیت:** Worker فقط توکن همین ربات و فقط با `PROXY_SECRET` در مسیر را عبور می‌دهد؛ هر چیز دیگر ۴۰۴.
- **هزینه:** پلن رایگان Workers روزانه ۱۰۰ هزار درخواست؛ هر پیام ربات یک درخواست.
- **قوانین هاست:** پیش از راه‌اندازی، شرایط هاست داخلی درباره‌ی اتصال به سرویس‌های فیلترشده را بررسی کنید.
