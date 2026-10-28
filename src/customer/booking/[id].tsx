import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Alert, RefreshControl } from 'react-native';

import { errorMessage } from '@/lib/api';
import { useBooking, useBookingActions, useSalonDetail } from '@/lib/customer/queries';
import { needsPrepayment } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { CustomerStatusBadge, PayCountdown, Stars } from '@/ui/customer';
import { Button, Card, ErrorBox, Field, KeyValue, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

/** جزئیات نوبت؛ فقط دکمه‌هایی که `actions` سرور اجازه می‌دهد (همان قاعده‌های وب: BookingPolicy، isPayable). */
export default function BookingDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const bookingId = Number(id);
  const p = usePalette();
  const booking = useBooking(bookingId);
  const salon = useSalonDetail();
  const { cancel, rate } = useBookingActions();
  const [stars, setStars] = useState(0);
  const [review, setReview] = useState('');

  if (booking.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (booking.error) {
    return <Screen><ErrorBox error={booking.error} onRetry={() => booking.refetch()} /></Screen>;
  }
  const b = booking.data;
  const rules = salon.data?.rules;

  const askCancel = () => {
    const fee = rules && rules.cancellation_fee_percentage > 0 && b.payment_status === 'paid'
      ? `\nطبق قانون سالن، لغو کمتر از ${faDigits(rules.cancellation_fee_within_hours)} ساعت پیش از نوبت ${faDigits(rules.cancellation_fee_percentage)}٪ پیش‌پرداخت را جریمه دارد؛ باقی به کیف پول شما برمی‌گردد.`
      : b.payment_status === 'paid' ? '\nپیش‌پرداخت به کیف پول شما برمی‌گردد.' : '';
    Alert.alert('لغو نوبت؟', `${b.service?.name ?? ''} — ${faDigits(b.booking_time_label ?? '')}${fee}`, [
      { text: 'نه', style: 'cancel' },
      {
        text: 'لغو نوبت',
        style: 'destructive',
        onPress: () => cancel.mutate(b.id, { onError: (e) => Alert.alert('لغو نشد', errorMessage(e)) }),
      },
    ]);
  };

  return (
    <Screen refreshControl={<RefreshControl refreshing={booking.isRefetching} onRefresh={() => booking.refetch()} colors={[p.gold]} />}>
      <Card>
        <Row style={{ justifyContent: 'space-between' }}>
          <T weight="bold" size={17} style={{ flexShrink: 1 }}>{b.service?.name ?? '—'}</T>
          <CustomerStatusBadge booking={b} />
        </Row>
        <T>{faDigits(b.booking_time_label ?? b.booking_time_jalali ?? '')}</T>
        <T muted>{b.specialist?.name}{b.duration_minutes ? ` — ${faDigits(b.duration_minutes)} دقیقه` : ''}</T>
        {b.pay_before && b.actions.pay ? <PayCountdown until={b.pay_before} /> : null}
      </Card>

      {b.course ? (
        <Card onPress={() => router.push(`/course/${b.course!.id}`)}>
          <T weight="medium">{faDigits(b.course.label ?? '')}</T>
          <T muted size={14}>دوره‌ی «{b.course.name}» — هزینه از قبل با دوره پرداخت شده است.</T>
        </Card>
      ) : (
      <Card>
        {b.service ? <KeyValue label="قیمت خدمت" value={toman(b.service.price)} /> : null}
        {needsPrepayment(b) || b.payment_status === 'paid'
          ? <KeyValue label={b.payment_status === 'paid' ? 'پیش‌پرداخت (پرداخت شد)' : 'پیش‌پرداخت'} value={toman(b.prepayment_amount)} />
          : null}
        {b.discount_amount > 0 ? <KeyValue label={`تخفیف${b.discount_code ? ` (${b.discount_code})` : ''}`} value={`− ${toman(b.discount_amount)}`} /> : null}
        <KeyValue label={needsPrepayment(b) || b.payment_status === 'paid' ? 'پرداخت در سالن' : 'پرداخت در سالن (بدون پیش‌پرداخت)'} value={toman(b.remaining_amount)} />
      </Card>
      )}

      {b.status === 'cancelled' && b.cancellation_reason ? <Notice tone="warning" text={`دلیل لغو: ${b.cancellation_reason}`} /> : null}
      {b.status === 'pending' ? <Notice text="نوبت شما ثبت شده و منتظر تأیید سالن است." /> : null}

      {b.actions.pay ? <Button title={`پرداخت پیش‌پرداخت (${toman(b.prepayment_amount)})`} onPress={() => router.push(`/pay/${b.id}`)} /> : null}
      {b.actions.reschedule ? <Button kind="secondary" title="تغییر زمان" onPress={() => router.push(`/reschedule/${b.id}`)} /> : null}
      {b.actions.cancel ? <Button kind="danger" title="لغو نوبت" onPress={askCancel} loading={cancel.isPending} /> : null}
      {!b.actions.cancel && (b.status === 'pending' || b.status === 'confirmed') ? (
        <T muted size={13}>لغو یا تغییر زمان از اپ فقط تا {faDigits(rules?.cancel_deadline_hours ?? 24)} ساعت پیش از نوبت ممکن است؛ بعد از آن با سالن تماس بگیرید.</T>
      ) : null}

      {b.rating !== null ? (
        <Card>
          <T weight="medium">امتیاز شما</T>
          <Stars value={b.rating} size={22} />
          {b.review ? <T muted>{b.review}</T> : null}
        </Card>
      ) : null}

      {b.actions.rate ? (
        <Card>
          <SectionTitle>به این نوبت امتیاز بدهید</SectionTitle>
          <Stars value={stars} onChange={setStars} />
          <Field label="نظر (اختیاری)" value={review} onChangeText={setReview} multiline maxLength={500} />
          {rate.error ? <ErrorBox error={rate.error} /> : null}
          <Button title="ثبت امتیاز" disabled={stars === 0} loading={rate.isPending} onPress={() => rate.mutate({ id: b.id, rating: stars, review: review.trim() })} />
        </Card>
      ) : null}
    </Screen>
  );
}
