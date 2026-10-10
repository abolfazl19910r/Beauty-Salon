import { useFonts } from 'expo-font';
import { SplashScreen, Stack, ThemeProvider, DarkTheme, DefaultTheme, router, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { I18nManager, useColorScheme } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { SessionProvider, useSession } from '@/lib/session';
import { fonts, usePalette } from '@/ui/theme';

// پشتیبان plugin بومی expo-localization (forcesRTL): اگر به هر دلیل چپ‌به‌راست بود، از اجرای بعد راست‌به‌چپ
if (!I18nManager.isRTL) {
  I18nManager.allowRTL(true);
  I18nManager.forceRTL(true);
}

void SplashScreen.preventAutoHideAsync();

const PUBLIC_SCREENS = ['login', 'verify', 'forgot', 'reset', 'server'];

function Navigator() {
  const { status, salonInactive } = useSession();
  const segments = useSegments();
  const p = usePalette();
  const scheme = useColorScheme();
  const [fontsLoaded] = useFonts({
    [fonts.regular]: require('../../assets/fonts/Vazirmatn-Regular.ttf'),
    [fonts.medium]: require('../../assets/fonts/Vazirmatn-Medium.ttf'),
    [fonts.bold]: require('../../assets/fonts/Vazirmatn-Bold.ttf'),
  });

  useEffect(() => {
    if (fontsLoaded && status !== 'loading') {
      void SplashScreen.hideAsync();
    }
  }, [fontsLoaded, status]);

  // از هر صفحه‌ی تودرتو: نشست باطل (۴۰۱، خروج) → ورود؛ سالن غیرفعال → صفحه‌ی خودش
  const current = segments[0] ?? '';
  useEffect(() => {
    if (status === 'signedOut' && !PUBLIC_SCREENS.includes(current)) {
      router.replace('/login');
    } else if (status === 'signedIn' && salonInactive && current !== 'salon-inactive') {
      router.replace('/salon-inactive');
    }
  }, [status, salonInactive, current]);

  if (!fontsLoaded || status === 'loading') {
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
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="verify" options={{ title: 'کد تأیید' }} />
        <Stack.Screen name="forgot" options={{ title: 'فراموشی رمز' }} />
        <Stack.Screen name="reset" options={{ title: 'رمز تازه' }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="booking/[id]" options={{ title: 'جزئیات نوبت' }} />
        <Stack.Screen name="schedule" options={{ title: 'برنامه‌ی هفتگی' }} />
        <Stack.Screen name="leaves" options={{ title: 'مرخصی‌ها' }} />
        <Stack.Screen name="leave-new" options={{ title: 'درخواست مرخصی' }} />
        <Stack.Screen name="withdraw" options={{ title: 'درخواست برداشت' }} />
        <Stack.Screen name="iban" options={{ title: 'شماره شبا' }} />
        <Stack.Screen name="transactions" options={{ title: 'تراکنش‌ها' }} />
        <Stack.Screen name="notifications" options={{ title: 'اعلان‌ها' }} />
        <Stack.Screen name="devices" options={{ title: 'دستگاه‌های من' }} />
        <Stack.Screen name="server" options={{ title: 'آدرس سرور', presentation: 'modal' }} />
        <Stack.Screen name="salon-inactive" options={{ headerShown: false }} />
      </Stack>
    </ThemeProvider>
  );
}

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <SessionProvider>
        <Navigator />
      </SessionProvider>
    </SafeAreaProvider>
  );
}
