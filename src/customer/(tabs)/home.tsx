import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useEffect } from 'react';
import { Linking, Pressable, RefreshControl, View } from 'react-native';

import { useHome } from '@/lib/customer/queries';
import { consumePendingSpecialist } from '@/lib/customer/store';
import type { CustomerBooking } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { useSession } from '@/lib/session';
import { BigTime, DayBox, MoreButton } from '@/ui/agenda';
import { bookingMoreItems, CustomerStatusBadge, PaymentBadge, SalonLogo } from '@/ui/customer';
import { Badge, Button, Card, ErrorBox, Loading, Notice, Row, Screen, T } from '@/ui/kit';
import { space, useLayoutMetrics, usePalette } from '@/ui/theme';
import { UpdateNotice } from '@/ui/UpdateNotice';

/**
 * خانه‌ی اپ مشتری — بسته‌ی ۵د، پیش‌نمایش تأییدشده‌ی «اپ مشتری» روی بوم (همان ساختار داشبورد وب، بسته‌ی ۴): کارت «نوبت امروز/بعدی»
 * با کار مستقیم و منوی «⋯»، کیف پول و باشگاه (فقط با امکان سالن؛ بی امتیاز «هنوز امتیازی ندارید»، نه «۰»)، «نوبت‌های بعدی»،
 * «نظرتان چه بود؟» و «دوباره رزرو کنید». اسکن QR و اعلان‌ها دکمه‌ی گرد بالای صفحه‌اند.
 */
export default function Home() {
  const { account } = useSession();
  const home = useHome();
  const p = usePalette();
  const firstName = (account?.user.name ?? '').split(' ')[0];
  const salonSlug = account?.salon.slug;

  // QR کارشناس پیش از ورود اسکن شده بود (بسته‌ی ۵): یک بار صفحه‌ی همان کارشناس
  useEffect(() => {
    if (!salonSlug) {
      return;
    }
    void consumePendingSpecialist(salonSlug).then((id) => {
      if (id) {
        router.push(`/specialist/${id}`);
      }
    });
  }, [salonSlug]);

  const d = home.data;
  const unread = d?.unread_notifications ?? 0;

  return (
    <Screen safeTop refreshControl={<RefreshControl refreshing={home.isRefetching} onRefresh={() => home.refetch()} colors={[p.gold]} />}>
      <Row gap={3}>
        {account ? <SalonLogo salon={d?.salon ?? account.salon} size={44} /> : null}
        <View style={{ flex: 1 }}>
          <T weight="bold" size={17}>سلام{firstName ? `، ${firstName}` : ''}</T>
          <T muted size={12.5} numberOfLines={1}>{d?.salon.name ?? account?.salon.name}{d?.date_label ? `، ${d.date_label}` : ''}</T>
        </View>
        <RoundIcon icon="scan-outline" label="اسکن QR کارشناس یا سالن" onPress={() => router.push('/scan')} />
        <RoundIcon icon="notifications-outline" label={unread > 0 ? `اعلان‌ها، ${faDigits(unread)} مورد تازه` : 'اعلان‌ها'} badge={unread} onPress={() => router.push('/notifications')} />
      </Row>

      <UpdateNotice />

      {home.isPending ? <Loading /> : home.error ? <ErrorBox error={home.error} onRetry={() => home.refetch()} /> : (
        <>
          {d!.salon.booking_paused ? <Notice tone="warning" text={`${d!.salon.booking_paused.message} نوبت‌های ثبت‌شده‌ی شما سر جایشان هستند.`} /> : null}
          {d!.awaiting_payment_count > 0 ? (
            <Notice tone="warning" text={`${faDigits(d!.awaiting_payment_count)} نوبت در انتظار پرداخت دارید؛ بدون پیش‌پرداخت در مهلتش لغو می‌شود.`} />
          ) : null}

          {d!.next_booking ? (
            <NextBookingCard
              booking={d!.next_booking}
              isToday={!!d!.next_is_today}
              canChange={d!.can_change ?? true}
              changeHours={d!.change_hours ?? 24}
              phone={d!.salon_contact?.phone ?? null}
              directionsUrl={d!.salon_contact?.directions_url ?? null}
            />
          ) : (
            <Card>
              <T weight="medium">نوبت پیش رویی ندارید.</T>
              {d!.salon.booking_paused ? null : <Button kind="strong" title="رزرو نوبت تازه" onPress={() => router.push('/book')} />}
            </Card>
          )}

          <Row gap={2.5} style={{ alignItems: 'stretch' }}>
            <Card style={{ flex: 1, padding: space(3.5), gap: space(0.5) }} onPress={() => router.push('/wallet')}>
              <T muted size={12.5}>کیف پول</T>
              <T weight="bold" size={17}>{toman(d!.wallet_balance)}</T>
              <T size={12.5} weight="bold" color={p.gold}>شارژ کیف پول</T>
            </Card>
            {d!.loyalty_enabled !== false ? (
              <Card style={{ flex: 1, padding: space(3.5), gap: space(0.5) }} onPress={() => router.push('/club')}>
                <T muted size={12.5}>باشگاه مشتریان</T>
                {d!.loyalty_points > 0 ? (
                  <T weight="bold" size={17}>{faDigits(d!.loyalty_points)} امتیاز</T>
                ) : (
                  <>
                    <T weight="bold" size={14.5}>هنوز امتیازی ندارید</T>
                    <T muted size={12}>با هر نوبت امتیاز بگیرید</T>
                  </>
                )}
              </Card>
            ) : null}
          </Row>

          {d!.features?.includes('treatment_courses') ? (
            <Card onPress={() => router.push('/courses')}>
              <T weight="medium">دوره‌های درمانی</T>
              <T muted size={13}>چند جلسه را یک‌جا و ارزان‌تر بخرید؛ دوره‌های خودتان را هم این‌جا ببینید.</T>
            </Card>
          ) : null}

          <NextBookingsList bookings={d!.next_bookings ?? d!.upcoming_bookings.filter((b) => b.id !== d!.next_booking?.id)} />

          {d!.to_rate ? (
            <Card onPress={() => router.push(`/booking/${d!.to_rate!.id}`)}>
              <T weight="bold" size={15.5}>نظرتان چه بود؟</T>
              <T muted size={13}>{d!.to_rate.service?.name}{d!.to_rate.date_label ? `، ${d!.to_rate.date_label}` : ''}</T>
              <T size={13} weight="bold" color={p.gold}>ثبت نظر</T>
            </Card>
          ) : null}

          {d!.rebook?.service || d!.suggestion ? (
            <Card>
              <T weight="bold" size={15.5}>دوباره رزرو کنید</T>
              <Row gap={2} style={{ alignItems: 'stretch' }}>
                {d!.rebook?.service ? (
                  <Suggestion title={d!.rebook.service.name} note={`آخرین بار ${d!.rebook.date_label}`} onPress={() => router.push(`/pick/${d!.rebook!.service!.id}${d!.rebook!.specialist ? `?specialist=${d!.rebook!.specialist.id}` : ''}`)} />
                ) : null}
                {d!.suggestion ? <Suggestion dashed title={d!.suggestion.name} note="پرطرفدار این سالن" onPress={() => router.push(`/pick/${d!.suggestion!.id}`)} /> : null}
              </Row>
            </Card>
          ) : null}
        </>
      )}
    </Screen>
  );
}

