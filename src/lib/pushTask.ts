import * as TaskManager from 'expo-task-manager';

import { BACKGROUND_TASK, parsePushData } from './push';
import { recordBackgroundRun, sendReceipts } from './pushReceipts';

/**
 * task پس‌زمینه‌ی پوش (بسته‌ی ۴) — باید پیش از هر صفحه و در سطح ماژول تعریف شود (index.ts)، چون وقتی اپ بسته است اندروید
 * فقط همین JS را بدون رابط اجرا می‌کند. اعلان را خود expo-notifications از title/message پیام می‌سازد؛ اینجا فقط رسید.
 */
TaskManager.defineTask(BACKGROUND_TASK, async ({ data, error }) => {
  if (error) {
    return;
  }
  const push = parsePushData(data);
  const outcome = push.messageId ? await sendReceipts([push.messageId]) : 'no-message';
  const keys = data && typeof data === 'object' ? Object.keys(data).slice(0, 12) : [];
  await recordBackgroundRun({ at: new Date().toISOString(), messageId: push.messageId, outcome, keys });
});
