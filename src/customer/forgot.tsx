import { Redirect, router } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/lib/api';
import { useCustomerSalon } from '@/lib/customer/salon';
import { isValidPhone, normalizePhone } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { Button, ErrorBox, Field, Screen, T } from '@/ui/kit';
import { PHONE_ERROR } from '@/lib/customer/messages';

export default function Forgot() {
  const { api } = useSession();
  const { salon } = useCustomerSalon();
  const [phone, setPhone] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  if (!salon) {
    return <Redirect href="/welcome" />;
  }

  const submit = async () => {
    const normalized = normalizePhone(phone);
    if (!isValidPhone(normalized)) {
      setError(new ApiError('validation_failed', PHONE_ERROR, 422));
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<Challenge>(`/customer/salons/${encodeURIComponent(salon.slug)}/password/forgot`, { phone: normalized }, false);
      router.replace({ pathname: '/reset', params: { challenge: data.challenge, phone_hint: data.phone_hint, resend_after: String(data.resend_after) } });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <T>شماره موبایلی که با آن در «{salon.name}» ثبت‌نام کرده‌اید را وارد کنید تا کد بازیابی پیامک شود.</T>
      <Field label="شماره موبایل" value={phone} onChangeText={setPhone} keyboardType="phone-pad" maxLength={14} />
      {error ? <ErrorBox error={error} /> : null}
      <Button title="فرستادن کد" onPress={submit} loading={busy} disabled={!phone} />
    </Screen>
  );
}
