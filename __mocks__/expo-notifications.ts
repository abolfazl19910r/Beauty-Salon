/// <reference types="jest" />
/**
 * expo-notifications در Jest (بسته‌ی ۴): ماژول بومی ندارد؛ این نسخه‌ی ساختگی اجازه را «داده شده» و توکن FCM را ثابت برمی‌گرداند
 * تا آزمون‌های دود ثبت گوشی را هم بگذرانند. آزمون‌ها با mockPushResponse یک لمس اعلان را شبیه‌سازی می‌کنند.
 */
type Listener = (value: unknown) => void;

const listeners: Record<string, Listener[]> = { token: [], received: [], response: [] };
let lastResponse: unknown = null;

const sub = (kind: string, fn: Listener) => {
  listeners[kind].push(fn);
  return { remove: () => void (listeners[kind] = listeners[kind].filter((l) => l !== fn)) };
};

export const AndroidImportance = { MAX: 5, HIGH: 4, DEFAULT: 3 };
export const setNotificationHandler = jest.fn();
export const setNotificationChannelAsync = jest.fn(async () => null);
export const registerTaskAsync = jest.fn(async () => null);
export const getPermissionsAsync = jest.fn(async () => ({ status: 'granted', granted: true }));
export const requestPermissionsAsync = jest.fn(async () => ({ status: 'granted', granted: true }));
export const getDevicePushTokenAsync = jest.fn(async () => ({ type: 'android', data: 'fcm-test-token-0001' }));
export const addPushTokenListener = jest.fn((fn: Listener) => sub('token', fn));
export const addNotificationReceivedListener = jest.fn((fn: Listener) => sub('received', fn));
export const addNotificationResponseReceivedListener = jest.fn((fn: Listener) => sub('response', fn));
export const getLastNotificationResponse = jest.fn(() => lastResponse);
export const clearLastNotificationResponse = jest.fn(() => void (lastResponse = null));

/** فقط برای آزمون‌ها */
export function mockPushResponse(data: Record<string, unknown>, asLaunch = false) {
  const response = { actionIdentifier: 'expo.modules.notifications.actions.DEFAULT', notification: { request: { content: { data } } } };
  if (asLaunch) {
    lastResponse = response;
  } else {
    listeners.response.forEach((l) => l(response));
  }
}

export function mockPushReceived(data: Record<string, unknown>) {
  listeners.received.forEach((l) => l({ request: { content: { data } } }));
}
