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

/**
 * Android App Links اپ مشتری (بسته‌ی ۵): لینک https سالن («rasta.mahru.ir/» و «…/specialists/12») اگر اپ نصب است خود اپ را باز می‌کند.
 * سرور: /.well-known/assetlinks.json با اثرانگشت SHA-256 امضای همین اپ (CUSTOMER_APP_SHA256_FINGERPRINTS)؛ اندروید برای میزبان wildcard
 * فایل را از دامنه‌ی اصلی می‌خواند. فقط صفحه‌ی اصلی سالن و صفحه‌ی متخصص — نه پرداخت، ورود یا پنل‌ها (پرداخت وب در مرورگر بماند).
 * APP_LINKS_DOMAIN فقط اگر دامنه‌ی دیگری لازم شد (خالی = mahru.ir). آزمایش محلی همان mahru.ir با اجازه‌ی دستی است (README، «App Links»)؛
 * http فقط در نسخه‌ی غیر production و تأیید خودکار ندارد.
 */
const APP_LINKS_DOMAIN = process.env.APP_LINKS_DOMAIN || 'mahru.ir';

function customerIntentFilters(production: boolean) {
  const schemes = production ? ['https'] : ['https', 'http'];
  const data = schemes.flatMap((scheme) => [
    { scheme, host: `*.${APP_LINKS_DOMAIN}`, path: '/' },
    { scheme, host: `*.${APP_LINKS_DOMAIN}`, pathPrefix: '/specialists/' },
  ]);
  return [{ action: 'VIEW', autoVerify: true, data, category: ['BROWSABLE', 'DEFAULT'] }];
}

/**
 * google-services.json هر اپ (بسته‌ی ۴، پوش FCM): در EAS متغیر محیطی نوع «File» به نام GOOGLE_SERVICES_JSON که مسیر فایل را می‌دهد
 * (هر پروژه‌ی expo.dev فایل اپ خودش). روی سیستم خودتان: GOOGLE_SERVICES_JSON=./google-services.staff.json (در .gitignore).
 * بدون آن build ساخته می‌شود ولی اپ توکن پوش نمی‌گیرد (صفحه‌ی «دریافت اعلان»: ثبت نشده).
 */
const GOOGLE_SERVICES_JSON = process.env.GOOGLE_SERVICES_JSON || undefined;

/**
 * حساب expo.dev که buildها با آن گرفته می‌شود (تصمیم ۲۰۲۶-۱۰-۱۶: حساب ‏abolfazl19910r؛ پروژه‌های قبلی ‏@abolfazl1991 دست‌نخورده‌اند).
 * پروژه‌ها با `eas init` ساخته شدند: ‏@abolfazl19910r/mahru-staff و ‏@abolfazl19910r/mahru. کلید امضای APK هم مال همین پروژه‌هاست.
 */
const EAS_OWNER = 'abolfazl19910r';
const EAS_PROJECT_IDS: Record<Variant, string> = {
  staff: 'e4dde0c7-c83e-45d6-a466-a5f2237d9e10',
  customer: 'c9ebfbca-701b-44a8-8c10-68f979a83e94',
};

export default ({ config }: ConfigContext): ExpoConfig => {
  const variant: Variant = process.env.APP_VARIANT === 'customer' ? 'customer' : 'staff';
  const v = VARIANTS[variant];
  const production = process.env.APP_ENV === 'production';

  return {
    ...config,
    owner: EAS_OWNER,
    name: v.name,
    slug: v.slug,
    scheme: v.scheme,
    version: '0.3.0',
    orientation: 'portrait',
    icon: `${v.assets}/icon.png`,
    userInterfaceStyle: 'automatic',
    platforms: ['android'],
    android: {
      package: v.package,
      // ۸: بسته‌ی ۵ (App Links، بررسی نسخه‌ی تازه) — نصب روی نسخه‌ی قبلی به‌صورت Update. هر APK تازه‌ای که آپلود می‌شود باید
      // versionCode بزرگ‌تری داشته باشد (ابرمدیر ← نسخه‌های اپ، از خود APK می‌خواند)
      versionCode: 8,
      ...(variant === 'customer' ? { intentFilters: customerIntentFilters(production) } : {}),
      googleServicesFile: GOOGLE_SERVICES_JSON,
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
      // پوش (بسته‌ی ۴): آیکون سفید نوار اعلان و کانال پیش‌فرض (کانال‌ها در src/lib/pushRuntime.ts ساخته می‌شوند)
      ['expo-notifications', { icon: './assets/notification-icon.png', color: '#A67F2E', defaultChannel: 'bookings' }],
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
