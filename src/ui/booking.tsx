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
      {booking.course?.label ? <Badge text={faDigits(booking.course.label)} tone="info" /> : null}
      <Row style={{ justifyContent: 'space-between' }}>
        <T muted size={14}>{booking.service?.name ?? '—'}</T>
        {showMoney && due > 0 ? <T muted size={13}>دریافت در سالن: {toman(due)}</T> : null}
      </Row>
      {showMoney ? (
        <Badge text={booking.payment_status === 'paid' ? 'پیش‌پرداخت شده' : 'پیش‌پرداخت نشده'} tone={booking.payment_status === 'paid' ? 'success' : 'warning'} />
      ) : null}
      {booking.status === 'cancelled' && booking.cancellation_reason ? <T muted size={13}>دلیل: {booking.cancellation_reason}</T> : null}
    </Card>
  );
}
