import { useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useBookings } from '@/lib/customer/queries';
import { CustomerBookingCard } from '@/ui/customer';
import { Chip, Empty, ErrorBox, Loading, Row } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

export default function Bookings() {
  const p = usePalette();
  const [scope, setScope] = useState<'upcoming' | 'past'>('upcoming');
  const query = useBookings(scope);
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];

  return (
    <SafeAreaView edges={['left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <Row style={{ padding: space(4), paddingBottom: 0 }}>
        <Chip label="پیش‌رو" active={scope === 'upcoming'} onPress={() => setScope('upcoming')} />
        <Chip label="گذشته و لغوشده" active={scope === 'past'} onPress={() => setScope('past')} />
      </Row>
      {query.isPending ? <Loading /> : (
        <FlatList
          data={items}
          keyExtractor={(b) => String(b.id)}
          contentContainerStyle={{ padding: space(4), gap: space(3), paddingBottom: space(10) }}
          ListHeaderComponent={query.error ? <ErrorBox error={query.error} onRetry={() => query.refetch()} /> : null}
          renderItem={({ item }) => <CustomerBookingCard booking={item} />}
          ListEmptyComponent={<Empty text={scope === 'upcoming' ? 'نوبت پیش‌رویی ندارید.' : 'نوبت گذشته‌ای ندارید.'} />}
          onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
          ListFooterComponent={query.isFetchingNextPage ? <View style={{ padding: space(4) }}><ActivityIndicator color={p.gold} /></View> : null}
          refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
        />
      )}
    </SafeAreaView>
  );
}
