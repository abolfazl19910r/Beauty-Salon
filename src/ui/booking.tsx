import { router } from 'expo-router';
import { Alert, Linking, View } from 'react-native';

import { faDigits, timeOf, toman } from '@/lib/format';
import { useBookingAction } from '@/lib/queries';
import type { Booking, BookingStatus } from '@/lib/types';
import { MoreButton, type MoreItem } from './agenda';
import { Badge, Button, Card, ErrorBox, Row, T } from './kit';
import { space } from './theme';

const TONE: Record<BookingStatus, 'warning' | 'success' | 'info' | 'danger' | 'muted'> = {
  pending: 'warning',
  confirmed: 'success',
  completed: 'info',
  cancelled: 'danger',
  no_show: 'danger',
  pending_payment: 'muted',
};

export function StatusBadge({ booking }: { booking: Pick<Booking, 'status' | 'status_label'> }) {
  return <Badge text={booking.status_label} tone={TONE[booking.status]} />;
}

/** مبلغ دریافتی در سالن؛ سرور قدیمی (بی due_at_salon) همان باقی‌مانده */
export function dueAtSalon(booking: Pick<Booking, 'due_at_salon' | 'remaining_amount'>): number {
  return booking.due_at_salon ?? booking.remaining_amount;
}

/** وضعیت پول نوبت برای کادر؛ نوبت «بدون پیش‌پرداخت» (بسته‌ی 5ه) کل مبلغ را در سالن می‌دهد */
export function paymentBadge(booking: Pick<Booking, 'payment_status' | 'prepayment_required'>): string {
  if (booking.payment_status === 'paid') return 'پیش‌پرداخت شده';
  return booking.prepayment_required === false ? 'بدون پیش‌پرداخت' : 'پیش‌پرداخت نشده';
}

/** روز و ساعت با رقم فارسی (بسته‌ی ۵د؛ سرور قدیمی ← رشته‌ی شمسی با رقم فارسی) */
export function whenOf(b: Pick<Booking, 'day_label' | 'date_label' | 'time_label' | 'booking_time_jalali'>): { day: string; time: string } {
  return {
    day: b.day_label ?? b.date_label ?? faDigits(b.booking_time_jalali.split(' ')[0] ?? ''),
    time: b.time_label ?? timeOf(b.booking_time_jalali),
  };
}

/**
 * کارهای «⋯» یک نوبت کارشناس (بسته‌ی ۵د، مثل منوی «⋯» پنل وب): جزئیات، تماس، «مشتری نیامد…» فقط بعد از ساعت نوبت، «لغو نوبت…».
 * لغو و «نیامد» در صفحه‌ی جزئیات با پرسش انجام می‌شوند.
 */
export function staffMoreItems(b: Booking): MoreItem[] {
  const items: MoreItem[] = [{ label: 'جزئیات', onPress: () => router.push(`/booking/${b.id}`) }];
  if (b.customer?.phone) {
    items.push({ label: 'تماس با مشتری', onPress: () => void Linking.openURL(`tel:${b.customer?.phone}`) });
  }
  if (b.actions.no_show) {
    items.push({ label: 'مشتری نیامد…', onPress: () => router.push(`/booking/${b.id}?act=no-show`) });
  }
  if (b.actions.cancel) {
    items.push({ label: b.status === 'pending' ? 'رد نوبت…' : 'لغو نوبت…', danger: true, onPress: () => router.push(`/booking/${b.id}?act=cancel`) });
  }
  return items;
}

