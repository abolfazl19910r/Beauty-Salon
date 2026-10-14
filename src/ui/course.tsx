import type { Course } from '@/lib/customer/types';
import { faDigits } from '@/lib/format';
import { Badge, Card, Row, T } from './kit';
import { router } from 'expo-router';

/** وضعیت دوره با رنگ (بسته‌ی ب) */
export function CourseStatusBadge({ course }: { course: Pick<Course, 'status' | 'status_label'> }) {
  const tone = ({ active: 'success', pending_payment: 'warning', completed: 'info', expired: 'muted', cancelled: 'danger' } as const)[course.status] ?? 'muted';
  return <Badge text={course.status_label} tone={tone} />;
}

/** «۲ از ۶ جلسه انجام شده، ۱ رزرو» */
export function courseProgress(c: Pick<Course, 'sessions_total' | 'sessions_used' | 'sessions_reserved' | 'sessions_burned'>): string {
  const parts = [`${faDigits(c.sessions_used)} از ${faDigits(c.sessions_total)} جلسه`];
  if (c.sessions_reserved > 0) {
    parts.push(`${faDigits(c.sessions_reserved)} رزرو`);
  }
  if (c.sessions_burned > 0) {
    parts.push(`${faDigits(c.sessions_burned)} سوخته`);
  }
  return parts.join('، ');
}

/** کارت دوره‌ی من؛ لمس → جزئیات */
export function CourseCard({ course }: { course: Course }) {
  return (
    <Card onPress={() => router.push(`/course/${course.id}`)}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold" style={{ flexShrink: 1 }}>{course.name}</T>
        <CourseStatusBadge course={course} />
      </Row>
      <T muted size={14}>{courseProgress(course)}</T>
      {course.expires_at_jalali ? <T muted size={13}>مهلت تا {faDigits(course.expires_at_jalali)}</T> : null}
    </Card>
  );
}
