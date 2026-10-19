import * as Device from 'expo-device';
import { useCallback, useEffect, useState } from 'react';
import { AppState, Linking } from 'react-native';

import { errorMessage } from '@/lib/api';
import { jalaliLabel } from '@/lib/jalali';
import { backgroundOutcomeLabel, deviceGuide } from '@/lib/push';
import { lastBackgroundRun, type BackgroundRun } from '@/lib/pushReceipts';
import { pushPermission, registerForPush, type PushPermission } from '@/lib/pushRuntime';
import { useApi } from '@/lib/session';
import { Badge, Button, Card, KeyValue, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';

type DeviceStatus = { registered: boolean; server_enabled: boolean; last_receipt_at: string | null; disabled_reason: string | null };

/**
 * «دریافت اعلان» — مشترک دو اپ (بسته‌ی ۴): وضعیت اجازه و ثبت گوشی، اعلان آزمایشی، و راهنمای همان سازنده (Autostart شیائومی،
 * Deep sleeping سامسونگ — یافته‌های بسته‌ی ۰).
 */
export function PushSetupScreen() {
  const api = useApi();
  const [permission, setPermission] = useState<PushPermission | null>(null);
  const [status, setStatus] = useState<DeviceStatus | null>(null);
  const [backgroundRun, setBackgroundRun] = useState<BackgroundRun | null>(null);
  const [busy, setBusy] = useState<'register' | 'test' | null>(null);
  const [message, setMessage] = useState<{ text: string; tone: 'info' | 'success' | 'warning' } | null>(null);
  const guide = deviceGuide(Device.manufacturer);

  const refresh = useCallback(async () => {
    setPermission(await pushPermission());
    setBackgroundRun(await lastBackgroundRun());
    try {
      setStatus((await api.get<DeviceStatus>('/push/device')).data);
    } catch {
      setStatus(null);
    }
  }, [api]);

  useEffect(() => {
    void refresh();
    // برگشت از تنظیمات گوشی
    const sub = AppState.addEventListener('change', (state) => state === 'active' && void refresh());
    return () => sub.remove();
  }, [refresh]);

  const register = async () => {
    setBusy('register');
    setMessage(null);
    const ok = await registerForPush(api, true);
    await refresh();
    setBusy(null);
    if (!ok && (await pushPermission()) === 'denied') {
      setMessage({ text: 'اجازه‌ی اعلان داده نشده است. از تنظیمات گوشی ← اعلان‌ها، برای ماهرو روشنش کنید.', tone: 'warning' });
    } else if (!ok) {
      setMessage({ text: 'ثبت گوشی نشد؛ اینترنت را بررسی و دوباره امتحان کنید.', tone: 'warning' });
    }
  };

  const test = async () => {
    setBusy('test');
    setMessage(null);
    try {
      await api.post('/push/test');
      setMessage({ text: 'اعلان آزمایشی فرستاده شد؛ باید تا چند ثانیه برسد. اگر نرسید، راهنمای پایین را انجام دهید.', tone: 'success' });
      setTimeout(() => void refresh(), 8000);
    } catch (error) {
      setMessage({ text: errorMessage(error), tone: 'warning' });
    } finally {
      setBusy(null);
    }
  };

  const ready = permission === 'granted' && status?.registered;

  return (
    <Screen>
      <Notice text="اعلان‌های نوبت (لغو، تغییر زمان، یادآوری) در خود اپ می‌آید؛ اگر اپ اعلان را تحویل نگیرد، همان پیام با پیامک فرستاده می‌شود." />

      <Card>
        <Row style={{ justifyContent: 'space-between' }}>
          <T weight="bold">وضعیت این گوشی</T>
          <Badge text={ready ? 'آماده' : 'ناقص'} tone={ready ? 'success' : 'warning'} />
        </Row>
        <KeyValue label="اجازه‌ی اعلان" value={permission === 'granted' ? 'داده شده' : permission === 'denied' ? 'داده نشده' : '—'} />
        <KeyValue label="ثبت در سرور" value={status?.registered ? 'ثبت شده' : status?.disabled_reason ? 'غیرفعال (اپ را دوباره ثبت کنید)' : 'ثبت نشده'} />
        <KeyValue label="آخرین اعلان تحویل‌شده" value={status?.last_receipt_at ? jalaliLabel(new Date(status.last_receipt_at)) : '—'} />
        <KeyValue
          label="آخرین اجرای پس‌زمینه"
          value={backgroundRun ? `${jalaliLabel(new Date(backgroundRun.at))} — ${backgroundOutcomeLabel(backgroundRun.outcome)}` : 'هنوز نه'}
        />
        {status && !status.server_enabled ? <T muted size={13}>ارسال اعلان روی سرور فعلاً خاموش است؛ پیام‌ها پیامک می‌شوند.</T> : null}
      </Card>

      {message ? <Notice text={message.text} tone={message.tone} /> : null}

      {!ready ? <Button title="اجازه و ثبت این گوشی" loading={busy === 'register'} onPress={register} /> : null}
      {permission === 'denied' ? <Button kind="secondary" title="باز کردن تنظیمات اپ" onPress={() => void Linking.openSettings()} /> : null}
      <Button kind={ready ? 'primary' : 'secondary'} title="اعلان آزمایشی" disabled={!status?.registered} loading={busy === 'test'} onPress={test} />

      <SectionTitle>{guide.title}</SectionTitle>
      <Card>
        {guide.steps.map((step, i) => (
          <T key={i}>{`${i + 1}. ${step}`}</T>
        ))}
        <Button small kind="secondary" title="باز کردن تنظیمات اپ" onPress={() => void Linking.openSettings()} />
      </Card>
    </Screen>
  );
}
