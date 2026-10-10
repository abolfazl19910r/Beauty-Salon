import { RefreshControl } from 'react-native';

import { faDigits, toman } from '@/lib/format';
import { useToday } from '@/lib/queries';
import { useSession } from '@/lib/session';
import { BookingCard } from '@/ui/booking';
import { Card, Empty, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

export default function Today() {
  const { account } = useSession();
  const today = useToday();
  const p = usePalette();

  return (
    <Screen refreshControl={<RefreshControl refreshing={today.isRefetching} onRefresh={() => today.refetch()} colors={[p.gold]} />}>
      <T weight="bold" size={18}>سلام {account?.specialist?.name ?? account?.user.name}</T>
      <T muted>{account?.salon.name}{today.data ? ` — ${today.data.date_label}` : ''}</T>

      {today.isPending ? <Loading /> : today.error ? <ErrorBox error={today.error} onRetry={() => today.refetch()} /> : (
        <>
          <Row gap={3}>
            <Card style={{ flex: 1 }}>
              <T muted size={13}>نوبت‌های امروز</T>
              <T weight="bold" size={22}>{faDigits(today.data.summary.count)}</T>
            </Card>
            <Card style={{ flex: 1 }}>
              <T muted size={13}>در انتظار تأیید</T>
              <T weight="bold" size={22} color={today.data.summary.pending_count > 0 ? p.warning : undefined}>{faDigits(today.data.summary.pending_count)}</T>
            </Card>
          </Row>
          <Card>
            <T muted size={13}>درآمد پرداخت‌شده‌ی امروز</T>
            <T weight="bold" size={18}>{toman(today.data.summary.revenue)}</T>
          </Card>
          {today.data.bookings.length === 0 ? <Empty text="برای امروز نوبتی ندارید." /> : today.data.bookings.map((b) => <BookingCard key={b.id} booking={b} />)}
        </>
      )}
    </Screen>
  );
}
