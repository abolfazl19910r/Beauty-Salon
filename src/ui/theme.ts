import { useColorScheme } from 'react-native';

/** رنگ‌های برند ماهرو (public/brand لاراول): طلایی هلال روی قهوه‌ای تیره؛ روشن/تیره از تنظیم گوشی */
const light = {
  background: '#FAF6EF',
  surface: '#FFFFFF',
  surfaceMuted: '#F3ECE0',
  border: '#E6DAC6',
  text: '#1A1410',
  textMuted: '#6B5E4E',
  gold: '#8A6A2F',
  goldSoft: '#F3E4B8',
  onGold: '#FFFFFF',
  success: '#2E7D4F',
  successSoft: '#DFF1E6',
  warning: '#9A6A00',
  warningSoft: '#FBEFD2',
  danger: '#B3261E',
  dangerSoft: '#F9DEDC',
  info: '#3B5B8C',
  infoSoft: '#E1E9F5',
};

const dark: typeof light = {
  background: '#1A1410',
  surface: '#241C16',
  surfaceMuted: '#2E241C',
  border: '#3D3127',
  text: '#F3EBDD',
  textMuted: '#B9AA95',
  gold: '#C9A24B',
  goldSoft: '#3A2F1C',
  onGold: '#1A1410',
  success: '#7BC79A',
  successSoft: '#1E3326',
  warning: '#E5B95C',
  warningSoft: '#3A2E14',
  danger: '#F2867D',
  dangerSoft: '#3D1F1C',
  info: '#9DB8E2',
  infoSoft: '#1F2A3A',
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

export const radius = { sm: 8, md: 12, lg: 18 };
export const space = (n: number) => n * 4;
