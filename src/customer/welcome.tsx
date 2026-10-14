import * as Clipboard from 'expo-clipboard';
import { Redirect, router } from 'expo-router';
import { useRef, useState } from 'react';
import { Image, Pressable, View } from 'react-native';

import { ApiError } from '@/lib/api';
import { ALLOW_SERVER_OVERRIDE } from '@/lib/config';
import { parseSalonInput } from '@/lib/customer/salonLink';
import { useOpenSalon } from '@/lib/customer/useOpenSalon';
import { useSession } from '@/lib/session';
import { Button, ErrorBox, Field, Notice, Screen, T } from '@/ui/kit';
import { space } from '@/ui/theme';

/** «ورود به سالن»: اسکن QR، لینک یا کد سالن (= slug). اپ فقط یک سالن نگه می‌دارد. */
export default function Welcome() {
  const { status, serverUrl } = useSession();
  const { open, busy, error, setError } = useOpenSalon();
  const [input, setInput] = useState('');
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

  const submit = async (value = input) => {
    const parsed = parseSalonInput(value);
    if (!parsed) {
      setError(new ApiError('invalid_salon', 'این لینک یا کد سالن درست نیست. کد را همان‌طور که سالن داده وارد کنید.', 422));
      return;
    }
    await open(parsed);
  };

  const paste = async () => {
    const text = await Clipboard.getStringAsync();
    if (text) {
      setInput(text.trim());
      await submit(text);
    }
  };

  return (
    <Screen>
      <View style={{ alignItems: 'center', gap: space(2), marginTop: space(10), marginBottom: space(4) }}>
        <Pressable onPress={onLogoTap} accessibilityLabel="ماهرو">
          <Image source={require('../../assets/customer/icon.png')} style={{ width: 96, height: 96, borderRadius: 22 }} />
        </Pressable>
        <T weight="bold" size={24}>ماهرو</T>
        <T muted center>نوبت آنلاین سالن زیبایی</T>
      </View>

      <T center>برای شروع، QR سالن را اسکن کنید یا لینک یا کد سالن را وارد کنید.</T>
      <Button title="اسکن QR سالن" onPress={() => router.push('/scan')} />

      <Field
        label="لینک یا کد سالن"
        value={input}
        onChangeText={setInput}
        autoCapitalize="none"
        autoCorrect={false}
        placeholder="مثلاً rasta"
        onSubmitEditing={() => submit()}
        style={{ textAlign: 'left' }}
      />
      {error ? <ErrorBox error={error} /> : null}
      <Button kind="secondary" title="ادامه" onPress={() => submit()} loading={busy} disabled={!input.trim()} />
      <Button kind="ghost" title="چسباندن لینک کپی‌شده" onPress={paste} disabled={busy} />
      {ALLOW_SERVER_OVERRIDE ? <Notice text={`سرور: ${serverUrl}`} /> : null}
    </Screen>
  );
}
