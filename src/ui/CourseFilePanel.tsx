import * as ImagePicker from 'expo-image-picker';
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
 * عکس‌های جلسه‌های قبلی همان دوره. عکس قبل/بعد با دوربین یا از گالری (فقط بعد از رضایت، حداکثر ۴ برای هر جلسه)؛ دیدن عکس‌ها با توکن.
 */
export const CONSENT_CONFIRM = 'متن رضایت را برای مشتری خواندید و او صریحاً موافقت کرد؟';

export function CourseFilePanel({ booking }: { booking: Booking }) {
  const p = usePalette();
  const api = useApi();
  const file = useCourseFile(booking.id, !!booking.course);
  const { saveNote, consent, uploadPhoto } = useCourseFileMutations(booking.id);
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
  const MAX_PHOTOS = 4;

  const pick = async (kind: 'before' | 'after', source: 'camera' | 'library') => {
    const permission = source === 'camera'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('اجازه داده نشد', source === 'camera' ? 'برای عکس گرفتن، اجازه‌ی دوربین را در تنظیمات گوشی بدهید.' : 'برای انتخاب عکس، اجازه‌ی گالری را در تنظیمات گوشی بدهید.');
      return;
    }
    const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 0.7, exif: false };
    const result = source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
    if (result.canceled || !result.assets[0]) {
      return;
    }
    const asset = result.assets[0];
    uploadPhoto.mutate({ uri: asset.uri, kind, mimeType: asset.mimeType }, { onError: (e) => Alert.alert('عکس ذخیره نشد', errorMessage(e)) });
  };

  const askPhoto = (kind: 'before' | 'after') =>
    Alert.alert(kind === 'before' ? 'عکس قبل' : 'عکس بعد', 'عکس فقط برای کادر سالن و خود مشتری دیده می‌شود.', [
      { text: 'انصراف', style: 'cancel' },
      { text: 'گالری', onPress: () => void pick(kind, 'library') },
      { text: 'دوربین', onPress: () => void pick(kind, 'camera') },
    ]);

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
          {file.data.photo_consent_text ? <T size={14} testID="photo-consent-text">{file.data.photo_consent_text}</T> : null}
          <Button kind="secondary" title="مشتری رضایت داد" loading={consent.isPending}
            onPress={() => Alert.alert('ثبت رضایت عکس؟', CONSENT_CONFIRM, [
              { text: 'انصراف', style: 'cancel' },
              { text: 'ثبت', onPress: () => consent.mutate(undefined, { onError: (e) => Alert.alert('ثبت نشد', errorMessage(e)) }) },
            ])} />
        </Card>
      ) : null}

      {file.data?.photo_consent && mine ? (
        <Card>
          <T weight="medium">عکس‌های این جلسه ({faDigits(mine.photos.length)} از {faDigits(MAX_PHOTOS)})</T>
          {mine.photos.length > 0 ? <PhotoStrip photos={mine.photos} source={(id) => api.imageSource(`/staff/course-photos/${id}`)} /> : null}
          {mine.photos.length < MAX_PHOTOS ? (
            <Row>
              <Button small kind="secondary" title="عکس قبل" onPress={() => askPhoto('before')} loading={uploadPhoto.isPending} />
              <Button small kind="secondary" title="عکس بعد" onPress={() => askPhoto('after')} loading={uploadPhoto.isPending} />
            </Row>
          ) : null}
        </Card>
      ) : null}

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
