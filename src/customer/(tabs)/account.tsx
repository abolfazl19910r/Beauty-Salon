import { router } from 'expo-router';
import { Alert, View } from 'react-native';

import { APP_VERSION, ALLOW_SERVER_OVERRIDE } from '@/lib/config';
import { useHome } from '@/lib/customer/queries';
import { faDigits, toman } from '@/lib/format';
import { useSession } from '@/lib/session';
import { MenuRow, SalonLogo } from '@/ui/customer';
import { Badge, Button, Card, KeyValue, Row, Screen, T } from '@/ui/kit';
import { space } from '@/ui/theme';

export default function Account() {
  const { account, signOut, serverUrl } = useSession();
  const home = useHome();
  const unread = home.data?.unread_notifications ?? 0;

  return (
    <Screen>
      <Card>
        <Row gap={3}>
          {account ? <SalonLogo salon={account.salon} size={48} /> : null}
          <View style={{ flex: 1 }}>
            <T weight="bold" size={17}>{account?.user.name}</T>
            <T muted size={13}>{faDigits(account?.user.phone ?? '')} — {account?.salon.name}</T>
          </View>
        </Row>
      </Card>

      <View style={{ gap: space(2) }}>
        <MenuRow icon="wallet-outline" title="کیف پول" subtitle={home.data ? toman(home.data.wallet_balance) : undefined} onPress={() => router.push('/wallet')} />
        <MenuRow icon="medkit-outline" title="دوره‌های درمانی" subtitle="دوره‌های من و خرید دوره" onPress={() => router.push('/courses')} />
        <MenuRow icon="notifications-outline" title="اعلان‌ها" onPress={() => router.push('/notifications')} trailing={unread > 0 ? <Badge text={faDigits(unread)} tone="gold" /> : null} />
        <MenuRow icon="storefront-outline" title="درباره‌ی سالن" subtitle="آدرس، تلفن، ساعت کاری، قوانین" onPress={() => router.push('/salon-info')} />
        <MenuRow icon="people-outline" title="متخصص‌ها" onPress={() => router.push('/specialists')} />
        <MenuRow icon="create-outline" title="نام و شماره" onPress={() => router.push('/profile')} />
        <MenuRow icon="key-outline" title="تغییر رمز" onPress={() => router.push('/password')} />
        <MenuRow icon="phone-portrait-outline" title="دستگاه‌های من" subtitle="خروج از گوشی‌های دیگر" onPress={() => router.push('/devices')} />
        <MenuRow icon="trash-outline" title="حذف حساب" onPress={() => router.push('/delete-account')} />
      </View>

      <Button
        kind="danger"
        title="خروج از حساب"
        onPress={() => Alert.alert('خروج از حساب؟', `برای ورود دوباره به «${account?.salon.name}» کد پیامکی لازم است.`, [
          { text: 'انصراف', style: 'cancel' },
          { text: 'خروج', style: 'destructive', onPress: () => signOut({ revoke: true }) },
        ])}
      />

      <Card>
        <KeyValue label="نسخه" value={faDigits(APP_VERSION)} />
        {ALLOW_SERVER_OVERRIDE ? <T muted size={12} style={{ writingDirection: 'ltr', textAlign: 'right' }}>{serverUrl}</T> : null}
      </Card>
    </Screen>
  );
}
