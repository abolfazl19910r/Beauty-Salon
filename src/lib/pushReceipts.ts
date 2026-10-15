import AsyncStorage from '@react-native-async-storage/async-storage';

import { buildUrl } from './api';
import { APP_VERSION, DEFAULT_API_URL } from './config';
import { enqueueReceipt } from './push';
import { loadServerOverride, loadSession } from './storage';

/**
 * رسید پوش (بسته‌ی ۴) بدون React — هم از اپ باز و هم از task پس‌زمینه وقتی اپ بسته است. توکن و آدرس سرور مستقیم از storage.
 */

const PENDING_KEY = 'mahru.push.pendingReceipts';

/** رسید پیام‌ها؛ بی‌اینترنت در صف می‌ماند و با باز شدن بعدی اپ فرستاده می‌شود */
export async function sendReceipts(ids: string[], opened = false): Promise<void> {
  const pending = await loadPending();
  const all = ids.reduce((queue, id) => enqueueReceipt(queue, id), pending);
  if (all.length === 0) {
    return;
  }

  const session = await loadSession();
  if (!session) {
    return;
  }
  const server = (await loadServerOverride()) ?? DEFAULT_API_URL;

  try {
    const response = await fetch(buildUrl(server, '/push/receipts'), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${session.token}`,
        'X-App-Version': APP_VERSION,
      },
      body: JSON.stringify({ ids: all, opened }),
    });
    // ۴۲۲ (شناسه‌ی نامعتبر) یا ۴۰۱ هم صف را خالی می‌کند؛ فقط خطای شبکه/سرور نگه داشته می‌شود
    await savePending(response.status >= 500 ? all : []);
  } catch {
    await savePending(all);
  }
}

export async function flushReceipts(): Promise<void> {
  await sendReceipts([]);
}

async function loadPending(): Promise<string[]> {
  try {
    const raw = await AsyncStorage.getItem(PENDING_KEY);
    const parsed = raw ? (JSON.parse(raw) as unknown) : [];
    return Array.isArray(parsed) ? parsed.filter((x): x is string => typeof x === 'string') : [];
  } catch {
    return [];
  }
}

async function savePending(ids: string[]): Promise<void> {
  await (ids.length ? AsyncStorage.setItem(PENDING_KEY, JSON.stringify(ids)) : AsyncStorage.removeItem(PENDING_KEY)).catch(() => undefined);
}

