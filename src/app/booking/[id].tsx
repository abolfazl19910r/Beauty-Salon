import Ionicons from '@expo/vector-icons/Ionicons';
import { useLocalSearchParams } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Alert, Linking, View } from 'react-native';

import { faDigits, toman } from '@/lib/format';
import { useBooking, useBookingAction } from '@/lib/queries';
import { BigTime } from '@/ui/agenda';
import { dueAtSalon, paymentBadge, StatusBadge, whenOf } from '@/ui/booking';
import { CourseFilePanel } from '@/ui/CourseFilePanel';
import { Badge, Button, Card, ErrorBox, Field, KeyValue, Loading, Notice, Row, Screen, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/**
 * جزئیات نوبت کارشناس — بسته‌ی ۵د (پیش‌نمایش تأییدشده): ساعت درشت و روز، مشتری با دکمه‌ی تماس، مبلغ، کار اصلی طبق `actions`
 * سرور (قاعده‌ی وب: «تأیید» فقط برای منتظر تأیید آینده، «انجام شد» فقط وقتی ساعت نوبت رسیده؛ تا آن موقع یک خط توضیح)،
 * «مشتری نیامد» بعد از ساعت نوبت، و «لغو نوبت…» متنی. ‎?act=cancel|no-show (از منوی «⋯») همان کار را باز می‌کند.
 */
export default function BookingDetail() {
  const { id, act } = useLocalSearchParams<{ id: string; act?: string }>();
  const p = usePalette();
  const booking = useBooking(Number(id));
  const action = useBookingAction();
  const [cancelling, setCancelling] = useState(act === 'cancel');
  const [reason, setReason] = useState('');
  const askedNoShow = useRef(false);

  const b = booking.data;
  const run = (kind: 'confirm' | 'complete' | 'no-show', title: string) => {
    if (!b) return;
    const when = whenOf(b);
    Alert.alert(title, `${b.customer?.name ?? ''}، ${when.day} ${when.time}`, [
      { text: 'انصراف', style: 'cancel' },
      { text: 'بله', onPress: () => action.mutate({ id: b.id, action: kind }) },
    ]);
  };

  useEffect(() => {
    if (act === 'no-show' && b?.actions.no_show && !askedNoShow.current) {
      askedNoShow.current = true;
      run('no-show', 'مشتری نیامد؟ پیش‌پرداخت برنمی‌گردد و جلسه‌ی دوره (اگر هست) می‌سوزد.');
    }
  });

  if (booking.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (booking.error || !b) {
    return <Screen><ErrorBox error={booking.error} onRetry={() => booking.refetch()} /></Screen>;
  }

  const when = whenOf(b);
  const notYet = b.status === 'confirmed' && !b.actions.complete;

  return (
    <Screen>
      <Card style={{ gap: space(2.5), padding: space(4.5) }}>
        <Row style={{ justifyContent: 'space-between', alignItems: 'flex-end' }}>
          <T muted size={14} style={{ flexShrink: 1 }}>{when.day}</T>
          <BigTime size={34}>{when.time}</BigTime>
        </Row>
        <View>
          <T weight="bold" size={18}>{b.customer?.name ?? '—'}</T>
          <T muted size={14}>{b.service?.name ?? '—'}{b.duration_minutes ? `، ${faDigits(b.duration_minutes)} دقیقه` : ''}</T>
        </View>
        <Row gap={2} style={{ flexWrap: 'wrap' }}>
          <StatusBadge booking={b} />
          {!b.course && b.status !== 'cancelled' && b.status !== 'no_show' ? (
            <Badge text={paymentBadge(b)} tone={b.payment_status === 'paid' ? 'success' : b.prepayment_required === false ? 'muted' : 'warning'} />
          ) : null}
          {b.customer?.no_show_label ? <Badge text={faDigits(b.customer.no_show_label)} tone="danger" /> : null}
        </Row>
        {b.customer?.phone ? (
          <Button kind="secondary" title={`تماس ${faDigits(b.customer.phone)}`} onPress={() => void Linking.openURL(`tel:${b.customer?.phone}`)} />
        ) : null}
      </Card>

      {b.course ? <CourseFilePanel booking={b} /> : (
        <Card style={{ gap: space(1) }}>
          <KeyValue label="قیمت خدمت" value={toman(b.service?.price)} />
          {/* پیش‌پرداخت فقط وقتی پرداخت شده (نوبت دستی مدیر ستون پیش‌فرض دارد)؛ لغوشده و نیامده چیزی برای گرفتن ندارند (آزمون ۲۰۲۶-۱۰-۲۲) */}
          {b.payment_status === 'paid' ? <KeyValue label="پیش‌پرداخت" value={`${toman(b.prepayment_amount)} (پرداخت شده)`} /> : null}
          {b.discount_amount > 0 ? <KeyValue label="تخفیف" value={toman(b.discount_amount)} /> : null}
          {b.status === 'cancelled' || b.status === 'no_show' ? null : (
            <KeyValue label={b.payment_status === 'paid' ? 'باقی‌مانده (حضوری)' : b.prepayment_required === false ? 'دریافت در سالن (بدون پیش‌پرداخت)' : 'دریافت در سالن (پیش‌پرداخت نشده)'} value={toman(dueAtSalon(b))} />
          )}
        </Card>
      )}

      {b.notes ? <Notice text={`یادداشت: ${b.notes}`} /> : null}
      {b.cancellation_reason ? <Notice tone="warning" text={`دلیل لغو: ${b.cancellation_reason}`} /> : null}
      {action.error ? <ErrorBox error={action.error} /> : null}

      {notYet ? (
        <View style={{ backgroundColor: p.surfaceMuted, borderRadius: 16, padding: space(3.5), flexDirection: 'row', gap: space(2) }}>
          <Ionicons name="time-outline" size={18} color={p.textMuted} />
          <T muted size={13.5} style={{ flex: 1 }}>«انجام شد» از ساعت {when.time} {when.day} فعال می‌شود. «مشتری نیامد» هم بعد از ساعت نوبت می‌آید.</T>
        </View>
      ) : null}

      {b.actions.confirm ? <Button kind="strong" title="تأیید نوبت" onPress={() => run('confirm', 'تأیید نوبت؟')} loading={action.isPending} /> : null}
      {b.actions.complete ? <Button kind="strong" title="انجام شد" onPress={() => run('complete', 'نوبت انجام شد؟')} loading={action.isPending} /> : null}
      {b.actions.no_show ? <Button kind="secondary" title="مشتری نیامد" onPress={() => run('no-show', 'مشتری نیامد؟ پیش‌پرداخت برنمی‌گردد و جلسه‌ی دوره (اگر هست) می‌سوزد.')} loading={action.isPending} /> : null}
      {b.actions.cancel && !cancelling ? (
        <View style={{ alignItems: 'center' }}>
          <Button small kind="dangerText" title={b.status === 'pending' ? 'رد نوبت…' : 'لغو نوبت…'} onPress={() => setCancelling(true)} />
        </View>
      ) : null}
      {cancelling && b.actions.cancel ? (
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
