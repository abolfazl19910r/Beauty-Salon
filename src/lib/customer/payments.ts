import { router, usePathname } from 'expo-router';
import { useEffect, useRef } from 'react';
import { AppState, Linking } from 'react-native';

import { loadPendingPayment, savePendingPayment } from './store';
import type { PayStart } from './types';

/**
 * پرداخت در مرورگر بیرونی (تصمیم ۲۰۲۶-۱۰-۱۱؛ نه WebView، نه Custom Tab):
 *  1. شروع روی سرور (pay یا شارژ) → checkout_url امضاشده‌ی ۱۵ دقیقه‌ای
 *  2. ذخیره‌ی tx و باز کردن مرورگر؛ اپ به صفحه‌ی «نتیجه‌ی پرداخت» همان tx می‌رود
 *  3. درگاه → سرور → mahru://payment?tx=… → همان صفحه؛ وضعیت فقط از GET payments/{tx} (پارامترهای لینک باور نمی‌شوند)
 *  4. اگر مشتری مرورگر را بست و خودش برگشت، برگشتن اپ به جلو tx ذخیره‌شده را دوباره نشان می‌دهد.
 */
export async function openCheckout(
  start: Extract<PayStart, { state: 'redirect' }>,
  purpose: 'booking' | 'wallet_charge',
  bookingId: number | null,
  now = Date.now(),
): Promise<void> {
  await savePendingPayment({
    tx: start.transaction,
    purpose,
    bookingId,
    checkoutUrl: start.checkout_url,
    checkoutExpiresAt: now + start.expires_in * 1000,
    startedAt: now,
  });
  router.replace({ pathname: '/payment', params: { tx: start.transaction } });
  await Linking.openURL(start.checkout_url);
}

/**
 * وقتی اپ از پس‌زمینه برمی‌گردد (مثلاً مشتری مرورگر را بدون دکمه‌ی برگشت بست) و پرداختی نیمه‌کاره ذخیره است،
 * صفحه‌ی نتیجه‌ی همان پرداخت باز می‌شود (اگر الان روی آن نیست). در ریشه‌ی اپ وارد‌شده صدا زده می‌شود.
 */
export function usePendingPaymentWatcher(enabled: boolean) {
  const pathname = usePathname();
  const pathRef = useRef(pathname);
  pathRef.current = pathname;

  useEffect(() => {
    if (!enabled) {
      return;
    }
    const sub = AppState.addEventListener('change', async (state) => {
      if (state !== 'active' || pathRef.current === '/payment') {
        return;
      }
      const pending = await loadPendingPayment();
      if (pending) {
        router.push({ pathname: '/payment', params: { tx: pending.tx } });
      }
    });
    return () => sub.remove();
  }, [enabled]);
}
