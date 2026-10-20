import { router } from 'expo-router';
import { useEffect } from 'react';
import { RefreshControl, View } from 'react-native';

import { useHome } from '@/lib/customer/queries';
import { consumePendingSpecialist } from '@/lib/customer/store';
import { UpdateNotice } from '@/ui/UpdateNotice';
import { faDigits, toman } from '@/lib/format';
import { useSession } from '@/lib/session';
import { CustomerBookingCard, SalonLogo } from '@/ui/customer';
import { Badge, Button, Card, Empty, ErrorBox, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

export default function Home() {
  const { account } = useSession();
  const home = useHome();
  const p = usePalette();
  const firstName = (account?.user.name ?? '').split(' ')[0];
  const salonSlug = account?.salon.slug;

  // QR متخصص پیش از ورود اسکن شده بود (بسته‌ی ۵): یک بار صفحه‌ی همان متخصص
  useEffect(() => {
    if (!salonSlug) {
      return;
    }
    void consumePendingSpecialist(salonSlug).then((id) => {
      if (id) {
        router.push(`/specialist/${id}`);
      }
    });
  }, [salonSlug]);

  return (
    <Screen refreshControl={<RefreshControl refreshing={home.isRefetching} onRefresh={() => home.refetch()} colors={[p.gold]} />}>
      <Row gap={3}>
        {account ? <SalonLogo salon={home.data?.salon ?? account.salon} size={52} /> : null}
        <View style={{ flex: 1 }}>
          <T weight="bold" size={18}>سلام{firstName ? ` ${firstName}` : ''}</T>
          <T muted>{home.data?.salon.name ?? account?.salon.name}</T>
        </View>
      </Row>

      <UpdateNotice />

      {home.isPending ? <Loading /> : home.error ? <ErrorBox error={home.error} onRetry={() => home.refetch()} /> : (
        <>
          {home.data.awaiting_payment_count > 0 ? (
            <Notice tone="warning" text={`${faDigits(home.data.awaiting_payment_count)} نوبت در انتظار پرداخت دارید؛ بدون پیش‌پرداخت در مهلتش لغو می‌شود.`} />
          ) : null}

          {home.data.salon.booking_paused ? (
            <Notice tone="warning" text={`${home.data.salon.booking_paused.message} نوبت‌های ثبت‌شده‌ی شما سر جایشان هستند.`} />
          ) : (
            <Button title="رزرو نوبت تازه" onPress={() => router.push('/book')} />
          )}

          {home.data.features?.includes('treatment_courses') ? (
            <Card onPress={() => router.push('/courses')}>
              <T weight="medium">دوره‌های درمانی</T>
              <T muted size={13}>چند جلسه را یک‌جا و ارزان‌تر بخرید؛ دوره‌های خودتان را هم این‌جا ببینید.</T>
            </Card>
          ) : null}

          <Row gap={3}>
            <Card style={{ flex: 1 }} onPress={() => router.push('/club')}>
              <T muted size={13}>امتیاز باشگاه</T>
              <T weight="bold" size={20}>{faDigits(home.data.loyalty_points)}</T>
            </Card>
            <Card style={{ flex: 1 }} onPress={() => router.push('/wallet')}>
              <T muted size={13}>کیف پول</T>
              <T weight="bold" size={16}>{toman(home.data.wallet_balance)}</T>
            </Card>
          </Row>

          {home.data.unread_notifications > 0 ? (
            <Card onPress={() => router.push('/notifications')}>
              <Row style={{ justifyContent: 'space-between' }}>
                <T weight="medium">اعلان‌ها</T>
                <Badge text={`${faDigits(home.data.unread_notifications)} تازه`} tone="gold" />
              </Row>
            </Card>
          ) : null}

          <SectionTitle>نوبت‌های پیش‌رو</SectionTitle>
          {home.data.upcoming_bookings.length === 0 ? (
            <Empty text="نوبت پیش‌رویی ندارید." />
          ) : (
            home.data.upcoming_bookings.map((b) => <CustomerBookingCard key={b.id} booking={b} />)
          )}
          {home.data.upcoming_bookings.length > 0 ? <Button kind="ghost" title="همه‌ی نوبت‌ها" onPress={() => router.push('/bookings')} /> : null}
        </>
      )}
    </Screen>
  );
}