function RoundIcon({ icon, label, onPress, badge = 0 }: { icon: React.ComponentProps<typeof Ionicons>['name']; label: string; onPress: () => void; badge?: number }) {
  const p = usePalette();
  return (
    <Pressable accessibilityRole="button" accessibilityLabel={label} onPress={onPress} style={({ pressed }) => ({ width: 44, height: 44, borderRadius: 22, borderWidth: 1, borderColor: p.borderStrong, backgroundColor: p.surface, alignItems: 'center', justifyContent: 'center', opacity: pressed ? 0.8 : 1 })}>
      <Ionicons name={icon} size={19} color={p.text} />
      {badge > 0 ? (
        <View style={{ position: 'absolute', top: -3, left: -3, minWidth: 18, paddingHorizontal: 5, borderRadius: 9, backgroundColor: p.danger }}>
          <T size={11} weight="bold" color="#FFFFFF" center style={{ lineHeight: 18 }}>{faDigits(badge)}</T>
        </View>
      ) : null}
    </Pressable>
  );
}

function Suggestion({ title, note, onPress, dashed }: { title: string; note: string; onPress: () => void; dashed?: boolean }) {
  const p = usePalette();
  const { radius } = useLayoutMetrics();
  return (
    <Pressable accessibilityRole="button" onPress={onPress} style={({ pressed }) => ({ flex: 1, padding: space(3), borderRadius: radius.md, borderWidth: 1, borderStyle: dashed ? 'dashed' : 'solid', borderColor: p.borderStrong, opacity: pressed ? 0.85 : 1 })}>
      <T weight="bold" size={14} numberOfLines={2}>{title}</T>
      <T muted size={12}>{note}</T>
    </Pressable>
  );
}

