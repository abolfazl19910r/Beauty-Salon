import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { RefreshControl, ScrollView } from 'react-native';

import { useServices } from '@/lib/customer/queries';
import { faDigits, toman } from '@/lib/format';
import { Button, Card, Chip, Empty, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/** رزرو، قدم ۱: خدمت (با دسته‌ها). بعد: متخصص ← روز ← ساعت (pick) ← پیش‌فاکتور (checkout) ← پرداخت. */
export default function Book() {
  const services = useServices();
  const p = usePalette();
  const [category, setCategory] = useState<number | null>(null);

  const list = useMemo(
    () => (services.data?.services ?? []).filter((s) => category === null || s.category?.id === category),
    [services.data, category],
  );
  const categories = (services.data?.categories ?? []).filter((c) => services.data?.services.some((s) => s.category?.id === c.id));

  return (
    <Screen refreshControl={<RefreshControl refreshing={services.isRefetching} onRefresh={() => services.refetch()} colors={[p.gold]} />}>
      <T weight="bold" size={17}>چه خدمتی می‌خواهید؟</T>
      {categories.length > 1 ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: space(2) }}>
          <Chip label="همه" active={category === null} onPress={() => setCategory(null)} />
          {categories.map((c) => <Chip key={c.id} label={c.name} active={category === c.id} onPress={() => setCategory(c.id)} />)}
        </ScrollView>
      ) : null}

      {services.isPending ? <Loading /> : services.error ? <ErrorBox error={services.error} onRetry={() => services.refetch()} /> : list.length === 0 ? (
        <Empty text="خدمتی برای رزرو آنلاین تعریف نشده است." />
      ) : (
        list.map((s) => (
          <Card key={s.id} onPress={() => router.push(`/pick/${s.id}`)}>
            <Row style={{ justifyContent: 'space-between' }}>
              <T weight="bold" style={{ flexShrink: 1 }}>{s.name}</T>
              <T weight="medium">{toman(s.price)}</T>
            </Row>
            {s.description ? <T muted size={13} numberOfLines={2}>{s.description}</T> : null}
            <Row style={{ justifyContent: 'space-between' }}>
              <T muted size={13}>{faDigits(s.duration_minutes)} دقیقه</T>
              {s.prepayment_amount > 0 ? <T muted size={13}>پیش‌پرداخت: {toman(s.prepayment_amount)}</T> : null}
            </Row>
          </Card>
        ))
      )}
      <Button kind="ghost" title="انتخاب بر اساس متخصص" onPress={() => router.push('/specialists')} />
    </Screen>
  );
}
