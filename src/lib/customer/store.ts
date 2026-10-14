import AsyncStorage from '@react-native-async-storage/async-storage';

import type { SalonCard } from './types';

/**
 * حافظه‌ی محلی اپ مشتری (جدا از توکن که در SecureStore است):
 *  - سالن انتخاب‌شده: اپ فقط یک سالن نگه می‌دارد (تصمیم ۲۰۲۶-۱۰-۱۱) و بعد از «خروج» همان سالن می‌ماند (تصمیم ۲۰۲۶-۱۰-۱۴)؛
 *    فقط «ورود به سالن دیگر» پاکش می‌کند.
 *  - پرداخت در جریان: وقتی صفحه‌ی بانک در مرورگر بیرونی باز است؛ اگر مشتری بدون لینک برگشت به اپ بیاید، از روی این
 *    وضعیت قطعی از سرور پرسیده می‌شود.
 */
const SALON_KEY = 'mahru.customer.salon';
const PENDING_KEY = 'mahru.customer.pendingPayment';

export type PendingPayment = {
  tx: string;
  purpose: 'booking' | 'wallet_charge';
  bookingId: number | null;
  checkoutUrl: string;
  /** زمان انقضای لینک امضاشده‌ی checkout (میلی‌ثانیه) */
  checkoutExpiresAt: number;
  startedAt: number;
};

/** بعد از این مدت، پرداخت نیمه‌کاره دیگر خودکار پیگیری نمی‌شود (مهلت پرداخت نوبت ۳۰ دقیقه است) */
export const PENDING_TTL_MS = 60 * 60 * 1000;

async function readJson<T>(key: string): Promise<T | null> {
  const raw = await AsyncStorage.getItem(key);
  if (!raw) {
    return null;
  }
  try {
    return JSON.parse(raw) as T;
  } catch {
    return null;
  }
}

export const loadSalon = () => readJson<SalonCard>(SALON_KEY);

export async function saveSalon(salon: SalonCard | null): Promise<void> {
  if (salon) {
    await AsyncStorage.setItem(SALON_KEY, JSON.stringify(salon));
  } else {
    await AsyncStorage.removeItem(SALON_KEY);
  }
}

export async function loadPendingPayment(now = Date.now()): Promise<PendingPayment | null> {
  const pending = await readJson<PendingPayment>(PENDING_KEY);
  if (pending && now - pending.startedAt > PENDING_TTL_MS) {
    await AsyncStorage.removeItem(PENDING_KEY);
    return null;
  }
  return pending;
}

export async function savePendingPayment(pending: PendingPayment | null): Promise<void> {
  if (pending) {
    await AsyncStorage.setItem(PENDING_KEY, JSON.stringify(pending));
  } else {
    await AsyncStorage.removeItem(PENDING_KEY);
  }
}

/** فقط اگر همان تراکنش است پاک شود (تراکنش تازه‌تر را پاک نکند) */
export async function clearPendingPayment(tx: string): Promise<void> {
  const pending = await readJson<PendingPayment>(PENDING_KEY);
  if (pending?.tx === tx) {
    await AsyncStorage.removeItem(PENDING_KEY);
  }
}
