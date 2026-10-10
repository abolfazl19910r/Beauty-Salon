/// <reference types="jest" />
import { enDigits, faDigits, isValidPhone, normalizePhone, timeOf, toman } from '../format';

describe('format', () => {
  it('converts digits both ways', () => {
    expect(faDigits('1405/07/20')).toBe('۱۴۰۵/۰۷/۲۰');
    expect(enDigits('۰۹۱۲٣٤٥')).toBe('0912345');
  });

  it('formats toman with Persian grouping', () => {
    expect(toman(1250000)).toBe('۱٬۲۵۰٬۰۰۰ تومان');
    expect(toman(0)).toBe('۰ تومان');
    expect(toman(-5000)).toBe('−۵٬۰۰۰ تومان');
  });

  it('normalizes phone input', () => {
    expect(normalizePhone('۰۹۱۲ ۳۴۵-۶۷۸۹')).toBe('09123456789');
    expect(normalizePhone('+98 912 345 6789')).toBe('09123456789');
    expect(normalizePhone('9123456789')).toBe('09123456789');
    expect(isValidPhone('09123456789')).toBe(true);
    expect(isValidPhone('0912345678')).toBe(false);
  });

  it('extracts the time from a Jalali date-time', () => {
    expect(timeOf('1405/07/20 10:30')).toBe('۱۰:۳۰');
  });
});
