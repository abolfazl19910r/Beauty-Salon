import { Redirect, router, useLocalSearchParams } from 'expo-router';
import { useEffect, useRef } from 'react';
import { View } from 'react-native';

import { useCustomerSalon } from '@/lib/customer/salon';
import { parseSalonInput } from '@/lib/customer/salonLink';
import { useOpenSalon } from '@/lib/customer/useOpenSalon';
import { useSession } from '@/lib/session';
import { Button, ErrorBox, Loading, Screen, T } from '@/ui/kit';
import { space } from '@/ui/theme';

/**
 * لینک mahru://salon/{slug} (دکمه‌ی «باز کردن در اپ ماهرو» صفحه‌ی وب سالن).
 * اپ فقط یک سالن نگه می‌دارد: اگر مشتری وارد سالن دیگری است، اول باید خارج شود.
 */
export default function SalonLink() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { status, account, signOut } = useSession();
  const { setSalon } = useCustomerSalon();
  const { open, busy, error } = useOpenSalon();
  const started = useRef(false);
  const parsed = parseSalonInput(`mahru://salon/${slug ?? ''}`);

  useEffect(() => {
    if (status === 'signedOut' && parsed && !started.current) {
      started.current = true;
      void open(parsed);
    }
  }, [status, parsed, open]);

  if (!parsed) {
    return <Redirect href="/" />;
  }

  if (status === 'signedIn' && account) {
    if (account.salon.slug === parsed.slug) {
      return <Redirect href="/home" />;
    }
    return (
      <Screen>
        <View style={{ gap: space(3), marginTop: space(8) }}>
          <T weight="bold" size={18} center>شما وارد «{account.salon.name}» هستید</T>
          <T muted center>اپ ماهرو هر بار به یک سالن وصل است. برای رفتن به سالن تازه، از این سالن خارج شوید.</T>
          <Button
            title="خروج و رفتن به سالن تازه"
            onPress={async () => {
              await signOut({ revoke: true });
              await setSalon(null);
              await open(parsed);
            }}
          />
          <Button kind="ghost" title="ماندن در همین سالن" onPress={() => router.replace('/home')} />
        </View>
      </Screen>
    );
  }

  return (
    <Screen>
      {busy || !error ? <Loading label="در حال پیدا کردن سالن…" /> : null}
      {error ? (
        <>
          <ErrorBox error={error} onRetry={() => open(parsed)} />
          <Button kind="ghost" title="وارد کردن لینک یا کد دیگر" onPress={() => router.replace('/welcome')} />
        </>
      ) : null}
    </Screen>
  );
}
