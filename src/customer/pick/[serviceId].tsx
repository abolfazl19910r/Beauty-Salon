import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Image, View } from 'react-native';

import { useServiceSpecialists, useServices } from '@/lib/customer/queries';
import { faDigits, toman } from '@/lib/format';
import { SlotPicker } from '@/ui/SlotPicker';
import { Button, Card, Empty, ErrorBox, Loading, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/** رزرو، قدم ۲: متخصص (اگر از پروفایل متخصص آمده، از پیش انتخاب‌شده) ← روز ← ساعت. */
export default function Pick() {
  const params = useLocalSearchParams<{ serviceId: string; specialist?: string }>();
  const serviceId = Number(params.serviceId);
  const p = usePalette();
  const services = useServices();
  const specialists = useServiceSpecialists(serviceId);
  const service = services.data?.services.find((s) => s.id === serviceId);
  const [specialistId, setSpecialistId] = useState<number | null>(params.specialist ? Number(params.specialist) : null);
  const [date, setDate] = useState<string | null>(null);
  const [time, setTime] = useState<string | null>(null);

  // تنها متخصص این خدمت خودکار انتخاب می‌شود
  useEffect(() => {
    if (specialistId === null && specialists.data?.length === 1) {
      setSpecialistId(specialists.data[0].id);
    }
  }, [specialists.data, specialistId]);

  const chooseSpecialist = (id: number) => {
    setSpecialistId(id);
    setDate(null);
    setTime(null);
  };

  return (
    <Screen>
      <Stack.Screen options={{ title: service?.name ?? 'انتخاب زمان' }} />
      {service ? (
        <Card>
          <Row style={{ justifyContent: 'space-between' }}>
            <T weight="bold">{service.name}</T>
            <T weight="medium">{toman(service.price)}</T>
          </Row>
          <T muted size={13}>{faDigits(service.duration_minutes)} دقیقه</T>
        </Card>
      ) : null}

      <SectionTitle>متخصص</SectionTitle>
      {specialists.isPending ? <Loading /> : specialists.error ? <ErrorBox error={specialists.error} onRetry={() => specialists.refetch()} /> : specialists.data.length === 0 ? (
        <Empty text="فعلاً متخصصی این خدمت را انجام نمی‌دهد." />
      ) : (
        specialists.data.map((s) => (
          <Card key={s.id} onPress={() => chooseSpecialist(s.id)} style={specialistId === s.id ? { borderColor: p.gold, borderWidth: 2 } : undefined}>
            <Row gap={3}>
              {s.photo_url ? <Image source={{ uri: s.photo_url }} style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: p.surfaceMuted }} /> : null}
              <View style={{ flex: 1 }}>
                <T weight="medium">{s.name}</T>
                {s.rating_avg ? <T muted size={12}>امتیاز {faDigits(s.rating_avg)} از ۵</T> : null}
              </View>
            </Row>
          </Card>
        ))
      )}

      {specialistId ? (
        <SlotPicker
          specialistId={specialistId}
          serviceId={serviceId}
          date={date}
          onDate={(d) => { setDate(d); setTime(null); }}
          value={time}
          onChange={setTime}
        />
      ) : null}

      <Button
        title="ادامه"
        disabled={!specialistId || !time}
        onPress={() => router.push({ pathname: '/checkout', params: { service_id: String(serviceId), specialist_id: String(specialistId), booking_time: time ?? '' } })}
      />
    </Screen>
  );
}