function NextBookingCard({ booking: b, isToday, canChange, changeHours, phone, directionsUrl }: {
  booking: CustomerBooking; isToday: boolean; canChange: boolean; changeHours: number; phone: string | null; directionsUrl: string | null;
}) {
  const p = usePalette();
  const primary = b.actions.pay
    ? { title: `پرداخت ${toman(b.prepayment_amount)}`, onPress: () => router.push(`/pay/${b.id}`) }
    : directionsUrl
      ? { title: 'مسیریابی', onPress: () => void Linking.openURL(directionsUrl) }
      : { title: 'جزئیات', onPress: () => router.push(`/booking/${b.id}`) };
  return (
    <Card style={{ gap: space(3), padding: space(4.5) }}>
      <Row style={{ justifyContent: 'space-between', alignItems: 'flex-end' }}>
        <T muted size={14} style={{ flexShrink: 1 }}>{isToday ? 'نوبت امروز' : `نوبت بعدی، ${b.day_label ?? b.date_label ?? ''}`}</T>
        <BigTime>{b.time_label ?? b.booking_time_jalali?.split(' ')[1] ?? ''}</BigTime>
      </Row>
      <View>
        <T weight="bold" size={18}>{b.service?.name ?? '—'}</T>
        <T muted size={14}>{b.specialist ? `با ${b.specialist.name}` : ''}{b.duration_minutes ? `، ${faDigits(b.duration_minutes)} دقیقه` : ''}</T>
      </View>
      <Row gap={2} style={{ flexWrap: 'wrap' }}>
        <CustomerStatusBadge booking={b} />
        <PaymentBadge booking={b} />
      </Row>
      <Row gap={2}>
        <View style={{ flex: 1 }}><Button kind="strong" title={primary.title} onPress={primary.onPress} /></View>
        {b.actions.reschedule ? <Button small kind="secondary" title="تغییر زمان" onPress={() => router.push(`/reschedule/${b.id}`)} /> : null}
        <MoreButton items={bookingMoreItems(b, phone)} label="کارهای بیشتر این نوبت" size={48} />
      </Row>
      <View style={{ borderTopWidth: 1, borderTopColor: p.border, paddingTop: space(2.5) }}>
        <T muted size={12.5}>
          {canChange
            ? `تغییر زمان یا لغو تا ${faDigits(changeHours)} ساعت پیش از نوبت ممکن است.`
            : `کمتر از ${faDigits(changeHours)} ساعت به نوبت مانده؛ برای تغییر یا لغو با سالن تماس بگیرید.`}
        </T>
      </View>
    </Card>
  );
}

function NextBookingsList({ bookings }: { bookings: CustomerBooking[] }) {
  const p = usePalette();
  if (bookings.length === 0) {
    return null;
  }
  return (
    <Card style={{ paddingBottom: space(1) }}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold" size={15.5}>نوبت‌های بعدی</T>
        <T size={13} weight="bold" color={p.gold} onPress={() => router.push('/bookings')}>همه</T>
      </Row>
      {bookings.slice(0, 4).map((b) => (
        <Pressable key={b.id} accessibilityRole="button" onPress={() => router.push(`/booking/${b.id}`)} style={{ flexDirection: 'row', alignItems: 'center', gap: space(3), paddingVertical: space(3), borderTopWidth: 1, borderTopColor: p.border }}>
          <DayBox label={b.date_label ?? b.booking_time_label} />
          <View style={{ flex: 1, gap: space(0.5) }}>
            <T weight="bold" size={14.5} numberOfLines={1}>{b.service?.name ?? '—'}{b.time_label ? `، ${b.time_label}` : ''}</T>
            {b.specialist ? <T muted size={12.5}>{b.specialist.name}</T> : null}
            <View style={{ alignSelf: 'flex-start' }}>
              {b.actions.pay ? <Badge text="منتظر پیش‌پرداخت" tone="danger" /> : <CustomerStatusBadge booking={b} />}
            </View>
          </View>
          {b.actions.pay ? <Button small kind="strong" title={`پرداخت ${toman(b.prepayment_amount)}`} onPress={() => router.push(`/pay/${b.id}`)} /> : null}
        </Pressable>
      ))}
    </Card>
  );
}

