import * as Crypto from 'expo-crypto';
import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Alert } from 'react-native';

import { enDigits, faDigits, toman } from '@/lib/format';
import { useWallet, useWalletMutations } from '@/lib/queries';
import { useApi } from '@/lib/session';
import { Button, Card, Chip, ErrorBox, Field, KeyValue, Loading, Row, Screen, T } from '@/ui/kit';

export default function Withdraw() {
  const api = useApi();
  const wallet = useWallet();
  const { withdraw } = useWalletMutations();
  const [amountText, setAmountText] = useState('');
  const [method, setMethod] = useState<'iban' | 'instant'>('iban');
  const [fee, setFee] = useState<{ fee: number; net_amount: number } | null>(null);
  // یک کلید برای این فرم: ارسال دوباره (قطع شبکه، دو بار زدن) همان درخواست اول را برمی‌گرداند، نه برداشت دوم
  const idempotencyKey = useRef(Crypto.randomUUID());
  const amount = Number(enDigits(amountText).replace(/\D/g, '')) || 0;

  useEffect(() => {
    setFee(null);
    if (amount <= 0) {
      return;
    }
    const timer = setTimeout(() => {
      api.get<{ fee: number; net_amount: number }>('/staff/wallet/fee', { amount, method }).then((r) => setFee(r.data)).catch(() => setFee(null));
    }, 400);
    return () => clearTimeout(timer);
  }, [amount, method, api]);

  if (wallet.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (wallet.error) {
    return <Screen><ErrorBox error={wallet.error} /></Screen>;
  }

  const limits = wallet.data.withdrawal_limits;

  const submit = () =>
    withdraw.mutate(
      { amount, method, idempotency_key: idempotencyKey.current },
      {
        onSuccess: ({ data }) => {
          Alert.alert('درخواست ثبت شد', `کد پیگیری: ${data.reference_code ?? '—'}`);
          router.back();
        },
      },
    );

  return (
    <Screen>
      <Card>
        <KeyValue label="موجودی" value={toman(wallet.data.balance)} />
        <KeyValue label="حداقل / حداکثر" value={`${toman(limits.minimum)} / ${toman(limits.maximum)}`} />
      </Card>
      <Field
        label="مبلغ (تومان)"
        value={amount ? faDigits(amount.toLocaleString('en-US').replace(/,/g, '٬')) : ''}
        onChangeText={setAmountText}
        keyboardType="number-pad"
      />
      <Row>
        <Chip label="واریز به شبا" active={method === 'iban'} onPress={() => setMethod('iban')} />
        <Chip label="فوری" active={method === 'instant'} onPress={() => setMethod('instant')} />
      </Row>
      {fee ? (
        <Card>
          <KeyValue label="کارمزد" value={toman(fee.fee)} />
          <KeyValue label="مبلغ دریافتی" value={toman(fee.net_amount)} />
        </Card>
      ) : null}
      {withdraw.error ? <ErrorBox error={withdraw.error} /> : null}
      <T muted size={13}>واریز به شبای ثبت‌شده‌ی شما ({wallet.data.iban?.masked ?? '—'}) انجام می‌شود.</T>
      <Button title="ثبت درخواست" onPress={submit} loading={withdraw.isPending} disabled={amount < limits.minimum || amount > wallet.data.balance} />
    </Screen>
  );
}
