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
    // نشان تازه‌ی برند (۲۰۲۶-۱۰-۱۴) روی اسپرسو، با نشان «همکار» زیر آن
    routerRoot: './src/app', assets: './assets', background: '#1A1410',
  },
  customer: {
    name: 'ماهرو', slug: 'mahru', package: 'ir.mahru', scheme: 'mahru',
    // هر دو اپ روی اسپرسو (راهنمای برند: نشان طلایی روی کرم/بژ نه — تصمیم ۲۰۲۶-۱۰-۱۴)؛ مشتری بدون نشان «همکار»
    routerRoot: './src/customer', assets: './assets/customer', background: '#1A1410',
  },
} as const;

type Variant = keyof typeof VARIANTS;

/** پروژه‌های expo.dev (ساخته‌شده با `eas init`): همکار ‏@abolfazl1991/mahru-staff (۲۰۲۶-۱۰-۱۰)، مشتری ‏@abolfazl1991/mahru (۲۰۲۶-۱۰-۱۴). */
const EAS_PROJECT_IDS: Record<Variant, string> = {
  staff: '1ae539ab-5b39-43c1-8f62-db0b1648abdc',
  customer: '0bc2a030-263a-4a66-8f80-d8e83e017298', // @abolfazl1991/mahru (`eas init`، ۲۰۲۶-۱۰-۱۴)
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
        // آیکون تک‌رنگ اندروید ۱۳ (همکار با نشان «همکار» بریده‌شده)
        monochromeImage: `${v.assets}/android-icon-monochrome.png`,
      },
      // دوربین: اپ مشتری برای اسکن QR سالن، اپ همکار برای عکس قبل/بعد جلسه‌ی دوره (بسته‌ی ب)؛ میکروفون هیچ‌کدام
      blockedPermissions: ['android.permission.RECORD_AUDIO'],
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
      // عکس قبل/بعد جلسه‌ی دوره‌ی درمانی (فقط اپ همکار، بسته‌ی ب)؛ بدون میکروفون
      ...(variant === 'staff'
        ? [['expo-image-picker', { cameraPermission: 'برای عکس قبل و بعد جلسه‌ی دوره، با رضایت مشتری', photosPermission: 'برای انتخاب عکس قبل و بعد جلسه‌ی دوره', microphonePermission: false }] as [string, object]]
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
