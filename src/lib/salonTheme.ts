import AsyncStorage from '@react-native-async-storage/async-storage';
import { useSyncExternalStore } from 'react';

/**
 * ⭐ بسته‌ی «و» (۲۰۲۶-۱۰-۱۵): قالب سالن در اپ‌ها. سرور در کارت سالن (اپ مشتری) و حساب کادر (اپ همکار) `theme` می‌دهد:
 * کلید، چیدمان، پالت و رنگ‌های هگز تیره/روشن (App\Support\Themes\SalonAppearance::toApi). اپ هنگام باز شدن آخرین قالب ذخیره‌شده را
 * می‌گذارد و با هر پاسخ تازه به‌روزش می‌کند — بی build تازه. «طلایی ماهرو» (mahru) همان رنگ‌های پایه‌ی ui/theme است.
 */
export type ThemeLayout = 'mahru' | 'signature' | 'editorial' | 'showcase' | 'app';

/**
 * بسته‌ی ۵ب (0.4.0): ۴ قالب ترکیبی سرور (شاهکار، روزنامه‌ی سالن، نمایشگاه، اپ) در `template` می‌آیند؛ `layout` برای اپ‌های
 * قدیمی همان کلید پیشین (ویترین/مجله/آرام/بنتو) می‌ماند. سرور قدیمی یا قالب ذخیره‌شده‌ی قدیمی با همین نگاشت خوانده می‌شود.
 */
const LEGACY_LAYOUTS: Record<string, ThemeLayout> = { vitrin: 'signature', majalle: 'editorial', aram: 'showcase', bento: 'app' };

export type ThemeColors = {
  bg: string;
  surface: string;
  surfaceAlt: string;
  card: string;
  text: string;
  muted: string;
  accent: string;
  accentStrong: string;
  onAccent: string;
  line: string;
  lineStrong?: string;
  /** بسته‌ی ۵الف: رنگ دوم پالت (برچسب‌ها و تأکید فرعی) و رنگ متن روی آن؛ سرور قدیمی ندارد */
  accent2?: string;
  onAccent2?: string;
};

export type SalonTheme = {
  key: string;
  layout: ThemeLayout;
  palette: string;
  name: string;
  colors: { dark: ThemeColors; light: ThemeColors };
};

const KEY = 'mahru.salonTheme';
const LAYOUTS: ThemeLayout[] = ['mahru', 'signature', 'editorial', 'showcase', 'app'];

/** `template` تازه، وگرنه نگاشت `layout` قدیمی؛ ناشناخته ← null */
function layoutOf(t: { template?: unknown; layout?: unknown }): ThemeLayout | null {
  if (typeof t.template === 'string' && LAYOUTS.includes(t.template as ThemeLayout)) {
    return t.template as ThemeLayout;
  }
  if (typeof t.layout === 'string') {
    if (LAYOUTS.includes(t.layout as ThemeLayout)) {
      return t.layout as ThemeLayout;
    }
    return LEGACY_LAYOUTS[t.layout] ?? null;
  }
  return null;
}

let current: SalonTheme | null = null;
const listeners = new Set<() => void>();

/** فقط شکل درست پذیرفته می‌شود (سرور قدیمی بی `theme` یا دادهٔ خراب ← پیش‌فرض) */
export function normalizeTheme(raw: unknown): SalonTheme | null {
  if (!raw || typeof raw !== 'object') {
    return null;
  }
  const t = raw as Partial<SalonTheme> & { template?: unknown; template_key?: unknown };
  const hex = (v: unknown) => typeof v === 'string' && /^#[0-9A-Fa-f]{6}$/.test(v);
  const okColors = (c: unknown): c is ThemeColors =>
    !!c && typeof c === 'object' && ['bg', 'surface', 'surfaceAlt', 'text', 'muted', 'accent', 'accentStrong', 'onAccent'].every((k) => hex((c as Record<string, unknown>)[k]));
  const layout = layoutOf(t);
  if (typeof t.key !== 'string' || !layout || !t.colors || !okColors(t.colors.dark) || !okColors(t.colors.light)) {
    return null;
  }
  // رنگ دوم فقط اگر هگز درست است؛ وگرنه حذف (پالت پایه جایگزین می‌کند)
  const withSecond = (c: ThemeColors): ThemeColors => {
    const { accent2, onAccent2, ...rest } = c;
    return hex(accent2) ? { ...rest, accent2, onAccent2: hex(onAccent2) ? onAccent2 : rest.onAccent } : rest;
  };
  const key = typeof t.template_key === 'string' && t.template_key !== '' ? t.template_key : t.key;
  return {
    key,
    layout,
    palette: String(t.palette ?? ''),
    name: String(t.name ?? ''),
    colors: { dark: withSecond(t.colors.dark), light: withSecond(t.colors.light) },
  };
}

export function getSalonTheme(): SalonTheme | null {
  return current;
}

export function setSalonTheme(raw: unknown): void {
  const next = normalizeTheme(raw);
  if (JSON.stringify(next) === JSON.stringify(current)) {
    return;
  }
  current = next;
  listeners.forEach((l) => l());
  (next ? AsyncStorage.setItem(KEY, JSON.stringify(next)) : AsyncStorage.removeItem(KEY)).catch(() => undefined);
}

/** هنگام باز شدن اپ (پیش از رسیدن پاسخ سرور) */
export async function loadSalonTheme(): Promise<void> {
  try {
    const raw = await AsyncStorage.getItem(KEY);
    const parsed = raw ? normalizeTheme(JSON.parse(raw)) : null;
    if (parsed && !current) {
      current = parsed;
      listeners.forEach((l) => l());
    }
  } catch {
    // حافظه‌ی خراب ← پیش‌فرض
  }
}

function subscribe(listener: () => void): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

export function useSalonTheme(): SalonTheme | null {
  return useSyncExternalStore(subscribe, getSalonTheme, getSalonTheme);
}

/** «#RRGGBB» → «rgba(r,g,b,a)» */
export function withAlpha(hex: string, alpha: number): string {
  const n = parseInt(hex.slice(1), 16);
  return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${alpha})`;
}
