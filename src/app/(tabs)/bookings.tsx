import { useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Ionicons from '@expo/vector-icons/Ionicons';

import { enDigits } from '@/lib/format';
import { useBookingsTab } from '@/lib/queries';
import type { StaffBookingTab } from '@/lib/types';
import { TabChip } from '@/ui/agenda';
import { StaffBookingRow } from '@/ui/booking';
import { Empty, ErrorBox, Loading, Row } from '@/ui/kit';
import { fonts, space, usePalette } from '@/ui/theme';

const TABS: { key: StaffBookingTab; label: string; empty: string }[] = [
  { key: 'upcoming', label: 'پیش رو', empty: 'نوبت پیش رویی ندارید.' },
  { key: 'pending', label: 'منتظر تأیید', empty: 'نوبتی منتظر تأیید شما نیست.' },
  { key: 'past', label: 'گذشته', empty: 'نوبت گذشته‌ای نیست.' },
  { key: 'cancelled', label: 'لغوشده', empty: 'نوبت لغوشده‌ای نیست.' },
];

/**
 * نوبت‌های کارشناس — بسته‌ی ۵د (پیش‌نمایش تأییدشده، مثل پنل وب): جستجوی نام یا شماره‌ی مشتری و تب «پیش رو / منتظر تأیید / گذشته /
 * لغوشده» با شمار (سرور: SpecialistBookingListFilter)، هر ردیف یک کار اصلی + «⋯».
 */
export default function Bookings() {
  const p = usePalette();
  const [tab, setTab] = useState<StaffBookingTab>('upcoming');
  const [text, setText] = useState('');
  const [q, setQ] = useState('');
  useEffect(() => {
    const t = setTimeout(() => setQ(enDigits(text.trim())), 350);
    return () => clearTimeout(t);
  }, [text]);
  const query = useBookingsTab(tab, q);
  const items = query.data?.pages.flatMap((page) => page.data) ?? [];
  const counts = (query.data?.pages[0]?.meta as { counts?: Record<StaffBookingTab, number> } | undefined)?.counts;

  return (
    <SafeAreaView edges={['left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      <View style={{ gap: space(2.5), paddingTop: space(3), paddingHorizontal: space(4) }}>
        <Row gap={2} style={{ minHeight: 44, paddingHorizontal: space(3.5), borderRadius: 22, borderWidth: 1, borderColor: p.borderStrong, backgroundColor: p.surface }}>
          <Ionicons name="search-outline" size={17} color={p.textMuted} />
          <TextInput
            value={text}
            onChangeText={setText}
            placeholder="نام یا شماره‌ی مشتری"
            placeholderTextColor={p.textMuted}
            accessibilityLabel="جستجوی نام یا شماره‌ی مشتری"
            returnKeyType="search"
            style={{ flex: 1, fontFamily: fonts.regular, fontSize: 14, color: p.text, textAlign: 'right', writingDirection: 'rtl', paddingVertical: space(2) }}
          />
        </Row>
      </View>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ flexGrow: 0, flexShrink: 0 }} contentContainerStyle={{ gap: space(1.5), paddingHorizontal: space(4), paddingVertical: space(2.5), alignItems: 'center' }} accessibilityRole="tablist">
        {TABS.map((t) => <TabChip key={t.key} label={t.label} count={counts?.[t.key]} active={tab === t.key} onPress={() => setTab(t.key)} />)}
      </ScrollView>
      {query.isPending ? <Loading /> : query.error ? (
        <View style={{ padding: space(4) }}><ErrorBox error={query.error} onRetry={() => query.refetch()} /></View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(b) => String(b.id)}
          contentContainerStyle={{ padding: space(4), paddingTop: space(1), gap: space(2.5), paddingBottom: space(10) }}
          renderItem={({ item }) => <StaffBookingRow booking={item} />}
          ListEmptyComponent={<Empty text={q ? 'نوبتی با این جستجو پیدا نشد.' : TABS.find((t) => t.key === tab)!.empty} />}
          onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
          onEndReachedThreshold={0.4}
          ListFooterComponent={query.isFetchingNextPage ? <Row style={{ justifyContent: 'center' }}><ActivityIndicator color={p.gold} /></Row> : null}
          refreshControl={<RefreshControl refreshing={query.isRefetching && !query.isFetchingNextPage} onRefresh={() => query.refetch()} colors={[p.gold]} />}
        />
      )}
    </SafeAreaView>
  );
}
