import { systemPathFor } from '@/lib/customer/incomingLink';

/**
 * expo-router: هر لینکی که سیستم به اپ می‌دهد (App Links ‏https سالن، mahru://) پیش از مسیریابی از اینجا می‌گذرد (بسته‌ی ۵).
 */
export function redirectSystemPath({ path }: { path: string; initial: boolean }): string {
  try {
    return systemPathFor(path);
  } catch {
    return '/';
  }
}
