import { enDigits } from '../format';

/**
 * ورودی «ورود به سالن» (QR، لینک چسبانده‌شده، کد تایپ‌شده یا لینک mahru://salon/{slug}) → slug سالن.
 * شکل‌های پذیرفته:
 *   rasta                                 کد سالن (= slug، قاعده‌ی alpha_dash لاراول)
 *   https://host/s/rasta[/...]             لینک قدیمی/همیشه‌زنده‌ی سالن (همان QR روی سرور لوکال)
 *   https://rasta.mahru.ir[/...]          ساب‌دامین سالن (وقتی CENTRAL_DOMAIN روی سرور پر است)
 *   mahru://salon/rasta                   لینک اپ (دکمه‌ی «باز کردن در اپ» صفحه‌ی وب سالن)
 * origin فقط برای لینک‌های http(s) برگردانده می‌شود تا نسخه‌ی آزمایشی بتواند سرور همان QR را پیشنهاد کند.
 */
export type ParsedSalon = { slug: string; origin: string | null };

/** حروف/رقم/خط تیره/زیرخط (یونیکد، مثل alpha_dash) — بدون فاصله و / */
const SLUG = /^[\p{L}\p{M}\p{N}_-]{1,100}$/u;

/** برچسب‌های میزبان که slug سالن نیستند */
const NOT_SALON = new Set(['www', 'app', 'api', 'admin', 'mahru']);

export function isSalonSlug(value: string): boolean {
  return SLUG.test(value);
}

export function parseSalonInput(raw: string): ParsedSalon | null {
  const input = enDigits(raw ?? '').trim();
  if (!input) {
    return null;
  }

  const app = /^[a-z][a-z0-9+.-]*:\/\/salon\/([^/?#]+)/i.exec(input);
  if (app && !/^https?:/i.test(input)) {
    const slug = safeDecode(app[1]);
    return slug && isSalonSlug(slug) ? { slug, origin: null } : null;
  }

  // «rasta.mahru.ir/...» بدون http هم لینک است
  const looksLikeUrl = /^https?:\/\//i.test(input) || /^[^\s/]+\.[^\s/]+(\/|$)/.test(input);
  if (!looksLikeUrl) {
    return isSalonSlug(input) ? { slug: input, origin: null } : null;
  }

  const m = /^(?:(https?):\/\/)?([^/?#\s]+)([^?#\s]*)/i.exec(input);
  if (!m) {
    return null;
  }
  const scheme = (m[1] ?? 'https').toLowerCase();
  const host = m[2];
  const path = m[3] ?? '';
  const origin = `${scheme}://${host}`;

  const legacy = /^\/s\/([^/]+)/.exec(path);
  if (legacy) {
    const slug = safeDecode(legacy[1]);
    return slug && isSalonSlug(slug) ? { slug, origin } : null;
  }

  // ساب‌دامین: اولین برچسب میزبانی که دست‌کم سه بخش دارد و آی‌پی نیست
  const hostname = host.replace(/:\d+$/, '').toLowerCase();
  const labels = hostname.split('.');
  if (labels.length >= 3 && !/^\d+$/.test(labels[labels.length - 1]) && !NOT_SALON.has(labels[0]) && isSalonSlug(labels[0])) {
    return { slug: labels[0], origin: `${scheme}://${labels.slice(1).join('.')}${host.match(/:\d+$/)?.[0] ?? ''}` };
  }

  return null;
}

function safeDecode(value: string): string | null {
  try {
    return decodeURIComponent(value);
  } catch {
    return null;
  }
}
