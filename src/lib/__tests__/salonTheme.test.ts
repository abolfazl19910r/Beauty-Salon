/// <reference types="jest" />
import { applySalonTheme, layoutMetrics } from '@/ui/theme';

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));

import { getSalonTheme, normalizeTheme, setSalonTheme, withAlpha } from '../salonTheme';

/** بسته‌ی «و» — همان شکل App\Support\Themes\SalonAppearance::toApi() سرور */
const colors = (accent: string, on: string) => ({
  bg: '#110F18', surface: '#191623', surfaceAlt: '#221E30', card: '#221E30', text: '#EFEBF9', muted: '#A79FC0',
  accent, accentStrong: '#D8CCFA', onAccent: on, line: 'rgba(197,179,246,.13)', lineStrong: 'rgba(197,179,246,.28)',
});
/** سرور پیش از ۵الف: فقط layout قدیمی، بی رنگ دوم */
const lilac = { key: 'aram:lilac', layout: 'aram', palette: 'lilac', name: 'آرام · یاس', colors: { dark: colors('#C5B3F6', '#1D1534'), light: colors('#5B43B5', '#FFFFFF') } };
/** سرور ۵الف/۵ب: layout قدیمی برای اپ‌های قدیمی + template تازه و رنگ دوم */
const sage = {
  key: 'majalle:sage', layout: 'majalle', template: 'editorial', template_key: 'editorial:sage', palette: 'sage', name: 'روزنامه‌ی سالن · مریم‌گلی',
  colors: { dark: { ...colors('#A9CFAB', '#17241B'), accent2: '#D8BE85', onAccent2: '#17241B' }, light: { ...colors('#335E45', '#FFFFFF'), accent2: '#7E5F28', onAccent2: '#FFFFFF' } },
};

const base = {
  background: '#15110E', surface: '#1C1712', surfaceMuted: '#251E17', border: 'b', borderStrong: 'bs', text: '#F4EDE0', textMuted: '#B3A48C',
  gold: '#E3C985', goldSoft: 'gs', button: '#D8B96A', onGold: '#221A0C', success: '#9CC58F', successSoft: 'x', warning: 'w', warningSoft: 'x',
  danger: 'd', dangerSoft: 'x', info: 'i', infoSoft: 'x', accent2: '#E3C985', onAccent2: '#221A0C', strong: '#F4EDE0', onStrong: '#15110E',
};

describe('salon theme', () => {
  it('accepts only a well-formed theme', () => {
    expect(normalizeTheme(lilac)?.layout).toBe('showcase');
    expect(normalizeTheme(null)).toBeNull();
    expect(normalizeTheme({ ...lilac, layout: 'unknown' })).toBeNull();
    expect(normalizeTheme({ ...lilac, colors: { dark: { ...lilac.colors.dark, accent: 'red' }, light: lilac.colors.light } })).toBeNull();
  });

  it('paints the palette but keeps status colours, and leaves Mahru untouched', () => {
    const themed = applySalonTheme(base, normalizeTheme(lilac), 'dark');
    expect(themed.background).toBe('#110F18');
    expect(themed.button).toBe('#C5B3F6');
    expect(themed.onGold).toBe('#1D1534');
    expect(themed.gold).toBe('#D8CCFA');
    expect(themed.goldSoft).toBe(withAlpha('#C5B3F6', 0.16));
    expect(themed.success).toBe(base.success);
    expect(applySalonTheme(base, normalizeTheme(lilac), 'light').button).toBe('#5B43B5');

    expect(applySalonTheme(base, null, 'dark')).toBe(base);
    expect(applySalonTheme(base, normalizeTheme({ ...lilac, key: 'mahru', layout: 'mahru' }), 'dark')).toBe(base);
  });

  it('keeps the latest theme in the store and clears it for a salon without one', () => {
    setSalonTheme(lilac);
    expect(getSalonTheme()?.key).toBe('aram:lilac');
    setSalonTheme(sage);
    expect(getSalonTheme()?.key).toBe('editorial:sage');
    setSalonTheme(undefined);
    expect(getSalonTheme()).toBeNull();
  });

  it('reads the new template first and maps the legacy layout of an older server or stored theme', () => {
    expect(normalizeTheme(sage)?.layout).toBe('editorial');
    expect(normalizeTheme(sage)?.key).toBe('editorial:sage');
    for (const [legacy, template] of [['vitrin', 'signature'], ['majalle', 'editorial'], ['aram', 'showcase'], ['bento', 'app']]) {
      expect(normalizeTheme({ ...lilac, layout: legacy })?.layout).toBe(template);
    }
    // template ناشناخته ← نگاشت layout؛ قالب ذخیره‌شده با کلید تازه هم پذیرفته می‌شود
    expect(normalizeTheme({ ...sage, template: 'future' })?.layout).toBe('editorial');
    expect(normalizeTheme({ ...lilac, layout: 'app' })?.layout).toBe('app');
  });

  it('uses the second colour when the server sends it and falls back to the accent otherwise', () => {
    const dark = applySalonTheme(base, normalizeTheme(sage), 'dark');
    expect(dark.accent2).toBe('#D8BE85');
    expect(dark.onAccent2).toBe('#17241B');
    expect(applySalonTheme(base, normalizeTheme(sage), 'light').accent2).toBe('#7E5F28');

    expect(applySalonTheme(base, normalizeTheme(lilac), 'dark').accent2).toBe('#D8CCFA');
    expect(applySalonTheme(base, normalizeTheme(lilac), 'light').accent2).toBe('#5B43B5');
    expect(applySalonTheme(base, normalizeTheme(lilac), 'dark').onAccent2).toBe('#1D1534');

    const broken = normalizeTheme({ ...sage, colors: { dark: { ...sage.colors.dark, accent2: 'gold' }, light: sage.colors.light } });
    expect(broken?.colors.dark.accent2).toBeUndefined();
  });

  it('gives each template its corners and heading font', () => {
    expect(layoutMetrics.editorial.radius.lg).toBe(0);
    expect(layoutMetrics.signature.radius.lg).toBeLessThan(layoutMetrics.mahru.radius.lg);
    expect(layoutMetrics.app.radius.lg).toBeGreaterThan(layoutMetrics.mahru.radius.lg);
    expect(layoutMetrics.signature.display).toBe('NotoNaskhArabic-Bold');
    expect(layoutMetrics.editorial.display).toBe('NotoNaskhArabic-Bold');
    expect(layoutMetrics.showcase.display).toBe('NotoKufiArabic-ExtraBold');
  });
});
