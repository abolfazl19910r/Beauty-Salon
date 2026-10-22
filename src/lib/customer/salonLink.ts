import { enDigits } from '../format';

/**
 * ورودی «ورود به سالن» (QR، لینک چسبانده‌شده، کد تایپ‌شده یا لینک mahru://salon/{slug}) → slug سالن.
 * شکل‌های پذیرفته:
 *   rasta                                 کد سالن (= slug، قاعده‌ی alpha_dash لاراول)
 *   https://host/s/rasta[/...]             لینک قدیمی/همیشه‌زنده‌ی سالن (همان QR روی سرور لوکال)
 *   https://rasta.arisheh.ir[/...]          ساب‌دامین سالن (وقتی CENTRAL_DOMAIN روی سرور پر است)
 *   mahru://salon/rasta                   لینک اپ (دکمه‌ی «باز کردن در اپ» صفحه‌ی وب سالن)
 *   …/specialists/12 (هر دو شکل بالا)      QR متخصص (بسته‌ی ۵): سالن + specialistId برای رزرو مستقیم با همان متخصص
 * origin فقط برای لینک‌های http(s) برگردانده می‌شود تا نسخه‌ی آزمایشی بتواند سرور همان QR را پیشنهاد کند — جز آدرسی که فقط روی
 * کامپیوتر سازنده‌ی QR باز می‌شود (127.0.0.1، localhost، *.localhost، *.test): پیشنهاد آن، اپ را به خود گوشی وصل می‌کرد (۲۰۲۶-۱۰-۱۷).
 */
export type ParsedSalon = { slug: string; origin: string | null; specialistId?: number | null };

/** حروف/رقم/خط تیره/زیرخط (یونیکد، مثل alpha_dash) — بدون فاصله و / */
const SLUG = /^[\p{L}\p{M}\p{N}_-]{1,100}$/u;

/** برچسب‌های میزبان که slug سالن نیستند */
const NOT_SALON = new Set(['www', 'app', 'api', 'admin', 'mahru']);

/** میزبانی که از گوشی در دسترس نیست (پنل روی همان کامپیوتر باز شده بود) */
export function isLocalOnlyHost(hostname: string): boolean {
  const h = hostname.toLowerCase().replace(/^\[|\]$/g, '');
  return h === 'localhost' || h === '::1' || h === '0.0.0.0' || /^127\./.test(h) || h.endsWith('.localhost') || h.endsWith('.test');
}

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

  // «rasta.arisheh.ir/...» بدون http هم لینک است
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

  const hostname = host.replace(/:\d+$/, '').toLowerCase();
  const localOnly = isLocalOnlyHost(hostname);

  const legacy = /^\/s\/([^/]+)(\/.*)?$/.exec(path);
  if (legacy) {
    const slug = safeDecode(legacy[1]);
    return slug && isSalonSlug(slug) ? { slug, origin: localOnly ? null : origin, ...specialistIn(legacy[2] ?? '') } : null;
  }

  // ساب‌دامین: اولین برچسب میزبانی که دست‌کم سه بخش دارد و آی‌پی نیست (rasta.localhost دو بخش است ولی ساب‌دامین است)
  const labels = hostname.split('.');
  const minLabels = hostname.endsWith('.localhost') ? 2 : 3;
  if (labels.length >= minLabels && !/^\d+$/.test(labels[labels.length - 1]) && !NOT_SALON.has(labels[0]) && isSalonSlug(labels[0])) {
    return {
      slug: labels[0],
      origin: localOnly ? null : `${scheme}://${labels.slice(1).join('.')}${host.match(/:\d+$/)?.[0] ?? ''}`,
      ...specialistIn(path),
    };
  }

  return null;
}

/** «/specialists/12…» در مسیر سالن → { specialistId: 12 }؛ وگرنه هیچ */
function specialistIn(path: string): { specialistId?: number } {
  const m = /^\/specialists\/(\d{1,10})(?:[/?#]|$)/.exec(path);
  return m ? { specialistId: Number(m[1]) } : {};
}

/** مسیر داخل اپ برای یک لینک سالن (App Links و اسکنر) */
export function salonRoute(parsed: ParsedSalon): string {
  const base = `/salon/${encodeURIComponent(parsed.slug)}`;
  return parsed.specialistId ? `${base}?specialist=${parsed.specialistId}` : base;
}

function safeDecode(value: string): string | null {
  try {
    return decodeURIComponent(value);
  } catch {
    return null;
  }
}
