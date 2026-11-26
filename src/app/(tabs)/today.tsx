import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { Alert, Linking, Pressable, RefreshControl, View } from 'react-native';

import { faDigits, toman } from '@/lib/format';
import { useBookingAction, useStaffHome } from '@/lib/queries';
import { useSession } from '@/lib/session';
import type { Booking } from '@/lib/types';
import { BigTime, MoreButton } from '@/ui/agenda';
import { dueAtSalon, paymentBadge, StatusBadge, staffMoreItems } from '@/ui/booking';
import { Badge, Button, Card, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { space, usePalette } from '@/ui/theme';
import { UpdateNotice } from '@/ui/UpdateNotice';

/**
 * خانه‌ی اپ همکار — بسته‌ی ۵د، پیش‌نمایش تأییدشده‌ی «اپ همکار» (همان ساختار داشبورد موبایل وب کارشناس): «نوبت بعدی» با ساعت درشت،
 * دریافت در سالن و کار مستقیم (تأیید / انجام شد / جزئیات، قاعده‌ی سرور) + تماس + «⋯»، درآمد ماه شمسی و قابل برداشت، «۷ روز آینده».
 * نام فایل «today» می‌ماند (نشانی /today در پوش‌ها و ورود).
 */
export default function Home() {
  const { account } = useSession();
  const home = useStaffHome();
  const p = usePalette();
  const firstName = (account?.specialist?.name ?? account?.user.name ?? '').split(' ')[0];
  const d = home.data;

  return (
    <Screen safeTop refreshControl={<RefreshControl refreshing={home.isRefetching} onRefresh={() => home.refetch()} colors={[p.gold]} />}>
      <Row gap={3}>
        <View style={{ flex: 1 }}>
          <T weight="bold" size={17}>سلام{firstName ? `، ${firstName}` : ''}</T>
          <T muted size={12.5} numberOfLines={1}>{account?.salon.name}{d?.date_label ? `، ${d.date_label}` : ''}</T>
        </View>
        <Pressable accessibilityRole="button" accessibilityLabel="اعلان‌ها" onPress={() => router.push('/notifications')} style={({ pressed }) => ({ width: 44, height: 44, borderRadius: 22, borderWidth: 1, borderColor: p.borderStrong, backgroundColor: p.surface, alignItems: 'center', justifyContent: 'center', opacity: pressed ? 0.8 : 1 })}>
          <Ionicons name="notifications-outline" size={19} color={p.text} />
        </Pressable>
      </Row>

      <UpdateNotice />

      {home.isPending ? <Loading /> : home.error ? <ErrorBox error={home.error} onRetry={() => home.refetch()} /> : (
        <>
          {d!.next ? <NextCard booking={d!.next} /> : (
            <Card><T weight="medium">نوبت پیش رویی ندارید.</T></Card>
          )}

          <Row gap={2.5} style={{ alignItems: 'stretch' }}>
            <Card style={{ flex: 1, padding: space(3.5), gap: space(0.5) }} onPress={() => router.push('/wallet')}>
              <T muted size={12.5}>درآمد {d!.month_label}</T>
              <T weight="bold" size={17}>{toman(d!.month_income)}</T>
              {d!.month_income <= 0 ? <T muted size={12}>هنوز درآمدی در {d!.month_label} نیست</T> : null}
            </Card>
            <Card style={{ flex: 1, padding: space(3.5), gap: space(0.5) }} onPress={() => router.push('/wallet')}>
              <T muted size={12.5}>قابل برداشت</T>
              <T weight="bold" size={17}>{toman(d!.withdrawable)}</T>
              <T size={12} weight="bold" color={p.gold}>کیف پول</T>
            </Card>
          </Row>

          <Row style={{ justifyContent: 'space-between' }}>
            <T weight="bold" size={15.5}>۷ روز آینده</T>
            <T size={13} weight="bold" color={p.gold} onPress={() => router.push('/bookings')}>همه</T>
          </Row>
          {d!.upcoming.length === 0 ? <T muted size={13.5}>در ۷ روز آینده نوبت دیگری ندارید.</T> : (
            <Card style={{ padding: 0, gap: 0 }}>
              {d!.upcoming.map((b, i) => <WeekRow key={b.id} booking={b} first={i === 0} />)}
            </Card>
          )}
        </>
      )}
    </Screen>
  );
}

function NextCard({ booking: b }: { booking: Booking }) {
  const p = usePalette();
  const action = useBookingAction();
  const due = dueAtSalon(b);
  const kind = b.primary_action ?? (b.actions.confirm ? 'confirm' : b.actions.complete ? 'complete' : 'details');
  const run = (k: 'confirm' | 'complete', title: string) =>
    Alert.alert(title, `${b.customer?.name ?? ''}، ${b.day_label ?? ''} ${b.time_label ?? ''}`, [
      { text: 'انصراف', style: 'cancel' },
      { text: 'بله', onPress: () => action.mutate({ id: b.id, action: k }) },
    ]);
  const primary = kind === 'confirm'
    ? { title: 'تأیید نوبت', onPress: () => run('confirm', 'تأیید نوبت؟') }
    : kind === 'complete'
      ? { title: 'انجام شد', onPress: () => run('complete', 'نوبت انجام شد؟') }
      : { title: 'جزئیات', onPress: () => router.push(`/booking/${b.id}`) };
  return (
    <Card style={{ gap: space(3), padding: space(4.5) }}>
      <Row style={{ justifyContent: 'space-between', alignItems: 'flex-end' }}>
        <T muted size={14} style={{ flexShrink: 1 }}>نوبت بعدی، {b.day_label ?? faDigits(b.booking_time_jalali.split(' ')[0])}</T>
        <BigTime>{b.time_label ?? b.booking_time_jalali.split(' ')[1]}</BigTime>
      </Row>
      <View>
        <T weight="bold" size={18}>{b.customer?.name ?? '—'}</T>
        <T muted size={14}>{b.service?.name ?? '—'}{b.duration_minutes ? `، ${faDigits(b.duration_minutes)} دقیقه` : ''}</T>
      </View>
      <Row gap={2} style={{ flexWrap: 'wrap' }}>
        <StatusBadge booking={b} />
        {!b.course ? <Badge text={paymentBadge(b)} tone={b.payment_status === 'paid' ? 'success' : b.prepayment_required === false ? 'muted' : 'warning'} /> : null}
        {b.customer?.no_show_label ? <Badge text={faDigits(b.customer.no_show_label)} tone="danger" /> : null}
      </Row>
      {!b.course && due > 0 ? (
        <Row style={{ justifyContent: 'space-between', borderTopWidth: 1, borderTopColor: p.border, paddingTop: space(2.5) }}>
          <T muted size={14.5}>دریافت در سالن</T>
          <T weight="bold" size={14.5}>{toman(due)}</T>
        </Row>
      ) : null}
      {action.error ? <ErrorBox error={action.error} /> : null}
      <Row gap={2}>
        <View style={{ flex: 1 }}><Button kind="strong" title={primary.title} onPress={primary.onPress} loading={action.isPending} /></View>
        {b.customer?.phone ? (
          <Pressable accessibilityRole="button" accessibilityLabel={`تماس با ${b.customer.name}`} onPress={() => void Linking.openURL(`tel:${b.customer?.phone}`)} style={({ pressed }) => ({ width: 52, height: 48, borderRadius: 24, borderWidth: 1, borderColor: p.borderStrong, alignItems: 'center', justifyContent: 'center', opacity: pressed ? 0.8 : 1 })}>
            <Ionicons name="call-outline" size={20} color={p.text} />
          </Pressable>
        ) : null}
        <MoreButton items={staffMoreItems(b)} label="کارهای بیشتر این نوبت" size={48} />
      </Row>
    </Card>
  );
}

function WeekRow({ booking: b, first }: { booking: Booking; first: boolean }) {
  const p = usePalette();
  return (
    <Pressable accessibilityRole="button" onPress={() => router.push(`/booking/${b.id}`)} style={{ flexDirection: 'row', alignItems: 'center', gap: space(3), paddingVertical: space(3), paddingHorizontal: space(3.5), borderTopWidth: first ? 0 : 1, borderTopColor: p.border }}>
      <View style={{ minWidth: 92 }}>
        <T weight="bold" size={14}>{b.time_label ?? b.booking_time_jalali.split(' ')[1]}</T>
        <T muted size={12}>{b.date_label ?? ''}</T>
      </View>
      <View style={{ flex: 1 }}>
        <T size={14} numberOfLines={1}>{b.customer?.name ?? '—'}</T>
        <T muted size={12.5} numberOfLines={1}>{b.service?.name ?? '—'}</T>
      </View>
      <StatusBadge booking={b} />
    </Pressable>
  );
}
