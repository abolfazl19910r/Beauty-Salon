import { faDigits, toman } from '../format';
import { jalaliLabel } from '../jalali';
import type { Gateway } from './types';

/**
 * کارمزد درگاه برای یک مبلغ (تومان) — همان فرمول SalonPaymentGateway::feeRialFor سرور:
 * سقف(درصد × مبلغ) + ثابت؛ مبلغ صفر کارمزد ندارد. وقتی بخشی از کیف پول پرداخت می‌شود، کارمزد روی باقی‌مانده است.
 */
export function gatewayFee(gateway: Pick<Gateway, 'fee_percent' | 'fee_fixed'>, amountToman: number): number {
  const base = Math.round(amountToman);
  if (base <= 0) {
    return 0;
  }
  const percent = Math.round(((gateway.fee_percent * base) / 100) * 10000) / 10000;
  return Math.max(0, Math.ceil(percent) + Math.round(gateway.fee_fixed));
}

/** سهم کیف پول در پرداخت: نه بیشتر از موجودی و نه بیشتر از مبلغ */
export function walletShare(useWallet: boolean, balance: number, amount: number): number {
  return useWallet ? Math.max(0, Math.min(balance, amount)) : 0;
}

/** «۲۰٪ تخفیف» / «۵۰٬۰۰۰ تومان تخفیف» */
export function discountLabel(type: 'fixed' | 'percentage', amount: number): string {
  return type === 'percentage' ? `${faDigits(Math.round(amount))}٪ تخفیف` : `${toman(amount)} تخفیف`;
}

const WEEKDAYS = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

/** «شنبه ۲۶ مهر ۱۴۰۵، ساعت ۱۰:۳۰» از زمان ISO سرور (ساعت گوشی؛ Intl فارسی در Hermes کامل نیست) */
export function bookingTimeLabel(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return '';
  }
  const hm = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
  return `${WEEKDAYS[d.getDay()]} ${jalaliLabel(d)}، ساعت ${faDigits(hm)}`;
}
