import { useEffect, useState } from 'react';
import { Switch, View } from 'react-native';

import { useProfile, useUpdateProfile } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Button, Card, ErrorBox, Field, Loading, Notice, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

/** نام و پیامک‌های پیگیری سالن (مثل وب؛ شماره هویت حساب در این سالن است و عوض نمی‌شود). */
export default function Profile() {
  const profile = useProfile();
  const update = useUpdateProfile();
  const marketing = useUpdateProfile();
  const p = usePalette();
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
      <Button title="ذخیره" onPress={() => update.mutate({ name: name.trim() })} loading={update.isPending} disabled={!name.trim() || name.trim() === profile.data.name} />

      <Card>
        <Row style={{ justifyContent: 'space-between' }}>
          <View style={{ flex: 1 }}>
            <T weight="medium">پیامک‌های پیگیری و پیشنهاد سالن</T>
            <T muted size={13}>مثلاً وقتی مدتی سر نزده‌اید، با کد تخفیف. پیامک‌های تأیید و یادآوری نوبت همیشه می‌رسد.</T>
          </View>
          <Switch
            value={profile.data.marketing_sms}
            onValueChange={(on) => marketing.mutate({ marketing_sms: on })}
            disabled={marketing.isPending}
            trackColor={{ true: p.gold }}
            accessibilityLabel="پیامک‌های پیگیری و پیشنهاد سالن"
          />
        </Row>
      </Card>
      {marketing.error ? <ErrorBox error={marketing.error} /> : null}
    </Screen>
  );
}
