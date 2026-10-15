/// <reference types="jest" />
/**
 * آزمون دود: صفحه‌های واقعی src/app با expo-router روی سرور ساختگی (fetch). روی گوشی امتحان نمی‌شود، ولی جریان
 * ورود، نمایش «امروز» و خروج اجباری با ۴۰۱ را از ابتدا تا انتها می‌گذراند.
 */
import { act, fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';
import { Alert } from 'react-native';

// اولین رندر expo-router روی ویندوز (تبدیل سرد فایل‌ها) از ۵ ثانیه‌ی پیش‌فرض Jest بیشتر طول می‌کشد (گزارش ۲۰۲۶-۱۰-۱۰)
jest.setTimeout(60_000);

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
jest.mock('expo-font', () => ({ ...jest.requireActual('expo-font'), useFonts: () => [true] }));
jest.mock('expo-device', () => ({ modelName: 'Galaxy A17' }));
jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(async () => ({ granted: true })),
  requestMediaLibraryPermissionsAsync: jest.fn(async () => ({ granted: true })),
  launchCameraAsync: jest.fn(async () => ({ canceled: false, assets: [{ uri: 'file:///tmp/after.jpg', mimeType: 'image/jpeg' }] })),
  launchImageLibraryAsync: jest.fn(async () => ({ canceled: true, assets: null })),
}));

const mockSecure = new Map<string, string>();
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async (k: string) => mockSecure.get(k) ?? null),
  setItemAsync: jest.fn(async (k: string, v: string) => void mockSecure.set(k, v)),
  deleteItemAsync: jest.fn(async (k: string) => void mockSecure.delete(k)),
}));

const ACCOUNT = {
  user: { id: 7, name: 'سارا', phone: '09120000007', type: 'staff' },
  salon: { id: 1, slug: 'rasta', name: 'سالن راستا', logo_url: null, public_url: 'http://x/s/rasta' },
  specialist: { id: 3, name: 'سارا احمدی' },
};

const BOOKING = {
  id: 41, status: 'pending', status_label: 'در انتظار تأیید', payment_status: 'paid', booking_time: '2026-10-11T10:00:00+03:30',
  booking_time_jalali: '1405/07/19 10:00', duration_minutes: 60, service: { id: 1, name: 'کوتاهی مو', price: 500000 },
  customer: { id: 9, name: 'مریم', phone: '09121111111' }, prepayment_amount: 100000, discount_amount: 0, remaining_amount: 400000,
  source: null, notes: null, cancellation_reason: null, cancelled_by: null, actions: { confirm: true, cancel: true, complete: false },
};

type Route = (url: string, init: RequestInit) => [number, unknown];
let routes: Route;
const calls: { url: string; body: unknown; auth?: string }[] = [];

beforeEach(async () => {
  mockSecure.clear();
  calls.length = 0;
  await require('@react-native-async-storage/async-storage').clear();
  globalThis.fetch = jest.fn(async (url: string, init: RequestInit) => {
    const headers = init.headers as Record<string, string>;
    calls.push({ url, body: init.body instanceof FormData ? init.body : init.body ? JSON.parse(String(init.body)) : undefined, auth: headers.Authorization });
    const [status, body] = routes(url, init);
    return { ok: status < 300, status, json: async () => body } as Response;
  }) as unknown as typeof fetch;
});

const ok = (data: unknown, meta?: unknown) => ({ success: true, data, meta });

