/// <reference types="jest" />
import AsyncStorage from '@react-native-async-storage/async-storage';

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
const mockSecure = new Map<string, string>();
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async (k: string) => mockSecure.get(k) ?? null),
  setItemAsync: jest.fn(async (k: string, v: string) => void mockSecure.set(k, v)),
  deleteItemAsync: jest.fn(async (k: string) => void mockSecure.delete(k)),
}));

const ID = '9b2f7c1e-1111-4a2b-8c3d-0123456789ab';
const ID2 = '9b2f7c1e-2222-4a2b-8c3d-0123456789ab';

/**
 * task پس‌زمینه (اپ بسته): رسید با توکن و آدرس سرور ذخیره‌شده، بدون React؛ بی‌اینترنت در صف می‌ماند و با رسید بعدی می‌رود.
 */
describe('background push task', () => {
  let fetchMock: jest.Mock;

  beforeEach(async () => {
    await AsyncStorage.clear();
    mockSecure.clear();
    mockSecure.set('mahru.token', '5|abc');
    await AsyncStorage.setItem('mahru.account', JSON.stringify({ user: { id: 1 } }));
    await AsyncStorage.setItem('mahru.server', 'http://192.168.1.2:8000');
    fetchMock = jest.fn(async () => ({ status: 200, ok: true, json: async () => ({ success: true }) }));
    globalThis.fetch = fetchMock as unknown as typeof fetch;
    require('../pushTask');
  });

  const run = (data: unknown) => require('expo-task-manager').tasks['mahru-push-receipt']({ data, error: null });

  it('sends a receipt with the saved session to the saved server', async () => {
    await run({ data: { dataString: JSON.stringify({ message_id: ID, booking_id: '41' }) } });

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe('http://192.168.1.2:8000/api/v1/push/receipts');
    expect(init.headers.Authorization).toBe('Bearer 5|abc');
    expect(JSON.parse(init.body)).toEqual({ ids: [ID], opened: false });
  });

  it('queues receipts while offline and sends them with the next one', async () => {
    fetchMock.mockRejectedValueOnce(new Error('offline'));
    await run({ data: { message_id: ID } });
    expect(JSON.parse((await AsyncStorage.getItem('mahru.push.pendingReceipts')) ?? '[]')).toEqual([ID]);

    await run({ data: { message_id: ID2 } });
    expect(JSON.parse(fetchMock.mock.calls[1][1].body).ids).toEqual([ID, ID2]);
    expect(await AsyncStorage.getItem('mahru.push.pendingReceipts')).toBeNull();
  });

  it('does nothing when signed out or the payload has no message', async () => {
    await run({ data: { title: 'x' } });
    mockSecure.clear();
    await run({ data: { message_id: ID } });
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('reads the JobService payload wrapped under notification', async () => {
    await run({ notification: { data: { dataString: JSON.stringify({ message_id: ID }), title: 'x' } } });
    expect(JSON.parse(fetchMock.mock.calls[0][1].body).ids).toEqual([ID]);
  });

  it('records the last background run for the push setup screen', async () => {
    const { lastBackgroundRun } = require('../pushReceipts');
    await run({ data: { message_id: ID }, messageId: '0:abc' });
    expect(await lastBackgroundRun()).toMatchObject({ messageId: ID, outcome: 'sent', keys: ['data', 'messageId'] });

    fetchMock.mockRejectedValueOnce(new Error('offline'));
    await run({ data: { message_id: ID2 } });
    expect((await lastBackgroundRun()).outcome).toBe('queued');

    await run({ data: { title: 'x' } });
    expect(await lastBackgroundRun()).toMatchObject({ messageId: null, outcome: 'no-message' });

    mockSecure.clear();
    await run({ data: { message_id: ID } });
    expect((await lastBackgroundRun()).outcome).toBe('signed-out');
  });
});
