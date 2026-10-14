import { useEffect, useState } from 'react';

import { useProfile, useUpdateProfile } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Button, ErrorBox, Field, Loading, Notice, Screen, T } from '@/ui/kit';

/** فقط نام عوض می‌شود (مثل وب؛ شماره هویت حساب در این سالن است). */
export default function Profile() {
  const profile = useProfile();
  const update = useUpdateProfile();
  const [name, setName] = useState('');

  useEffect(() => {
    if (profile.data) {
      setName(profile.data.name);
    }
  }, [profile.data]);

  if (profile.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (profile.error) {
    return <Screen><ErrorBox error={profile.error} onRetry={() => profile.refetch()} /></Screen>;
  }

  return (
    <Screen>
      <Field label="نام و نام خانوادگی" value={name} onChangeText={setName} />
      <T muted>شماره موبایل: {faDigits(profile.data.phone)} (قابل تغییر نیست)</T>
      {update.isSuccess ? <Notice tone="success" text="ذخیره شد." /> : null}
      {update.error ? <ErrorBox error={update.error} /> : null}
      <Button title="ذخیره" onPress={() => update.mutate(name.trim())} loading={update.isPending} disabled={!name.trim() || name.trim() === profile.data.name} />
    </Screen>
  );
}
