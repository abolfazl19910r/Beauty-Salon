import { ActivityIndicator, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useLoyaltyHistory } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Card, Empty, ErrorBox, Loading, Row, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

const LABELS: Record<string, string> = { earned: 'دریافت', spent: 'خرج پاداش', expired: 'منقضی‌شده' };

export default function ClubHistory() {
  const p = usePalette();
  const query = useLoyaltyHistory();
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];

  if (query.isPending) {
    return <Loading />;
  }

  return (
    <SafeAreaView edges={['bottom', 'left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <FlatList
        data={items}
        keyExtractor={(e) => String(e.id)}
        contentContainerStyle={{ padding: space(4), gap: space(3) }}
        ListHeaderComponent={query.error ? <ErrorBox error={query.error} onRetry={() => query.refetch()} /> : null}
        renderItem={({ item }) => (
          <Card>
            <Row style={{ justifyContent: 'space-between' }}>
              <T weight="medium">{LABELS[item.type] ?? item.type}</T>
              <T weight="bold" color={item.points >= 0 ? p.success : p.textMuted}>{item.points >= 0 ? '+' : '−'}{faDigits(Math.abs(item.points))}</T>
            </Row>
            {item.description ? <T muted size={13}>{item.description}</T> : null}
            <T muted size={12}>{faDigits(item.created_at_jalali ?? '')}</T>
          </Card>
        )}
        ListEmptyComponent={<Empty text="هنوز امتیازی نگرفته‌اید. بعد از هر نوبت انجام‌شده امتیاز می‌گیرید." />}
        onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
        ListFooterComponent={query.isFetchingNextPage ? <ActivityIndicator color={p.gold} /> : null}
        refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
      />
    </SafeAreaView>
  );
}
