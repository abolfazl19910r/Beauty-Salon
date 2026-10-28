/// <reference types="jest" />
/**
 * آزمون دود اپ «ماهرو» (مشتری): صفحه‌های واقعی src/customer با expo-router روی سرور ساختگی (fetch).
 * انتخاب سالن با کد ← ثبت‌نام ← خانه؛ رزرو کامل تا صفحه‌ی بانک در مرورگر ← نتیجه از سرور؛ لینک برگشت که به state خودش
 * اعتماد نمی‌شود؛ ۴۰۱ ← ورود همان سالن (سالن می‌ماند)؛ سالن معلق.
 */
import { act, fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';
import { Alert, Linking } from 'react-native';

jest.setTimeout(60_000);

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
jest.mock('expo-font', () => ({ ...jest.requireActual('expo-font'), useFonts: () => [true] }));
jest.mock('expo-device', () => ({ modelName: 'Redmi Note 8' }));
jest.mock('expo-crypto', () => ({ randomUUID: () => '11111111-2222-4333-8444-555555555555' }));
jest.mock('expo-clipboard', () => ({ getStringAsync: jest.fn(async () => ''), setStringAsync: jest.fn(async () => true) }));
// دوربین: پیش‌فرض بی‌اجازه؛ آزمون اسکن اجازه را روشن می‌کند و onBarcodeScanned را خودش صدا می‌زند
const mockCamera: { granted: boolean; props: any } = { granted: false, props: null };
jest.mock('expo-camera', () => ({
  CameraView: (props: any) => {
    mockCamera.props = props;
    return null;
  },
  useCameraPermissions: () => [{ granted: mockCamera.granted, canAskAgain: true }, jest.fn()],
}));

const mockSecure = new Map<string, string>();
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async (k: string) => mockSecure.get(k) ?? null),
  setItemAsync: jest.fn(async (k: string, v: string) => void mockSecure.set(k, v)),
  deleteItemAsync: jest.fn(async (k: string) => void mockSecure.delete(k)),
}));

const storage = () => require('@react-native-async-storage/async-storage');

const SALON = { id: 1, slug: 'rasta', name: 'سالن راستا', tagline: 'زیبایی با آرامش', logo_url: null, public_url: 'http://x/s/rasta' };
const ACCOUNT = {
  user: { id: 9, name: 'مریم رضایی', phone: '09121111111', type: 'customer' },
  salon: { id: 1, slug: 'rasta', name: 'سالن راستا', logo_url: null, public_url: 'http://x/s/rasta' },
};
const TOKEN = { token: '7|cus', token_type: 'Bearer', token_id: 7, expires_at: '2027-01-12T00:00:00Z', account: ACCOUNT };
const CHALLENGE = { challenge: 'CH9', expires_in: 600, code_expires_in: 120, resend_after: 60, phone_hint: '0912***1111' };

const BOOKING = {
  id: 41, status: 'pending_payment', status_label: 'در انتظار پرداخت', payment_status: 'unpaid',
  booking_time: '2026-10-20T10:00:00+03:30', booking_time_jalali: '1405/07/28 10:00', booking_time_label: 'دوشنبه ۲۸ مهر، 10:00',
  duration_minutes: 60, service: { id: 3, name: 'کوتاهی مو', price: 500000 }, specialist: { id: 5, name: 'سارا', photo_url: null },
  prepayment_amount: 150000, discount_code: null, discount_amount: 0, remaining_amount: 350000, paid_at: null,
  pay_before: null, cancellation_reason: null, cancelled_by: null, rating: null, review: null,
  actions: { pay: true, cancel: true, reschedule: false, rate: false },
};
const HOME = { salon: SALON, upcoming_bookings: [BOOKING], awaiting_payment_count: 1, loyalty_points: 40, wallet_balance: 0, unread_notifications: 0 };
const TERMS = {
  provider: { name: 'سالن راستا', address: 'تهران، خیابان آزادی، پلاک ۵', phone: '02144556677', platform: 'ماهرو' },
  cancellation: ['لغو رایگان تا ۲۴ ساعت پیش از زمان نوبت.', 'نوبت پرداخت‌نشده بعد از ۳۰ دقیقه لغو می‌شود.'],
  complaints: 'شکایت: پروفایل ← شکایت و پیگیری (پاسخ حداکثر ظرف ۷۲ ساعت)',
  terms_url: 'https://arisheh.ir/terms',
};
const GATEWAY = { id: 2, name: 'زرین‌پال', driver: 'zarinpal', fee: 1500, total: 151500, fee_percent: 1, fee_fixed: 0 };

