import type { ConfigContext, ExpoConfig } from 'expo/config';

/**
 * یک کد، دو اپ (تصمیم ۲۰۲۶-۱۰-۰۲): APP_VARIANT=staff → «ماهرو همکار»، APP_VARIANT=customer → «ماهرو» (بسته‌ی ۳).
 * APP_ENV=production → فیلد مخفی «آدرس سرور» در صفحه‌ی ورود خاموش (نسخه‌های آزمایشی روشن).
 * EXPO_PUBLIC_API_URL → آدرس پیش‌فرض سرور (در expo.dev به‌عنوان متغیر محیطی EAS).
 */
const VARIANTS = {
  staff: { name: 'ماهرو همکار', slug: 'mahru-staff', package: 'ir.mahru.staff', scheme: 'mahru-staff' },
  customer: { name: 'ماهرو', slug: 'mahru', package: 'ir.mahru', scheme: 'mahru' },
} as const;

type Variant = keyof typeof VARIANTS;

/** بعد از اولین `eas init` شناسه‌ی پروژه‌ی expo.dev را اینجا بگذارید (README، بخش «ساخت APK») */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? '';

export default ({ config }: ConfigContext): ExpoConfig => {
  const variant: Variant = process.env.APP_VARIANT === 'customer' ? 'customer' : 'staff';
  const v = VARIANTS[variant];
  const production = process.env.APP_ENV === 'production';

  return {
    ...config,
    owner: 'abolfazl1991',
    name: v.name,
    slug: v.slug,
    scheme: v.scheme,
    version: '0.1.0',
    orientation: 'portrait',
    icon: './assets/icon.png',
    userInterfaceStyle: 'automatic',
    platforms: ['android'],
    android: {
      package: v.package,
      versionCode: 1,
      adaptiveIcon: {
        backgroundColor: '#1A1410',
        foregroundImage: './assets/android-icon-foreground.png',
        backgroundImage: './assets/android-icon-background.png',
        monochromeImage: './assets/android-icon-monochrome.png',
      },
      predictiveBackGestureEnabled: false,
    },
    plugins: [
      'expo-router',
      'expo-secure-store',
      ['expo-splash-screen', { image: './assets/splash-icon.png', imageWidth: 180, resizeMode: 'contain', backgroundColor: '#1A1410' }],
      // راست‌به‌چپ از اولین اجرا، در سطح بومی (نه فقط I18nManager که بعد از راه‌اندازی دوباره اثر می‌کند)
      ['expo-localization', { supportsRTL: true, forcesRTL: true }],
      ['expo-font', { fonts: ['./assets/fonts/Vazirmatn-Regular.ttf', './assets/fonts/Vazirmatn-Medium.ttf', './assets/fonts/Vazirmatn-Bold.ttf'] }],
      // سرور آزمایشی روی شبکه‌ی محلی (XAMPP) با http است؛ در نسخه‌ی production خاموش
      ['expo-build-properties', { android: { usesCleartextTraffic: !production } }],
    ],
    experiments: { typedRoutes: false },
    extra: {
      variant,
      allowServerOverride: !production,
      defaultApiUrl: process.env.EXPO_PUBLIC_API_URL ?? 'https://mahru.ir',
      eas: EAS_PROJECT_ID ? { projectId: EAS_PROJECT_ID } : undefined,
    },
  };
};
