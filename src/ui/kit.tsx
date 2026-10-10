import { type ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  type StyleProp,
  StyleSheet,
  Text,
  TextInput,
  type TextInputProps,
  type TextStyle,
  View,
  type ViewStyle,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, NETWORK_ERROR_MESSAGE } from '@/lib/api';
import { fonts, type Palette, radius, space, usePalette } from './theme';

type Weight = 'regular' | 'medium' | 'bold';

export function T({
  children,
  weight = 'regular',
  size = 15,
  muted,
  color,
  center,
  style,
  numberOfLines,
}: {
  children: ReactNode;
  weight?: Weight;
  size?: number;
  muted?: boolean;
  color?: string;
  center?: boolean;
  style?: StyleProp<TextStyle>;
  numberOfLines?: number;
}) {
  const p = usePalette();
  return (
    <Text
      numberOfLines={numberOfLines}
      style={[
        { fontFamily: fonts[weight], fontSize: size, lineHeight: Math.round(size * 1.7), color: color ?? (muted ? p.textMuted : p.text), textAlign: center ? 'center' : 'left', writingDirection: 'rtl' },
        style,
      ]}
    >
      {children}
    </Text>
  );
}

export function Screen({ children, scroll = true, padded = true, refreshControl }: { children: ReactNode; scroll?: boolean; padded?: boolean; refreshControl?: React.ReactElement<any> }) {
  const p = usePalette();
  const inner = padded ? { padding: space(4), gap: space(3) } : undefined;
  return (
    <SafeAreaView edges={['bottom', 'left', 'right']} style={{ flex: 1, backgroundColor: p.background }}>
      {scroll ? (
        <ScrollView contentContainerStyle={[inner, { paddingBottom: space(10) }]} keyboardShouldPersistTaps="handled" refreshControl={refreshControl}>
          {children}
        </ScrollView>
      ) : (
        <View style={[{ flex: 1 }, inner]}>{children}</View>
      )}
    </SafeAreaView>
  );
}

export function Card({ children, style, onPress }: { children: ReactNode; style?: StyleProp<ViewStyle>; onPress?: () => void }) {
  const p = usePalette();
  const base = [{ backgroundColor: p.surface, borderColor: p.border, borderWidth: StyleSheet.hairlineWidth, borderRadius: radius.lg, padding: space(4), gap: space(2) }, style];
  return onPress ? (
    <Pressable onPress={onPress} style={({ pressed }) => [base, pressed && { opacity: 0.85 }]} accessibilityRole="button">
      {children}
    </Pressable>
  ) : (
    <View style={base}>{children}</View>
  );
}

export function Row({ children, style, gap = 2 }: { children: ReactNode; style?: StyleProp<ViewStyle>; gap?: number }) {
  return <View style={[{ flexDirection: 'row', alignItems: 'center', gap: space(gap) }, style]}>{children}</View>;
}

type ButtonKind = 'primary' | 'secondary' | 'danger' | 'ghost';

export function Button({ title, onPress, kind = 'primary', loading, disabled, small }: { title: string; onPress: () => void; kind?: ButtonKind; loading?: boolean; disabled?: boolean; small?: boolean }) {
  const p = usePalette();
  const colors: Record<ButtonKind, { bg: string; fg: string; border: string }> = {
    primary: { bg: p.gold, fg: p.onGold, border: p.gold },
    secondary: { bg: p.surface, fg: p.text, border: p.border },
    danger: { bg: p.dangerSoft, fg: p.danger, border: p.dangerSoft },
    ghost: { bg: 'transparent', fg: p.gold, border: 'transparent' },
  };
  const c = colors[kind];
  const inactive = disabled || loading;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: !!inactive, busy: !!loading }}
      disabled={inactive}
      onPress={onPress}
      style={({ pressed }) => ({
        backgroundColor: c.bg,
        borderColor: c.border,
        borderWidth: 1,
        borderRadius: radius.md,
        paddingVertical: small ? space(2) : space(3),
        paddingHorizontal: space(4),
        alignItems: 'center',
        opacity: inactive ? 0.55 : pressed ? 0.85 : 1,
        flexGrow: small ? 0 : 1,
      })}
    >
      {loading ? <ActivityIndicator color={c.fg} /> : <T weight="bold" size={small ? 13 : 15} color={c.fg}>{title}</T>}
    </Pressable>
  );
}

