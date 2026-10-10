import { router } from 'expo-router';
import { Alert } from 'react-native';

import { faDigits } from '@/lib/format';
import { APP_VERSION, ALLOW_SERVER_OVERRIDE } from '@/lib/config';
import { useNotifications } from '@/lib/queries';
import { useSession } from '@/lib/session';
import { Badge, Button, Card, KeyValue, Row, Screen, T } from '@/ui/kit';

export default function More() {
  const { account, signOut, serverUrl } = useSession();
  const notifications = useNotifications();
  const unread = Number(notifications.data?.pages[0]?.meta.unread_count ?? 0);

  return (
    <Screen>
      <Card>
        <T weight="bold" size={17}>{account?.specialist?.name ?? account?.user.name}</T>
        <T muted>{account?.salon.name}</T>
        <T muted size={13}>{faDigits(account?.user.phone ?? '')}</T>
      </Card>

      <Card onPress={() => router.push('/notifications')}>
        <Row style={{ justifyContent: 'space-between' }}>
          <T weight="medium">اعلان‌ها</T>
          {unread > 0 ? <Badge text={`${faDigits(unread)} خوانده‌نشده`} tone="gold" /> : null}
        </Row>
      </Card>
      <Card onPress={() => router.push('/devices')}>
        <T weight="medium">دستگاه‌های من</T>
        <T muted size={13}>خروج از گوشی‌های دیگر</T>
      </Card>

      <Button
        kind="danger"
        title="خروج از حساب"
        onPress={() => Alert.alert('خروج از حساب؟', 'برای ورود دوباره کد پیامکی لازم است.', [
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
