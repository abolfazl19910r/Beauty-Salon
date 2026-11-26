import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Alert, Linking, RefreshControl, View } from 'react-native';

import { errorMessage } from '@/lib/api';
import { useBooking, useBookingActions, useHome, useSalonDetail } from '@/lib/customer/queries';
import { needsPrepayment } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { BigTime } from '@/ui/agenda';
import { CustomerStatusBadge, PayCountdown, PaymentBadge, Stars } from '@/ui/customer';
import { Button, Card, ErrorBox, Field, KeyValue, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/**
 * جزئیات نوبت — بسته‌ی ۵د (پیش‌نمایش تأییدشده): ساعت درشت و وضعیت، کارت مبلغ، کارت سالن (مسیریابی، تماس، افزودن به تقویم)،
 * کار اصلی (پرداخت پیش‌پرداخت)، «تغییر زمان» و «لغو نوبت…» با یک خط پیامد مالی. فقط کارهایی که `actions` سرور اجازه می‌دهد.
 * ‎?cancel=1 (از منوی «⋯») پرسش لغو را همان اول باز می‌کند.
 */
export default function BookingDetail() {
  const { id, cancel: askOnOpen } = useLocalSearchParams<{ id: string; cancel?: string }>();
  const bookingId = Number(id);
  const p = usePalette();
  const booking = useBooking(bookingId);
  const salon = useSalonDetail();
  const contact = useHome().data?.salon_contact;
  const { cancel, rate } = useBookingActions();
  const [stars, setStars] = useState(0);
  const [review, setReview] = useState('');
  const asked = useRef(false);

  const b = booking.data;
  const rules = salon.data?.rules;

  const askCancel = () => {
    if (!b) {
      return;
    }
    const note = b.cancel_note ?? (b.payment_status === 'paid' ? 'پیش‌پرداخت به کیف پول شما برمی‌گردد.' : '');
    Alert.alert('لغو نوبت؟', `${b.service?.name ?? ''}، ${faDigits(b.booking_time_label ?? '')}${note ? `\n${note}` : ''}`, [
      { text: 'نه', style: 'cancel' },
      { text: 'لغو نوبت', style: 'destructive', onPress: () => cancel.mutate(b.id, { onError: (e) => Alert.alert('لغو نشد', errorMessage(e)) }) },
    ]);
  };

  useEffect(() => {
    if (askOnOpen === '1' && b && b.actions.cancel && !asked.current) {
      asked.current = true;
      askCancel();
    }
  });

  if (booking.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (booking.error || !b) {
    return <Screen><ErrorBox error={booking.error} onRetry={() => booking.refetch()} /></Screen>;
  }

  const address = contact?.address ?? salon.data?.address ?? null;
  const phone = contact?.phone ?? salon.data?.phone ?? null;
  const directions = contact?.directions_url ?? null;
  const open = b.status === 'pending' || b.status === 'confirmed' || b.status === 'pending_payment';

  return (
    <Screen refreshControl={<RefreshControl refreshing={booking.isRefetching} onRefresh={() => booking.refetch()} colors={[p.gold]} />}>
      <Card style={{ gap: space(2.5), padding: space(4.5) }}>
        <Row style={{ justifyContent: 'space-between', alignItems: 'flex-end' }}>
          <T muted size={14} style={{ flexShrink: 1 }}>{b.day_label ?? faDigits(b.booking_time_label ?? '')}</T>
          <BigTime size={34}>{b.time_label ?? b.booking_time_jalali?.split(' ')[1] ?? ''}</BigTime>
        </Row>
        <View>
          <T weight="bold" size={18}>{b.service?.name ?? '—'}</T>
          <T muted size={14}>{b.specialist ? `با ${b.specialist.name}` : ''}{b.duration_minutes ? `، ${faDigits(b.duration_minutes)} دقیقه` : ''}</T>
        </View>
        <Row gap={2} style={{ flexWrap: 'wrap' }}>
          <CustomerStatusBadge booking={b} />
          <PaymentBadge booking={b} />
        </Row>
        {b.pay_before && b.actions.pay ? <PayCountdown until={b.pay_before} /> : null}
        {b.status === 'pending' ? <T muted size={13}>نوبت شما ثبت شده و منتظر تأیید سالن است.</T> : null}
      </Card>

      {b.course ? (
        <Card onPress={() => router.push(`/course/${b.course!.id}`)}>
          <T weight="medium">{faDigits(b.course.label ?? '')}</T>
          <T muted size={14}>دوره‌ی «{b.course.name}»، هزینه از قبل با دوره پرداخت شده است.</T>
        </Card>
      ) : (
        <Card style={{ gap: space(1) }}>
          {b.service ? <KeyValue label="قیمت خدمت" value={toman(b.service.price)} /> : null}
          {needsPrepayment(b) || b.payment_status === 'paid'
            ? <KeyValue label={b.payment_status === 'paid' ? 'پیش‌پرداخت (پرداخت شد)' : 'پیش‌پرداخت آنلاین'} value={toman(b.prepayment_amount)} />
            : null}
          {b.discount_amount > 0 ? <KeyValue label={`تخفیف${b.discount_code ? ` (${b.discount_code})` : ''}`} value={`− ${toman(b.discount_amount)}`} /> : null}
          <KeyValue label="پرداخت در سالن" value={toman(b.remaining_amount)} />
        </Card>
      )}

      {open && (address || phone || directions || b.calendar_url) ? (
        <Card>
          <View>
            <T weight="bold" size={15}>{salon.data?.name ?? ''}</T>
            {address ? <T muted size={13}>{faDigits(address)}</T> : null}
          </View>
          <Row gap={2} style={{ flexWrap: 'wrap' }}>
            {directions ? <Button small kind="secondary" title="مسیریابی" onPress={() => void Linking.openURL(directions)} /> : null}
            {phone ? <Button small kind="secondary" title="تماس" onPress={() => void Linking.openURL(`tel:${phone}`)} /> : null}
            {b.calendar_url ? <Button small kind="secondary" title="افزودن به تقویم" onPress={() => void Linking.openURL(b.calendar_url!)} /> : null}
          </Row>
        </Card>
      ) : null}

      {b.status === 'cancelled' && b.cancellation_reason ? <Notice tone="warning" text={`دلیل لغو: ${b.cancellation_reason}`} /> : null}

      {b.actions.pay ? <Button kind="strong" title={`پرداخت پیش‌پرداخت ${toman(b.prepayment_amount)}`} onPress={() => router.push(`/pay/${b.id}`)} /> : null}
      {b.actions.reschedule ? <Button kind="secondary" title="تغییر زمان" onPress={() => router.push(`/reschedule/${b.id}`)} /> : null}
      {b.actions.cancel ? (
        <View style={{ alignItems: 'center', gap: space(0.5) }}>
          <Button small kind="dangerText" title="لغو نوبت…" onPress={askCancel} loading={cancel.isPending} />
          {b.cancel_note ? <T muted size={12} center>{b.cancel_note}</T> : null}
        </View>
      ) : null}
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
          <SectionTitle>نظرتان چه بود؟</SectionTitle>
          <Stars value={stars} onChange={setStars} />
          <Field label="نظر (اختیاری)" value={review} onChangeText={setReview} multiline maxLength={500} />
          {rate.error ? <ErrorBox error={rate.error} /> : null}
          <Button kind="strong" title="ثبت نظر" disabled={stars === 0} loading={rate.isPending} onPress={() => rate.mutate({ id: b.id, rating: stars, review: review.trim() })} />
        </Card>
      ) : null}
    </Screen>
  );
}
