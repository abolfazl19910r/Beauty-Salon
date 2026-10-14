import Ionicons from '@expo/vector-icons/Ionicons';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { AppState, Linking, View } from 'react-native';

import { clearPendingPayment, loadPendingPayment, type PendingPayment } from '@/lib/customer/store';
import { usePaymentStatus, useRefreshMoney } from '@/lib/customer/queries';
import { faDigits, toman } from '@/lib/format';
import { Button, Card, ErrorBox, KeyValue, Loading, Screen, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';

/**
 * نتیجه‌ی پرداخت (مقصد mahru://payment?tx=… و صفحه‌ای که هنگام باز شدن بانک نشان داده می‌شود).
 * فقط tx از لینک خوانده می‌شود؛ state/purpose لینک باور نمی‌شوند — وضعیت قطعی از GET payments/{tx}.
 * pending هر ۳ ثانیه و با برگشتن اپ به جلو دوباره پرسیده می‌شود.
 */
export default function PaymentResult() {
  const { tx } = useLocalSearchParams<{ tx: string }>();
  const p = usePalette();
  const refresh = useRefreshMoney();
  const status = usePaymentStatus(tx ?? '', true);
  const [pending, setPending] = useState<PendingPayment | null>(null);
  const state = status.data?.state;

  useEffect(() => {
    loadPendingPayment().then((pp) => setPending(pp?.tx === tx ? pp : null));
  }, [tx]);

  const refetch = status.refetch;
  useEffect(() => {
    const sub = AppState.addEventListener('change', (s) => {
      if (s === 'active') {
        void refetch();
      }
    });
    return () => sub.remove();
  }, [refetch]);

  // وضعیت قطعی → پیگیری خودکار تمام، داده‌های پولی تازه
  useEffect(() => {
    if (tx && state && state !== 'pending') {
      void clearPendingPayment(tx);
      setPending(null);
      refresh();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tx, state]);

  if (!tx) {
    return <Screen><Button title="بازگشت به خانه" onPress={() => router.replace('/home')} /></Screen>;
  }
  if (status.isPending) {
    return <Screen><Loading label="در حال پرسیدن وضعیت پرداخت…" /></Screen>;
  }
  if (status.error) {
    return (
      <Screen>
        <ErrorBox error={status.error} onRetry={() => status.refetch()} />
        <Button kind="ghost" title="بازگشت به خانه" onPress={() => router.replace('/home')} />
      </Screen>
    );
  }

  const d = status.data;
  const isBooking = d.purpose === 'booking';
  const isCourse = d.purpose === 'course';
  const view = {
    pending: { icon: 'time-outline', color: p.warning, title: 'در انتظار نتیجه‌ی بانک', text: 'اگر پرداخت را در مرورگر تمام کرده‌اید چند لحظه صبر کنید. اگر صفحه‌ی بانک را بستید، می‌توانید دوباره بازش کنید.' },
    paid: { icon: 'checkmark-circle', color: p.success, title: 'پرداخت موفق بود', text: isBooking ? 'نوبت شما ثبت شد.' : isCourse ? 'پرداخت روی دوره‌ی شما ثبت شد.' : 'کیف پول شما شارژ شد.' },
    failed: { icon: 'close-circle', color: p.danger, title: 'پرداخت انجام نشد', text: 'اگر از حسابتان پولی کم شده، طبق قانون بانک حداکثر تا ۷۲ ساعت برمی‌گردد.' },
    refunded: { icon: 'return-down-back-outline', color: p.info, title: 'پرداخت برگشت خورد', text: isCourse ? 'این دوره دیگر پرداخت نمی‌پذیرفت؛ مبلغ به کیف پول شما برگشت.' : 'نوبت نگه داشته نشد و مبلغ برگشت داده شد.' },
  }[d.state] as { icon: React.ComponentProps<typeof Ionicons>['name']; color: string; title: string; text: string };

  const canReopen = d.state === 'pending' && pending && pending.checkoutExpiresAt > Date.now();

  return (
    <Screen>
      <View style={{ alignItems: 'center', gap: space(2), marginTop: space(6) }}>
        <Ionicons name={view.icon} size={64} color={view.color} />
        <T weight="bold" size={20} center>{view.title}</T>
        <T muted center>{view.text}</T>
      </View>

      <Card>
        <KeyValue label={isBooking ? 'پیش‌پرداخت نوبت' : isCourse ? 'پرداخت دوره' : 'شارژ کیف پول'} value={toman(d.amount)} />
        {d.fee > 0 ? <KeyValue label="کارمزد درگاه" value={toman(d.fee)} /> : null}
        {d.ref_id ? <KeyValue label="کد پیگیری" value={faDigits(d.ref_id)} /> : null}
        {isBooking && d.booking ? <KeyValue label="نوبت" value={`${d.booking.service?.name ?? ''} — ${faDigits(d.booking.booking_time_label ?? '')}`} /> : null}
        {isCourse && d.course ? <KeyValue label="دوره" value={d.course.name} /> : null}
        {!isBooking && d.wallet_balance !== undefined ? <KeyValue label="موجودی کیف پول" value={toman(d.wallet_balance)} /> : null}
      </Card>

      {d.state === 'pending' ? (
        <>
          <Loading label="هر چند ثانیه دوباره می‌پرسیم…" />
          <Button kind="secondary" title="بررسی دوباره" onPress={() => status.refetch()} loading={status.isRefetching} />
          {canReopen ? <Button kind="ghost" title="باز کردن دوباره‌ی صفحه‌ی بانک" onPress={() => Linking.openURL(pending.checkoutUrl)} /> : null}
        </>
      ) : null}

      {isBooking && d.booking ? (
        d.state === 'failed' && d.booking.actions.pay
          ? <Button title="تلاش دوباره برای پرداخت" onPress={() => router.replace(`/pay/${d.booking!.id}`)} />
          : <Button kind={d.state === 'paid' ? 'primary' : 'secondary'} title="دیدن نوبت" onPress={() => router.replace(`/booking/${d.booking!.id}`)} />
      ) : null}
      {isCourse && d.course ? (
        d.state === 'failed' && d.course.actions.pay
          ? <Button title="تلاش دوباره برای پرداخت" onPress={() => router.replace(`/course-pay/${d.course!.id}`)} />
          : <Button kind={d.state === 'paid' ? 'primary' : 'secondary'} title="دیدن دوره" onPress={() => router.replace(`/course/${d.course!.id}`)} />
      ) : null}
      {!isBooking && !isCourse ? (
        d.state === 'failed'
          ? <Button title="شارژ دوباره" onPress={() => router.replace('/charge')} />
          : <Button kind={d.state === 'paid' ? 'primary' : 'secondary'} title="کیف پول" onPress={() => router.replace('/wallet')} />
      ) : null}
      <Button kind="ghost" title="بازگشت به خانه" onPress={() => router.replace('/home')} />
    </Screen>
  );
}
