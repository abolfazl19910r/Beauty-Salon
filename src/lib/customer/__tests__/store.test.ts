/// <reference types="jest" />
import AsyncStorage from '@react-native-async-storage/async-storage';

import { clearPendingPayment, loadPendingPayment, PENDING_TTL_MS, savePendingPayment } from '../store';

jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));

const pending = (tx: string, startedAt: number) => ({ tx, purpose: 'booking' as const, bookingId: 5, checkoutUrl: 'http://x', checkoutExpiresAt: startedAt + 900_000, startedAt });

beforeEach(() => AsyncStorage.clear());

it('forgets a pending payment after the TTL', async () => {
  await savePendingPayment(pending('A', 1000));
  expect((await loadPendingPayment(1000 + PENDING_TTL_MS - 1))?.tx).toBe('A');
  expect(await loadPendingPayment(1000 + PENDING_TTL_MS + 1)).toBeNull();
  expect(await loadPendingPayment(1000)).toBeNull();
});

it('clears only the same transaction', async () => {
  await savePendingPayment(pending('NEW', 5000));
  await clearPendingPayment('OLD');
  expect((await loadPendingPayment(5000))?.tx).toBe('NEW');
  await clearPendingPayment('NEW');
  expect(await loadPendingPayment(5000)).toBeNull();
});
