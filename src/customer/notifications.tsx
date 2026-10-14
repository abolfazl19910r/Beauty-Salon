import { router } from 'expo-router';
import { ActivityIndicator, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useNotificationMutations, useNotifications } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Button, Card, Empty, ErrorBox, Loading, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/** صندوق اعلان فقط‌خواندنی (تصمیم ۲۰۲۶-۱۰-۱۱؛ پوش در بسته‌ی ۴)؛ اعلان نوبت → جزئیات همان نوبت */
export default function Notifications() {
  const p = usePalette();
  const query = useNotifications();
  const { read, readAll } = useNotificationMutations();
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];
  const unread = Number(query.data?.pages[0]?.meta.unread_count ?? 0);

  if (query.isPending) {
    return <Loading />;
  }

  return (
    <SafeAreaView edges={['bottom', 'left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <FlatList
        data={items}
        keyExtractor={(n) => n.id}
        contentContainerStyle={{ padding: space(4), gap: space(3) }}
        ListHeaderComponent={
          <>
            {query.error ? <ErrorBox error={query.error} onRetry={() => query.refetch()} /> : null}
            {unread > 0 ? <Button small kind="secondary" title="همه خوانده شد" loading={readAll.isPending} onPress={() => readAll.mutate()} /> : null}
          </>
        }
        renderItem={({ item }) => (
          <Card
            style={item.read ? undefined : { borderColor: p.gold, borderWidth: 1 }}
            onPress={() => {
              if (!item.read) {
                read.mutate(item.id);
              }
              if (item.booking_id) {
                router.push(`/booking/${item.booking_id}`);
              }
            }}
          >
            {item.title ? <T weight="bold">{item.title}</T> : null}
            <T weight={item.read || item.title ? 'regular' : 'bold'}>{item.message}</T>
            <T muted size={12}>{faDigits(item.created_at_jalali ?? '')}</T>
          </Card>
        )}
        ListEmptyComponent={<Empty text="اعلانی ندارید." />}
        onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
        ListFooterComponent={query.isFetchingNextPage ? <ActivityIndicator color={p.gold} /> : null}
        refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
      />
    </SafeAreaView>
  );
}
