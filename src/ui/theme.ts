import { useColorScheme } from 'react-native';

import { type SalonTheme, type ThemeLayout, useSalonTheme, withAlpha } from '@/lib/salonTheme';

/**
 * رنگ‌های برند ماهرو — همان توکن‌های لندینگ و پنل‌ها (MAHRU_UI_PROMPT، بخش ۴، ۲۰۲۶-۱۰-۱۴)؛ روشن/تیره از تنظیم گوشی.
 * نگاشت: background=--bg، surface=--bg2، surfaceMuted=--bg3، border=--ln، text=--tx، textMuted=--mu، gold=--gl (متن/آیکن تأکیدی)،
 * goldSoft=--tint، button=رنگ میانی گرادیان --btn (React Native گرادیان ندارد)، onGold=--on-gold، وضعیت‌ها --ok/--warn/--bad/--info.
 * پس‌زمینه‌ی کم‌رنگ وضعیت‌ها همان «color-mix ۱۴٪» پرامپت است.
 */
const light = {
  background: '#FBF8F2',
  surface: '#FFFFFF',
  surfaceMuted: '#F5EFE3',
  border: 'rgba(122,90,28,0.13)',
  borderStrong: 'rgba(122,90,28,0.28)',
  text: '#21190F',
  textMuted: '#6B5D48',
  gold: '#8A6A22',
  goldSoft: 'rgba(201,164,85,0.16)',
  button: '#D2B060',
  onGold: '#221A0C',
  accent2: '#8A6A22',
  onAccent2: '#FFFFFF',
  success: '#3F7A34',
  successSoft: 'rgba(63,122,52,0.14)',
  warning: '#9A6A00',
  warningSoft: 'rgba(154,106,0,0.14)',
  danger: '#A9461F',
  dangerSoft: 'rgba(169,70,31,0.14)',
  info: '#3B5F8F',
  infoSoft: 'rgba(59,95,143,0.14)',
};

const dark: typeof light = {
  background: '#15110E',
  surface: '#1C1712',
  surfaceMuted: '#251E17',
  border: 'rgba(235,214,155,0.12)',
  borderStrong: 'rgba(235,214,155,0.28)',
  text: '#F4EDE0',
  textMuted: '#B3A48C',
  gold: '#E3C985',
  goldSoft: 'rgba(166,127,46,0.16)',
  button: '#D8B96A',
  onGold: '#221A0C',
  accent2: '#E3C985',
  onAccent2: '#221A0C',
  success: '#9CC58F',
  successSoft: 'rgba(156,197,143,0.14)',
  warning: '#E0B25C',
  warningSoft: 'rgba(224,178,92,0.14)',
  danger: '#D88A78',
  dangerSoft: 'rgba(216,138,120,0.14)',
  info: '#9DB4D6',
  infoSoft: 'rgba(157,180,214,0.14)',
};

export type Palette = typeof light;

/**
 * ⭐ بسته‌ی «و»: رنگ‌های قالب سالن روی پالت پایه (وضعیت‌ها — موفق/هشدار/خطا/اطلاع — همان ماهرو می‌مانند).
 * «طلایی ماهرو» یا بدون قالب ← همان پالت پایه، بایت به بایت.
 */
export function applySalonTheme(base: Palette, theme: SalonTheme | null, scheme: 'dark' | 'light'): Palette {
  if (!theme || theme.layout === 'mahru') {
    return base;
  }
  const c = theme.colors[scheme];
  return {
    ...base,
    background: c.bg,
    surface: c.surface,
    surfaceMuted: c.surfaceAlt,
    border: c.line || base.border,
    borderStrong: c.lineStrong || base.borderStrong,
    text: c.text,
    textMuted: c.muted,
    gold: scheme === 'dark' ? c.accentStrong : c.accent,
    goldSoft: withAlpha(c.accent, 0.16),
    button: c.accent,
    onGold: c.onAccent,
    // بسته‌ی ۵الف/۵ب: رنگ دوم پالت؛ سرور قدیمی ← همان رنگ تأکید
    accent2: c.accent2 ?? (scheme === 'dark' ? c.accentStrong : c.accent),
    onAccent2: c.onAccent2 ?? c.onAccent,
  };
}

export function usePalette(): Palette {
  const scheme = useColorScheme() === 'dark' ? 'dark' : 'light';
  const theme = useSalonTheme();
  return applySalonTheme(scheme === 'dark' ? dark : light, theme, scheme);
}

export const fonts = {
  regular: 'Vazirmatn-Regular',
  medium: 'Vazirmatn-Medium',
  bold: 'Vazirmatn-Bold',
  light: 'Vazirmatn-Light',
  naskh: 'NotoNaskhArabic-Bold',
  kufi: 'NotoKufiArabic-ExtraBold',
};

/** فایل فونت‌ها برای useFonts هر دو اپ (فونت تیتر قالب‌ها در خود اپ است، نه از اینترنت) */
export const fontFiles = {
  [fonts.regular]: require('../../assets/fonts/Vazirmatn-Regular.ttf'),
  [fonts.medium]: require('../../assets/fonts/Vazirmatn-Medium.ttf'),
  [fonts.bold]: require('../../assets/fonts/Vazirmatn-Bold.ttf'),
  [fonts.light]: require('../../assets/fonts/Vazirmatn-Light.ttf'),
  [fonts.naskh]: require('../../assets/fonts/NotoNaskhArabic-Bold.ttf'),
  [fonts.kufi]: require('../../assets/fonts/NotoKufiArabic-ExtraBold.ttf'),
};

/** گردی گوشه‌ها: --r-xs/--r-sm/--r-md لندینگ */
export const radius = { sm: 10, md: 14, lg: 20 };

/**
 * چیدمان قالب در اپ: گردی کارت/دکمه/فیلد و فونت تیترها (همان قواعد partials/theme-layout وب، بسته‌ی ۵ب). اپ چیدمان صفحه‌ها را
 * عوض نمی‌کند؛ چند واریانت آماده است. شاهکار و روزنامه‌ی سالن: نسخ و گوشه‌ی تیز؛ نمایشگاه: کوفی و گوشه‌ی نرم؛ اپ: خیلی گرد.
 */
export const layoutMetrics: Record<ThemeLayout, { radius: typeof radius; display: string; displayWeightLight?: boolean }> = {
  mahru: { radius, display: fonts.bold },
  signature: { radius: { sm: 12, md: 4, lg: 6 }, display: fonts.naskh },
  editorial: { radius: { sm: 0, md: 0, lg: 0 }, display: fonts.naskh },
  showcase: { radius: { sm: 14, md: 16, lg: 22 }, display: fonts.kufi },
  app: { radius: { sm: 14, md: 18, lg: 26 }, display: fonts.bold },
};

export function useLayoutMetrics() {
  const theme = useSalonTheme();
  return layoutMetrics[theme?.layout ?? 'mahru'];
}
export const space = (n: number) => n * 4;
