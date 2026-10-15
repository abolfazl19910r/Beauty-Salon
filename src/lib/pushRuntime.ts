import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';

import type { ApiClient } from './api';
import { APP_VARIANT, APP_VERSION } from './config';
import { BACKGROUND_TASK, CHANNELS, parsePushData, routeFor } from './push';
import { flushReceipts, sendReceipts } from './pushReceipts';

/**
 * بخش بومی پوش (بسته‌ی ۴): کانال‌ها، اجازه، ثبت توکن FCM، رسید و باز کردن صفحه‌ی درست با لمس اعلان.
 * رسید در pushReceipts.ts (بدون React و expo-router، برای task پس‌زمینه).
 */

export type PushPermission = 'granted' | 'denied' | 'undetermined';

let configured = false;

/** یک بار در هر اجرا: نمایش اعلان وقتی اپ باز است، کانال‌های اندروید، و ثبت task پس‌زمینه */
export async function configurePush(): Promise<void> {
  if (configured) {
    return;
  }
  configured = true;

  Notifications.setNotificationHandler({
    handleNotification: async () => ({ shouldShowBanner: true, shouldShowList: true, shouldPlaySound: true, shouldSetBadge: false }),
  });

  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync(CHANNELS.bookings.id, {
      name: CHANNELS.bookings.name,
      importance: Notifications.AndroidImportance.MAX,
      sound: 'default',
      vibrationPattern: [0, 250, 250, 250],
    }).catch(() => undefined);
    await Notifications.setNotificationChannelAsync(CHANNELS.general.id, {
      name: CHANNELS.general.name,
      importance: Notifications.AndroidImportance.DEFAULT,
    }).catch(() => undefined);
  }

  await Notifications.registerTaskAsync(BACKGROUND_TASK).catch(() => undefined);
}

export async function pushPermission(): Promise<PushPermission> {
  const status = (await Notifications.getPermissionsAsync().catch(() => null))?.status;
  return status === 'granted' ? 'granted' : status === 'denied' ? 'denied' : 'undetermined';
}

/** اجازه (اندروید ۱۳+) + توکن FCM + ثبت روی سرور برای همین نشست. false = اجازه نداد یا توکن نشد. */
export async function registerForPush(api: ApiClient, askPermission: boolean): Promise<boolean> {
  await configurePush();

  let permission = await pushPermission();
  if (permission !== 'granted' && askPermission) {
    permission = (await Notifications.requestPermissionsAsync()).status === 'granted' ? 'granted' : 'denied';
  }
  if (permission !== 'granted') {
    return false;
  }

  try {
    const token = await Notifications.getDevicePushTokenAsync();
    await api.put('/push/device', {
      provider: 'fcm',
      token: String(token.data),
      app_version: APP_VERSION,
      manufacturer: Device.manufacturer ?? null,
      model: Device.modelName ?? null,
      os_version: Device.osVersion ?? null,
    });
    await flushReceipts();

    return true;
  } catch {
    return false;
  }
}

function openFromResponse(response: Notifications.NotificationResponse) {
  const data = parsePushData(response.notification.request.content.data);
  if (data.messageId) {
    void sendReceipts([data.messageId], true);
  }
  router.push(routeFor(APP_VARIANT, data) as never);
}

/**
 * در layout هر دو اپ (داخل SessionProvider): بعد از ورود ثبت گوشی (اجازه را می‌پرسد)، تازه شدن توکن FCM، رسید پیام‌هایی که با
 * اپ باز می‌رسند، و لمس اعلان (حتی وقتی اپ با همان لمس باز شد).
 */
export function usePushSetup(api: ApiClient, signedIn: boolean): void {
  const handledLaunch = useRef(false);

  useEffect(() => {
    if (!signedIn) {
      return;
    }

    void registerForPush(api, true);

    const tokenSub = Notifications.addPushTokenListener(() => {
      void registerForPush(api, false);
    });
    const receivedSub = Notifications.addNotificationReceivedListener((notification) => {
      const data = parsePushData(notification.request.content.data);
      if (data.messageId) {
        void sendReceipts([data.messageId]);
      }
    });
    const responseSub = Notifications.addNotificationResponseReceivedListener(openFromResponse);

    if (!handledLaunch.current) {
      handledLaunch.current = true;
      let response: Notifications.NotificationResponse | null = null;
      try {
        response = Notifications.getLastNotificationResponse();
      } catch {
        response = null;
      }
      if (response) {
        openFromResponse(response);
        // تا اجرای بعدی اپ دوباره همان صفحه را باز نکند
        Notifications.clearLastNotificationResponse();
      }
    }

    return () => {
      tokenSub.remove();
      receivedSub.remove();
      responseSub.remove();
    };
  }, [api, signedIn]);
}
