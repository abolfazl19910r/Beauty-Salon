import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';

import { bookingTimeLabel } from '@/lib/customer/money';
import { useBooking, useBookingActions } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { SlotPicker } from '@/ui/SlotPicker';
import { Button, Card, ErrorBox, Loading, Notice, Screen, T } from '@/ui/kit';

/** جابه‌جایی نوبت: همان متخصص و خدمت (مثل وب، BookingRescheduleService)؛ فقط تا ۲۴ ساعت پیش از نوبت. */
export default function Reschedule() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const booking = useBooking(Number(id));
  const { reschedule } = useBookingActions();
  const [date, setDate] = useState<string | null>(null);
  const [time, setTime] = useState<string | null>(null);

  if (booking.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (booking.error) {
    return <Screen><ErrorBox error={booking.error} onRetry={() => booking.refetch()} /></Screen>;
  }
  const b = booking.data;
  if (!b.actions.reschedule || !b.service || !b.specialist) {
    return <Screen><Notice tone="warning" text="زمان این نوبت دیگر از اپ عوض نمی‌شود." /></Screen>;
  }

  return (
    <Screen>
      <Card>
        <T weight="bold">{b.service.name} — {b.specialist.name}</T>
        <T muted>زمان فعلی: {faDigits(b.booking_time_label ?? '')}</T>
      </Card>
      <SlotPicker specialistId={b.specialist.id} serviceId={b.service.id} date={date} onDate={(d) => { setDate(d); setTime(null); }} value={time} onChange={setTime} />
      {time ? <Notice text={`زمان تازه: ${bookingTimeLabel(time)}`} /> : null}
      {reschedule.error ? <ErrorBox error={reschedule.error} /> : null}
      <Button
        title="ثبت زمان تازه"
        disabled={!time}
        loading={reschedule.isPending}
        onPress={() => time && reschedule.mutate({ id: b.id, booking_time: time }, { onSuccess: () => router.back() })}
      />
    </Screen>
  );
}