it('logs in with password then code and lands on today', async () => {
  routes = (url) => {
    if (url.endsWith('/staff/login')) return [200, ok({ challenge: 'CH1', expires_in: 600, code_expires_in: 120, resend_after: 60, phone_hint: '0912***0007' })];
    if (url.endsWith('/staff/login/verify')) return [201, ok({ token: '5|abc', token_type: 'Bearer', token_id: 5, expires_at: '2027-01-08T00:00:00Z', account: ACCOUNT })];
    if (url.includes('/staff/today')) return [200, ok({ date: '2026-10-11', date_jalali: '1405/07/19', date_label: 'یکشنبه، ۱۹ مهر ۱۴۰۵', summary: { count: 1, pending_count: 1, revenue: 100000 }, bookings: [BOOKING] })];
    if (url.includes('/staff/notifications')) return [200, ok([], { current_page: 1, last_page: 1, total: 0, per_page: 20, unread_count: 0 })];
    if (url.endsWith('/push/device')) return [200, ok({ registered: true, provider: 'fcm', server_enabled: true, last_receipt_at: null, disabled_reason: null })];
    return [404, { success: false, error: { code: 'not_found', message: 'x' } }];
  };

  renderRouter('./src/app', { initialUrl: '/' });

  fireEvent.changeText(await screen.findByLabelText('شماره موبایل'), '۰۹۱۲۰۰۰۰۰۰۷');
  fireEvent.changeText(screen.getByLabelText('رمز عبور'), 'secret123');
  fireEvent.press(screen.getByText('ادامه'));

  await screen.findByText(/کد ۶ رقمی/);
  expect(calls[0].body).toEqual({ phone: '09120000007', password: 'secret123' });
  expect(calls[0].auth).toBeUndefined();

  fireEvent.changeText(screen.getByLabelText('کد تأیید'), '۱۲۳۴۵۶');
  fireEvent.press(screen.getByText('ورود'));

  await screen.findByText('مریم');
  expect(calls.find((c) => c.url.endsWith('/verify'))?.body).toEqual({ challenge: 'CH1', code: '123456', device_name: 'Galaxy A17' });
  expect(mockSecure.get('mahru.token')).toBe('5|abc');
  expect(calls.find((c) => c.url.includes('/staff/today'))?.auth).toBe('Bearer 5|abc');
  expect(screen.getAllByText('در انتظار تأیید')).toHaveLength(2); // کارت خلاصه + نشان نوبت
  expect(screen.getByText('سلام سارا احمدی')).toBeTruthy();

  // پوش (بسته‌ی ۴): بعد از ورود گوشی با توکن FCM برای همین نشست ثبت می‌شود
  await waitFor(() => expect(calls.find((c) => c.url.endsWith('/push/device'))?.body).toMatchObject({ provider: 'fcm', token: 'fcm-test-token-0001', manufacturer: null }));
  expect(calls.find((c) => c.url.endsWith('/push/device'))?.auth).toBe('Bearer 5|abc');
});

it('tapping a push opens its booking and sends an opened receipt; a push received in the app is acknowledged', async () => {
  const { mockPushResponse, mockPushReceived } = require('expo-notifications');
  mockSecure.set('mahru.token', '5|abc');
  await require('@react-native-async-storage/async-storage').setItem('mahru.account', JSON.stringify(ACCOUNT));
  routes = (url) => {
    if (url.includes('/staff/bookings/41/course')) return [404, { success: false, error: { code: 'not_found', message: 'x' } }];
    if (url.includes('/staff/bookings/41')) return [200, ok(BOOKING)];
    if (url.includes('/staff/today')) return [200, ok({ date: '2026-10-11', date_jalali: '1405/07/19', date_label: 'x', summary: { count: 0, pending_count: 0, revenue: 0 }, bookings: [] })];
    if (url.includes('/staff/notifications')) return [200, ok([], { current_page: 1, last_page: 1, total: 0, per_page: 20, unread_count: 0 })];
    if (url.endsWith('/push/device') || url.endsWith('/push/receipts')) return [200, ok({ acknowledged: 1 })];
    return [404, { success: false, error: { code: 'not_found', message: 'x' } }];
  };

  renderRouter('./src/app', { initialUrl: '/' });
  await waitFor(() => expect(calls.some((c) => c.url.endsWith('/push/device'))).toBe(true));

  const id = '9b2f7c1e-1111-4a2b-8c3d-0123456789ab';
  await act(async () => mockPushReceived({ message_id: id, type: 'booking.created.specialist', booking_id: '41' }));
  await waitFor(() => expect(calls.find((c) => c.url.endsWith('/push/receipts'))?.body).toEqual({ ids: [id], opened: false }));

  await act(async () => mockPushResponse({ message_id: id, type: 'booking.created.specialist', booking_id: '41' }));
  expect(await screen.findByText('مریم')).toBeTruthy();
  await waitFor(() => expect(calls.filter((c) => c.url.endsWith('/push/receipts')).pop()?.body).toEqual({ ids: [id], opened: true }));
});

