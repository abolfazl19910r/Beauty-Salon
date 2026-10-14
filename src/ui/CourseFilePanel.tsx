import { useEffect, useState } from 'react';
import { Alert, Image, Switch, View } from 'react-native';

import { errorMessage } from '@/lib/api';
import { faDigits } from '@/lib/format';
import { useCourseFile, useCourseFileMutations } from '@/lib/queries';
import { useApi } from '@/lib/session';
import type { Booking } from '@/lib/types';
import { Button, Card, ErrorBox, Field, Loading, Notice, Row, SectionTitle, T } from './kit';
import { radius, space, usePalette } from './theme';

/**
 * پرونده‌ی جلسه‌ی دوره‌ی درمانی روی صفحه‌ی نوبت (بسته‌ی ب): یادداشت همین جلسه (با «نمایش به مشتری»)، رضایت عکس، و یادداشت‌ها و
 * عکس‌های جلسه‌های قبلی همان دوره. عکس از پنل وب گرفته می‌شود؛ این‌جا فقط دیده می‌شود (خصوصی، با توکن).
 */
export function CourseFilePanel({ booking }: { booking: Booking }) {
  const p = usePalette();
  const api = useApi();
  const file = useCourseFile(booking.id, !!booking.course);
  const { saveNote, consent } = useCourseFileMutations(booking.id);
  const mine = file.data?.sessions.find((s) => s.booking_id === booking.id);
  const [note, setNote] = useState('');
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if (mine) {
      setNote(mine.note ?? '');
      setVisible(!!mine.note_visible_to_customer);
    }
  }, [mine?.note, mine?.note_visible_to_customer]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!booking.course) {
    return null;
  }
  const others = (file.data?.sessions ?? []).filter((s) => s.booking_id !== booking.id && (s.note || s.photos.length > 0));

  return (
    <>
      <Card>
        <T weight="bold">{faDigits(booking.course.label ?? '')}</T>
        <T muted size={14}>دوره‌ی «{booking.course.name}» — هزینه با دوره پرداخت شده؛ از مشتری چیزی نگیرید.</T>
      </Card>

      <SectionTitle>یادداشت این جلسه</SectionTitle>
      {file.isPending ? <Loading /> : file.error ? <ErrorBox error={file.error} onRetry={() => file.refetch()} /> : (
        <Card>
          <Field label="مثلاً شدت، ناحیه، واکنش پوست" value={note} onChangeText={setNote} maxLength={2000} multiline />
          <Row style={{ justifyContent: 'space-between' }}>
            <T size={14}>مشتری هم ببیند</T>
            <Switch value={visible} onValueChange={setVisible} trackColor={{ true: p.gold }} accessibilityLabel="نمایش یادداشت به مشتری" />
          </Row>
          <Button
            title="ذخیره‌ی یادداشت"
            loading={saveNote.isPending}
            onPress={() => saveNote.mutate({ note: note.trim(), visible_to_customer: visible }, {
              onSuccess: () => Alert.alert('ذخیره شد'),
              onError: (e) => Alert.alert('ذخیره نشد', errorMessage(e)),
            })}
          />
        </Card>
      )}

      {file.data && !file.data.photo_consent ? (
        <Card>
          <Notice tone="warning" text="برای عکس قبل/بعد، رضایت مشتری لازم است (یک بار برای کل دوره)." />
          <Button kind="secondary" title="مشتری رضایت داد" loading={consent.isPending}
            onPress={() => Alert.alert('ثبت رضایت عکس؟', 'فقط وقتی مشتری حضوری موافقت کرده است.', [
              { text: 'انصراف', style: 'cancel' },
              { text: 'ثبت', onPress: () => consent.mutate(undefined, { onError: (e) => Alert.alert('ثبت نشد', errorMessage(e)) }) },
            ])} />
        </Card>
      ) : null}

      {mine && mine.photos.length > 0 ? <PhotoStrip photos={mine.photos} source={(id) => api.imageSource(`/staff/course-photos/${id}`)} /> : null}

      {others.length > 0 ? (
        <>
          <SectionTitle>جلسه‌های دیگر این دوره</SectionTitle>
          {others.map((s) => (
            <Card key={s.booking_id}>
              <T weight="medium">{faDigits(s.label ?? '')}{s.booking_time_jalali ? ` — ${faDigits(s.booking_time_jalali)}` : ''}</T>
              {s.specialist ? <T muted size={13}>{s.specialist.name}</T> : null}
              {s.note ? <T size={14}>{s.note}</T> : null}
              {s.photos.length > 0 ? <PhotoStrip photos={s.photos} source={(id) => api.imageSource(`/staff/course-photos/${id}`)} /> : null}
            </Card>
          ))}
        </>
      ) : null}
    </>
  );
}

function PhotoStrip({ photos, source }: { photos: { id: number; kind: 'before' | 'after' }[]; source: (id: number) => { uri: string; headers?: Record<string, string> } }) {
  const p = usePalette();
  return (
    <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space(2) }}>
      {photos.map((ph) => (
        <View key={ph.id}>
          <Image source={source(ph.id)} style={{ width: 88, height: 88, borderRadius: radius.md, backgroundColor: p.surfaceMuted }} />
          <T muted size={11} center>{ph.kind === 'before' ? 'قبل' : 'بعد'}</T>
        </View>
      ))}
    </View>
  );
}
