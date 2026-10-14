import { ScrollView, View } from 'react-native';

import { useDates, useSlots } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Chip, Empty, ErrorBox, Loading, SectionTitle, T } from './kit';
import { space } from './theme';

/**
 * روز (۳۰ روز کاری آینده‌ی متخصص، بدون مرخصی/تعطیلی) و ساعت آزاد با مدت همان خدمت —
 * GET specialists/{id}/dates و slots. مقدار انتخاب‌شده booking_time ISO سرور است.
 */
export function SlotPicker({
  specialistId,
  serviceId,
  date,
  onDate,
  value,
  onChange,
}: {
  specialistId: number;
  serviceId: number;
  date: string | null;
  onDate: (date: string) => void;
  value: string | null;
  onChange: (bookingTime: string) => void;
}) {
  const dates = useDates(specialistId, serviceId);
  const slots = useSlots(specialistId, serviceId, date);

  return (
    <View style={{ gap: space(3) }}>
      <SectionTitle>روز</SectionTitle>
      {dates.isPending ? <Loading /> : dates.error ? <ErrorBox error={dates.error} onRetry={() => dates.refetch()} /> : dates.data.length === 0 ? (
        <Empty text="این متخصص در ۳۰ روز آینده روز کاری آزاد ندارد." />
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: space(2) }}>
          {dates.data.map((d) => <Chip key={d.date} label={faDigits(d.label)} active={date === d.date} onPress={() => onDate(d.date)} />)}
        </ScrollView>
      )}

      {date ? (
        <>
          <SectionTitle>ساعت</SectionTitle>
          {slots.isPending ? <Loading /> : slots.error ? <ErrorBox error={slots.error} onRetry={() => slots.refetch()} /> : slots.data.length === 0 ? (
            <Empty text="این روز ساعت آزادی ندارد؛ روز دیگری را امتحان کنید." />
          ) : (
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space(2) }}>
              {slots.data.map((s) => <Chip key={s.booking_time} label={faDigits(s.time)} active={value === s.booking_time} onPress={() => onChange(s.booking_time)} />)}
            </View>
          )}
        </>
      ) : (
        <T muted>یک روز انتخاب کنید.</T>
      )}
    </View>
  );
}