type Route = (url: string, init: RequestInit) => [number, unknown];
let routes: Route;
const calls: { method: string; url: string; body: any; auth?: string }[] = [];
const ok = (data: unknown, meta?: unknown) => ({ success: true, data, meta });
const notFound: [number, unknown] = [404, { success: false, error: { code: 'not_found', message: 'یافت نشد.' } }];

beforeEach(async () => {
  mockSecure.clear();
  mockCamera.granted = false;
  mockCamera.props = null;
  calls.length = 0;
  await storage().clear();
  jest.spyOn(Linking, 'openURL').mockResolvedValue(true);
  globalThis.fetch = jest.fn(async (url: string, init: RequestInit) => {
    const headers = init.headers as Record<string, string>;
    calls.push({ method: init.method ?? 'GET', url, body: init.body ? JSON.parse(String(init.body)) : undefined, auth: headers.Authorization });
    const [status, body] = routes(url, init);
    return { ok: status < 300, status, json: async () => body } as Response;
  }) as unknown as typeof fetch;
});

async function signedIn() {
  mockSecure.set('mahru.token', '7|cus');
  await storage().setItem('mahru.account', JSON.stringify(ACCOUNT));
  await storage().setItem('mahru.customer.salon', JSON.stringify(SALON));
}

it('picks the salon by code, signs up with the SMS code and lands on home', async () => {
  routes = (url) => {
    if (url.endsWith('/customer/salons/rasta')) return [200, ok(SALON)];
    if (url.endsWith('/customer/salons/rasta/register')) return [200, ok(CHALLENGE)];
    if (url.endsWith('/customer/salons/rasta/register/verify')) return [201, ok(TOKEN)];
    if (url.includes('/customer/home')) return [200, ok(HOME)];
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/' });

  fireEvent.changeText(await screen.findByLabelText('لینک یا کد سالن'), ' rasta ');
  fireEvent.press(screen.getByText('ادامه'));

  await screen.findByText('زیبایی با آرامش');
  expect(calls[0]).toMatchObject({ method: 'GET', auth: undefined });
  expect(JSON.parse(await storage().getItem('mahru.customer.salon'))).toMatchObject({ slug: 'rasta' });

  fireEvent.press(screen.getByText('حساب ندارم — ثبت‌نام'));
  fireEvent.changeText(await screen.findByLabelText('نام و نام خانوادگی'), 'مریم رضایی');
  fireEvent.changeText(screen.getByLabelText('شماره موبایل'), '۹۱۲۱۱۱۱۱۱۱');
  fireEvent.changeText(screen.getByLabelText('رمز عبور'), 'secret123');
  fireEvent.changeText(screen.getByLabelText('تکرار رمز'), 'secret123');

  // دکمه‌ی «چشم»: رمز را پیش از فرستادن نشان می‌دهد و دوباره پنهان می‌کند (هر فیلد جدا)
  expect(screen.getByLabelText('رمز عبور').props.secureTextEntry).toBe(true);
  fireEvent.press(screen.getAllByLabelText('نمایش رمز')[0]);
  expect(screen.getByLabelText('رمز عبور').props.secureTextEntry).toBe(false);
  expect(screen.getByLabelText('تکرار رمز').props.secureTextEntry).toBe(true);
  fireEvent.press(screen.getByLabelText('پنهان کردن رمز'));
  expect(screen.getByLabelText('رمز عبور').props.secureTextEntry).toBe(true);

  // بسته‌ی مجوز 5د: بی پذیرش قوانین و حریم خصوصی کدی فرستاده نمی‌شود؛ لینک‌ها صفحه‌های دامنه‌ی مرکزی را باز می‌کنند
  fireEvent.press(screen.getByText('فرستادن کد تأیید'));
  expect(calls.some((c) => c.url.endsWith('/register'))).toBe(false);
  fireEvent.press(screen.getByText('قوانین و شرایط'));
  expect(Linking.openURL).toHaveBeenLastCalledWith(expect.stringMatching(/^https?:\/\/[^/]+\/terms$/));
  fireEvent.press(screen.getByText('حریم خصوصی'));
  expect(Linking.openURL).toHaveBeenLastCalledWith(expect.stringMatching(/\/privacy$/));
  fireEvent(screen.getByLabelText('پذیرش قوانین و حریم خصوصی'), 'valueChange', true);
  fireEvent.press(screen.getByText('فرستادن کد تأیید'));

  fireEvent.changeText(await screen.findByLabelText('کد تأیید'), '۱۲۳۴۵۶');
  expect(calls.find((c) => c.url.endsWith('/register'))?.body).toEqual({ name: 'مریم رضایی', phone: '09121111111', password: 'secret123', password_confirmation: 'secret123', accept_terms: true });
  fireEvent.press(screen.getByText('ساخت حساب'));

  await screen.findByText('سلام مریم');
  expect(calls.find((c) => c.url.endsWith('/register/verify'))?.body).toEqual({ challenge: 'CH9', code: '123456', device_name: 'Redmi Note 8' });
  expect(mockSecure.get('mahru.token')).toBe('7|cus');
  expect(calls.find((c) => c.url.includes('/customer/home'))?.auth).toBe('Bearer 7|cus');
  expect(await screen.findByText(/نوبت در انتظار پرداخت دارید/)).toBeTruthy();
});

it('books a service, opens the bank in the browser and trusts only the server for the result', async () => {
  await signedIn();
  let paymentState = 'pending';
  routes = (url, init) => {
    if (url.includes('/customer/services/3/specialists')) return [200, ok([{ id: 5, name: 'سارا', photo_url: null }])];
    if (url.includes('/customer/services')) return [200, ok({ categories: [], services: [{ id: 3, name: 'کوتاهی مو', description: null, price: 500000, duration_minutes: 60, prepayment_amount: 150000, image_url: null, category: null }] })];
    if (url.includes('/specialists/5/dates')) return [200, ok([{ date: '2026-10-20', date_jalali: '1405/07/28', label: 'دوشنبه 28 مهر' }])];
    if (url.includes('/specialists/5/slots')) return [200, ok([{ time: '10:00', booking_time: '2026-10-20T10:00:00+03:30' }])];
    if (url.endsWith('/customer/bookings/quote')) return [200, ok({ service: { id: 3, name: 'کوتاهی مو' }, price: 500000, prepayment_amount: 150000, discount_code: null, discount_amount: 0, remaining_amount: 350000, online_payment: true, gateways: [GATEWAY], wallet_balance: 0, terms: TERMS })];
    if (url.endsWith('/customer/bookings') && init.method === 'POST') return [201, ok(BOOKING, { payment: { required: true, amount: 150000, gateways: [GATEWAY], wallet_balance: 0 } })];
    if (url.endsWith('/customer/bookings/41/pay')) return [200, ok({ state: 'redirect', checkout_url: 'http://x/s/rasta/app/checkout/tx-1?signature=s', transaction: '0b8f6b3e-1111-4222-8333-944444444444', gateway: 'zarinpal', fee: 1500, expires_in: 900 })];
    if (url.includes('/customer/bookings/41')) return [200, ok(BOOKING)];
    if (url.includes('/customer/wallet/charge')) return [200, ok({ online_payment: true, min: 10000, max: 50000000, suggested: [], gateways: [GATEWAY] })];
    if (url.endsWith('/customer/wallet')) return [200, ok({ balance: 0, total_deposited: 0, total_spent: 0 })];
    if (url.includes('/customer/payments/0b8f6b3e')) {
      return [200, ok({ transaction: '0b8f6b3e-1111-4222-8333-944444444444', purpose: 'booking', state: paymentState, amount: 150000, fee: 1500, ref_id: paymentState === 'paid' ? '987654' : null, created_at: null, booking: { ...BOOKING, ...(paymentState === 'paid' ? { status: 'pending', status_label: 'در انتظار تأیید سالن', payment_status: 'paid', actions: { pay: false, cancel: true, reschedule: true, rate: false } } : {}) } })];
    }
    if (url.includes('/customer/home')) return [200, ok(HOME)];
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/book' });

  fireEvent.press(await screen.findByText('کوتاهی مو'));
  // تنها متخصص خودکار انتخاب می‌شود
  fireEvent.press(await screen.findByText('دوشنبه ۲۸ مهر'));
  fireEvent.press(await screen.findByText('۱۰:۰۰'));
  fireEvent.press(screen.getByText('ادامه'));

  // پیش‌فاکتور: پیش‌پرداخت و پرداخت در سالن
  await screen.findByText('۳۵۰٬۰۰۰ تومان');
  // اطلاعات پیش از خرید (مواد ۳۳ تا ۳۵): ارائه‌دهنده، شرایط لغو و راه شکایت پیش از ثبت
  expect(screen.getByText('ارائه‌دهنده‌ی خدمت')).toBeTruthy();
  expect(screen.getByText('تهران، خیابان آزادی، پلاک ۵')).toBeTruthy();
  expect(screen.getByText(/لغو رایگان تا ۲۴ ساعت/)).toBeTruthy();
  expect(screen.getByText(/پاسخ حداکثر ظرف ۷۲ ساعت/)).toBeTruthy();
  fireEvent.press(screen.getByText('قوانین و شرایط کامل'));
  expect(Linking.openURL).toHaveBeenLastCalledWith('https://arisheh.ir/terms');
  fireEvent.press(screen.getByText('ثبت نوبت'));

  await screen.findByText('زرین‌پال');
  expect(calls.find((c) => c.url.endsWith('/customer/bookings') && c.method === 'POST')?.body).toEqual({
    service_id: 3, specialist_id: 5, booking_time: '2026-10-20T10:00:00+03:30', idempotency_key: '11111111-2222-4333-8444-555555555555',
  });

  fireEvent.press(await screen.findByText('پرداخت ۱۵۱٬۵۰۰ تومان'));
  await waitFor(() => expect(Linking.openURL).toHaveBeenCalledWith('http://x/s/rasta/app/checkout/tx-1?signature=s'));
  expect(calls.find((c) => c.url.endsWith('/41/pay'))?.body).toEqual({ gateway_id: 2 });
  expect(JSON.parse(await storage().getItem('mahru.customer.pendingPayment'))).toMatchObject({ tx: '0b8f6b3e-1111-4222-8333-944444444444', bookingId: 41 });

  await screen.findByText('در انتظار نتیجه‌ی بانک');
  paymentState = 'paid';
  fireEvent.press(screen.getByText('بررسی دوباره'));
  await screen.findByText('پرداخت موفق بود');
  expect(screen.getByText('۹۸۷۶۵۴')).toBeTruthy();
  await waitFor(async () => expect(await storage().getItem('mahru.customer.pendingPayment')).toBeNull());
});

it('ignores the state in the return link and shows what the server says', async () => {
  await signedIn();
  routes = (url) => {
    if (url.includes('/customer/payments/0b8f6b3e')) return [200, ok({ transaction: '0b8f6b3e-1111-4222-8333-944444444444', purpose: 'wallet_charge', state: 'failed', amount: 50000, fee: 0, ref_id: null, created_at: null, credited: false, wallet_balance: 0 })];
    if (url.includes('/customer/home')) return [200, ok(HOME)];
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/payment?tx=0b8f6b3e-1111-4222-8333-944444444444&state=paid&purpose=wallet_charge' });

  await screen.findByText('پرداخت انجام نشد');
  expect(screen.queryByText('پرداخت موفق بود')).toBeNull();
  expect(screen.getByText('شارژ دوباره')).toBeTruthy();
});

it('a 401 returns to the same salon login and keeps the salon', async () => {
  await signedIn();
  routes = () => [401, { success: false, error: { code: 'unauthenticated', message: 'ابتدا وارد حساب خود شوید.' } }];

  renderRouter('./src/customer', { initialUrl: '/' });

  await screen.findByText('زیبایی با آرامش');
  expect(screen.getByText('حساب ندارم — ثبت‌نام')).toBeTruthy();
  await waitFor(() => expect(mockSecure.has('mahru.token')).toBe(false));
  expect(JSON.parse(await storage().getItem('mahru.customer.salon'))).toMatchObject({ slug: 'rasta' });
});

it('an unknown or suspended salon code gets one message and nothing is saved', async () => {
  routes = () => notFound;

  renderRouter('./src/customer', { initialUrl: '/' });

  fireEvent.changeText(await screen.findByLabelText('لینک یا کد سالن'), 'http://192.168.1.5:8000/s/nope');
  // نسخه‌ی آزمایشی: سرور QR با سرور اپ فرق دارد → پرسش (اینجا رد می‌شود)
  const alert = jest.spyOn(require('react-native').Alert, 'alert').mockImplementation((...args: unknown[]) => {
    const buttons = args[2] as { text: string; onPress?: () => void }[];
    buttons.find((b) => b.text === 'نه')?.onPress?.();
  });
  fireEvent.press(screen.getByText('ادامه'));

  await screen.findByText(/سالنی با این کد پیدا نشد/);
  expect(calls[0].url).toContain('/api/v1/customer/salons/nope');
  expect(await storage().getItem('mahru.customer.salon')).toBeNull();
  alert.mockRestore();
});

it('a suspended salon shows its own screen without logging out', async () => {
  await signedIn();
  routes = () => [403, { success: false, error: { code: 'salon_inactive', message: 'اشتراک این سالن پایان یافته یا غیرفعال شده است.' } }];

  renderRouter('./src/customer', { initialUrl: '/' });

  await screen.findByText('نوبت‌دهی آنلاین فعلاً بسته است');
  expect(mockSecure.get('mahru.token')).toBe('7|cus');
  await act(async () => undefined);
});

it('the profile switch turns the salon follow-up texts off', async () => {
  await signedIn();
  let marketing = true;
  routes = (url, init) => {
    if (url.endsWith('/customer/profile')) {
      if (init.method === 'PATCH') marketing = JSON.parse(String(init.body)).marketing_sms;
      return [200, ok({ id: 9, name: 'مریم رضایی', phone: '09121111111', marketing_sms: marketing, created_at: null })];
    }
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/profile' });

  const toggle = await screen.findByLabelText('پیامک‌های پیگیری و پیشنهاد سالن');
  expect(toggle.props.value).toBe(true);
  fireEvent(toggle, 'valueChange', false);

  await waitFor(() => expect(calls.find((c) => c.method === 'PATCH')?.body).toEqual({ marketing_sms: false }));
  await waitFor(() => expect(screen.getByLabelText('پیامک‌های پیگیری و پیشنهاد سالن').props.value).toBe(false));
});

const COURSE = {
  id: 12, name: 'لیزر ۶ جلسه', status: 'pending_payment', status_label: 'در انتظار پرداخت', sessions_total: 6, sessions_used: 0, sessions_burned: 0,
  sessions_reserved: 0, sessions_remaining: 6, paid_sessions: 6, price_total: 4800000, amount_paid: 0, balance_due: 4800000, min_prepayment: 1200000,
  discount_amount: 0, service_id: 3, specialist_id: null, purchased_at: null, expires_at: null, expires_at_jalali: null, next_due_at: null,
  photo_consent: false, refunded_amount: 0, actions: { pay: true, book: false }, book_blocker: 'پرداخت دوره هنوز انجام نشده است.',
};

it('buys a treatment course, pays the minimum from the wallet and books the next session', async () => {
  await signedIn();
  let course: any = COURSE;
  routes = (url, init) => {
    if (url.includes('/customer/courses/packages/7/purchase')) return [201, ok(COURSE)];
    if (url.includes('/customer/courses/packages')) return [200, ok([{ id: 7, name: 'لیزر ۶ جلسه', description: null, sessions_count: 6, interval_days: 35, validity_days: 365, price: 4800000, list_price: 6000000, saving: 1200000, min_prepayment: 1200000, service: { id: 3, name: 'لیزر', duration_minutes: 30 }, specialist: null }])];
    if (url.includes('/customer/courses/12/pay') && init.method === 'POST') {
      course = { ...COURSE, status: 'active', status_label: 'فعال', amount_paid: 1200000, balance_due: 3600000, paid_sessions: 2, actions: { pay: true, book: true }, book_blocker: null };
      return [200, ok({ state: 'paid', course })];
    }
    if (url.includes('/customer/courses/12/pay')) return [200, ok({ balance_due: 4800000, min_amount: 1200000, wallet_balance: 2000000, online_payment: true, gateways: [GATEWAY] })];
    if (url.includes('/customer/courses/12/specialists')) return [200, ok([{ id: 5, name: 'سارا', photo_url: null }])];
    if (url.includes('/customer/courses/12/dates')) return [200, ok({ from: '2026-10-15', to: '2026-11-29', dates: ['2026-10-20'] })];
    if (url.includes('/customer/courses/12/slots')) return [200, ok({ date: '2026-10-20', slots: ['10:00'] })];
    if (url.includes('/customer/courses/12/bookings')) return [201, ok({ booking: { ...BOOKING, status: 'confirmed', payment_status: 'paid', course: { id: 12, name: 'لیزر ۶ جلسه', session_number: 1, sessions_total: 6, label: 'جلسه‌ی ۱ از ۶', photo_consent: false } }, course })];
    if (url.includes('/customer/courses/12')) return [200, ok({ ...course, sessions: [], payments: [] })];
    if (url.includes('/customer/courses')) return [200, ok([])];
    if (url.includes('/customer/home')) return [200, ok({ ...HOME, features: ['treatment_courses'] })];
    if (url.includes('/customer/wallet')) return [200, ok({ balance: 2000000, total_deposited: 0, total_spent: 0 })];
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/courses' });

  fireEvent.press(await screen.findByText('لیزر ۶ جلسه'));
  fireEvent.press(await screen.findByText('خرید این دوره'));

  // پرداخت اول: کمترین مبلغ، از کیف پول
  fireEvent.press(await screen.findByText('کمترین مبلغ'));
  fireEvent(screen.getByLabelText('استفاده از کیف پول'), 'valueChange', true);
  fireEvent.press(await screen.findByText('پرداخت از کیف پول'));
  await waitFor(() => expect(calls.find((c) => c.url.includes('/courses/12/pay') && c.method === 'POST')?.body).toEqual({ amount: 1200000, use_wallet: true }));

  fireEvent.press(await screen.findByText('رزرو جلسه‌ی بعد'));
  fireEvent.press(await screen.findByText('سارا'));
  fireEvent.press(await screen.findByText(/مهر/));
  fireEvent.press(await screen.findByText('۱۰:۰۰'));
  fireEvent.press(screen.getByText('رزرو این جلسه'));
  await waitFor(() => expect(calls.find((c) => c.url.includes('/courses/12/bookings'))?.body).toEqual({ specialist_id: 5, date: '2026-10-20', time: '10:00' }));
});

it('lets a signed-in customer scan a specialist QR of the same salon from home (2026-10-21)', async () => {
  await signedIn();
  mockCamera.granted = true;
  routes = (url) => {
    if (url.includes('/customer/specialists/5')) return [200, ok({ id: 5, name: 'سارا', photo_url: null, bio: null, rating: null, reviews_count: 0, services: [] })];
    if (url.includes('/customer/home')) return [200, ok(HOME)];
    return notFound;
  };

  const router = renderRouter('./src/customer', { initialUrl: '/home' });

  fireEvent.press(await screen.findByText('اسکن QR متخصص یا سالن'));
  await waitFor(() => expect(mockCamera.props?.onBarcodeScanned).toBeTruthy());
  await act(async () => {
    await mockCamera.props.onBarcodeScanned({ data: 'http://192.168.1.2:8000/s/rasta/specialists/5?src=qr' });
  });

  await waitFor(() => expect(router.getPathname()).toBe('/specialist/5'));
  // اسکن پس از ورود، سالن را عوض نمی‌کند و درخواست «کارت سالن» نمی‌فرستد
  expect(calls.some((c) => c.url.endsWith('/customer/salons/rasta'))).toBe(false);
});

it('the customer can withdraw the course photo consent (5د)', async () => {
  await signedIn();
  let consent = true;
  routes = (url, init) => {
    if (url.includes('/customer/courses/12/photo-consent/withdraw') && init.method === 'POST') {
      consent = false;
      return [200, ok({ photo_consent: false })];
    }
    if (url.includes('/customer/courses/12')) {
      return [200, ok({ ...COURSE, status: 'active', status_label: 'فعال', photo_consent: consent, photo_consent_text: 'با گرفتن عکس قبل و بعد موافقم.', actions: { pay: false, book: false }, book_blocker: null, sessions: [], payments: [] })];
    }
    return notFound;
  };
  jest.spyOn(Alert, 'alert').mockImplementation((_t, _m, buttons) => buttons?.find((b) => b.text === 'پس می‌گیرم')?.onPress?.());

  renderRouter('./src/customer', { initialUrl: '/course/12' });

  expect(await screen.findByText('با گرفتن عکس قبل و بعد موافقم.')).toBeTruthy();
  fireEvent.press(screen.getByText('پس گرفتن رضایت'));
  await waitFor(() => expect(calls.some((c) => c.method === 'POST' && c.url.endsWith('/customer/courses/12/photo-consent/withdraw'))).toBe(true));
  await waitFor(() => expect(screen.queryByText('پس گرفتن رضایت')).toBeNull());
});

const PENDING = [{
  document: 'privacy', title: 'حریم خصوصی', version: '1405-09-01', accepted_version: '1405-08-03',
  changes: [{ version: '1405-09-01', major: true, summary: 'نگهداری IP شش ماه' }], url: 'https://arisheh.ir/privacy',
}];

it('after a major change the terms page comes first; accepting records it and goes home (2026-10-28)', async () => {
  await signedIn();
  let pending = PENDING;
  routes = (url, init) => {
    if (url.endsWith('/me')) return [200, ok({ ...ACCOUNT, legal_pending: pending })];
    if (url.endsWith('/customer/legal/accept') && init.method === 'POST') {
      pending = [];
      return [200, ok({ pending: [] })];
    }
    if (url.endsWith('/customer/legal')) return [200, ok({ pending })];
    if (url.includes('/customer/home')) return [200, ok(HOME)];
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/home' });

  expect(await screen.findByText(/نگهداری IP شش ماه/)).toBeTruthy();
  expect(screen.getByText('مهم')).toBeTruthy();
  fireEvent.press(screen.getByText('متن کامل حریم خصوصی'));
  expect(Linking.openURL).toHaveBeenLastCalledWith('https://arisheh.ir/privacy');

  fireEvent.press(screen.getByText('پذیرش و ادامه'));
  expect(calls.some((c) => c.url.endsWith('/customer/legal/accept'))).toBe(false);
  fireEvent(screen.getByLabelText('پذیرش نسخه‌ی تازه'), 'valueChange', true);
  fireEvent.press(screen.getByText('پذیرش و ادامه'));

  await screen.findByText('سلام مریم');
  expect(calls.find((c) => c.url.endsWith('/customer/legal/accept'))?.body).toEqual({ accept_terms: true });
});

it('a 409 terms_update_required on booking opens the terms page; "not now" keeps the current bookings (2026-10-28)', async () => {
  await signedIn();
  routes = (url) => {
    if (url.endsWith('/me')) return [200, ok(ACCOUNT)];
    if (url.includes('/customer/services/3/specialists')) return [200, ok([{ id: 5, name: 'سارا', photo_url: null }])];
    if (url.includes('/customer/services')) return [200, ok({ categories: [], services: [{ id: 3, name: 'کوتاهی مو', description: null, price: 500000, duration_minutes: 60, prepayment_amount: 150000, image_url: null, category: null }] })];
    if (url.includes('/specialists/5/dates')) return [200, ok([{ date: '2026-10-20', date_jalali: '1405/07/28', label: 'دوشنبه 28 مهر' }])];
    if (url.includes('/specialists/5/slots')) return [200, ok([{ time: '10:00', booking_time: '2026-10-20T10:00:00+03:30' }])];
    if (url.endsWith('/customer/bookings/quote')) return [409, { success: false, error: { code: 'terms_update_required', message: 'قوانین ماهرو به‌روز شده است.' } }];
    if (url.endsWith('/customer/legal')) return [200, ok({ pending: PENDING })];
    if (url.includes('/customer/bookings')) return [200, ok([BOOKING], { current_page: 1, last_page: 1, per_page: 20, total: 1 })];
    if (url.includes('/customer/home')) return [200, ok(HOME)];
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/book' });

  fireEvent.press(await screen.findByText('کوتاهی مو'));
  fireEvent.press(await screen.findByText('دوشنبه ۲۸ مهر'));
  fireEvent.press(await screen.findByText('۱۰:۰۰'));
  fireEvent.press(screen.getByText('ادامه'));

  expect(await screen.findByText(/نگهداری IP شش ماه/)).toBeTruthy();
  fireEvent.press(screen.getByText('فعلاً نه — نوبت‌های فعلی'));
  await waitFor(() => expect(screen.queryByText(/نگهداری IP شش ماه/)).toBeNull());
  expect(calls.some((c) => c.url.endsWith('/customer/legal/accept'))).toBe(false);
});

it('the customer answers the salon photo consent request on the course page (2026-10-28)', async () => {
  await signedIn();
  let status = 'pending';
  const state = () => ({
    status, text: 'متن کامل رضایت عکس.', text_version: '1405-08-03', open_request: status === 'pending' ? { requested_at: null, expires_at: null, send_count: 1 } : null,
    can_request: false, request_blocked_reason: null, next_request_at: null, can_resend: false, next_resend_at: null,
    history: status === 'granted' ? [{ status: 'granted', source: 'app', label: 'موافقت — اپ مشتری', at: null, at_jalali: '1405/08/06 10:00' }] : [],
  });
  routes = (url, init) => {
    if (url.endsWith('/customer/courses/12/photo-consent') && init.method === 'POST') {
      status = 'granted';
      return [200, ok({ photo_consent: true, photo_consent_state: state() })];
    }
    if (url.includes('/customer/courses/12')) {
      return [200, ok({ ...COURSE, status: 'active', status_label: 'فعال', photo_consent: status === 'granted', photo_consent_text: 'متن کامل رضایت عکس.', photo_consent_state: state(), actions: { pay: false, book: false }, book_blocker: null, sessions: [], payments: [] })];
    }
    return notFound;
  };

  renderRouter('./src/customer', { initialUrl: '/course/12' });

  expect(await screen.findByText('سالن رضایت شما را برای عکس قبل و بعد این دوره خواسته است.')).toBeTruthy();
  expect(screen.getByText('موافق نیستم')).toBeTruthy();
  fireEvent.press(screen.getByText('موافقم'));
  expect(calls.some((c) => c.url.endsWith('/photo-consent'))).toBe(false);
  fireEvent(screen.getByLabelText('متن را خواندم'), 'valueChange', true);
  fireEvent.press(screen.getByText('موافقم'));

  await waitFor(() => expect(calls.find((c) => c.method === 'POST' && c.url.endsWith('/customer/courses/12/photo-consent'))?.body).toEqual({ decision: 'agree' }));
  expect(await screen.findByText('پس گرفتن رضایت')).toBeTruthy();
  expect(screen.getByText(/موافقت — اپ مشتری/)).toBeTruthy();
});
