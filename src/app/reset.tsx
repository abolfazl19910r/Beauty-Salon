import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { ApiError } from '@/lib/api';
import { enDigits, faDigits } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { useCountdown } from '@/lib/useCountdown';
import { Button, ErrorBox, Field, Screen, T } from '@/ui/kit';

export default function Reset() {
  const params = useLocalSearchParams<{ challenge: string; phone_hint: string; resend_after: string }>();
  const { api } = useSession();
  const [challenge, setChallenge] = useState(params.challenge);
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const countdown = useCountdown(Number(params.resend_after ?? 60));
  const apiError = error instanceof ApiError ? error : null;

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      await api.post('/staff/password/reset', { challenge, code: enDigits(code), password, password_confirmation: confirm }, false);
      Alert.alert('رمز عوض شد', 'با رمز تازه وارد شوید. همه‌ی دستگاه‌ها خارج شدند.');
      router.replace('/login');
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  const resend = async () => {
    try {
      const { data } = await api.post<Challenge>('/staff/password/resend', { challenge }, false);
      setChallenge(data.challenge);
      countdown.restart(data.resend_after);
    } catch (e) {
      setError(e);
    }
  };

  return (
    <Screen>
      <T>کد پیامک‌شده به {faDigits(params.phone_hint ?? '')} و رمز تازه را وارد کنید.</T>
      <Field label="کد بازیابی" value={code} onChangeText={(v) => setCode(enDigits(v).replace(/\D/g, '').slice(0, 6))} keyboardType="number-pad" maxLength={6} error={apiError?.field('code')} />
      <Field label="رمز تازه" value={password} onChangeText={setPassword} secureTextEntry hint="حداقل ۸ کاراکتر" error={apiError?.field('password')} />
      <Field label="تکرار رمز تازه" value={confirm} onChangeText={setConfirm} secureTextEntry />
      {error && !apiError?.fields ? <ErrorBox error={error} /> : null}
      <Button title="ذخیره‌ی رمز" onPress={submit} loading={busy} disabled={code.length !== 6 || password.length < 8 || password !== confirm} />
      <Button kind="ghost" title={countdown.left > 0 ? `ارسال دوباره تا ${faDigits(countdown.left)} ثانیه` : 'ارسال دوباره‌ی کد'} onPress={resend} disabled={countdown.left > 0} />
    </Screen>
  );
}
