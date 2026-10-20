import { useQuery } from '@tanstack/react-query';
import Constants from 'expo-constants';

import { APP_VARIANT } from './config';
import { useApi } from './session';

/**
 * نسخه‌ی تازه‌ی اپ (بسته‌ی ۵): اپ‌ها در مارکت نیستند و APK از صفحه‌ی «دانلود اپ ماهرو» نصب می‌شود؛ پس اپ خودش می‌پرسد
 * (GET /api/v1/app/latest، بدون ورود). update_required = نسخه‌ی اجباری تازه‌تر از نسخه‌ی گوشی منتشر شده است.
 */
export type AppUpdate = {
  latest: {
    version_name: string;
    version_code: number;
    notes: string | null;
    size_bytes: number;
    download_url: string;
    page_url: string;
  } | null;
  update_available: boolean;
  update_required: boolean;
};

/** versionCode اندروید همین build (app.config.ts) */
export const VERSION_CODE = Number(Constants.expoConfig?.android?.versionCode ?? 0);

export function useAppUpdate() {
  const api = useApi();
  return useQuery({
    queryKey: ['app-update', APP_VARIANT, VERSION_CODE],
    queryFn: async () =>
      (await api.request<AppUpdate>('GET', '/app/latest', { auth: false, query: { variant: APP_VARIANT, version_code: VERSION_CODE } })).data,
    // روزی دو بار کافی است؛ خطا (سرور قدیمی بدون این مسیر، شبکه) فقط یعنی پیامی نشان داده نمی‌شود
    staleTime: 12 * 60 * 60 * 1000,
    retry: false,
    enabled: VERSION_CODE > 0,
  });
}
