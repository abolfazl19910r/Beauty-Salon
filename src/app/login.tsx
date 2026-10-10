import { Redirect, router } from 'expo-router';
import { useRef, useState } from 'react';
import { Image, Pressable, View } from 'react-native';

import { ApiError } from '@/lib/api';
import { ALLOW_SERVER_OVERRIDE } from '@/lib/config';
import { isValidPhone, normalizePhone } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { Button, ErrorBox, Field, Notice, Screen, T } from '@/ui/kit';
import { space } from '@/ui/theme';

export default function Login() {
  const { api, status, serverUrl } = useSession();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const taps = useRef<number[]>([]);

  if (status === 'signedIn') {
    return <Redirect href="/" />;
  }

  // پنج ضربه در سه ثانیه روی لوگو → آدرس سرور (فقط نسخه‌ی آزمایشی)
  const onLogoTap = () => {
    if (!ALLOW_SERVER_OVERRIDE) {
      return;
    }
    const now = Date.now();
    taps.current = [...taps.current.filter((t) => now - t < 3000), now];
    if (taps.current.length >= 5) {
      taps.current = [];
      router.push('/server');
    }
  };

  const submit = async () => {
    const normalized = normalizePhone(phone);
    if (!isValidPhone(normalized)) {
      setError(new ApiError('validation_failed', 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.', 422));
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<Challenge>('/staff/login', { phone: normalized, password }, false);
      router.push({ pathname: '/verify', params: { ...data, expires_in: String(data.expires_in), code_expires_in: String(data.code_expires_in), resend_after: String(data.resend_after) } });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <View style={{ alignItems: 'center', gap: space(2), marginTop: space(10), marginBottom: space(4) }}>
        <Pressable onPress={onLogoTap} accessibilityLabel="ماهرو">
          <Image source={require('../../assets/icon.png')} style={{ width: 88, height: 88, borderRadius: 20 }} />
        </Pressable>
        <T weight="bold" size={22}>ماهرو همکار</T>
        <T muted>ورود متخصص‌های سالن</T>
      </View>

      <Field label="شماره موبایل" value={phone} onChangeText={setPhone} keyboardType="phone-pad" autoComplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" maxLength={14} />
      <Field label="رمز عبور" value={password} onChangeText={setPassword} secureTextEntry autoComplete="password" onSubmitEditing={submit} />
      {error ? <ErrorBox error={error} /> : null}
      <Button title="ادامه" onPress={submit} loading={busy} disabled={!phone || !password} />
      <Button kind="ghost" title="رمز را فراموش کرده‌ام" onPress={() => router.push('/forgot')} />
      {ALLOW_SERVER_OVERRIDE ? <Notice text={`سرور: ${serverUrl}`} /> : null}
    </Screen>
  );
}