/** کارت نوبت در تقویم؛ لمس → جزئیات */
export function BookingCard({ booking, showDate }: { booking: Booking; showDate?: boolean }) {
  const due = dueAtSalon(booking);
  const showMoney = !booking.course && booking.status !== 'cancelled' && booking.status !== 'no_show';
  const when = whenOf(booking);

  return (
    <Card onPress={() => router.push(`/booking/${booking.id}`)}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold" size={17} style={{ flexShrink: 1 }}>{showDate ? `${when.day}، ` : ''}{when.time}</T>
        <StatusBadge booking={booking} />
      </Row>
      <T weight="medium">{booking.customer?.name ?? '—'}</T>
      {booking.customer?.no_show_label ? <Badge text={faDigits(booking.customer.no_show_label)} tone="danger" /> : null}
      {booking.course?.label ? <Badge text={faDigits(booking.course.label)} tone="info" /> : null}
      <Row style={{ justifyContent: 'space-between' }}>
        <T muted size={14}>{booking.service?.name ?? '—'}</T>
        {showMoney && due > 0 ? <T muted size={13}>دریافت در سالن: {toman(due)}</T> : null}
      </Row>
      {showMoney ? (
        <Badge text={paymentBadge(booking)} tone={booking.payment_status === 'paid' ? 'success' : booking.prepayment_required === false ? 'muted' : 'warning'} />
      ) : null}
      {booking.status === 'cancelled' && booking.cancellation_reason ? <T muted size={13}>دلیل: {booking.cancellation_reason}</T> : null}
    </Card>
  );
}

/**
 * ردیف «نوبت‌ها» — بسته‌ی ۵د (پیش‌نمایش تأییدشده): ساعت درشت و روز، مشتری، خدمت و دریافت در سالن، وضعیت و پرداخت، یک کار اصلی
 * (تأیید برای منتظر تأیید آینده، انجام شد وقتی ساعت رسیده، وگرنه جزئیات) + «⋯».
 */
export function StaffBookingRow({ booking: b }: { booking: Booking }) {
  const action = useBookingAction();
  const due = dueAtSalon(b);
  const when = whenOf(b);
  const showMoney = !b.course && b.status !== 'cancelled' && b.status !== 'no_show';
  const kind = b.primary_action ?? (b.actions.confirm ? 'confirm' : b.actions.complete ? 'complete' : 'details');
  const run = (k: 'confirm' | 'complete', title: string) =>
    Alert.alert(title, `${b.customer?.name ?? ''}، ${when.day} ${when.time}`, [
      { text: 'انصراف', style: 'cancel' },
      { text: 'بله', onPress: () => action.mutate({ id: b.id, action: k }) },
    ]);

  return (
    <Card onPress={() => router.push(`/booking/${b.id}`)} style={{ gap: space(2), padding: space(3.5) }}>
      <Row style={{ justifyContent: 'space-between', alignItems: 'baseline' }}>
        <T weight="bold" size={18}>{when.time}</T>
        <T muted size={13}>{when.day}</T>
      </Row>
      <View>
        <T weight="bold" size={15}>{b.customer?.name ?? '—'}</T>
        <T muted size={13}>{b.service?.name ?? '—'}{showMoney && due > 0 ? `، دریافت در سالن ${toman(due)}` : ''}</T>
      </View>
      <Row gap={1.5} style={{ flexWrap: 'wrap' }}>
        <StatusBadge booking={b} />
        {showMoney ? <Badge text={paymentBadge(b)} tone={b.payment_status === 'paid' ? 'success' : b.prepayment_required === false ? 'muted' : 'warning'} /> : null}
        {b.customer?.no_show_label ? <Badge text={faDigits(b.customer.no_show_label)} tone="danger" /> : null}
        {b.course?.label ? <Badge text={faDigits(b.course.label)} tone="info" /> : null}
      </Row>
      {b.status === 'cancelled' && b.cancellation_reason ? <T muted size={13}>دلیل: {b.cancellation_reason}</T> : null}
      {action.error ? <ErrorBox error={action.error} /> : null}
      <Row gap={2}>
        <View style={{ flex: 1 }}>
          {kind === 'confirm' ? <Button small kind="strong" title="تأیید" loading={action.isPending} onPress={() => run('confirm', 'تأیید نوبت؟')} />
            : kind === 'complete' ? <Button small kind="strong" title="انجام شد" loading={action.isPending} onPress={() => run('complete', 'نوبت انجام شد؟')} />
              : <Button small kind="secondary" title="جزئیات" onPress={() => router.push(`/booking/${b.id}`)} />}
        </View>
        <MoreButton items={staffMoreItems(b)} label="کارهای بیشتر این نوبت" size={40} />
      </Row>
    </Card>
  );
}
