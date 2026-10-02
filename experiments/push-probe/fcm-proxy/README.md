# واسط FCM روی Cloudflare Workers (راه دوم آزمایش پوش)

فقط وقتی لازم است که روی سیستم شما `php artisan push:probe check` در ردیف «توکن دسترسی FCM — مستقیم» **ناموفق** بدهد
(اتصال برقرار نشد، یا 403 از گوگل). همان الگوی واسط تلگرام (`deploy/cloudflare/telegram-proxy`).

```
سیستم ماهرو (ایران) ──> https://push-probe.mahru.ir/<PROXY_SECRET>/token ──────────────> oauth2.googleapis.com
                    ──> https://push-probe.mahru.ir/<PROXY_SECRET>/v1/projects/... ───> fcm.googleapis.com
```

## راه‌اندازی (از سیستم خودتان، Node.js 18+)
1. در `wrangler.toml`، `FCM_PROJECT_ID` را برابر `project_id` فایل حساب سرویس و `pattern` را زیردامنه‌ی خودتان کنید.
2. ```bash
   cd experiments/push-probe/fcm-proxy
   npx wrangler login
   npx wrangler secret put PROXY_SECRET    # رشته‌ی تصادفی، دست‌کم ۱۶ حرف (فقط حروف و اعداد)
   npx wrangler deploy
   ```
3. در `.env`: `PUSH_PROBE_FCM_PROXY=https://push-probe.mahru.ir/<PROXY_SECRET>` و بعد `php artisan config:clear`.
4. `php artisan push:probe check` → ردیف «توکن دسترسی FCM — از Worker» باید «موفق» باشد؛ بعد `push:probe fcm-proxy <توکن>`.

## نکته‌ها
- **workers.dev استفاده نکنید** — از داخل ایران معمولاً فیلتر است (`workers_dev = false`).
- Worker کلیدی نگه نمی‌دارد؛ کلید حساب سرویس فقط روی سیستم شماست و فقط JWT امضاشده و توکن کوتاه‌عمر از Worker رد می‌شوند.
- این Worker فقط مسیر **سرور → گوگل** را حل می‌کند. مسیر **گوگل → گوشی** همان FCM است و در قطعی اینترنت بین‌الملل با Worker هم نمی‌رسد.
