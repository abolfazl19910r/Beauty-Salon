import { useState } from 'react';
import { Modal, Pressable, View } from 'react-native';

import { faDigits } from '@/lib/format';
import { MONTHS, WEEK_HEADER, isoDate, jalaliLabel, monthGrid, parseIsoDate, shiftMonth, toJ } from '@/lib/jalali';
import { Button, Row, T } from './kit';
import { radius, space, usePalette } from './theme';

/** انتخاب تاریخ شمسی (ماهانه، شنبه تا جمعه). مقدار ورودی/خروجی میلادی Y-m-d مثل API. */
export function JalaliDateField({ label, value, onChange, minDate }: { label: string; value: string | null; onChange: (iso: string) => void; minDate?: string }) {
  const p = usePalette();
  const [open, setOpen] = useState(false);
  const initial = toJ(value ? parseIsoDate(value) : new Date());
  const [view, setView] = useState({ jy: initial.jy, jm: initial.jm });

  return (
    <View style={{ gap: space(1) }}>
      <T weight="medium" size={13}>{label}</T>
      <Pressable
        accessibilityRole="button"
        onPress={() => setOpen(true)}
        style={{ backgroundColor: p.surface, borderColor: p.border, borderWidth: 1, borderRadius: radius.md, padding: space(3) }}
      >
        <T muted={!value}>{value ? jalaliLabel(parseIsoDate(value)) : 'انتخاب تاریخ'}</T>
      </Pressable>

      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <Pressable style={{ flex: 1, backgroundColor: '#0008', justifyContent: 'center', padding: space(5) }} onPress={() => setOpen(false)}>
          <Pressable style={{ backgroundColor: p.surface, borderRadius: radius.lg, padding: space(4), gap: space(3) }} onPress={() => undefined}>
            <Row style={{ justifyContent: 'space-between' }}>
              <Button small kind="ghost" title="‹ قبل" onPress={() => setView(shiftMonth(view.jy, view.jm, -1))} />
              <T weight="bold">{MONTHS[view.jm - 1]} {faDigits(view.jy)}</T>
              <Button small kind="ghost" title="بعد ›" onPress={() => setView(shiftMonth(view.jy, view.jm, 1))} />
            </Row>
            <View style={{ flexDirection: 'row', flexWrap: 'wrap' }}>
              {WEEK_HEADER.map((h) => (
                <View key={h} style={{ width: `${100 / 7}%`, alignItems: 'center', paddingVertical: space(1) }}>
                  <T muted size={12}>{h}</T>
                </View>
              ))}
              {monthGrid(view.jy, view.jm).map((d, i) => {
                if (!d) {
                  return <View key={`e${i}`} style={{ width: `${100 / 7}%` }} />;
                }
                const iso = isoDate(d);
                const disabled = minDate !== undefined && iso < minDate;
                const selected = iso === value;
                return (
                  <Pressable
                    key={iso}
                    disabled={disabled}
                    onPress={() => {
                      onChange(iso);
                      setOpen(false);
                    }}
                    style={{ width: `${100 / 7}%`, alignItems: 'center', paddingVertical: space(2), borderRadius: radius.sm, backgroundColor: selected ? p.gold : 'transparent', opacity: disabled ? 0.3 : 1 }}
                  >
                    <T color={selected ? p.onGold : undefined}>{faDigits(toJ(d).jd)}</T>
                  </Pressable>
                );
              })}
            </View>
          </Pressable>
        </Pressable>
      </Modal>
    </View>
  );
}
