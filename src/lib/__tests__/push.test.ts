/// <reference types="jest" />
import { deviceGuide, enqueueReceipt, parsePushData, routeFor } from '../push';

const ID = '9b2f7c1e-1111-4a2b-8c3d-0123456789ab';

it('reads the message from notification content data (the JSON "body" key)', () => {
  const data = parsePushData({ message_id: ID, type: 'booking.cancelled.customer', urgency: 'urgent', booking_id: '41', salon_id: '1' });
  expect(data).toEqual({ messageId: ID, type: 'booking.cancelled.customer', bookingId: 41, courseId: null, withdrawalId: null, leaveId: null, test: false });
});

it('reads the background task payload: dataString first, flat keys as backup', () => {
  expect(parsePushData({ data: { dataString: JSON.stringify({ message_id: ID, course_id: '7' }), title: 'x' } }).courseId).toBe(7);
  expect(parsePushData({ data: { message_id: ID, booking_id: '9', body: JSON.stringify({ message_id: ID, booking_id: '9' }) } }).bookingId).toBe(9);
  expect(parsePushData({ data: { dataString: 'not json', message_id: ID } }).messageId).toBe(ID);
});

it('ignores junk', () => {
  expect(parsePushData(null).messageId).toBeNull();
  expect(parsePushData({ message_id: 'abc', booking_id: '-3' })).toMatchObject({ messageId: null, bookingId: null });
  expect(parsePushData({ test: '1' }).test).toBe(true);
});

it('routes a tap to the right screen in each app', () => {
  const base = parsePushData({});
  expect(routeFor('customer', { ...base, bookingId: 41 })).toBe('/booking/41');
  expect(routeFor('customer', { ...base, courseId: 7 })).toBe('/course/7');
  expect(routeFor('staff', { ...base, courseId: 7 })).toBe('/notifications');
  expect(routeFor('staff', { ...base, leaveId: 3 })).toBe('/leaves');
  expect(routeFor('staff', { ...base, withdrawalId: 3 })).toBe('/transactions');
  expect(routeFor('staff', { ...base, test: true })).toBe('/push');
  expect(routeFor('customer', base)).toBe('/notifications');
});

it('keeps unsent receipts unique and capped', () => {
  let q: string[] = [];
  q = enqueueReceipt(q, ID);
  q = enqueueReceipt(q, ID);
  q = enqueueReceipt(q, 'nope');
  expect(q).toEqual([ID]);
  const many = Array.from({ length: 60 }, (_, i) => `00000000-0000-4000-8000-${String(i).padStart(12, '0')}`);
  expect(many.reduce((acc, id) => enqueueReceipt(acc, id), [] as string[])).toHaveLength(50);
});

it('gives the Xiaomi Autostart and Samsung deep-sleep guides from package 0', () => {
  expect(deviceGuide('Xiaomi').maker).toBe('xiaomi');
  expect(deviceGuide('POCO').steps.join(' ')).toContain('شروع خودکار');
  expect(deviceGuide('samsung').steps.join(' ')).toContain('Deep sleeping');
  expect(deviceGuide(null).maker).toBe('other');
});
