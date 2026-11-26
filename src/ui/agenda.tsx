import { useState } from 'react';
import { Modal, Pressable, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { faDigits } from '@/lib/format';
import { T } from './kit';
import { space, useLayoutMetrics, usePalette } from './theme';

/**
 * بسته‌ی ۵د (پیش‌نمایش تأییدشده‌ی «اپ مشتری» و «اپ همکار» روی بوم): قطعه‌های مشترک خانه و فهرست نوبت‌ها — جعبه‌ی روز، ساعت درشت،
 * دکمه‌ی گرد «⋯» با منوی پایین صفحه (همان منوی «⋯» وب: کارهای کم‌کاربرد و «لغو نوبت…» با یک خط پیامد).
 */

/** «جمعه ۶ آذر» → جعبه‌ی روز؛ سال دیگر («۶ آذر ۱۴۰۶») هم کار می‌کند */
export function DayBox({ label }: { label: string | null | undefined }) {
  const p = usePalette();
  const { radius } = useLayoutMetrics();
  const parts = (label ?? '').replace(/^(امروز|فردا)\s*/, '').trim().split(/\s+/);
  const [weekday, day, month] = /^[۰-۹0-9]/.test(parts[0] ?? '') ? ['', parts[0], parts[1]] : [parts[0], parts[1], parts[2]];
  return (
    <View testID="day-box" style={{ width: 56, alignItems: 'center', paddingVertical: space(2), borderRadius: radius.md, backgroundColor: p.surfaceMuted }}>
      {weekday ? <T muted size={11}>{weekday}</T> : null}
      <T weight="bold" size={21} style={{ lineHeight: 26 }}>{faDigits(day ?? '')}</T>
      <T size={11}>{month ?? ''}</T>
    </View>
  );
}

/** ساعت درشت کارت «نوبت بعدی» با فونت تیتر قالب */
export function BigTime({ children, size = 36 }: { children: string | null | undefined; size?: number }) {
  const { display } = useLayoutMetrics();
  return <T weight="bold" size={size} style={{ fontFamily: display, lineHeight: Math.round(size * 1.25) }}>{faDigits(children ?? '')}</T>;
}

export type MoreItem = { label: string; onPress: () => void; danger?: boolean; note?: string | null };

/** دکمه‌ی گرد «⋯» و منوی پایین صفحه */
export function MoreButton({ items, label = 'کارهای بیشتر', size = 44 }: { items: MoreItem[]; label?: string; size?: number }) {
  const p = usePalette();
  const { radius } = useLayoutMetrics();
  const insets = useSafeAreaInsets();
  const [open, setOpen] = useState(false);
  if (items.length === 0) {
    return null;
  }
  return (
    <>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={label}
        onPress={() => setOpen(true)}
        style={({ pressed }) => ({ width: size, height: size, borderRadius: Math.min(size / 2, radius.md * 2), borderWidth: 1, borderColor: p.borderStrong, alignItems: 'center', justifyContent: 'center', backgroundColor: p.surface, opacity: pressed ? 0.8 : 1 })}
      >
        <T weight="bold" size={18} style={{ lineHeight: 22 }}>⋯</T>
      </Pressable>
      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <Pressable accessibilityLabel="بستن" style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.35)' }} onPress={() => setOpen(false)} />
        <View style={{ backgroundColor: p.surface, borderTopLeftRadius: radius.lg, borderTopRightRadius: radius.lg, paddingTop: space(2), paddingBottom: space(4) + insets.bottom, paddingHorizontal: space(3) }}>
          {items.map((item, i) => (
            <View key={item.label}>
              {item.danger && i > 0 ? <View style={{ height: 1, backgroundColor: p.border, marginVertical: space(1) }} /> : null}
              <Pressable
                accessibilityRole="button"
                onPress={() => { setOpen(false); item.onPress(); }}
                style={({ pressed }) => ({ paddingVertical: space(3), paddingHorizontal: space(3), borderRadius: radius.md, backgroundColor: pressed ? p.goldSoft : 'transparent' })}
              >
                <T weight={item.danger ? 'bold' : 'medium'} color={item.danger ? p.danger : undefined}>{item.label}</T>
                {item.note ? <T muted size={12}>{item.note}</T> : null}
              </Pressable>
            </View>
          ))}
        </View>
      </Modal>
    </>
  );
}

/** تب با شمار («پیش رو ۳») */
export function TabChip({ label, count, active, onPress }: { label: string; count?: number; active: boolean; onPress: () => void }) {
  const p = usePalette();
  return (
    <Pressable
      accessibilityRole="tab"
      accessibilityState={{ selected: active }}
      onPress={onPress}
      style={{ minHeight: 40, paddingHorizontal: space(3.5), borderRadius: 999, justifyContent: 'center', backgroundColor: active ? p.strong : p.surface, borderWidth: active ? 0 : 1, borderColor: p.borderStrong }}
    >
      <T weight={active ? 'bold' : 'medium'} size={14} color={active ? p.onStrong : p.text}>{label}{count !== undefined ? ` ${faDigits(count)}` : ''}</T>
    </Pressable>
  );
}
