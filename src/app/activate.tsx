import { router } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/lib/api';
import { isValidPhone, normalizePhone } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { Button, ErrorBox, Field, Notice, Screen } from '@/ui/kit';

/**
 * فعال‌سازی حساب متخصص (۲۰۲۶-۱۰-۲۲): شماره‌ای که مدیر سالن در «متخصصین» ثبت کرده و هنوز حساب ندارد ← رمز ← کد پیامکی (صفحه‌ی verify با
 * flow=activate) ← وارد اپ. سرور: POST /staff/activate.
 */
export default function Activate() {
  const { api } = useSession();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const submit = async () => {
    const normalized = normalizePhone(phone);
    if (!isValidPhone(normalized)) {
      setError(new ApiError('validation_failed', 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.', 422));
      return;
    }
    if (password.length < 8) {
      setError(new ApiError('validation_failed', 'رمز عبور حداقل ۸ حرف باشد.', 422));
      return;
    }
    if (password !== confirmation) {
      setError(new ApiError('validation_failed', 'تکرار رمز با رمز یکی نیست.', 422));
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<Challenge>('/staff/activate', { phone: normalized, password, password_confirmation: confirmation }, false);
      router.push({ pathname: '/verify', params: { ...data, flow: 'activate', expires_in: String(data.expires_in), code_expires_in: String(data.code_expires_in), resend_after: String(data.resend_after) } });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <Notice text="با همان شماره‌ای که مدیر سالن برای شما ثبت کرده؛ یک رمز برای ورودهای بعد انتخاب کنید." />
      <Field label="شماره موبایل" value={phone} onChangeText={setPhone} keyboardType="phone-pad" autoComplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" maxLength={14} />
      <Field label="رمز عبور (حداقل ۸ حرف)" value={password} onChangeText={setPassword} secureTextEntry autoComplete="new-password" />
      <Field label="تکرار رمز عبور" value={confirmation} onChangeText={setConfirmation} secureTextEntry autoComplete="new-password" onSubmitEditing={submit} />
      {error ? <ErrorBox error={error} /> : null}
      <Button title="ارسال کد تأیید" onPress={submit} loading={busy} disabled={!phone || !password || !confirmation} />
    </Screen>
  );
}