it('a 401 on an authenticated call clears the session and returns to login', async () => {
  mockSecure.set('mahru.token', 'old');
  await require('@react-native-async-storage/async-storage').setItem('mahru.account', JSON.stringify(ACCOUNT));
  routes = () => [401, { success: false, error: { code: 'unauthenticated', message: 'ابتدا وارد حساب خود شوید.' } }];

  renderRouter('./src/app', { initialUrl: '/' });

  await screen.findByText('ورود متخصص‌های سالن');
  await waitFor(() => expect(mockSecure.has('mahru.token')).toBe(false));
});

it('a suspended salon shows its own screen without logging out', async () => {
  mockSecure.set('mahru.token', 'tok');
  await require('@react-native-async-storage/async-storage').setItem('mahru.account', JSON.stringify(ACCOUNT));
  routes = () => [403, { success: false, error: { code: 'salon_inactive', message: 'اشتراک این سالن پایان یافته یا غیرفعال شده است.' } }];

  renderRouter('./src/app', { initialUrl: '/' });

  await screen.findByText('سالن فعلاً غیرفعال است');
  expect(mockSecure.get('mahru.token')).toBe('tok');
  await act(async () => undefined);
});

it('a treatment-course session shows its label and saves the session note', async () => {
  mockSecure.set('mahru.token', '5|abc');
  await require('@react-native-async-storage/async-storage').setItem('mahru.account', JSON.stringify(ACCOUNT));
  const course = { id: 12, name: 'لیزر ۶ جلسه', session_number: 2, sessions_total: 6, label: 'جلسه‌ی ۲ از ۶', photo_consent: true };
  routes = (url) => {
    if (url.includes('/staff/bookings/41/session-note')) return [200, ok({ note: 'شدت ۱۸', visible_to_customer: true })];
    if (url.includes('/staff/bookings/41/course')) {
      return [200, ok({ id: 12, name: 'لیزر ۶ جلسه', status: 'active', status_label: 'فعال', sessions_total: 6, sessions_used: 1, photo_consent: true, sessions: [
        { booking_id: 40, session_number: 1, label: 'جلسه‌ی ۱ از ۶', status: 'completed', burned: false, booking_time_jalali: '1405/06/10 10:00', specialist: { id: 3, name: 'سارا احمدی' }, note: 'پوست حساس', note_visible_to_customer: false, photos: [] },
        { booking_id: 41, session_number: 2, label: 'جلسه‌ی ۲ از ۶', status: 'confirmed', burned: false, booking_time_jalali: '1405/07/19 10:00', specialist: { id: 3, name: 'سارا احمدی' }, note: null, note_visible_to_customer: null, photos: [] },
      ] })];
    }
    if (url.includes('/staff/bookings/41')) return [200, ok({ ...BOOKING, status: 'confirmed', prepayment_amount: 0, remaining_amount: 0, course, actions: { confirm: false, cancel: true, complete: true } })];
    if (url.includes('/staff/today')) return [200, ok({ date: '2026-10-11', date_jalali: '1405/07/19', date_label: 'x', summary: { count: 0, pending_count: 0, revenue: 0 }, bookings: [] })];
    if (url.includes('/staff/notifications')) return [200, ok([], { current_page: 1, last_page: 1, total: 0, per_page: 20, unread_count: 0 })];
    return [404, { success: false, error: { code: 'not_found', message: 'x' } }];
  };

  renderRouter('./src/app', { initialUrl: '/booking/41' });

  expect(await screen.findByText('جلسه‌ی ۲ از ۶')).toBeTruthy();
  expect(await screen.findByText('پوست حساس')).toBeTruthy();
  fireEvent.changeText(screen.getByLabelText('مثلاً شدت، ناحیه، واکنش پوست'), 'شدت ۱۸');
  fireEvent(screen.getByLabelText('نمایش یادداشت به مشتری'), 'valueChange', true);
  fireEvent.press(screen.getByText('ذخیره‌ی یادداشت'));
  await waitFor(() => expect(calls.find((c) => c.url.includes('/session-note'))?.body).toEqual({ note: 'شدت ۱۸', visible_to_customer: true }));
});

