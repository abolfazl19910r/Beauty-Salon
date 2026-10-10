import { useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Alert, Linking } from 'react-native';

import { faDigits, toman } from '@/lib/format';
import { useBooking, useBookingAction } from '@/lib/queries';
import { StatusBadge } from '@/ui/booking';
import { Button, Card, ErrorBox, Field, KeyValue, Loading, Notice, Row, Screen, T } from '@/ui/kit';

export default function BookingDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const booking = useBooking(Number(id));
  const action = useBookingAction();
  const [cancelling, setCancelling] = useState(false);
  const [reason, setReason] = useState('');

  if (booking.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (booking.error) {
    return <Screen><ErrorBox error={booking.error} onRetry={() => booking.refetch()} /></Screen>;
  }

  const b = booking.data;
  const run = (kind: 'confirm' | 'complete', title: string) =>
    Alert.alert(title, `${b.customer?.name ?? ''} — ${b.booking_time_jalali}`, [
      { text: 'انصراف', style: 'cancel' },
      { text: 'بله', onPress: () => action.mutate({ id: b.id, action: kind }) },
    ]);

  return (
    <Screen>
      <Card>
        <Row style={{ justifyContent: 'space-between' }}>
          <T weight="bold" size={18}>{faDigits(b.booking_time_jalali)}</T>
          <StatusBadge booking={b} />
        </Row>
        <KeyValue label="خدمت" value={b.service?.name ?? '—'} />
        {b.duration_minutes ? <KeyValue label="مدت" value={`${faDigits(b.duration_minutes)} دقیقه`} /> : null}
        <KeyValue label="مشتری" value={b.customer?.name ?? '—'} />
        {b.customer?.phone ? (
          <Row style={{ justifyContent: 'space-between' }}>
            <T muted size={14}>موبایل</T>
            <Button small kind="ghost" title={faDigits(b.customer.phone)} onPress={() => Linking.openURL(`tel:${b.customer?.phone}`)} />
          </Row>
        ) : null}
      </Card>

      <Card>
        <KeyValue label="قیمت خدمت" value={toman(b.service?.price)} />
        <KeyValue label="پیش‌پرداخت" value={`${toman(b.prepayment_amount)}${b.payment_status === 'paid' ? ' (پرداخت شده)' : ''}`} />
        {b.discount_amount > 0 ? <KeyValue label="تخفیف" value={toman(b.discount_amount)} /> : null}
        <KeyValue label="باقی‌مانده (حضوری)" value={toman(b.remaining_amount)} />
      </Card>

      {b.notes ? <Notice text={`یادداشت: ${b.notes}`} /> : null}
      {b.cancellation_reason ? <Notice tone="warning" text={`دلیل لغو: ${b.cancellation_reason}`} /> : null}
      {action.error ? <ErrorBox error={action.error} /> : null}

      {b.actions.confirm ? <Button title="پذیرش نوبت" onPress={() => run('confirm', 'پذیرش نوبت؟')} loading={action.isPending} /> : null}
      {b.actions.complete ? <Button title="انجام شد" onPress={() => run('complete', 'نوبت انجام شد؟')} loading={action.isPending} /> : null}
      {b.actions.cancel && !cancelling ? <Button kind="danger" title={b.status === 'pending' ? 'رد نوبت' : 'لغو نوبت'} onPress={() => setCancelling(true)} /> : null}
      {cancelling ? (
        <Card>
          <Notice tone="warning" text="مبلغ پیش‌پرداخت طبق قوانین سالن به مشتری برمی‌گردد و ممکن است جریمه‌ی لغو اعمال شود." />
          <Field label="دلیل (برای مشتری پیامک می‌شود)" value={reason} onChangeText={setReason} maxLength={500} multiline />
          <Row>
            <Button kind="secondary" title="انصراف" onPress={() => setCancelling(false)} />
            <Button
              kind="danger"
              title="لغو قطعی"
              loading={action.isPending}
              onPress={() => action.mutate({ id: b.id, action: 'cancel', reason: reason.trim() || undefined }, { onSuccess: () => setCancelling(false) })}
            />
          </Row>
        </Card>
      ) : null}
    </Screen>
  );
}
