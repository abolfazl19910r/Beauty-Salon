/// <reference types="jest" />
import { addDays, fromJ, isoDate, jalaliLabel, monthGrid, parseIsoDate, shiftMonth, startOfWeek, toJ } from '../jalali';

describe('jalali', () => {
  it('converts known dates', () => {
    // تاریخ‌های تست سمت لاراول: 2026-10-20 = ۲۸ مهر ۱۴۰۵
    expect(toJ(parseIsoDate('2026-10-20'))).toEqual({ jy: 1405, jm: 7, jd: 28 });
    expect(isoDate(fromJ({ jy: 1405, jm: 1, jd: 1 }))).toBe('2026-03-21');
    expect(jalaliLabel(parseIsoDate('2026-10-20'))).toBe('۲۸ مهر ۱۴۰۵');
  });

  it('starts the week on Saturday', () => {
    // 2026-10-11 یکشنبه است → شنبه 2026-10-10
    expect(isoDate(startOfWeek(parseIsoDate('2026-10-11')))).toBe('2026-10-10');
    expect(isoDate(startOfWeek(parseIsoDate('2026-10-10')))).toBe('2026-10-10');
    expect(isoDate(startOfWeek(parseIsoDate('2026-10-16')))).toBe('2026-10-10');
  });

  it('builds a Saturday-first month grid', () => {
    const grid = monthGrid(1405, 7);
    expect(grid.length % 7).toBe(0);
    const first = grid.find((d) => d !== null) as Date;
    expect(toJ(first)).toEqual({ jy: 1405, jm: 7, jd: 1 });
    // ۱ مهر ۱۴۰۵ = 2026-09-23 چهارشنبه → خانه‌ی پنجم (ش ی د س چ)
    expect(grid.indexOf(first)).toBe(4);
    expect(grid.filter(Boolean)).toHaveLength(30);
  });

  it('shifts months across years and adds days', () => {
    expect(shiftMonth(1405, 12, 1)).toEqual({ jy: 1406, jm: 1 });
    expect(shiftMonth(1405, 1, -1)).toEqual({ jy: 1404, jm: 12 });
    expect(isoDate(addDays(parseIsoDate('2026-12-31'), 1))).toBe('2027-01-01');
  });
});
