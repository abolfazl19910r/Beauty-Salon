import { useState } from 'react';
import { Alert } from 'react-native';

import { ApiError } from '@/lib/api';
import { useSession } from '@/lib/session';
import { Button, ErrorBox, Field, Screen, T } from '@/ui/kit';

/** تغییر رمز؛ سرور همه‌ی توکن‌ها (از جمله همین گوشی) را باطل می‌کند، پس اپ دوباره به ورود می‌رود. */
export default function Password() {
  const { api, signOut } = useSession();
  const [current, setCurrent] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const apiError = error instanceof ApiError ? error : null;

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      await api.put('/customer/password', { current_password: current, password, password_confirmation: confirm });
      Alert.alert('رمز عوض شد', 'با رمز تازه دوباره وارد شوید. همه‌ی دستگاه‌ها خارج شدند.');
      await signOut();
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <Field label="رمز فعلی" value={current} onChangeText={setCurrent} secureTextEntry error={apiError?.field('current_password')} />
      <Field label="رمز تازه" value={password} onChangeText={setPassword} secureTextEntry hint="حداقل ۸ کاراکتر" error={apiError?.field('password')} />
      <Field label="تکرار رمز تازه" value={confirm} onChangeText={setConfirm} secureTextEntry />
      <T muted size={13}>بعد از تغییر رمز، از همه‌ی دستگاه‌ها (از جمله همین گوشی) خارج می‌شوید.</T>
      {error && !apiError?.fields ? <ErrorBox error={error} /> : null}
      <Button title="تغییر رمز" onPress={submit} loading={busy} disabled={!current || password.length < 8 || password !== confirm} />
    </Screen>
  );
}
