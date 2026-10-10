import * as Device from 'expo-device';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';

import { ApiError } from '@/lib/api';
import { enDigits, faDigits } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge, TokenResponse } from '@/lib/types';
import { useCountdown } from '@/lib/useCountdown';
import { Button, ErrorBox, Field, Notice, Screen, T } from '@/ui/kit';

export default function Verify() {
  const params = useLocalSearchParams<{ challenge: string; phone_hint: string; resend_after: string }>();
  const { api, signIn } = useSession();
  const [challenge, setChallenge] = useState(params.challenge);
  const [code, setCode] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const [info, setInfo] = useState<string | null>(null);
  const countdown = useCountdown(Number(params.resend_after ?? 60));

  const restartLogin = (e: unknown) => e instanceof ApiError && ['challenge_expired', 'too_many_code_attempts', 'resend_limit_reached'].includes(e.code);

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<TokenResponse>(
        '/staff/login/verify',
        { challenge, code: enDigits(code), device_name: Device.modelName ?? 'Android' },
        false,
      );
      await signIn(data);
      router.replace('/');
    } catch (e) {
      setError(e);
      setCode('');
    } finally {
      setBusy(false);
    }
  };

  const resend = async () => {
    setError(null);
    try {
      const { data } = await api.post<Challenge>('/staff/login/resend', { challenge }, false);
      setChallenge(data.challenge);
      countdown.restart(data.resend_after);
      setInfo('کد تازه فرستاده شد.');
    } catch (e) {
      setError(e);
    }
  };

  return (
    <Screen>
      <T>کد ۶ رقمی پیامک‌شده به {faDigits(params.phone_hint ?? '')} را وارد کنید.</T>
      <Field label="کد تأیید" value={code} onChangeText={(v) => setCode(enDigits(v).replace(/\D/g, '').slice(0, 6))} keyboardType="number-pad" autoComplete="sms-otp" textContentType="oneTimeCode" maxLength={6} autoFocus onSubmitEditing={submit} />
      {info ? <Notice tone="success" text={info} /> : null}
      {error ? <ErrorBox error={error} /> : null}
      {restartLogin(error) ? (
        <Button kind="secondary" title="ورود از اول" onPress={() => router.back()} />
      ) : (
        <>
          <Button title="ورود" onPress={submit} loading={busy} disabled={code.length !== 6} />
          <Button
            kind="ghost"
            title={countdown.left > 0 ? `ارسال دوباره تا ${faDigits(countdown.left)} ثانیه` : 'ارسال دوباره‌ی کد'}
            onPress={resend}
            disabled={countdown.left > 0}
          />
        </>
      )}
    </Screen>
  );
}
