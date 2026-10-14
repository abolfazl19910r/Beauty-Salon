import { router, Stack, useLocalSearchParams } from 'expo-router';
import { Image, View } from 'react-native';

import { useSpecialist } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Stars } from '@/ui/customer';
import { Card, Chip, Empty, ErrorBox, Loading, Screen, SectionTitle, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/** پروفایل متخصص: خدمت‌ها (لمس → رزرو همان خدمت با همین متخصص) و ۵ نظر آخر (فقط نام کوچک مشتری‌ها) */
export default function SpecialistProfile() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const p = usePalette();
  const sp = useSpecialist(Number(id));

  if (sp.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (sp.error) {
    return <Screen><ErrorBox error={sp.error} onRetry={() => sp.refetch()} /></Screen>;
  }
  const s = sp.data;

  return (
    <Screen>
      <Stack.Screen options={{ title: s.name }} />
      <View style={{ alignItems: 'center', gap: space(2) }}>
        {s.photo_url ? <Image source={{ uri: s.photo_url }} style={{ width: 96, height: 96, borderRadius: 48, backgroundColor: p.surfaceMuted }} /> : null}
        <T weight="bold" size={20}>{s.name}</T>
        {s.rating_avg ? <Stars value={Math.round(s.rating_avg)} size={18} /> : null}
        <T muted size={13}>
          {s.rating_avg ? `${faDigits(s.rating_avg)} از ۵ (${faDigits(s.rating_count ?? 0)} نظر)` : 'هنوز امتیازی ندارد'}
          {s.completed_bookings ? ` — ${faDigits(s.completed_bookings)} نوبت انجام‌شده` : ''}
        </T>
      </View>

      <SectionTitle>رزرو با {s.name}</SectionTitle>
      {s.services?.length ? (
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space(2) }}>
          {s.services.map((svc) => (
            <Chip key={svc.id} label={svc.name} active={false} onPress={() => router.push({ pathname: '/pick/[serviceId]', params: { serviceId: String(svc.id), specialist: String(s.id) } })} />
          ))}
        </View>
      ) : <Empty text="خدمتی برای رزرو ندارد." />}

      <SectionTitle>نظرها</SectionTitle>
      {s.reviews.length === 0 ? <Empty text="هنوز نظری ثبت نشده است." /> : s.reviews.map((r, i) => (
        <Card key={i}>
          <Stars value={r.rating} size={16} />
          {r.review ? <T>{r.review}</T> : null}
          <T muted size={12}>{r.customer} — {faDigits(r.date_jalali)}</T>
        </Card>
      ))}
    </Screen>
  );
}
