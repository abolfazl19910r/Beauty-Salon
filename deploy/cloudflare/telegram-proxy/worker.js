/**
 * واسط Bot API تلگرام برای سرور ماهرو داخل ایران (۲۰۲۶-۱۰-۰۱).
 *
 * سرور داخل ایران به api.telegram.org دسترسی ندارد؛ این Worker درخواست را از بیرون ایران به تلگرام می‌رساند:
 *   سرور ماهرو ──> https://<دامنه‌ی Worker>/<PROXY_SECRET>/bot<TOKEN>/<method> ──> https://api.telegram.org/bot<TOKEN>/<method>
 *
 * در .env سرور:  TELEGRAM_API_BASE=https://<دامنه‌ی Worker>/<PROXY_SECRET>
 *
 * فقط توکن همین ربات و فقط با بخش مخفی مسیر عبور می‌کند (پراکسی باز برای بقیه نیست). مسیر برگشت (webhook) از این
 * Worker نمی‌گذرد: تلگرام مستقیم به https://<دامنه‌ی ماهرو>/api/bot/webhook/telegram/<BOT_WEBHOOK_SECRET> می‌زند.
 *
 * Secretها (wrangler secret put): TELEGRAM_BOT_TOKEN، PROXY_SECRET
 */
const METHOD = /^[A-Za-z]{1,64}$/;

export default {
  async fetch(request, env) {
    if (!env.TELEGRAM_BOT_TOKEN || !env.PROXY_SECRET || env.PROXY_SECRET.length < 16) {
      return new Response('Not configured', { status: 500 });
    }

    const url = new URL(request.url);
    const prefix = `/${env.PROXY_SECRET}/bot${env.TELEGRAM_BOT_TOKEN}/`;

    if (!url.pathname.startsWith(prefix)) {
      return new Response('Not found', { status: 404 });
    }

    const method = url.pathname.slice(prefix.length);
    if (!METHOD.test(method) || !['GET', 'POST'].includes(request.method)) {
      return new Response('Not found', { status: 404 });
    }

    const upstream = await fetch(`https://api.telegram.org/bot${env.TELEGRAM_BOT_TOKEN}/${method}${url.search}`, {
      method: request.method,
      headers: { 'content-type': request.headers.get('content-type') || 'application/json' },
      body: request.method === 'GET' ? undefined : request.body,
    });

    // پاسخ تلگرام همان‌طور (وضعیت ۴۰۳ «بلاک شده» برای حذف خودکار اتصال لازم است)
    return new Response(upstream.body, {
      status: upstream.status,
      headers: { 'content-type': upstream.headers.get('content-type') || 'application/json' },
    });
  },
};
