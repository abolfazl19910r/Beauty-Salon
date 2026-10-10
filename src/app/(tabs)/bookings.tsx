import { useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { addDays, isoDate } from '@/lib/jalali';
import { useBookings } from '@/lib/queries';
import type { BookingStatus } from '@/lib/types';
import { BookingCard } from '@/ui/booking';
import { Chip, Empty, ErrorBox, Loading, Row } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

const FILTERS: { key: BookingStatus | null; label: string }[] = [
  { key: null, label: 'همه' },
  { key: 'pending', label: 'در انتظار تأیید' },
  { key: 'confirmed', label: 'تأیید شده' },
  { key: 'completed', label: 'انجام شده' },
  { key: 'cancelled', label: 'لغو شده' },
];

const RANGES = [
  { key: 'upcoming', label: '۳۰ روز آینده', from: 0, to: 30 },
  { key: 'past', label: '۳۰ روز گذشته', from: -30, to: 0 },
];

export default function Bookings() {
  const p = usePalette();
  const [status, setStatus] = useState<BookingStatus | null>(null);
  const [range, setRange] = useState(RANGES[0]);
  const { from, to } = useMemo(() => ({ from: isoDate(addDays(new Date(), range.from)), to: isoDate(addDays(new Date(), range.to)) }), [range]);
  const query = useBookings(status, from, to);
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];

  return (
    <SafeAreaView edges={['left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <View style={{ gap: space(2), paddingTop: space(3) }}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: space(2), paddingHorizontal: space(4) }}>
          {RANGES.map((r) => <Chip key={r.key} label={r.label} active={range.key === r.key} onPress={() => setRange(r)} />)}
        </ScrollView>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: space(2), paddingHorizontal: space(4) }}>
          {FILTERS.map((f) => <Chip key={f.label} label={f.label} active={status === f.key} onPress={() => setStatus(f.key)} />)}
        </ScrollView>
      </View>
      {query.isPending ? <Loading /> : query.error ? (
        <View style={{ padding: space(4) }}><ErrorBox error={query.error} onRetry={() => query.refetch()} /></View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(b) => String(b.id)}
          contentContainerStyle={{ padding: space(4), gap: space(3) }}
          renderItem={({ item }) => <BookingCard booking={item} showDate />}
          ListEmptyComponent={<Empty text="نوبتی در این بازه نیست." />}
          onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
          onEndReachedThreshold={0.4}
          ListFooterComponent={query.isFetchingNextPage ? <Row style={{ justifyContent: 'center' }}><ActivityIndicator color={p.gold} /></Row> : null}
          refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
        />
      )}
    </SafeAreaView>
  );
}