export function Field({ label, error, hint, ...input }: TextInputProps & { label: string; error?: string; hint?: string }) {
  const p = usePalette();
  return (
    <View style={{ gap: space(1) }}>
      <T weight="medium" size={13}>{label}</T>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor={p.textMuted}
        {...input}
        style={[
          { fontFamily: fonts.regular, fontSize: 16, color: p.text, backgroundColor: p.surface, borderColor: error ? p.danger : p.border, borderWidth: 1, borderRadius: radius.md, paddingHorizontal: space(3), paddingVertical: space(3), textAlign: 'right' },
          input.style,
        ]}
      />
      {error ? <T size={12} color={p.danger}>{error}</T> : hint ? <T size={12} muted>{hint}</T> : null}
    </View>
  );
}

export function Loading({ label = 'در حال دریافت…' }: { label?: string }) {
  const p = usePalette();
  return (
    <View style={{ padding: space(8), alignItems: 'center', gap: space(3) }}>
      <ActivityIndicator color={p.gold} />
      <T muted>{label}</T>
    </View>
  );
}

export function errorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }
  return NETWORK_ERROR_MESSAGE;
}

export function ErrorBox({ error, onRetry }: { error: unknown; onRetry?: () => void }) {
  const p = usePalette();
  return (
    <View style={{ backgroundColor: p.dangerSoft, borderRadius: radius.md, padding: space(3), gap: space(2) }}>
      <T color={p.danger}>{errorMessage(error)}</T>
      {onRetry ? <Button small kind="secondary" title="تلاش دوباره" onPress={onRetry} /> : null}
    </View>
  );
}

export function Notice({ text, tone = 'info' }: { text: string; tone?: 'info' | 'success' | 'warning' }) {
  const p = usePalette();
  const map = { info: [p.infoSoft, p.info], success: [p.successSoft, p.success], warning: [p.warningSoft, p.warning] } as const;
  return (
    <View style={{ backgroundColor: map[tone][0], borderRadius: radius.md, padding: space(3) }}>
      <T color={map[tone][1]}>{text}</T>
    </View>
  );
}

export function Empty({ text }: { text: string }) {
  return (
    <View style={{ padding: space(8), alignItems: 'center' }}>
      <T muted center>{text}</T>
    </View>
  );
}

export function Badge({ text, tone }: { text: string; tone: 'gold' | 'success' | 'warning' | 'danger' | 'info' | 'muted' }) {
  const p = usePalette();
  const map: Record<string, [string, string]> = {
    gold: [p.goldSoft, p.gold],
    success: [p.successSoft, p.success],
    warning: [p.warningSoft, p.warning],
    danger: [p.dangerSoft, p.danger],
    info: [p.infoSoft, p.info],
    muted: [p.surfaceMuted, p.textMuted],
  };
  return (
    <View style={{ backgroundColor: map[tone][0], borderRadius: 99, paddingHorizontal: space(2.5), paddingVertical: 2, alignSelf: 'flex-start' }}>
      <T size={12} weight="medium" color={map[tone][1]}>{text}</T>
    </View>
  );
}

export function Chip({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  const p = usePalette();
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      style={{ borderRadius: 99, paddingHorizontal: space(3), paddingVertical: space(1.5), backgroundColor: active ? p.gold : p.surface, borderColor: active ? p.gold : p.border, borderWidth: 1 }}
    >
      <T size={13} weight="medium" color={active ? p.onGold : p.text}>{label}</T>
    </Pressable>
  );
}

export function KeyValue({ label, value }: { label: string; value: ReactNode }) {
  return (
    <Row style={{ justifyContent: 'space-between' }}>
      <T muted size={14}>{label}</T>
      {typeof value === 'string' || typeof value === 'number' ? <T size={14} weight="medium">{value}</T> : value}
    </Row>
  );
}

export function SectionTitle({ children }: { children: ReactNode }) {
  return <T weight="bold" size={16} style={{ marginTop: space(2) }}>{children}</T>;
}

export type { Palette };
