/// <reference types="jest" />
import { applySalonTheme, layoutMetrics } from '@/ui/theme';

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));

import { getSalonTheme, normalizeTheme, setSalonTheme, withAlpha } from '../salonTheme';

/** بسته‌ی «و» — همان شکل App\Support\Themes\SalonAppearance::toApi() سرور */
const colors = (accent: string, on: string) => ({
  bg: '#110F18', surface: '#191623', surfaceAlt: '#221E30', card: '#221E30', text: '#EFEBF9', muted: '#A79FC0',
  accent, accentStrong: '#D8CCFA', onAccent: on, line: 'rgba(197,179,246,.13)', lineStrong: 'rgba(197,179,246,.28)',
});
const lilac = { key: 'aram:lilac', layout: 'aram', palette: 'lilac', name: 'آرام · یاس', colors: { dark: colors('#C5B3F6', '#1D1534'), light: colors('#5B43B5', '#FFFFFF') } };

const base = {
  background: '#15110E', surface: '#1C1712', surfaceMuted: '#251E17', border: 'b', borderStrong: 'bs', text: '#F4EDE0', textMuted: '#B3A48C',
  gold: '#E3C985', goldSoft: 'gs', button: '#D8B96A', onGold: '#221A0C', success: '#9CC58F', successSoft: 'x', warning: 'w', warningSoft: 'x',
  danger: 'd', dangerSoft: 'x', info: 'i', infoSoft: 'x',
};

describe('salon theme', () => {
  it('accepts only a well-formed theme', () => {
    expect(normalizeTheme(lilac)?.layout).toBe('aram');
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
    setSalonTheme(undefined);
    expect(getSalonTheme()).toBeNull();
  });

  it('gives each layout its corners and heading font', () => {
    expect(layoutMetrics.majalle.radius.lg).toBeLessThan(layoutMetrics.mahru.radius.lg);
    expect(layoutMetrics.bento.radius.lg).toBeGreaterThan(layoutMetrics.mahru.radius.lg);
    expect(layoutMetrics.majalle.display).toBe('NotoNaskhArabic-Bold');
    expect(layoutMetrics.bento.display).toBe('NotoKufiArabic-ExtraBold');
  });
});
