import { Redirect, router } from 'expo-router';
import { useState } from 'react';
import { Alert, View } from 'react-native';

import { ApiError } from '@/lib/api';
import { useCustomerSalon } from '@/lib/customer/salon';
import { isValidPhone, normalizePhone } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { PHONE_ERROR } from '@/lib/customer/messages';
import { SalonHeader } from '@/ui/customer';
import { Button, ErrorBox, Field, Screen } from '@/ui/kit';
import { space } from '@/ui/theme';

/** ورود مشتری به سالن ذخیره‌شده (رمز ← کد پیامکی ← توکن). حساب مشتری در هر سالن جداست. */
export default function Login() {
  const { api, status } = useSession();
  const { salon, setSalon } = useCustomerSalon();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  if (status === 'signedIn') {
    return <Redirect href="/" />;
  }
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
      const { data } = await api.post<Challenge>(`/customer/salons/${encodeURIComponent(salon.slug)}/login`, { phone: normalized, password }, false);
      router.push({ pathname: '/verify', params: { mode: 'login', challenge: data.challenge, phone_hint: data.phone_hint, resend_after: String(data.resend_after) } });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  const otherSalon = () =>
    Alert.alert('ورود به سالن دیگر؟', `«${salon.name}» از اپ برداشته می‌شود و می‌توانید لینک، QR یا کد سالن دیگری را وارد کنید.`, [
      { text: 'انصراف', style: 'cancel' },
      { text: 'سالن دیگر', onPress: async () => { await setSalon(null); router.replace('/welcome'); } },
    ]);

  return (
    <Screen>
      <View style={{ marginTop: space(10), marginBottom: space(4) }}>
        <SalonHeader salon={salon} />
      </View>
      <Field label="شماره موبایل" value={phone} onChangeText={setPhone} keyboardType="phone-pad" autoComplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" maxLength={14} />
      <Field label="رمز عبور" value={password} onChangeText={setPassword} secureTextEntry autoComplete="password" onSubmitEditing={submit} />
      {error ? <ErrorBox error={error} /> : null}
      <Button title="ورود" onPress={submit} loading={busy} disabled={!phone || !password} />
      <Button kind="secondary" title="حساب ندارم — ثبت‌نام" onPress={() => router.push('/register')} />
      <Button kind="ghost" title="رمز را فراموش کرده‌ام" onPress={() => router.push('/forgot')} />
      <Button kind="ghost" title="ورود به سالن دیگر" onPress={otherSalon} />
    </Screen>
  );
}
