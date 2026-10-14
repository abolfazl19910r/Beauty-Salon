import { useColorScheme } from 'react-native';

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

export function usePalette(): Palette {
  return useColorScheme() === 'dark' ? dark : light;
}

export const fonts = {
  regular: 'Vazirmatn-Regular',
  medium: 'Vazirmatn-Medium',
  bold: 'Vazirmatn-Bold',
};

/** گردی گوشه‌ها: --r-xs/--r-sm/--r-md لندینگ */
export const radius = { sm: 10, md: 14, lg: 20 };
export const space = (n: number) => n * 4;
