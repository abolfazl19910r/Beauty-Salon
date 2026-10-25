import { router, useLocalSearchParams } from 'expo-router';
import { Alert, Image, Pressable, RefreshControl, View } from 'react-native';

import { errorMessage } from '@/lib/api';
import { useCourse, useCourseActions } from '@/lib/customer/queries';
import type { CoursePhoto } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { useApi } from '@/lib/session';
import { CourseStatusBadge, courseProgress } from '@/ui/course';
import { Badge, Button, Card, Empty, ErrorBox, KeyValue, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { radius, space, usePalette } from '@/ui/theme';

const METHOD_LABELS: Record<string, string> = {
  gateway: 'درگاه', wallet: 'کیف پول', wallet_gateway: 'کیف پول + درگاه', in_person: 'حضوری', full_discount: 'تخفیف کامل',
};

/** جزئیات «دوره‌ی من»: جلسه‌ها با یادداشت‌های قابل نمایش و عکس‌ها (خصوصی؛ با توکن)، پرداخت باقی، رزرو جلسه‌ی بعد */
export default function CourseDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const courseId = Number(id);
  const p = usePalette();
  const api = useApi();
  const course = useCourse(courseId);
  const { deletePhoto, withdrawConsent } = useCourseActions();

  if (course.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (course.error) {
    return <Screen><ErrorBox error={course.error} onRetry={() => course.refetch()} /></Screen>;
  }
  const c = course.data;

  const askDelete = (photo: CoursePhoto) => {
    Alert.alert('حذف عکس؟', 'عکس برای همیشه از پرونده‌ی دوره پاک می‌شود.', [
      { text: 'نه', style: 'cancel' },
      {
        text: 'حذف',
        style: 'destructive',
        onPress: () => deletePhoto.mutate({ courseId: c.id, photoId: photo.id }, { onError: (e) => Alert.alert('حذف نشد', errorMessage(e)) }),
      },
    ]);
  };

  return (
    <Screen refreshControl={<RefreshControl refreshing={course.isRefetching} onRefresh={() => course.refetch()} colors={[p.gold]} />}>
      <Card>
        <Row style={{ justifyContent: 'space-between' }}>
          <T weight="bold" size={17} style={{ flexShrink: 1 }}>{c.name}</T>
          <CourseStatusBadge course={c} />
        </Row>
        {c.services_label ? <T muted size={14}>{c.services_label}{c.session_minutes ? ` — هر جلسه ${faDigits(c.session_minutes)} دقیقه` : ''}</T> : null}
        <T>{courseProgress(c)}</T>
        {c.status === 'active' ? <T muted size={14}>{faDigits(c.sessions_remaining)} جلسه‌ی رزرونشده مانده</T> : null}
        {c.expires_at_jalali ? <T muted size={13}>مهلت استفاده تا {faDigits(c.expires_at_jalali)}</T> : null}
      </Card>

      <Card>
        <KeyValue label="قیمت دوره" value={toman(c.price_total)} />
        <KeyValue label="پرداخت‌شده" value={toman(c.amount_paid)} />
        {c.balance_due > 0 ? <KeyValue label="باقی‌مانده" value={<T weight="bold" color={p.gold}>{toman(c.balance_due)}</T>} /> : null}
        {c.refunded_amount > 0 ? <KeyValue label="برگشت به کیف پول" value={toman(c.refunded_amount)} /> : null}
      </Card>

      {c.book_blocker && c.status !== 'completed' ? <Notice tone="warning" text={c.book_blocker} /> : null}
      {c.actions.book ? <Button title="رزرو جلسه‌ی بعد" onPress={() => router.push(`/course-book/${c.id}`)} /> : null}
      {c.actions.pay ? <Button kind={c.actions.book ? 'secondary' : 'primary'} title={c.status === 'pending_payment' ? 'پرداخت دوره' : `پرداخت باقی‌مانده (${toman(c.balance_due)})`} onPress={() => router.push(`/course-pay/${c.id}`)} /> : null}

      {c.photo_consent ? (
        <Card>
          <T weight="medium">رضایت عکس قبل و بعد</T>
          {c.photo_consent_text ? <T size={13} muted>{c.photo_consent_text}</T> : null}
          <Button small kind="ghost" title="پس گرفتن رضایت" loading={withdrawConsent.isPending}
            onPress={() => Alert.alert('پس گرفتن رضایت عکس؟', 'از این به بعد سالن عکس تازه‌ای در این دوره نمی‌گیرد. عکس‌های قبلی را می‌توانید جدا پاک کنید.', [
              { text: 'نه', style: 'cancel' },
              { text: 'پس می‌گیرم', style: 'destructive', onPress: () => withdrawConsent.mutate(c.id, { onError: (e) => Alert.alert('انجام نشد', errorMessage(e)) }) },
            ])} />
        </Card>
      ) : null}

      <SectionTitle>جلسه‌ها</SectionTitle>
      {c.sessions.length === 0 ? <Empty text="هنوز جلسه‌ای رزرو نشده است." /> : c.sessions.map((s) => (
        <Card key={s.booking_id} onPress={() => router.push(`/booking/${s.booking_id}`)}>
          <Row style={{ justifyContent: 'space-between' }}>
            <T weight="bold">{faDigits(s.label ?? '')}</T>
            <Badge text={s.burned ? 'سوخت' : ({ pending: 'منتظر تأیید', confirmed: 'رزرو شده', completed: 'انجام شد', cancelled: 'لغو شد', pending_payment: 'در انتظار پرداخت' } as Record<string, string>)[s.status] ?? s.status}
              tone={s.burned ? 'danger' : s.status === 'completed' ? 'success' : s.status === 'cancelled' ? 'muted' : 'gold'} />
          </Row>
          <T muted size={14}>{faDigits(s.booking_time_jalali ?? '')}{s.specialist ? ` — ${s.specialist.name}` : ''}</T>
          {s.note ? <T size={14}>یادداشت: {s.note}</T> : null}
          {s.photos.length > 0 ? (
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space(2) }}>
              {s.photos.map((ph) => (
                <Pressable key={ph.id} onLongPress={() => askDelete(ph)} accessibilityHint="برای حذف نگه دارید">
                  <Image source={api.imageSource(`/customer/course-photos/${ph.id}`)} style={{ width: 88, height: 88, borderRadius: radius.md, backgroundColor: p.surfaceMuted }} />
                  <T muted size={11} center>{ph.kind === 'before' ? 'قبل' : 'بعد'}</T>
                </Pressable>
              ))}
            </View>
          ) : null}
        </Card>
      ))}
      {c.sessions.some((s) => s.photos.length > 0) ? <T muted size={12}>برای حذف یک عکس، آن را نگه دارید.</T> : null}

      {c.payments.length > 0 ? (
        <>
          <SectionTitle>پرداخت‌ها</SectionTitle>
          <Card>
            {c.payments.map((pay, i) => (
              <KeyValue key={i} label={METHOD_LABELS[pay.method] ?? pay.method} value={toman(pay.amount)} />
            ))}
          </Card>
        </>
      ) : null}
    </Screen>
  );
}
