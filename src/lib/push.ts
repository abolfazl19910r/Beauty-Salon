/**
 * پوش اپ‌ها (بسته‌ی ۴) — منطق خالص (بدون ماژول بومی) تا با Jest سنجیده شود. بخش بومی در pushRuntime.ts و pushTask.ts.
 *
 * شکل پیام سرور (FcmClient لاراول): data-only با اولویت بالا؛ کلیدهای title/message/channelId را خود expo-notifications برای ساختن
 * اعلان در پس‌زمینه می‌خواند و «body» یک JSON است که در اپ همان content.data اعلان و dataString در task پس‌زمینه می‌شود:
 *   {message_id, type, urgency, booking_id?, course_id?, withdrawal_id?, leave_id?, review_id?, salon_id?, test?}
 */

export type PushData = {
  messageId: string | null;
  type: string | null;
  bookingId: number | null;
  courseId: number | null;
  withdrawalId: number | null;
  leaveId: number | null;
  test: boolean;
};

export const CHANNELS = {
  bookings: { id: 'bookings', name: 'نوبت‌ها و یادآوری‌ها' },
  general: { id: 'general', name: 'باشگاه، تخفیف و اطلاع‌رسانی' },
} as const;

export const BACKGROUND_TASK = 'mahru-push-receipt';

const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

function int(value: unknown): number | null {
  const n = typeof value === 'number' ? value : typeof value === 'string' && /^\d+$/.test(value) ? Number(value) : NaN;
  return Number.isFinite(n) && n > 0 ? n : null;
}

function record(value: unknown): Record<string, unknown> {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return value as Record<string, unknown>;
  }
  if (typeof value === 'string' && value.trim().startsWith('{')) {
    try {
      return record(JSON.parse(value));
    } catch {
      return {};
    }
  }
  return {};
}

/**
 * داده‌ی پیام از هر شکلی که expo بدهد: content.data اعلان (همان JSON کلید body)، یا payload task پس‌زمینه
 * ({data: {dataString, ...کلیدهای تخت}}). کلیدهای تخت پشتیبان dataString هستند.
 */
export function parsePushData(raw: unknown): PushData {
  const outer = record(raw);
  const inner = record(outer.data);
  const merged: Record<string, unknown> = {
    ...inner,
    ...record(inner.body),
    ...record(inner.dataString),
    ...outer,
    ...record(outer.body),
    ...record(outer.dataString),
  };

  const messageId = typeof merged.message_id === 'string' && UUID.test(merged.message_id) ? merged.message_id : null;

  return {
    messageId,
    type: typeof merged.type === 'string' ? merged.type : null,
    bookingId: int(merged.booking_id),
    courseId: int(merged.course_id),
    withdrawalId: int(merged.withdrawal_id),
    leaveId: int(merged.leave_id),
    test: merged.test === 1 || merged.test === '1' || merged.test === true,
  };
}

/** صفحه‌ای که لمس اعلان باز می‌کند (مسیرهای expo-router هر اپ) */
export function routeFor(variant: 'staff' | 'customer', data: PushData): string {
  if (data.test) {
    return '/push';
  }
  if (data.bookingId) {
    return `/booking/${data.bookingId}`;
  }
  if (variant === 'customer' && data.courseId) {
    return `/course/${data.courseId}`;
  }
  if (variant === 'staff' && data.leaveId) {
    return '/leaves';
  }
  if (variant === 'staff' && data.withdrawalId) {
    return '/transactions';
  }
  return '/notifications';
}

/** صف رسیدهای نفرستاده (بی‌اینترنت): تکراری نه، حداکثر ۵۰ (سقف API) */
export function enqueueReceipt(queue: string[], id: string, max = 50): string[] {
  if (!UUID.test(id) || queue.includes(id)) {
    return queue;
  }
  return [...queue, id].slice(-max);
}

export type DeviceGuide = { maker: 'xiaomi' | 'samsung' | 'other'; title: string; steps: string[] };

/**
 * راهنمای دریافت اعلان هر سازنده — یافته‌های بسته‌ی ۰: شیائومی بدون Autostart بعد از ریستارت اتصال را از دست می‌دهد و تغییر
 * Autostart خود اپ را می‌بندد؛ سامسونگ اپ کم‌استفاده را به «Deep sleeping» می‌برد و آنجا هیچ پوشی نمی‌رسد.
 */
export function deviceGuide(manufacturer: string | null | undefined): DeviceGuide {
  const m = (manufacturer ?? '').toLowerCase();
  if (/xiaomi|redmi|poco/.test(m)) {
    return {
      maker: 'xiaomi',
      title: 'گوشی شیائومی (MIUI / HyperOS)',
      steps: [
        'تنظیمات ← برنامه‌ها ← مدیریت برنامه‌ها ← ماهرو ← «شروع خودکار» (Autostart) را روشن کنید.',
        'همان صفحه ← «صرفه‌جویی باتری» ← «بدون محدودیت» (No restrictions).',
        'بعد از تغییر این تنظیم‌ها یک بار اپ را باز کنید و «اعلان آزمایشی» را بزنید.',
      ],
    };
  }
  if (/samsung/.test(m)) {
    return {
      maker: 'samsung',
      title: 'گوشی سامسونگ (One UI)',
      steps: [
        'تنظیمات ← مراقبت از باتری و دستگاه ← باتری ← «محدودیت‌های استفاده در پس‌زمینه».',
        'اگر ماهرو در «برنامه‌های در خواب عمیق» (Deep sleeping apps) هست، آن را بیرون بیاورید؛ آنجا هیچ اعلانی نمی‌رسد.',
        'بهتر: ماهرو را به «برنامه‌هایی که هرگز به خواب نمی‌روند» اضافه کنید.',
      ],
    };
  }
  return {
    maker: 'other',
    title: 'تنظیم باتری',
    steps: [
      'تنظیمات ← برنامه‌ها ← ماهرو ← باتری ← «بدون محدودیت» یا «بهینه نشود».',
      'اگر گوشی «شروع خودکار» دارد، برای ماهرو روشنش کنید.',
    ],
  };
}
