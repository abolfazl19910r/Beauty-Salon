/**
 * بسته‌ی د — خوانایی لوگو: همان قاعده‌ی وب (Salon::appearanceFor). رنگ لوگو عوض نمی‌شود؛ روی تمی که کنتراست کم دارد
 * داخل پلاک گرد با پس‌زمینه‌ی مخالف می‌نشیند. تم تیره لوگوی دوم سالن (پس‌زمینه‌ی تیره) را اگر دارد نشان می‌دهد.
 */
export type LogoTone = 'light' | 'dark' | 'mid' | null;

export type LogoFields = {
  logo_url: string | null;
  logo_tone?: LogoTone;
  logo_dark_url?: string | null;
  logo_dark_tone?: LogoTone;
};

/** پلاک تم روشن تیره است و پلاک تم تیره روشن (پس‌زمینه‌های --m-bg وب) */
export const LOGO_PLAQUE = { light: '#1C1712', dark: '#FBF8F2' } as const;

export function logoAppearance(salon: LogoFields, scheme: 'light' | 'dark'): { uri: string; plaque: string | null } | null {
  if (!salon.logo_url) return null;
  if (scheme === 'light') {
    return { uri: salon.logo_url, plaque: salon.logo_tone === 'light' ? LOGO_PLAQUE.light : null };
  }
  if (salon.logo_dark_url) {
    return { uri: salon.logo_dark_url, plaque: salon.logo_dark_tone === 'dark' ? LOGO_PLAQUE.dark : null };
  }
  return { uri: salon.logo_url, plaque: salon.logo_tone === 'dark' ? LOGO_PLAQUE.dark : null };
}
