import type { ConfigContext, ExpoConfig } from 'expo/config';

/**
 * یک کد، دو اپ (تصمیم ۲۰۲۶-۱۰-۰۲): APP_VARIANT=staff → «ماهرو همکار»، APP_VARIANT=customer → «ماهرو» (بسته‌ی ۳ب).
 * APP_ENV=production → فیلد مخفی «آدرس سرور» در صفحه‌ی ورود خاموش (نسخه‌های آزمایشی روشن).
 * EXPO_PUBLIC_API_URL → آدرس پیش‌فرض سرور (در expo.dev به‌عنوان متغیر محیطی EAS).
 */
const VARIANTS = {
  staff: {
    name: 'ماهرو همکار', slug: 'mahru-staff', package: 'ir.mahru.staff', scheme: 'mahru-staff',
    // هر اپ درخت صفحه‌های خودش را دارد؛ APK فقط صفحه‌های همان نسخه را می‌گیرد (بسته‌ی ۳ب)
    routerRoot: './src/app', assets: './assets', background: '#1A1410',
  },
  customer: {
    name: 'ماهرو', slug: 'mahru', package: 'ir.mahru', scheme: 'mahru',
    // آیکون جدا (تصمیم ۲۰۲۶-۱۰-۱۴): هلال طلایی روی زمینه‌ی کرم، تا کنار «ماهرو همکار» اشتباه گرفته نشود
    routerRoot: './src/customer', assets: './assets/customer', background: '#F7F0E3',
  },
} as const;

type Variant = keyof typeof VARIANTS;

/** پروژه‌ی expo.dev ‏@abolfazl1991/mahru-staff (ساخته‌شده با `eas init`، ۲۰۲۶-۱۰-۱۰). اپ مشتری (بسته‌ی ۳) پروژه‌ی جدا می‌گیرد. */
const EAS_PROJECT_IDS: Record<Variant, string> = {
  staff: '1ae539ab-5b39-43c1-8f62-db0b1648abdc',
  customer: '', // بعد از اولین `APP_VARIANT=customer eas init` پر شود (README)
};

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
    icon: `${v.assets}/icon.png`,
    userInterfaceStyle: 'automatic',
    platforms: ['android'],
    android: {
      package: v.package,
      versionCode: 1,
      adaptiveIcon: {
        backgroundColor: v.background,
        foregroundImage: `${v.assets}/android-icon-foreground.png`,
        backgroundImage: `${v.assets}/android-icon-background.png`,
        // شکل نشان در هر دو یکی است؛ آیکون تک‌رنگ اندروید ۱۳ مشترک
        monochromeImage: './assets/android-icon-monochrome.png',
      },
      // expo-camera (اسکن QR سالن، فقط اپ مشتری) مجوز دوربین را در manifest کتابخانه دارد؛ اپ همکار آن را نمی‌خواهد
      blockedPermissions: variant === 'staff' ? ['android.permission.CAMERA', 'android.permission.RECORD_AUDIO'] : ['android.permission.RECORD_AUDIO'],
      predictiveBackGestureEnabled: false,
    },
    plugins: [
      ['expo-router', { root: v.routerRoot }],
      'expo-secure-store',
      ['expo-splash-screen', { image: `${v.assets}/splash-icon.png`, imageWidth: 180, resizeMode: 'contain', backgroundColor: v.background }],
      // راست‌به‌چپ از اولین اجرا، در سطح بومی (نه فقط I18nManager که بعد از راه‌اندازی دوباره اثر می‌کند)
      ['expo-localization', { supportsRTL: true, forcesRTL: true }],
      ['expo-font', { fonts: ['./assets/fonts/Vazirmatn-Regular.ttf', './assets/fonts/Vazirmatn-Medium.ttf', './assets/fonts/Vazirmatn-Bold.ttf'] }],
      // اسکن QR سالن (فقط اپ مشتری)؛ بدون میکروفون
      ...(variant === 'customer'
        ? [['expo-camera', { cameraPermission: 'برای اسکن QR سالن', recordAudioAndroid: false, microphonePermission: false }] as [string, object]]
        : []),
      // سرور آزمایشی روی شبکه‌ی محلی (XAMPP) با http است؛ در نسخه‌ی production خاموش
      ['expo-build-properties', { android: { usesCleartextTraffic: !production } }],
    ],
    experiments: { typedRoutes: false },
    extra: {
      variant,
      allowServerOverride: !production,
      defaultApiUrl: process.env.EXPO_PUBLIC_API_URL ?? 'https://mahru.ir',
      eas: (process.env.EAS_PROJECT_ID ?? EAS_PROJECT_IDS[variant]) ? { projectId: process.env.EAS_PROJECT_ID ?? EAS_PROJECT_IDS[variant] } : undefined,
    },
  };
};
