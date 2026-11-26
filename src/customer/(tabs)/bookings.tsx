import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useBookingsTab, useHome } from '@/lib/customer/queries';
import type { CustomerBooking, CustomerBookingTab } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { DayBox, MoreButton, TabChip } from '@/ui/agenda';
import { bookingMoreItems, CustomerStatusBadge, PaymentBadge } from '@/ui/customer';
import { Badge, Button, Card, Empty, ErrorBox, Loading, Row, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

const TABS: { key: CustomerBookingTab; label: string; empty: string }[] = [
  { key: 'upcoming', label: 'پیش رو', empty: 'نوبت پیش رویی ندارید.' },
  { key: 'past', label: 'گذشته', empty: 'نوبت گذشته‌ای ندارید.' },
  { key: 'cancelled', label: 'لغوشده', empty: 'نوبت لغوشده‌ای ندارید.' },
];

/**
 * «نوبت‌های من» — بسته‌ی ۵د (پیش‌نمایش تأییدشده، مثل وب): تب «پیش رو / گذشته / لغوشده» با شمار؛ هر ردیف جعبه‌ی روز، ساعت و خدمت،
 * وضعیت و پرداخت، یک کار اصلی با نام (همان قاعده‌ی سرور: پرداخت، تغییر زمان، ثبت نظر، رزرو دوباره…) و منوی «⋯».
 */
export default function Bookings() {
  const p = usePalette();
  const [tab, setTab] = useState<CustomerBookingTab>('upcoming');
  const query = useBookingsTab(tab);
  const phone = useHome().data?.salon_contact?.phone ?? null;
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];
  const counts = (query.data?.pages[0]?.meta as { counts?: Record<CustomerBookingTab, number> } | undefined)?.counts;

  return (
    <SafeAreaView edges={['left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ flexGrow: 0, flexShrink: 0 }} contentContainerStyle={{ gap: space(2), padding: space(4), paddingBottom: space(1), alignItems: 'center' }} accessibilityRole="tablist">
        {TABS.map((t) => <TabChip key={t.key} label={t.label} count={counts?.[t.key]} active={tab === t.key} onPress={() => setTab(t.key)} />)}
      </ScrollView>
      {query.isPending ? <Loading /> : (
        <FlatList
          data={items}
          keyExtractor={(b) => String(b.id)}
          contentContainerStyle={{ padding: space(4), gap: space(2.5), paddingBottom: space(10) }}
          ListHeaderComponent={query.error ? <ErrorBox error={query.error} onRetry={() => query.refetch()} /> : null}
          renderItem={({ item }) => <BookingRow booking={item} phone={phone} />}
          ListEmptyComponent={<Empty text={TABS.find((t) => t.key === tab)!.empty} />}
          onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
          ListFooterComponent={query.isFetchingNextPage ? <View style={{ padding: space(4) }}><ActivityIndicator color={p.gold} /></View> : null}
          refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
        />
      )}
    </SafeAreaView>
  );
}

function primaryButton(b: CustomerBooking): { title: string; strong: boolean; onPress: () => void } | null {
  const kind = b.primary?.[0]?.kind ?? (b.actions.pay ? 'pay' : b.actions.reschedule ? 'reschedule' : b.actions.rate ? 'review' : 'details');
  switch (kind) {
    case 'pay':
      return { title: `پرداخت ${toman(b.prepayment_amount)}`, strong: true, onPress: () => router.push(`/pay/${b.id}`) };
    case 'reschedule':
      return { title: 'تغییر زمان', strong: false, onPress: () => router.push(`/reschedule/${b.id}`) };
    case 'review':
      return { title: 'ثبت نظر', strong: true, onPress: () => router.push(`/booking/${b.id}`) };
    case 'rebook':
      return b.service ? { title: 'رزرو دوباره', strong: false, onPress: () => router.push(`/pick/${b.service!.id}${b.specialist ? `?specialist=${b.specialist.id}` : ''}`) } : null;
    case 'directions':
    case 'details':
    default:
      return { title: 'جزئیات', strong: false, onPress: () => router.push(`/booking/${b.id}`) };
  }
}

function BookingRow({ booking: b, phone }: { booking: CustomerBooking; phone: string | null }) {
  const primary = primaryButton(b);
  return (
    <Card onPress={() => router.push(`/booking/${b.id}`)} style={{ flexDirection: 'row', gap: space(3), padding: space(3.5) }}>
      <DayBox label={b.date_label ?? b.booking_time_label} />
      <View style={{ flex: 1, gap: space(1.5) }}>
        <View>
          <T weight="bold" size={15} numberOfLines={2}>{b.time_label ? `${b.time_label}، ` : ''}{b.service?.name ?? '—'}</T>
          <T muted size={13}>{b.specialist?.name ?? ''}{b.duration_minutes ? `، ${faDigits(b.duration_minutes)} دقیقه` : ''}</T>
        </View>
        {b.course?.label ? <Badge text={faDigits(b.course.label)} tone="info" /> : null}
        <Row gap={1.5} style={{ flexWrap: 'wrap' }}>
          <CustomerStatusBadge booking={b} />
          <PaymentBadge booking={b} />
        </Row>
        <Row gap={2}>
          {primary ? <View style={{ flex: 1 }}><Button small kind={primary.strong ? 'strong' : 'secondary'} title={primary.title} onPress={primary.onPress} /></View> : <View style={{ flex: 1 }} />}
          <MoreButton items={bookingMoreItems(b, phone)} label="کارهای بیشتر این نوبت" size={40} />
        </Row>
      </View>
    </Card>
  );
}
