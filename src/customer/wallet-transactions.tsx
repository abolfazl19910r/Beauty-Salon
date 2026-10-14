import { ActivityIndicator, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useWalletTransactions } from '@/lib/customer/queries';
import { faDigits, toman } from '@/lib/format';
import { Card, Empty, ErrorBox, Loading, Row, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

const LABELS: Record<string, string> = { deposit: 'شارژ', payment: 'پرداخت نوبت', refund: 'برگشت وجه', adjustment: 'اصلاح سالن' };

export default function WalletTransactions() {
  const p = usePalette();
  const query = useWalletTransactions();
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];

  if (query.isPending) {
    return <Loading />;
  }

  return (
    <SafeAreaView edges={['bottom', 'left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <FlatList
        data={items}
        keyExtractor={(t) => String(t.id)}
        contentContainerStyle={{ padding: space(4), gap: space(3) }}
        ListHeaderComponent={query.error ? <ErrorBox error={query.error} onRetry={() => query.refetch()} /> : null}
        renderItem={({ item }) => {
          const out = item.type === 'payment' || item.amount < 0;
          return (
            <Card>
              <Row style={{ justifyContent: 'space-between' }}>
                <T weight="medium">{LABELS[item.type] ?? item.type}</T>
                <T weight="bold" color={out ? p.danger : p.success}>{out ? '−' : '+'} {toman(Math.abs(item.amount))}</T>
              </Row>
              {item.description ? <T muted size={13}>{item.description}</T> : null}
              <Row style={{ justifyContent: 'space-between' }}>
                <T muted size={12}>{faDigits(item.created_at_jalali ?? '')}</T>
                <T muted size={12}>مانده: {toman(item.balance_after)}</T>
              </Row>
            </Card>
          );
        }}
        ListEmptyComponent={<Empty text="تراکنشی ندارید." />}
        onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
        ListFooterComponent={query.isFetchingNextPage ? <ActivityIndicator color={p.gold} /> : null}
        refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
      />
    </SafeAreaView>
  );
}
