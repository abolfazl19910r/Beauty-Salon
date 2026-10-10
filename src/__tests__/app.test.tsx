/// <reference types="jest" />
/**
 * آزمون دود: صفحه‌های واقعی src/app با expo-router روی سرور ساختگی (fetch). روی گوشی امتحان نمی‌شود، ولی جریان
 * ورود، نمایش «امروز» و خروج اجباری با ۴۰۱ را از ابتدا تا انتها می‌گذراند.
 */
import { act, fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';

// اولین رندر expo-router روی ویندوز (تبدیل سرد فایل‌ها) از ۵ ثانیه‌ی پیش‌فرض Jest بیشتر طول می‌کشد (گزارش ۲۰۲۶-۱۰-۱۰)
jest.setTimeout(60_000);

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
jest.mock('expo-font', () => ({ ...jest.requireActual('expo-font'), useFonts: () => [true] }));
jest.mock('expo-device', () => ({ modelName: 'Galaxy A17' }));

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
    calls.push({ url, body: init.body ? JSON.parse(String(init.body)) : undefined, auth: headers.Authorization });
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
