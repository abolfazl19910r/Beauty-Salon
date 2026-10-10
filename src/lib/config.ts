import Constants from 'expo-constants';

type Extra = { variant?: 'staff' | 'customer'; allowServerOverride?: boolean; defaultApiUrl?: string };

const extra = (Constants.expoConfig?.extra ?? {}) as Extra;

export const APP_VARIANT = extra.variant ?? 'staff';
export const APP_VERSION = Constants.expoConfig?.version ?? '0.0.0';
/** نسخه‌های آزمایشی: آدرس سرور از صفحه‌ی ورود قابل تغییر است (سرور XAMPP روی شبکه‌ی محلی) */
export const ALLOW_SERVER_OVERRIDE = extra.allowServerOverride ?? __DEV__;
export const DEFAULT_API_URL = extra.defaultApiUrl ?? 'https://mahru.ir';
