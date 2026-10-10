import { router } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { ApiError } from '@/lib/api';
import { enDigits } from '@/lib/format';
import { useWalletMutations } from '@/lib/queries';
import { Button, ErrorBox, Field, Notice, Screen } from '@/ui/kit';

export default function Iban() {
  const { iban } = useWalletMutations();
  const [number, setNumber] = useState('');
  const [holder, setHolder] = useState('');
  const [bank, setBank] = useState('');
  const [password, setPassword] = useState('');
  const apiError = iban.error instanceof ApiError ? iban.error : null;
  const digits = enDigits(number).replace(/\D/g, '');

  return (
    <Screen>
      <Notice tone="warning" text="شبای تازه تا تأیید مالک سالن «تأییدنشده» است و تسویه‌ی خودکار ندارد. مالک از این تغییر باخبر می‌شود." />
      <Field label="شماره شبا (۲۴ رقم بدون IR)" value={number} onChangeText={setNumber} keyboardType="number-pad" maxLength={30} style={{ textAlign: 'left' }} error={apiError?.field('iban')} />
      <Field label="نام صاحب حساب" value={holder} onChangeText={setHolder} error={apiError?.field('account_holder_name')} />
      <Field label="نام بانک" value={bank} onChangeText={setBank} error={apiError?.field('bank_name')} />
      <Field label="رمز عبور فعلی" value={password} onChangeText={setPassword} secureTextEntry hint="برای امنیت؛ گوشی گم‌شده نباید برای عوض کردن شبا کافی باشد." error={apiError?.field('current_password')} />
      {iban.error && !apiError?.fields ? <ErrorBox error={iban.error} /> : null}
      <Button
        title="ذخیره‌ی شبا"
        loading={iban.isPending}
        disabled={digits.length !== 24 || holder.trim().length < 3 || !bank.trim() || !password}
        onPress={() =>
          iban.mutate(
            { iban: digits, account_holder_name: holder.trim(), bank_name: bank.trim(), current_password: password },
            {
              onSuccess: () => {
                Alert.alert('شبا ثبت شد', 'منتظر تأیید مالک سالن است.');
                router.back();
              },
            },
          )
        }
      />
    </Screen>
  );
}
