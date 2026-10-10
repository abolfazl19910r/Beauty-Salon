const FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

/** ارقام لاتین → فارسی (برای نمایش) */
export function faDigits(value: string | number): string {
  return String(value).replace(/[0-9]/g, (d) => FA_DIGITS[Number(d)]);
}

/** ارقام فارسی/عربی → لاتین (ورودی کاربر پیش از ارسال به سرور) */
export function enDigits(value: string): string {
  return value
    .replace(/[۰-۹]/g, (d) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)))
    .replace(/[٠-٩]/g, (d) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)));
}

/** ۱۲۵۰۰۰۰ → «۱٬۲۵۰٬۰۰۰ تومان» (بدون وابستگی به Intl که Hermes برای fa کامل ندارد) */
export function toman(amount: number | null | undefined): string {
  const n = Math.round(Number(amount ?? 0));
  const grouped = Math.abs(n)
    .toString()
    .replace(/\B(?=(\d{3})+(?!\d))/g, '٬');

  return `${n < 0 ? '−' : ''}${faDigits(grouped)} تومان`;
}

/** فقط ساعت از رشته‌ی شمسی سرور: «1405/07/20 10:30» → «۱۰:۳۰» */
export function timeOf(jalaliDateTime: string): string {
  return faDigits(jalaliDateTime.split(' ')[1] ?? '');
}

/** شماره‌ی موبایل ورودی: ارقام فارسی، فاصله و خط تیره پاک؛ 9xxxxxxxxx → 09xxxxxxxxx */
export function normalizePhone(input: string): string {
  const digits = enDigits(input).replace(/\D/g, '');
  if (digits.startsWith('98') && digits.length === 12) {
    return `0${digits.slice(2)}`;
  }
  if (digits.length === 10 && digits.startsWith('9')) {
    return `0${digits}`;
  }
  return digits;
}

export function isValidPhone(phone: string): boolean {
  return /^09[0-9]{9}$/.test(phone);
}
