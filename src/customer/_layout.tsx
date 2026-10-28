import { useFonts } from 'expo-font';
import { DarkTheme, DefaultTheme, SplashScreen, Stack, ThemeProvider, router, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { I18nManager, useColorScheme } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { usePendingPaymentWatcher } from '@/lib/customer/payments';
import { CustomerSalonProvider, useCustomerSalon } from '@/lib/customer/salon';
import { usePushSetup } from '@/lib/pushRuntime';
import { SessionProvider, useSession } from '@/lib/session';
import { fontFiles, fonts, usePalette } from '@/ui/theme';
import { UpdateGate } from '@/ui/UpdateNotice';

/**
 * اپ «ماهرو» (مشتری، بسته‌ی ۳ب) — ریشه‌ی جدای expo-router (app.config.ts: APP_VARIANT=customer → src/customer).
 * همه‌چیز بعد از ورود (تصمیم ۲۰۲۶-۱۰-۱۱)؛ پیش از ورود فقط انتخاب سالن، کارت سالن، ورود، ثبت‌نام و بازیابی رمز.
 */
if (!I18nManager.isRTL) {
  I18nManager.allowRTL(true);
  I18nManager.forceRTL(true);
}

void SplashScreen.preventAutoHideAsync();

/** صفحه‌هایی که با قوانین پذیرفته‌نشده هم باز می‌مانند (خروج و حذف حساب، تصمیم ۲۰۲۶-۱۰-۲۸) */
const TERMS_OPEN_SCREENS = ['terms-update', 'delete-account', 'salon-inactive'];

/** صفحه‌هایی که بدون ورود باز می‌شوند */
const PUBLIC_SCREENS = ['welcome', 'scan', 'salon', 'login', 'verify', 'register', 'forgot', 'reset', 'server'];

function Navigator() {
  const { status, salonInactive, api, termsPending, termsDeferred } = useSession();
  const { ready, salon } = useCustomerSalon();
  const segments = useSegments();
  const p = usePalette();
  const scheme = useColorScheme();
  const [fontsLoaded] = useFonts(fontFiles);

  // پوش (بسته‌ی ۴): ثبت گوشی بعد از ورود، رسید، و باز کردن صفحه‌ی درست با لمس اعلان
  usePushSetup(api, status === 'signedIn');
  const loading = !fontsLoaded || status === 'loading' || !ready;

  usePendingPaymentWatcher(status === 'signedIn');

  useEffect(() => {
    if (!loading) {
      void SplashScreen.hideAsync();
    }
  }, [loading]);

  // از هر صفحه: نشست باطل (۴۰۱، خروج) → ورود همان سالن (یا انتخاب سالن)؛ سالن غیرفعال → صفحه‌ی خودش
  const current = segments[0] ?? '';
  useEffect(() => {
    if (loading) {
      return;
    }
    if (status === 'signedOut' && !PUBLIC_SCREENS.includes(current)) {
      router.replace(salon ? '/login' : '/welcome');
    } else if (status === 'signedIn' && salonInactive && current !== 'salon-inactive') {
      router.replace('/salon-inactive');
    } else if (status === 'signedIn' && termsPending && !termsDeferred && !TERMS_OPEN_SCREENS.includes(current)) {
      // پذیرش دوباره (۲۰۲۶-۱۰-۲۸): بعد از ورود یا ۴۰۹ رزرو، اول «قوانین به‌روز شد»
      router.replace('/terms-update');
    }
  }, [loading, status, salonInactive, current, salon, termsPending, termsDeferred]);

  if (loading) {
    return null;
  }

  const base = scheme === 'dark' ? DarkTheme : DefaultTheme;

  return (
    <ThemeProvider value={{ ...base, colors: { ...base.colors, primary: p.gold, background: p.background, card: p.surface, text: p.text, border: p.border } }}>
      <StatusBar style={scheme === 'dark' ? 'light' : 'dark'} />
      <Stack
        screenOptions={{
          headerTitleStyle: { fontFamily: fonts.bold, fontSize: 17 },
          headerTitleAlign: 'center',
          headerShadowVisible: false,
          contentStyle: { backgroundColor: p.background },
        }}
      >
        <Stack.Screen name="index" options={{ headerShown: false }} />
        <Stack.Screen name="welcome" options={{ headerShown: false }} />
        <Stack.Screen name="scan" options={{ title: 'اسکن QR', presentation: 'modal' }} />
        <Stack.Screen name="salon/[slug]" options={{ title: 'سالن' }} />
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="verify" options={{ title: 'کد تأیید' }} />
        <Stack.Screen name="register" options={{ title: 'ثبت‌نام' }} />
        <Stack.Screen name="forgot" options={{ title: 'فراموشی رمز' }} />
        <Stack.Screen name="reset" options={{ title: 'رمز تازه' }} />
        <Stack.Screen name="server" options={{ title: 'آدرس سرور', presentation: 'modal' }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="booking/[id]" options={{ title: 'جزئیات نوبت' }} />
        <Stack.Screen name="pick/[serviceId]" options={{ title: 'انتخاب زمان' }} />
        <Stack.Screen name="checkout" options={{ title: 'تأیید رزرو' }} />
        <Stack.Screen name="pay/[id]" options={{ title: 'پرداخت' }} />
        <Stack.Screen name="payment" options={{ title: 'نتیجه‌ی پرداخت', headerBackVisible: false }} />
        <Stack.Screen name="reschedule/[id]" options={{ title: 'تغییر زمان نوبت' }} />
        <Stack.Screen name="courses" options={{ title: 'دوره‌های درمانی' }} />
        <Stack.Screen name="course/[id]" options={{ title: 'دوره‌ی من' }} />
        <Stack.Screen name="course-pay/[id]" options={{ title: 'پرداخت دوره' }} />
        <Stack.Screen name="course-book/[id]" options={{ title: 'رزرو جلسه' }} />
        <Stack.Screen name="wallet" options={{ title: 'کیف پول' }} />
        <Stack.Screen name="charge" options={{ title: 'شارژ کیف پول' }} />
        <Stack.Screen name="wallet-transactions" options={{ title: 'تراکنش‌های کیف پول' }} />
        <Stack.Screen name="club-history" options={{ title: 'تاریخچه‌ی امتیاز' }} />
        <Stack.Screen name="notifications" options={{ title: 'اعلان‌ها' }} />
        <Stack.Screen name="salon-info" options={{ title: 'درباره‌ی سالن' }} />
        <Stack.Screen name="specialists" options={{ title: 'متخصص‌ها' }} />
        <Stack.Screen name="specialist/[id]" options={{ title: 'متخصص' }} />
        <Stack.Screen name="profile" options={{ title: 'نام و شماره' }} />
        <Stack.Screen name="password" options={{ title: 'تغییر رمز' }} />
        <Stack.Screen name="devices" options={{ title: 'دستگاه‌های من' }} />
        <Stack.Screen name="push" options={{ title: 'دریافت اعلان' }} />
        <Stack.Screen name="delete-account" options={{ title: 'حذف حساب' }} />
        <Stack.Screen name="terms-update" options={{ title: 'قوانین به‌روز شد', headerBackVisible: false }} />
        <Stack.Screen name="salon-inactive" options={{ headerShown: false }} />
      </Stack>
      {/* نسخه‌ی اجباری (بسته‌ی ۵): اپ بدون مارکت، تا به‌روز نشود ادامه نمی‌دهد */}
      <UpdateGate />
    </ThemeProvider>
  );
}

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <SessionProvider>
        <CustomerSalonProvider>
          <Navigator />
        </CustomerSalonProvider>
      </SessionProvider>
    </SafeAreaProvider>
  );
}
