import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, ScrollView, View } from 'react-native';

import { errorMessage } from '@/lib/api';
import { useCourse, useCourseActions, useCourseDates, useCourseSlots, useCourseSpecialists } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { jalaliLabel, parseIsoDate } from '@/lib/jalali';
import { Button, Chip, Empty, ErrorBox, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { space } from '@/ui/theme';

/** رزرو جلسه‌ی بعد دوره — بدون پیش‌پرداخت؛ فقط روزهای مجاز دوره (فاصله‌ی حداقل تا پایان مهلت) */
export default function CourseBook() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const courseId = Number(id);
  const course = useCourse(courseId);
  const specialists = useCourseSpecialists(courseId);
  const [specialistId, setSpecialistId] = useState(0);
  const [date, setDate] = useState<string | null>(null);
  const [time, setTime] = useState<string | null>(null);
  const dates = useCourseDates(courseId, specialistId);
  const slots = useCourseSlots(courseId, specialistId, date);
  const { book } = useCourseActions();

  useEffect(() => {
    if (specialistId === 0 && specialists.data?.length === 1) {
      setSpecialistId(specialists.data[0].id);
    }
  }, [specialists.data, specialistId]);

  if (course.isPending || specialists.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (course.error || specialists.error) {
    return <Screen><ErrorBox error={course.error ?? specialists.error} onRetry={() => { void course.refetch(); void specialists.refetch(); }} /></Screen>;
  }
  const c = course.data;
  if (!c.actions.book) {
    return <Screen><Notice tone="warning" text={c.book_blocker ?? 'الان جلسه‌ای قابل رزرو نیست.'} /></Screen>;
  }

  const submit = () => {
    if (!date || !time) {
      return;
    }
    book.mutate({ courseId: c.id, specialist_id: specialistId, date, time }, {
      onSuccess: (r) => {
        Alert.alert('رزرو شد', `${faDigits(r.booking.course?.label ?? '')} — ${faDigits(r.booking.booking_time_label ?? '')}`);
        router.replace(`/course/${c.id}`);
      },
      onError: (e) => Alert.alert('رزرو نشد', errorMessage(e)),
    });
  };

  const next = c.sessions_used + c.sessions_reserved + 1;

  return (
    <Screen>
      <T weight="bold" size={17}>{c.name} — جلسه‌ی {faDigits(next)} از {faDigits(c.sessions_total)}</T>

      <SectionTitle>متخصص</SectionTitle>
      {specialists.data.length === 0 ? <Empty text="فعلاً متخصصی برای این دوره نیست؛ با سالن تماس بگیرید." /> : (
        <Row style={{ flexWrap: 'wrap' }}>
          {specialists.data.map((s) => (
            <Chip key={s.id} label={s.name} active={s.id === specialistId} onPress={() => { setSpecialistId(s.id); setDate(null); setTime(null); }} />
          ))}
        </Row>
      )}

      {specialistId > 0 ? (
        <>
          <SectionTitle>روز</SectionTitle>
          {dates.isPending ? <Loading /> : dates.error ? <ErrorBox error={dates.error} onRetry={() => dates.refetch()} /> : dates.data.dates.length === 0 ? (
            <Empty text="در بازه‌ی مجاز این دوره روز آزادی نیست؛ با سالن تماس بگیرید." />
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: space(2) }}>
              {dates.data.dates.map((d) => (
                <Chip key={d} label={faDigits(jalaliLabel(parseIsoDate(d)))} active={date === d} onPress={() => { setDate(d); setTime(null); }} />
              ))}
            </ScrollView>
          )}
        </>
      ) : null}

      {date ? (
        <>
          <SectionTitle>ساعت</SectionTitle>
          {slots.isPending ? <Loading /> : slots.error ? <ErrorBox error={slots.error} onRetry={() => slots.refetch()} /> : slots.data.slots.length === 0 ? (
            <Empty text="این روز ساعت آزادی ندارد." />
          ) : (
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space(2) }}>
              {slots.data.slots.map((t) => <Chip key={t} label={faDigits(t)} active={time === t} onPress={() => setTime(t)} />)}
            </View>
          )}
        </>
      ) : null}

      <Button title="رزرو این جلسه" onPress={submit} loading={book.isPending} disabled={!date || !time || specialistId === 0} />
      <T muted size={12}>جلسه‌های دوره پیش‌پرداخت جدا ندارند؛ لغو به‌موقع جلسه را به دوره برمی‌گرداند.</T>
    </Screen>
  );
}
