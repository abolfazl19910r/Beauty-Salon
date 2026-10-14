import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, RefreshControl } from 'react-native';

import { errorMessage } from '@/lib/api';
import { useCourseActions, useCoursePackages, useCourses } from '@/lib/customer/queries';
import type { CoursePackage } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { CourseCard } from '@/ui/course';
import { Button, Card, Empty, ErrorBox, Field, KeyValue, Loading, Screen, SectionTitle, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

/**
 * دوره‌های درمانی (بسته‌ی ب): «دوره‌های من» همیشه؛ بسته‌های فروش آنلاین فقط اگر در پلن سالن باشد (وگرنه سرور ۴۰۴ می‌دهد و
 * بخش خرید نشان داده نمی‌شود). خرید → صفحه‌ی پرداخت (کل یا پیش‌پرداخت).
 */
export default function Courses() {
  const p = usePalette();
  const courses = useCourses();
  const packages = useCoursePackages();
  const { purchase } = useCourseActions();
  const [openId, setOpenId] = useState<number | null>(null);
  const [code, setCode] = useState('');

  const buy = (pkg: CoursePackage) => {
    purchase.mutate(
      { packageId: pkg.id, discount_code: code.trim() },
      {
        onSuccess: (course) => {
          setOpenId(null);
          setCode('');
          router.push(course.status === 'active' ? `/course/${course.id}` : `/course-pay/${course.id}`);
        },
        onError: (e) => Alert.alert('خرید انجام نشد', errorMessage(e)),
      },
    );
  };

  const refreshing = courses.isRefetching || packages.isRefetching;

  return (
    <Screen refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { void courses.refetch(); void packages.refetch(); }} colors={[p.gold]} />}>
      <SectionTitle>دوره‌های من</SectionTitle>
      {courses.isPending ? <Loading /> : courses.error ? <ErrorBox error={courses.error} onRetry={() => courses.refetch()} /> : courses.data.length === 0 ? (
        <Empty text="هنوز دوره‌ای نخریده‌اید." />
      ) : (
        courses.data.map((c) => <CourseCard key={c.id} course={c} />)
      )}

      {packages.isSuccess && packages.data.length > 0 ? (
        <>
          <SectionTitle>خرید دوره</SectionTitle>
          <T muted size={13}>چند جلسه را یک‌جا و ارزان‌تر بخرید؛ موعد هر جلسه را برایتان یادآوری می‌کنیم.</T>
          {packages.data.map((pkg) => (
            <Card key={pkg.id} onPress={() => setOpenId(openId === pkg.id ? null : pkg.id)}>
              <T weight="bold">{pkg.name}</T>
              <T muted size={14}>{faDigits(pkg.sessions_count)} جلسه {pkg.services?.length ? pkg.services.map((s) => s.name).join(' + ') : pkg.service?.name ?? ''} — هر {faDigits(pkg.interval_days)} روز</T>
              <KeyValue label="قیمت دوره" value={<T weight="bold" color={p.gold}>{toman(pkg.price)}</T>} />
              {pkg.saving > 0 ? <KeyValue label="صرفه‌جویی" value={toman(pkg.saving)} /> : null}
              {pkg.min_prepayment < pkg.price ? <KeyValue label="کمترین پرداخت اول" value={toman(pkg.min_prepayment)} /> : null}
              {pkg.validity_days ? <T muted size={13}>مهلت استفاده: {faDigits(pkg.validity_days)} روز بعد از پرداخت</T> : null}
              {pkg.description && openId === pkg.id ? <T size={14}>{pkg.description}</T> : null}
              {openId === pkg.id ? (
                <>
                  <Field label="کد تخفیف (اختیاری)" value={code} onChangeText={setCode} autoCapitalize="characters" />
                  <Button title="خرید این دوره" onPress={() => buy(pkg)} loading={purchase.isPending} />
                </>
              ) : null}
            </Card>
          ))}
        </>
      ) : null}
    </Screen>
  );
}
