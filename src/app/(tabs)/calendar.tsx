import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { RefreshControl } from 'react-native';

import { faDigits } from '@/lib/format';
import { addDays, isoDate, jalaliLabel, startOfWeek } from '@/lib/jalali';
import { useCalendar } from '@/lib/queries';
import type { CalendarDay } from '@/lib/types';
import { BookingCard } from '@/ui/booking';
import { Badge, Button, Card, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

function DayCard({ day, isToday }: { day: CalendarDay; isToday: boolean }) {
  const [open, setOpen] = useState(isToday);
  const p = usePalette();
  const hours = day.working_hours;

  return (
    <Card onPress={day.bookings.length ? () => setOpen((o) => !o) : undefined} style={isToday ? { borderColor: p.gold, borderWidth: 1 } : undefined}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold">{day.weekday_label} {faDigits(day.date_jalali.slice(5))}</T>
        <Row>
          {day.leave ? <Badge text={day.leave.status === 'approved' ? 'مرخصی' : 'مرخصی (در انتظار)'} tone="warning" /> : null}
          {day.holiday ? <Badge text="تعطیل" tone="danger" /> : null}
          {day.bookings.length ? <Badge text={`${faDigits(day.bookings.length)} نوبت`} tone="gold" /> : null}
        </Row>
      </Row>
      <T muted size={13}>
        {hours && !day.holiday
          ? `ساعت کاری ${faDigits(hours.start_time ?? '')} تا ${faDigits(hours.end_time ?? '')}${hours.break_start ? ` (استراحت ${faDigits(hours.break_start)}–${faDigits(hours.break_end ?? '')})` : ''}`
          : day.holiday?.description ?? 'روز کاری نیست'}
      </T>
      {open ? day.bookings.map((b) => <BookingCard key={b.id} booking={b} />) : null}
    </Card>
  );
}

export default function Calendar() {
  const p = usePalette();
  const [weekStart, setWeekStart] = useState(() => startOfWeek(new Date()));
  const range = useMemo(() => ({ from: isoDate(weekStart), to: isoDate(addDays(weekStart, 6)) }), [weekStart]);
  const calendar = useCalendar(range.from, range.to);
  const today = isoDate(new Date());

  return (
    <Screen refreshControl={<RefreshControl refreshing={calendar.isRefetching} onRefresh={() => calendar.refetch()} colors={[p.gold]} />}>
      <Row>
        <Button small kind="secondary" title="برنامه‌ی هفتگی" onPress={() => router.push('/schedule')} />
        <Button small kind="secondary" title="مرخصی‌ها" onPress={() => router.push('/leaves')} />
      </Row>
      <Row style={{ justifyContent: 'space-between' }}>
        <Button small kind="ghost" title="‹ هفته‌ی قبل" onPress={() => setWeekStart((w) => addDays(w, -7))} />
        <T weight="medium" size={13}>{jalaliLabel(weekStart)}</T>
        <Button small kind="ghost" title="هفته‌ی بعد ›" onPress={() => setWeekStart((w) => addDays(w, 7))} />
      </Row>
      {calendar.isPending ? <Loading /> : calendar.error ? <ErrorBox error={calendar.error} onRetry={() => calendar.refetch()} /> : (
        calendar.data.map((day) => <DayCard key={day.date} day={day} isToday={day.date === today} />)
      )}
    </Screen>
  );
}
