import { router } from 'expo-router';
import { useState } from 'react';

import { createClient } from '@/lib/api';
import { APP_VERSION, DEFAULT_API_URL } from '@/lib/config';
import { useSession } from '@/lib/session';
import { Button, ErrorBox, Field, Notice, Screen, T } from '@/ui/kit';

/** فقط نسخه‌ی آزمایشی: آدرس سرور لاراول (مثلاً XAMPP روی شبکه‌ی محلی) — با GET /api/v1/status امتحان می‌شود */
export default function Server() {
  const { serverUrl, setServerUrl } = useSession();
  const [url, setUrl] = useState(serverUrl);
  const [busy, setBusy] = useState(false);
  const [result, setResult] = useState<string | null>(null);
  const [error, setError] = useState<unknown>(null);

  const test = async (save: boolean) => {
    const candidate = url.trim().replace(/\/+$/, '');
    setBusy(true);
    setError(null);
    setResult(null);
    try {
      const client = createClient({ baseUrl: () => candidate, token: () => null, appVersion: APP_VERSION });
      const { data } = await client.get<{ api_version: string; server_time: string }>('/status');
      setResult(`سرور پاسخ داد (${data.api_version}، ساعت سرور ${data.server_time}).`);
      if (save) {
        await setServerUrl(candidate);
        router.back();
      }
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <T muted>آدرس پروژه‌ی لاراول، مثلاً http://192.168.1.5:8000 (گوشی و سیستم روی یک Wi‑Fi؛ php artisan serve --host=0.0.0.0).</T>
      <Field label="آدرس سرور" value={url} onChangeText={setUrl} autoCapitalize="none" autoCorrect={false} keyboardType="url" style={{ textAlign: 'left' }} />
      {result ? <Notice tone="success" text={result} /> : null}
      {error ? <ErrorBox error={error} /> : null}
      <Button title="امتحان و ذخیره" onPress={() => test(true)} loading={busy} />
      <Button kind="secondary" title="فقط امتحان" onPress={() => test(false)} disabled={busy} />
      <Button
        kind="ghost"
        title={`برگشت به پیش‌فرض (${DEFAULT_API_URL})`}
        onPress={async () => {
          await setServerUrl(null);
          router.back();
        }}
      />
    </Screen>
  );
}
