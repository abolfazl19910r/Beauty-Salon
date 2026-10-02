/**
 * واسط FCM برای آزمایش پوش (بسته‌ی ۰ اپلیکیشن، ۲۰۲۶-۱۰-۰۲) — فقط وقتی لازم است که `push:probe check` نشان دهد
 * سیستم/سرور داخل ایران به گوگل نمی‌رسد یا گوگل آن را رد می‌کند.
 *
 *   سیستم ماهرو ──> https://<دامنه>/<PROXY_SECRET>/token                                 ──> https://oauth2.googleapis.com/token
 *   سیستم ماهرو ──> https://<دامنه>/<PROXY_SECRET>/v1/projects/<FCM_PROJECT_ID>/messages:send ──> https://fcm.googleapis.com/...
 *
 * در .env:  PUSH_PROBE_FCM_PROXY=https://<دامنه>/<PROXY_SECRET>
 * فقط همین دو مسیر و فقط برای پروژه‌ی FCM_PROJECT_ID باز است (پراکسی باز نیست). Worker کلید و توکنی نگه نمی‌دارد؛
 * JWT و توکن دسترسی همان‌طور که سیستم ماهرو فرستاده عبور می‌کنند.
 *
 * Secret (wrangler secret put): PROXY_SECRET — متغیر (wrangler.toml): FCM_PROJECT_ID
 */
export default {
  async fetch(request, env) {
    if (!env.PROXY_SECRET || env.PROXY_SECRET.length < 16 || !env.FCM_PROJECT_ID) {
      return new Response('Not configured', { status: 500 });
    }
    if (request.method !== 'POST') {
      return new Response('Not found', { status: 404 });
    }

    const url = new URL(request.url);
    const prefix = `/${env.PROXY_SECRET}`;
    if (!url.pathname.startsWith(prefix + '/')) {
      return new Response('Not found', { status: 404 });
    }

    const path = url.pathname.slice(prefix.length);
    let upstream;
    if (path === '/token') {
      upstream = 'https://oauth2.googleapis.com/token';
    } else if (path === `/v1/projects/${env.FCM_PROJECT_ID}/messages:send`) {
      upstream = `https://fcm.googleapis.com${path}`;
    } else {
      return new Response('Not found', { status: 404 });
    }

    const headers = { 'content-type': request.headers.get('content-type') || 'application/json' };
    const auth = request.headers.get('authorization');
    if (auth) {
      headers.authorization = auth;
    }

    const response = await fetch(upstream, { method: 'POST', headers, body: request.body });

    // پاسخ گوگل همان‌طور (کد خطاها مثل UNREGISTERED برای فرم نتیجه لازم است)
    return new Response(response.body, {
      status: response.status,
      headers: { 'content-type': response.headers.get('content-type') || 'application/json' },
    });
  },
};
