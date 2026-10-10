import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Switch } from 'react-native';

import { ApiError } from '@/lib/api';
import { enDigits } from '@/lib/format';
import { useSaveSchedule, useSchedule } from '@/lib/queries';
import type { ScheduleDay } from '@/lib/types';
import { Button, Card, ErrorBox, Field, Loading, Notice, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

/** ترتیب نمایش هفته‌ی ایرانی: شنبه (۶) تا جمعه (۵) — مقدار day_of_week مثل سرور: ۰ = یکشنبه */
const ORDER = [6, 0, 1, 2, 3, 4, 5];

const clean = (v: string) => enDigits(v).replace(/[^0-9:]/g, '').slice(0, 5);

export default function ScheduleScreen() {
  const p = usePalette();
  const schedule = useSchedule();
  const save = useSaveSchedule();
  const [days, setDays] = useState<ScheduleDay[]>([]);
  const [autoConfirm, setAutoConfirm] = useState(false);

  useEffect(() => {
    if (schedule.data) {
      setDays(schedule.data.schedules);
      setAutoConfirm(schedule.data.auto_confirm_bookings);
    }
  }, [schedule.data]);

  if (schedule.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (schedule.error) {
    return <Screen><ErrorBox error={schedule.error} onRetry={() => schedule.refetch()} /></Screen>;
  }

  const update = (dow: number, patch: Partial<ScheduleDay>) => setDays((all) => all.map((d) => (d.day_of_week === dow ? { ...d, ...patch } : d)));
  const fieldError = (dow: number, field: string) => {
    if (!(save.error instanceof ApiError)) {
      return undefined;
    }
    const index = days.findIndex((d) => d.day_of_week === dow);
    return save.error.field(`schedules.${index}.${field}`);
  };

  const submit = () =>
    save.mutate(
      {
        auto_confirm_bookings: autoConfirm,
        schedules: days.map((d) => ({
          day_of_week: d.day_of_week,
          is_active: d.is_active,
          start_time: d.is_active ? d.start_time : null,
          end_time: d.is_active ? d.end_time : null,
          break_start: d.is_active && d.break_start ? d.break_start : null,
          break_end: d.is_active && d.break_end ? d.break_end : null,
        })),
      },
      { onSuccess: () => router.back() },
    );

  return (
    <Screen>
      <Card>
        <Row style={{ justifyContent: 'space-between' }}>
          <T weight="medium" style={{ flex: 1 }}>تأیید خودکار نوبت‌ها</T>
          <Switch value={autoConfirm} onValueChange={setAutoConfirm} trackColor={{ true: p.gold }} />
        </Row>
        <T muted size={13}>روشن باشد، نوبت‌های پرداخت‌شده بدون پذیرش شما تأیید می‌شوند.</T>
      </Card>

      {ORDER.map((dow) => days.find((d) => d.day_of_week === dow)).filter((d): d is ScheduleDay => !!d).map((d) => (
        <Card key={d.day_of_week}>
          <Row style={{ justifyContent: 'space-between' }}>
            <T weight="bold">{d.weekday_label}</T>
            <Switch
              value={d.is_active}
              onValueChange={(on) => update(d.day_of_week, on ? { is_active: true, start_time: d.start_time ?? '09:00', end_time: d.end_time ?? '18:00' } : { is_active: false })}
              trackColor={{ true: p.gold }}
            />
          </Row>
          {d.is_active ? (
            <>
              <Row>
                <Field label="شروع" value={d.start_time ?? ''} onChangeText={(v) => update(d.day_of_week, { start_time: clean(v) })} placeholder="09:00" keyboardType="numbers-and-punctuation" style={{ minWidth: 110, textAlign: 'center' }} error={fieldError(d.day_of_week, 'start_time')} />
                <Field label="پایان" value={d.end_time ?? ''} onChangeText={(v) => update(d.day_of_week, { end_time: clean(v) })} placeholder="18:00" keyboardType="numbers-and-punctuation" style={{ minWidth: 110, textAlign: 'center' }} error={fieldError(d.day_of_week, 'end_time')} />
              </Row>
              <Row>
                <Field label="شروع استراحت" value={d.break_start ?? ''} onChangeText={(v) => update(d.day_of_week, { break_start: clean(v) || null })} placeholder="اختیاری" keyboardType="numbers-and-punctuation" style={{ minWidth: 110, textAlign: 'center' }} error={fieldError(d.day_of_week, 'break_start')} />
                <Field label="پایان استراحت" value={d.break_end ?? ''} onChangeText={(v) => update(d.day_of_week, { break_end: clean(v) || null })} placeholder="اختیاری" keyboardType="numbers-and-punctuation" style={{ minWidth: 110, textAlign: 'center' }} error={fieldError(d.day_of_week, 'break_end')} />
              </Row>
            </>
          ) : <T muted size={13}>تعطیل</T>}
        </Card>
      ))}

      <Notice text="نوبت‌های قبلاً ثبت‌شده با تغییر برنامه لغو نمی‌شوند." />
      {save.error ? <ErrorBox error={save.error} /> : null}
      <Button title="ذخیره‌ی برنامه" onPress={submit} loading={save.isPending} />
    </Screen>
  );
}
