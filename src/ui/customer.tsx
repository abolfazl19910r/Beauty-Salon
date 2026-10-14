import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Image, Pressable, useColorScheme, View } from 'react-native';

import type { CustomerBooking, CustomerBookingStatus, SalonCard } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { logoAppearance } from '@/lib/logo';
import { Badge, Card, Row, T } from './kit';
import { radius, space, usePalette } from './theme';

const TONE: Record<CustomerBookingStatus, 'warning' | 'success' | 'info' | 'danger' | 'muted' | 'gold'> = {
  pending_payment: 'gold',
  pending: 'warning',
  confirmed: 'success',
  completed: 'info',
  cancelled: 'danger',
  no_show: 'danger',
};

export function CustomerStatusBadge({ booking }: { booking: Pick<CustomerBooking, 'status' | 'status_label'> }) {
  return <Badge text={booking.status_label} tone={TONE[booking.status] ?? 'muted'} />;
}

/**
 * لوگوی سالن (اگر دارد) یا نشان ماهرو. بسته‌ی د: لوگو کامل دیده می‌شود (contain، نه برش) و روی تمی که کنتراست کم دارد
 * داخل پلاک گرد با پس‌زمینه‌ی مخالف می‌نشیند — همان قاعده‌ی وب؛ تم تیره لوگوی دوم سالن را اگر دارد نشان می‌دهد.
 */
export function SalonLogo({ salon, size = 72 }: { salon: Pick<SalonCard, 'logo_url' | 'logo_tone' | 'logo_dark_url' | 'logo_dark_tone' | 'name'>; size?: number }) {
  const look = logoAppearance(salon, useColorScheme() === 'dark' ? 'dark' : 'light');
  if (look) {
    // پلاک قاب بیرونی است (padding روی خود Image در iOS نادیده گرفته می‌شود)؛ اندازه‌ی کل همان size می‌ماند.
    const pad = look.plaque ? Math.round(size / 10) : 0;
    return (
      <View testID="salon-logo" style={{ width: size, height: size, borderRadius: size / 4, padding: pad, backgroundColor: look.plaque ?? 'transparent' }}>
        <Image source={{ uri: look.uri }} accessibilityLabel={salon.name} resizeMode="contain" style={{ width: size - pad * 2, height: size - pad * 2 }} />
      </View>
    );
  }
  return (
    <Image source={require('../../assets/customer/icon.png')} accessibilityLabel={salon.name} style={{ width: size, height: size, borderRadius: size / 4 }} />
  );
}

export function SalonHeader({ salon }: { salon: SalonCard }) {
  return (
    <View style={{ alignItems: 'center', gap: space(2) }}>
      <SalonLogo salon={salon} size={84} />
      <T weight="bold" size={20} center>{salon.name}</T>
      {salon.tagline ? <T muted center>{salon.tagline}</T> : null}
    </View>
  );
}

/** کارت نوبت مشتری (خانه و «نوبت‌ها»)؛ لمس → جزئیات */
export function CustomerBookingCard({ booking }: { booking: CustomerBooking }) {
  const p = usePalette();
  return (
    <Card onPress={() => router.push(`/booking/${booking.id}`)}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold" style={{ flexShrink: 1 }}>{booking.service?.name ?? '—'}</T>
        <CustomerStatusBadge booking={booking} />
      </Row>
      <T muted size={14}>{faDigits(booking.booking_time_label ?? booking.booking_time_jalali ?? '')}</T>
      {booking.course?.label ? <Badge text={faDigits(booking.course.label)} tone="info" /> : null}
      <Row style={{ justifyContent: 'space-between' }}>
        <T size={14}>{booking.specialist?.name ?? ''}</T>
        {booking.actions.pay ? <T size={13} weight="medium" color={p.gold}>پرداخت پیش‌پرداخت: {toman(booking.prepayment_amount)}</T> : null}
      </Row>
      {booking.pay_before && booking.actions.pay ? <PayCountdown until={booking.pay_before} /> : null}
    </Card>
  );
}

/** شمارش معکوس مهلت پرداخت نوبت «در انتظار پرداخت» (pay_before سرور) */
export function PayCountdown({ until }: { until: string }) {
  const p = usePalette();
  const end = new Date(until).getTime();
  const [now, setNow] = useState(Date.now());
  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);
  const left = Math.max(0, Math.floor((end - now) / 1000));
  if (Number.isNaN(end)) {
    return null;
  }
  const text = left > 0
    ? `مهلت پرداخت: ${faDigits(String(Math.floor(left / 60)).padStart(2, '0'))}:${faDigits(String(left % 60).padStart(2, '0'))}`
    : 'مهلت پرداخت تمام شده است؛ نوبت به‌زودی لغو می‌شود.';
  return <T size={13} color={left > 0 ? p.warning : p.danger}>{text}</T>;
}

/** امتیاز ۱ تا ۵ ستاره (ورودی یا فقط نمایش) */
export function Stars({ value, onChange, size = 28 }: { value: number; onChange?: (v: number) => void; size?: number }) {
  const p = usePalette();
  return (
    <Row gap={1}>
      {[1, 2, 3, 4, 5].map((n) => (
        <Pressable key={n} disabled={!onChange} onPress={() => onChange?.(n)} accessibilityRole="button" accessibilityLabel={`${faDigits(n)} ستاره`}>
          <Ionicons name={n <= value ? 'star' : 'star-outline'} size={size} color={p.gold} />
        </Pressable>
      ))}
    </Row>
  );
}

/** ردیف منو در «حساب» */
export function MenuRow({ icon, title, subtitle, onPress, trailing }: { icon: React.ComponentProps<typeof Ionicons>['name']; title: string; subtitle?: string; onPress: () => void; trailing?: React.ReactNode }) {
  const p = usePalette();
  return (
    <Pressable onPress={onPress} accessibilityRole="button" style={({ pressed }) => ({ flexDirection: 'row', alignItems: 'center', gap: space(3), paddingVertical: space(3), paddingHorizontal: space(4), backgroundColor: p.surface, borderRadius: radius.md, opacity: pressed ? 0.85 : 1 })}>
      <Ionicons name={icon} size={22} color={p.gold} />
      <View style={{ flex: 1 }}>
        <T weight="medium">{title}</T>
        {subtitle ? <T muted size={12}>{subtitle}</T> : null}
      </View>
      {trailing}
      <Ionicons name="chevron-back" size={18} color={p.textMuted} />
    </Pressable>
  );
}
