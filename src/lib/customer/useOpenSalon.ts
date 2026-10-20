import { router } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { ApiError } from '../api';
import { ALLOW_SERVER_OVERRIDE } from '../config';
import { useSession } from '../session';
import type { ParsedSalon } from './salonLink';
import { useCustomerSalon } from './salon';
import { savePendingSpecialist } from './store';
import type { SalonCard } from './types';

export const SALON_NOT_FOUND = 'سالنی با این کد پیدا نشد یا فعلاً فعال نیست. لینک یا کد را از خود سالن بپرسید.';

/** نسخه‌ی آزمایشی: QR سرور لوکال دیگری را نشان می‌دهد → بپرس آدرس سرور عوض شود؟ */
function askServerSwitch(origin: string, current: string): Promise<boolean> {
  return new Promise((resolve) => {
    Alert.alert('سرور دیگر', `این لینک مال ${origin} است ولی اپ به ${current} وصل است. سرور اپ عوض شود؟`, [
      { text: 'نه', style: 'cancel', onPress: () => resolve(false) },
      { text: 'بله', onPress: () => resolve(true) },
    ]);
  });
}

/**
 * لینک/QR/کد اعتبارسنجی‌شده → کارت سالن از سرور (GET customer/salons/{slug}، بدون ورود) → ذخیره و رفتن به ورود همان سالن.
 * سالن ناموجود/معلق/منقضی از سرور ۴۰۴ می‌گیرد (عمداً بدون تفکیک).
 */
export function useOpenSalon() {
  const { api, serverUrl, setServerUrl } = useSession();
  const { setSalon } = useCustomerSalon();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const open = async (parsed: ParsedSalon): Promise<SalonCard | null> => {
    setBusy(true);
    setError(null);
    try {
      if (ALLOW_SERVER_OVERRIDE && parsed.origin && parsed.origin.replace(/\/+$/, '') !== serverUrl.replace(/\/+$/, '')) {
        if (await askServerSwitch(parsed.origin, serverUrl)) {
          await setServerUrl(parsed.origin);
        }
      }
      const { data } = await api.request<SalonCard>('GET', `/customer/salons/${encodeURIComponent(parsed.slug)}`, { auth: false });
      await setSalon(data);
      if (parsed.specialistId) {
        // QR متخصص: بعد از ورود، خانه صفحه‌ی همان متخصص را باز می‌کند
        await savePendingSpecialist(data.slug, parsed.specialistId);
      }
      router.replace('/login');
      return data;
    } catch (e) {
      setError(e instanceof ApiError && e.code === 'not_found' ? new ApiError('not_found', SALON_NOT_FOUND, 404) : e);
      return null;
    } finally {
      setBusy(false);
    }
  };

  return { open, busy, error, setError };
}
