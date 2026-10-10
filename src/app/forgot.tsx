import { router } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/lib/api';
import { isValidPhone, normalizePhone } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { Button, ErrorBox, Field, Screen, T } from '@/ui/kit';

export default function Forgot() {
  const { api } = useSession();
  const [phone, setPhone] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const submit = async () => {
    const normalized = normalizePhone(phone);
    if (!isValidPhone(normalized)) {
      setError(new ApiError('validation_failed', 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.', 422));
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<Challenge>('/staff/password/forgot', { phone: normalized }, false);
      router.replace({ pathname: '/reset', params: { challenge: data.challenge, phone_hint: data.phone_hint, resend_after: String(data.resend_after) } });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <T>شماره موبایل حساب همکار را وارد کنید تا کد بازیابی پیامک شود.</T>
      <Field label="شماره موبایل" value={phone} onChangeText={setPhone} keyboardType="phone-pad" maxLength={14} />
      {error ? <ErrorBox error={error} /> : null}
      <Button title="فرستادن کد" onPress={submit} loading={busy} disabled={!phone} />
    </Screen>
  );
}
