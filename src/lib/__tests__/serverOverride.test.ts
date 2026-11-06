/**
 * آدرس سرور قابل تغییر فقط در نسخه‌ی آزمایشی (۲۰۲۶-۱۱-۰۶): production آدرس ذخیره‌شده را نه می‌خواند نه ذخیره می‌کند
 * (نصب روی نسخه‌ی آزمایشی آن را با خودش می‌آورد؛ و صفحه‌ی server با لینک mahru://server هم باز می‌شد).
 */
jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
jest.mock('expo-secure-store', () => ({ setItemAsync: jest.fn(), getItemAsync: jest.fn(), deleteItemAsync: jest.fn() }));

function load(allowServerOverride: boolean) {
  jest.resetModules();
  jest.doMock('expo-constants', () => ({ __esModule: true, default: { expoConfig: { version: '0.3.9', extra: { allowServerOverride } } } }));
  const mod = require('@react-native-async-storage/async-storage');
  const AsyncStorage = mod.default ?? mod;
  const storage = require('../storage');
  return { AsyncStorage, storage };
}

afterEach(() => jest.dontMock('expo-constants'));

it('production ignores and clears a server saved by an earlier test build', async () => {
  const { AsyncStorage, storage } = load(false);
  await AsyncStorage.setItem('mahru.server', 'http://192.168.1.2:8000');

  expect(await storage.loadServerOverride()).toBeNull();
  expect(await AsyncStorage.getItem('mahru.server')).toBeNull();

  await storage.saveServerOverride('https://evil.example');
  expect(await AsyncStorage.getItem('mahru.server')).toBeNull();
});

it('test builds keep the server field working', async () => {
  const { AsyncStorage, storage } = load(true);

  await storage.saveServerOverride('http://192.168.1.2:8000');
  expect(await storage.loadServerOverride()).toBe('http://192.168.1.2:8000');

  await storage.saveServerOverride(null);
  expect(await AsyncStorage.getItem('mahru.server')).toBeNull();
});
