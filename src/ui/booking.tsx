import { router } from 'expo-router';

import { timeOf, toman } from '@/lib/format';
import type { Booking, BookingStatus } from '@/lib/types';
import { Badge, Card, Row, T } from './kit';

const TONE: Record<BookingStatus, 'warning' | 'success' | 'info' | 'danger' | 'muted'> = {
  pending: 'warning',
  confirmed: 'success',
  completed: 'info',
  cancelled: 'danger',
  pending_payment: 'muted',
};

export function StatusBadge({ booking }: { booking: Pick<Booking, 'status' | 'status_label'> }) {
  return <Badge text={booking.status_label} tone={TONE[booking.status]} />;
}

/** کارت نوبت در «امروز»، فهرست و تقویم؛ لمس → جزئیات */
export function BookingCard({ booking, showDate }: { booking: Booking; showDate?: boolean }) {
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
      <Row style={{ justifyContent: 'space-between' }}>
        <T muted size={14}>{booking.service?.name ?? '—'}</T>
        {booking.remaining_amount > 0 ? <T muted size={13}>باقی‌مانده: {toman(booking.remaining_amount)}</T> : null}
      </Row>
    </Card>
  );
}
