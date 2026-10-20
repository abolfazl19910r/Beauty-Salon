/*
 * service worker سایت سالن و پنل متخصص ماهرو (بسته‌ی ۶) — نسخه: __VERSION__ (از manifest ساخت Vite؛ ساخت تازه = کش تازه).
 * فقط این‌ها کش می‌شوند: فایل‌های ثابت هش‌دار (/build/assets)، فونت‌ها و آیکون‌های برند (اول کش)، و صفحه‌ی «اینترنت قطع است».
 * صفحه‌ها همیشه از شبکه می‌آیند (اطلاعات حساب، توکن CSRF و نوبت‌ها هرگز کهنه نمی‌شوند)؛ فقط اگر شبکه نبود صفحه‌ی آفلاین.
 * درخواست‌های غیر GET، API و میزبان‌های دیگر (درگاه بانک) دست نمی‌خورند.
 */
const CACHE = 'mahru-__VERSION__';
const OFFLINE_URL = '/offline';
const STATIC_PATH = /^\/(build\/assets|fonts|brand)\//;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE)
      .then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith('mahru-') && key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  if (STATIC_PATH.test(url.pathname)) {
    event.respondWith(
      caches.open(CACHE).then(async (cache) => {
        const hit = await cache.match(request);
        if (hit) return hit;
        const response = await fetch(request);
        if (response.ok) cache.put(request, response.clone());
        return response;
      }),
    );
  }
});
