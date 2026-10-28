import { router } from 'expo-router';

import { faDigits, timeOf, toman } from '@/lib/format';
import type { Booking, BookingStatus } from '@/lib/types';
import { Badge, Card, Row, T } from './kit';

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

/** کارت نوبت در «امروز»، فهرست و تقویم؛ لمس → جزئیات */
export function BookingCard({ booking, showDate }: { booking: Booking; showDate?: boolean }) {
  const due = dueAtSalon(booking);
  const showMoney = !booking.course && booking.status !== 'cancelled' && booking.status !== 'no_show';

  return (
    <Card onPress={() => router.push(`/booking/${booking.id}`)}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold" size={17}>
          {showDate ? booking.booking_time_jalali.split(' ')[0] + ' — ' : ''}
          {timeOf(booking.booking_time_jalali)}
        </T>
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
