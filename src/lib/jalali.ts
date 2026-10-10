import { jalaaliMonthLength, toGregorian, toJalaali } from 'jalaali-js';

import { faDigits } from './format';

export const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
/** ستون‌های تقویم شمسی: شنبه تا جمعه */
export const WEEK_HEADER = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

export type JDate = { jy: number; jm: number; jd: number };

const pad = (n: number) => String(n).padStart(2, '0');

/** «2026-10-11» (میلادی، قالب API) */
export function isoDate(d: Date): string {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function parseIsoDate(value: string): Date {
  const [y, m, d] = value.split('-').map(Number);
  return new Date(y, m - 1, d);
}

export function addDays(d: Date, days: number): Date {
  const r = new Date(d.getFullYear(), d.getMonth(), d.getDate());
  r.setDate(r.getDate() + days);
  return r;
}

export function toJ(d: Date): JDate {
  return toJalaali(d.getFullYear(), d.getMonth() + 1, d.getDate());
}

export function fromJ(j: JDate): Date {
  const g = toGregorian(j.jy, j.jm, j.jd);
  return new Date(g.gy, g.gm - 1, g.gd);
}

/** «۲۰ مهر ۱۴۰۵» */
export function jalaliLabel(d: Date): string {
  const j = toJ(d);
  return `${faDigits(j.jd)} ${MONTHS[j.jm - 1]} ${faDigits(j.jy)}`;
}

/** شروع هفته‌ی شمسی (شنبه) */
export function startOfWeek(d: Date): Date {
  // getDay: یکشنبه=۰ … شنبه=۶ → فاصله تا شنبه‌ی قبل
  return addDays(d, -((d.getDay() + 1) % 7));
}

/** ماه شمسی به‌صورت جدول ۷ستونه از شنبه؛ خانه‌های خالی null */
export function monthGrid(jy: number, jm: number): (Date | null)[] {
  const first = fromJ({ jy, jm, jd: 1 });
  const lead = (first.getDay() + 1) % 7;
  const days = jalaaliMonthLength(jy, jm);
  const cells: (Date | null)[] = Array.from({ length: lead }, () => null);
  for (let jd = 1; jd <= days; jd++) {
    cells.push(fromJ({ jy, jm, jd }));
  }
  while (cells.length % 7 !== 0) {
    cells.push(null);
  }
  return cells;
}

export function shiftMonth(jy: number, jm: number, delta: number): { jy: number; jm: number } {
  const index = jy * 12 + (jm - 1) + delta;
  return { jy: Math.floor(index / 12), jm: (index % 12) + 1 };
}
