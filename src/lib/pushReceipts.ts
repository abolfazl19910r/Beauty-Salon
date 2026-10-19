import AsyncStorage from '@react-native-async-storage/async-storage';

import { buildUrl } from './api';
import { APP_VERSION, DEFAULT_API_URL } from './config';
import { enqueueReceipt } from './push';
import { loadServerOverride, loadSession } from './storage';

/**
 * رسید پوش (بسته‌ی ۴) بدون React — هم از اپ باز و هم از task پس‌زمینه وقتی اپ بسته است. توکن و آدرس سرور مستقیم از storage.
 */

const PENDING_KEY = 'mahru.push.pendingReceipts';
const LAST_RUN_KEY = 'mahru.push.lastBackgroundRun';

/** نتیجه‌ی فرستادن رسید: sent (۲xx)، rejected (۴xx؛ صف خالی می‌شود)، queued (بی‌اینترنت/۵xx)، signed-out، empty */
export type ReceiptOutcome = 'sent' | 'rejected' | 'queued' | 'signed-out' | 'empty';

/** رسید پیام‌ها؛ بی‌اینترنت در صف می‌ماند و با باز شدن بعدی اپ فرستاده می‌شود */
export async function sendReceipts(ids: string[], opened = false): Promise<ReceiptOutcome> {
  const pending = await loadPending();
  const all = ids.reduce((queue, id) => enqueueReceipt(queue, id), pending);
  if (all.length === 0) {
    return 'empty';
  }

  const session = await loadSession();
  if (!session) {
    return 'signed-out';
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
    return response.status >= 500 ? 'queued' : response.status >= 400 ? 'rejected' : 'sent';
  } catch {
    await savePending(all);
    return 'queued';
  }
}

export async function flushReceipts(): Promise<void> {
  await sendReceipts([]);
}

/**
 * ردپای آخرین اجرای task پس‌زمینه (۲۰۲۶-۱۰-۲۰): رسید اپ بسته روی گوشی‌های آزمایشی نمی‌رسید و از بیرون نمی‌شد فهمید task اصلاً
 * اجرا نشده (اندروید/سازنده اپ را بیدار نکرده)، پیام را نخوانده (شکل payload)، یا فرستادن نشده (نشست/شبکه). صفحه‌ی «دریافت اعلان»
 * همین را نشان می‌دهد. keys فقط نام کلیدهای سطح اول payload است، نه محتوا.
 */
export type BackgroundRun = { at: string; messageId: string | null; outcome: ReceiptOutcome | 'no-message'; keys: string[] };

export async function recordBackgroundRun(run: BackgroundRun): Promise<void> {
  await AsyncStorage.setItem(LAST_RUN_KEY, JSON.stringify(run)).catch(() => undefined);
}

export async function lastBackgroundRun(): Promise<BackgroundRun | null> {
  try {
    const raw = await AsyncStorage.getItem(LAST_RUN_KEY);
    const parsed = raw ? (JSON.parse(raw) as Partial<BackgroundRun>) : null;
    return parsed && typeof parsed.at === 'string' ? (parsed as BackgroundRun) : null;
  } catch {
    return null;
  }
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

