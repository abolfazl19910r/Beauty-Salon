import AsyncStorage from '@react-native-async-storage/async-storage';
import { useSyncExternalStore } from 'react';

/**
 * ⭐ بسته‌ی «و» (۲۰۲۶-۱۰-۱۵): قالب سالن در اپ‌ها. سرور در کارت سالن (اپ مشتری) و حساب کادر (اپ همکار) `theme` می‌دهد:
 * کلید، چیدمان، پالت و رنگ‌های هگز تیره/روشن (App\Support\Themes\SalonAppearance::toApi). اپ هنگام باز شدن آخرین قالب ذخیره‌شده را
 * می‌گذارد و با هر پاسخ تازه به‌روزش می‌کند — بی build تازه. «طلایی ماهرو» (mahru) همان رنگ‌های پایه‌ی ui/theme است.
 */
export type ThemeLayout = 'mahru' | 'vitrin' | 'majalle' | 'aram' | 'bento';

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
};

export type SalonTheme = {
  key: string;
  layout: ThemeLayout;
  palette: string;
  name: string;
  colors: { dark: ThemeColors; light: ThemeColors };
};

const KEY = 'mahru.salonTheme';
const LAYOUTS: ThemeLayout[] = ['mahru', 'vitrin', 'majalle', 'aram', 'bento'];

let current: SalonTheme | null = null;
const listeners = new Set<() => void>();

/** فقط شکل درست پذیرفته می‌شود (سرور قدیمی بی `theme` یا دادهٔ خراب ← پیش‌فرض) */
export function normalizeTheme(raw: unknown): SalonTheme | null {
  if (!raw || typeof raw !== 'object') {
    return null;
  }
  const t = raw as Partial<SalonTheme>;
  const hex = (v: unknown) => typeof v === 'string' && /^#[0-9A-Fa-f]{6}$/.test(v);
  const okColors = (c: unknown): c is ThemeColors =>
    !!c && typeof c === 'object' && ['bg', 'surface', 'surfaceAlt', 'text', 'muted', 'accent', 'accentStrong', 'onAccent'].every((k) => hex((c as Record<string, unknown>)[k]));
  if (typeof t.key !== 'string' || !LAYOUTS.includes(t.layout as ThemeLayout) || !t.colors || !okColors(t.colors.dark) || !okColors(t.colors.light)) {
    return null;
  }
  return { key: t.key, layout: t.layout as ThemeLayout, palette: String(t.palette ?? ''), name: String(t.name ?? ''), colors: t.colors };
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