it('takes an after photo for a course session with the camera and uploads it', async () => {
  mockSecure.set('mahru.token', '5|abc');
  await require('@react-native-async-storage/async-storage').setItem('mahru.account', JSON.stringify(ACCOUNT));
  const course = { id: 12, name: 'لیزر ۶ جلسه', session_number: 2, sessions_total: 6, label: 'جلسه‌ی ۲ از ۶', photo_consent: true };
  let photos: { id: number; kind: string; url: string }[] = [];
  routes = (url, init) => {
    if (url.includes('/staff/bookings/41/photos') && init.method === 'POST') {
      photos = [{ id: 7, kind: 'after', url: 'http://x/api/v1/staff/course-photos/7' }];
      return [201, ok(photos[0])];
    }
    if (url.includes('/staff/bookings/41/course')) {
      return [200, ok({ id: 12, name: 'لیزر ۶ جلسه', status: 'active', status_label: 'فعال', sessions_total: 6, sessions_used: 1, photo_consent: true, sessions: [
        { booking_id: 41, session_number: 2, label: 'جلسه‌ی ۲ از ۶', status: 'confirmed', burned: false, booking_time_jalali: '1405/07/19 10:00', specialist: { id: 3, name: 'سارا احمدی' }, note: null, note_visible_to_customer: null, photos },
      ] })];
    }
    if (url.includes('/staff/bookings/41')) return [200, ok({ ...BOOKING, status: 'confirmed', course, actions: { confirm: false, cancel: true, complete: true } })];
    if (url.includes('/staff/notifications')) return [200, ok([], { current_page: 1, last_page: 1, total: 0, per_page: 20, unread_count: 0 })];
    return [404, { success: false, error: { code: 'not_found', message: 'x' } }];
  };
  jest.spyOn(Alert, 'alert').mockImplementation((_t, _m, buttons) => buttons?.find((b) => b.text === 'دوربین')?.onPress?.());
  // FormData در jest همان Node است (فایل RN را رشته می‌کند)؛ append را می‌پاییم
  const appended: [string, unknown][] = [];
  const append = jest.spyOn(FormData.prototype, 'append').mockImplementation(function (this: FormData, k: string, v: unknown) {
    appended.push([k, v]);
  } as any);

  renderRouter('./src/app', { initialUrl: '/booking/41' });

  expect(await screen.findByText(/عکس‌های این جلسه/)).toBeTruthy();
  fireEvent.press(screen.getByText('عکس بعد'));

  await waitFor(() => expect(calls.some((c) => c.url.includes('/staff/bookings/41/photos'))).toBe(true));
  const upload = calls.find((c) => c.url.includes('/photos'))!;
  expect(upload.body).toBeInstanceOf(FormData);
  expect(upload.auth).toBe('Bearer 5|abc');
  expect(appended.find(([k]) => k === 'kind')?.[1]).toBe('after');
  expect(appended.find(([k]) => k === 'photo')?.[1]).toMatchObject({ uri: 'file:///tmp/after.jpg', type: 'image/jpeg', name: 'photo.jpg' });
  append.mockRestore();
});

it('marks a past booking as no-show', async () => {
  mockSecure.set('mahru.token', '5|abc');
  await require('@react-native-async-storage/async-storage').setItem('mahru.account', JSON.stringify(ACCOUNT));
  let status = 'confirmed';
  routes = (url, init) => {
    if (url.includes('/staff/bookings/41/no-show') && init.method === 'POST') {
      status = 'no_show';
    }
    if (url.includes('/staff/bookings/41')) {
      return [200, ok({ ...BOOKING, status, status_label: status === 'no_show' ? 'نیامد' : 'تأیید شده', actions: { confirm: false, cancel: status !== 'no_show', complete: status !== 'no_show', no_show: status !== 'no_show' } })];
    }
    if (url.includes('/staff/notifications')) return [200, ok([], { current_page: 1, last_page: 1, total: 0, per_page: 20, unread_count: 0 })];
    return [404, { success: false, error: { code: 'not_found', message: 'x' } }];
  };
  jest.spyOn(Alert, 'alert').mockImplementation((_t, _m, buttons) => buttons?.find((b) => b.text === 'بله')?.onPress?.());

  renderRouter('./src/app', { initialUrl: '/booking/41' });

  fireEvent.press(await screen.findByText('مشتری نیامد'));
  await waitFor(() => expect(calls.some((c) => c.url.endsWith('/staff/bookings/41/no-show'))).toBe(true));
  expect(await screen.findByText('نیامد')).toBeTruthy();
});
