import { Redirect, router } from 'expo-router';
import { useState } from 'react';
import { Linking, Switch } from 'react-native';

import { ApiError } from '@/lib/api';
import { useCustomerSalon } from '@/lib/customer/salon';
import { isValidPhone, normalizePhone } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { Challenge } from '@/lib/types';
import { Button, ErrorBox, Field, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';
import { PHONE_ERROR } from '@/lib/customer/messages';
import { legalUrl } from '@/lib/legal';

/** ثبت‌نام در سالن ذخیره‌شده؛ حساب فقط بعد از کد پیامکی ساخته می‌شود (تصمیم ۲۰۲۶-۱۰-۱۱). */
export default function Register() {
  const { api, serverUrl } = useSession();
  const p = usePalette();
  const [accepted, setAccepted] = useState(false);
  const { salon } = useCustomerSalon();
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const apiError = error instanceof ApiError ? error : null;

  if (!salon) {
    return <Redirect href="/welcome" />;
  }

  const submit = async () => {
    const normalized = normalizePhone(phone);
    if (!isValidPhone(normalized)) {
      setError(new ApiError('validation_failed', PHONE_ERROR, 422, { phone: [PHONE_ERROR] }));
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<Challenge>(
        `/customer/salons/${encodeURIComponent(salon.slug)}/register`,
        { name: name.trim(), phone: normalized, password, password_confirmation: confirm, accept_terms: true },
        false,
      );
      router.push({ pathname: '/verify', params: { mode: 'register', challenge: data.challenge, phone_hint: data.phone_hint, resend_after: String(data.resend_after) } });
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <T muted>حساب شما فقط در «{salon.name}» است.</T>
      <Field label="نام و نام خانوادگی" value={name} onChangeText={setName} autoComplete="name" error={apiError?.field('name')} />
      <Field label="شماره موبایل" value={phone} onChangeText={setPhone} keyboardType="phone-pad" autoComplete="tel" maxLength={14} error={apiError?.field('phone')} />
      <Field label="رمز عبور" value={password} onChangeText={setPassword} secureTextEntry autoComplete="new-password" hint="حداقل ۸ کاراکتر" error={apiError?.field('password')} />
      <Field label="تکرار رمز" value={confirm} onChangeText={setConfirm} secureTextEntry error={confirm && confirm !== password ? 'تکرار رمز یکی نیست.' : undefined} />
      {/* بسته‌ی مجوز 5د: پذیرش قوانین و حریم خصوصی پیش از ساخت حساب (سرور نسخه، زمان و IP را ثبت می‌کند) */}
      <Row style={{ alignItems: 'center' }}>
        <Switch value={accepted} onValueChange={setAccepted} trackColor={{ true: p.gold }} accessibilityLabel="پذیرش قوانین و حریم خصوصی" testID="accept-terms" />
        <T size={14} style={{ flexShrink: 1 }}>
          <T size={14} color={p.gold} onPress={() => Linking.openURL(legalUrl(serverUrl, 'terms'))}>قوانین و شرایط</T>
          {' و '}
          <T size={14} color={p.gold} onPress={() => Linking.openURL(legalUrl(serverUrl, 'privacy'))}>حریم خصوصی</T>
          {' را خواندم و می‌پذیرم.'}
        </T>
      </Row>
      {apiError?.field('accept_terms') ? <T size={13} color={p.danger}>{apiError.field('accept_terms')}</T> : null}
      {error && !apiError?.fields ? <ErrorBox error={error} /> : null}
      <Button title="فرستادن کد تأیید" onPress={submit} loading={busy} disabled={!accepted || !name.trim() || !phone || password.length < 8 || password !== confirm} />
    </Screen>
  );
}
