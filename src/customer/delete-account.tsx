import { useState } from 'react';
import { Alert } from 'react-native';

import { useSession } from '@/lib/session';
import { Button, Card, ErrorBox, Field, Screen, T } from '@/ui/kit';

/**
 * حذف حساب مشتری (تصمیم ۲۰۲۶-۱۰-۱۱ «الف»): با نوبت فعال آینده یا موجودی کیف پول رد می‌شود؛ بی‌سابقه → حذف کامل؛
 * با سابقه → اطلاعات شخصی پاک و نوبت‌ها برای حساب‌وکتاب سالن می‌مانند. توکن‌ها روی سرور پاک می‌شوند.
 */
export default function DeleteAccount() {
  const { api, account, signOut } = useSession();
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const remove = async () => {
    setBusy(true);
    setError(null);
    try {
      await api.request('DELETE', '/customer/account', { body: { password } });
      await signOut();
      Alert.alert('حساب حذف شد', 'هر وقت خواستید می‌توانید با همین شماره دوباره ثبت‌نام کنید.');
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  const ask = () =>
    Alert.alert('حساب حذف شود؟', 'این کار برگشت ندارد.', [
      { text: 'انصراف', style: 'cancel' },
      { text: 'حذف حساب', style: 'destructive', onPress: () => void remove() },
    ]);

  return (
    <Screen>
      <Card>
        <T weight="bold">حذف حساب در «{account?.salon.name}»</T>
        <T muted size={14}>• اگر نوبت فعال آینده یا موجودی کیف پول دارید، اول نوبت را لغو و موجودی را خرج کنید (یا با سالن تماس بگیرید).</T>
        <T muted size={14}>• نام و شماره‌ی شما پاک می‌شود؛ سابقه‌ی نوبت‌ها بی‌نام برای حساب‌وکتاب سالن می‌ماند.</T>
        <T muted size={14}>• امتیازها و کدهای تخفیف دیگر قابل استفاده نیستند، اعلان‌ها پاک می‌شوند و از همه‌ی دستگاه‌ها خارج می‌شوید.</T>
      </Card>
      <Field label="رمز عبور برای تأیید" value={password} onChangeText={setPassword} secureTextEntry />
      {error ? <ErrorBox error={error} /> : null}
      <Button kind="danger" title="حذف حساب" onPress={ask} loading={busy} disabled={!password} />
    </Screen>
  );
}
