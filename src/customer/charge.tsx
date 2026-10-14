import { useEffect, useState } from 'react';
import { Pressable, View } from 'react-native';

import { gatewayFee } from '@/lib/customer/money';
import { openCheckout } from '@/lib/customer/payments';
import { useChargeOptions } from '@/lib/customer/queries';
import type { PayStart } from '@/lib/customer/types';
import { enDigits, toman } from '@/lib/format';
import { useApi } from '@/lib/session';
import { Button, Chip, ErrorBox, Field, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { radius, space, usePalette } from '@/ui/theme';

/** شارژ کیف پول با درگاه (POST wallet/charge) — صفحه‌ی بانک در مرورگر بیرونی، مثل پرداخت نوبت. */
export default function Charge() {
  const api = useApi();
  const p = usePalette();
  const options = useChargeOptions();
  const [text, setText] = useState('');
  const [gatewayId, setGatewayId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const gateways = options.data?.gateways ?? [];
  useEffect(() => {
    if (gatewayId === null && gateways.length > 0) {
      setGatewayId(gateways[0].id);
    }
  }, [gateways, gatewayId]);

  if (options.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (options.error) {
    return <Screen><ErrorBox error={options.error} onRetry={() => options.refetch()} /></Screen>;
  }
  const o = options.data;
  const amount = Number(enDigits(text).replace(/\D/g, '')) || 0;
  const valid = amount >= o.min && amount <= o.max;
  const gateway = gateways.find((g) => g.id === gatewayId) ?? null;
  const fee = gateway && valid ? gatewayFee(gateway, amount) : 0;

  if (!o.online_payment || gateways.length === 0) {
    return <Screen><Notice tone="warning" text="شارژ آنلاین در این سالن فعلاً در دسترس نیست." /></Screen>;
  }

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<PayStart>('/customer/wallet/charge', { amount, gateway_id: gatewayId ?? undefined });
      if (data.state === 'redirect') {
        await openCheckout(data, 'wallet_charge', null);
      }
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <Field
        label="مبلغ (تومان)"
        value={text}
        onChangeText={(v) => setText(enDigits(v).replace(/\D/g, ''))}
        keyboardType="number-pad"
        hint={`از ${toman(o.min)} تا ${toman(o.max)}`}
        error={text && !valid ? 'مبلغ خارج از محدوده است.' : undefined}
      />
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: space(2) }}>
        {o.suggested.map((s) => <Chip key={s} label={toman(s)} active={amount === s} onPress={() => setText(String(s))} />)}
      </View>

      <SectionTitle>درگاه پرداخت</SectionTitle>
      {gateways.map((g) => {
        const active = g.id === gatewayId;
        return (
          <Pressable key={g.id} onPress={() => setGatewayId(g.id)} accessibilityRole="radio" accessibilityState={{ selected: active }}
            style={{ padding: space(3), borderRadius: radius.md, borderWidth: active ? 2 : 1, borderColor: active ? p.gold : p.border, backgroundColor: p.surface }}>
            <Row style={{ justifyContent: 'space-between' }}>
              <T weight="medium">{g.name}</T>
              <T muted size={13}>{g.fee_percent > 0 || g.fee_fixed > 0 ? (valid ? `کارمزد ${toman(gatewayFee(g, amount))}` : 'با کارمزد') : 'بدون کارمزد'}</T>
            </Row>
          </Pressable>
        );
      })}
      <T muted size={12}>صفحه‌ی بانک در مرورگر گوشی باز می‌شود و بعد از پرداخت به اپ برمی‌گردید.</T>
      {error ? <ErrorBox error={error} /> : null}
      <Button title={valid ? `پرداخت ${toman(amount + fee)}` : 'پرداخت'} onPress={submit} loading={busy} disabled={!valid || !gatewayId} />
    </Screen>
  );
}
