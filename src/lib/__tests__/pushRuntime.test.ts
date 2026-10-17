/// <reference types="jest" />
jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async () => null),
  setItemAsync: jest.fn(async () => undefined),
  deleteItemAsync: jest.fn(async () => undefined),
}));
jest.mock('expo-router', () => ({ router: { push: jest.fn() } }));

import { onPushToken, registerForPush, resetPushRegistration } from '../pushRuntime';

// eslint-disable-next-line @typescript-eslint/no-require-imports
const notifications = require('expo-notifications');

/**
 * گزارش ۲۰۲۶-۱۰-۱۷: getDevicePushTokenAsync رویداد «توکن تازه» را هم می‌فرستد؛ شنونده نباید دوباره ثبت کند (حلقه‌ی بی‌پایان PUT).
 */
describe('push registration', () => {
  const flush = () => new Promise((r) => setTimeout(r, 0));
  let api: { put: jest.Mock };

  beforeEach(() => {
    resetPushRegistration();
    api = { put: jest.fn(async () => ({})) };
    notifications.getDevicePushTokenAsync.mockClear();
  });

  it('registers once even though fetching the token fires the token listener', async () => {
    const sub = notifications.addPushTokenListener((e: { data: string }) => onPushToken(api as never, String(e.data)));

    expect(await registerForPush(api as never, true)).toBe(true);
    await flush();
    await flush();

    expect(api.put).toHaveBeenCalledTimes(1);
    expect(api.put.mock.calls[0][1]).toMatchObject({ provider: 'fcm', token: 'fcm-test-token-0001' });
    expect(notifications.getDevicePushTokenAsync).toHaveBeenCalledTimes(1);

    // همان توکن دوباره ← هیچ؛ توکن واقعاً عوض شد ← یک ثبت
    notifications.mockTokenRotated('fcm-test-token-0001');
    notifications.mockTokenRotated('fcm-rotated-0002');
    await flush();
    expect(api.put).toHaveBeenCalledTimes(2);
    expect(api.put.mock.calls[1][1]).toMatchObject({ token: 'fcm-rotated-0002' });
    sub.remove();
  });

  it('concurrent callers share one request', async () => {
    const [a, b] = await Promise.all([registerForPush(api as never, true), registerForPush(api as never, false)]);

    expect(a).toBe(true);
    expect(b).toBe(true);
    expect(api.put).toHaveBeenCalledTimes(1);
  });

  it('a failed request is not cached', async () => {
    api.put.mockRejectedValueOnce(new Error('network'));
    expect(await registerForPush(api as never, true)).toBe(false);
    expect(await registerForPush(api as never, true)).toBe(true);
    expect(api.put).toHaveBeenCalledTimes(2);
  });
});
