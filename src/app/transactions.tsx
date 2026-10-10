import { ActivityIndicator, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { faDigits, toman } from '@/lib/format';
import { useTransactions } from '@/lib/queries';
import { Card, Empty, ErrorBox, Loading, Row, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

const TYPES: Record<string, string> = {
  income: 'درآمد نوبت',
  withdrawal: 'برداشت',
  cancellation_fee: 'جریمه‌ی لغو',
  refund: 'برگشت',
  adjustment: 'اصلاح مدیر',
};

export default function Transactions() {
  const p = usePalette();
  const query = useTransactions();
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];

  if (query.isPending) {
    return <Loading />;
  }

  return (
    <SafeAreaView edges={['bottom', 'left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      {query.error ? <ErrorBox error={query.error} onRetry={() => query.refetch()} /> : null}
      <FlatList
        data={items}
        keyExtractor={(t) => String(t.id)}
        contentContainerStyle={{ padding: space(4), gap: space(3) }}
        renderItem={({ item }) => (
          <Card>
            <Row style={{ justifyContent: 'space-between' }}>
              <T weight="medium">{TYPES[item.type] ?? item.type}</T>
              <T weight="bold" color={item.type === 'income' || item.type === 'refund' ? p.success : p.danger}>{toman(item.amount)}</T>
            </Row>
            {item.description ? <T muted size={13}>{item.description}</T> : null}
            <T muted size={12}>{faDigits(item.created_at_jalali)}{item.balance_after !== null ? ` — مانده ${toman(item.balance_after)}` : ''}</T>
          </Card>
        )}
        ListEmptyComponent={<Empty text="تراکنشی ثبت نشده است." />}
        onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
        ListFooterComponent={query.isFetchingNextPage ? <ActivityIndicator color={p.gold} /> : null}
        refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
      />
    </SafeAreaView>
  );
}
