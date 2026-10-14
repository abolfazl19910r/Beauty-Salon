import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, Switch, View } from 'react-native';

import { gatewayFee, walletShare } from '@/lib/customer/money';
import { openCheckout } from '@/lib/customer/payments';
import { ckeys, useBooking, useRefreshMoney, useWallet } from '@/lib/customer/queries';
import type { ChargeOptions, PayStart } from '@/lib/customer/types';
import { faDigits, toman } from '@/lib/format';
import { useApi } from '@/lib/session';
import { PayCountdown } from '@/ui/customer';
import { Button, Card, ErrorBox, KeyValue, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { radius, space, usePalette } from '@/ui/theme';

/**
 * پرداخت پیش‌پرداخت یک نوبت: کیف پول (بخشی یا کامل) + درگاه انتخابی مشتری (تصمیم ۲۰۲۶-۰۹-۲۵؛ کارمزد روی مبلغ درگاه).
 * POST bookings/{id}/pay → «پرداخت شد» یا صفحه‌ی بانک در مرورگر بیرونی.
 */
export default function Pay() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const bookingId = Number(id);
  const api = useApi();
  const p = usePalette();
  const refresh = useRefreshMoney();
  const booking = useBooking(bookingId);
  const wallet = useWallet();
  const amount = booking.data?.prepayment_amount ?? 0;
  // فهرست درگاه‌های فعال سالن با کارمزد (همان gatewayOptions سرور؛ مسیر شارژ برای هر مبلغی جواب می‌دهد)
  const options = useQuery({
    queryKey: [...ckeys.chargeOptions, amount],
    queryFn: async () => (await api.get<ChargeOptions>('/customer/wallet/charge', { amount: Math.round(amount) })).data,
    enabled: amount > 0,
  });
  const [useWalletMoney, setUseWalletMoney] = useState(false);
  const [gatewayId, setGatewayId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const balance = wallet.data?.balance ?? 0;
  const fromWallet = walletShare(useWalletMoney, balance, amount);
  const rest = Math.max(0, amount - fromWallet);
  const gateways = options.data?.gateways ?? [];
  const gateway = gateways.find((g) => g.id === gatewayId) ?? null;
  const fee = gateway ? gatewayFee(gateway, rest) : 0;

  useEffect(() => {
    if (gatewayId === null && gateways.length > 0) {
      setGatewayId(gateways[0].id);
    }
  }, [gateways, gatewayId]);

  if (booking.isPending || wallet.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (booking.error) {
    return <Screen><ErrorBox error={booking.error} onRetry={() => booking.refetch()} /></Screen>;
  }
  const b = booking.data;

  if (!b.actions.pay) {
    return (
      <Screen>
        <Notice tone={b.payment_status === 'paid' ? 'success' : 'warning'} text={b.payment_status === 'paid' ? 'پیش‌پرداخت این نوبت پرداخت شده است.' : 'این نوبت دیگر قابل پرداخت نیست.'} />
        <Button title="دیدن نوبت" onPress={() => router.replace(`/booking/${b.id}`)} />
      </Screen>
    );
  }

  const pay = async () => {
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<PayStart>(`/customer/bookings/${b.id}/pay`, {
        gateway_id: rest > 0 ? gatewayId ?? undefined : undefined,
        wallet_amount: fromWallet > 0 ? fromWallet : undefined,
      });
      refresh();
      if (data.state === 'paid') {
        router.replace(`/booking/${b.id}`);
      } else {
        await openCheckout(data, 'booking', b.id);
      }
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  const noGateway = rest > 0 && options.isSuccess && (gateways.length === 0 || !options.data.online_payment);

  return (
    <Screen>
      <Card>
        <T weight="bold">{b.service?.name}</T>
        <T muted size={14}>{b.specialist?.name} — {faDigits(b.booking_time_label ?? '')}</T>
        {b.pay_before ? <PayCountdown until={b.pay_before} /> : null}
      </Card>

      <Card>
        <KeyValue label="پیش‌پرداخت" value={toman(amount)} />
        {fromWallet > 0 ? <KeyValue label="از کیف پول" value={`− ${toman(fromWallet)}`} /> : null}
        {rest > 0 && fee > 0 ? <KeyValue label="کارمزد درگاه" value={toman(fee)} /> : null}
        <KeyValue label="پرداخت با کارت" value={<T weight="bold" color={p.gold}>{toman(rest > 0 ? rest + fee : 0)}</T>} />
      </Card>

      {balance > 0 ? (
        <Card>
          <Row style={{ justifyContent: 'space-between' }}>
            <View style={{ flex: 1 }}>
              <T weight="medium">استفاده از کیف پول</T>
              <T muted size={13}>موجودی: {toman(balance)}</T>
            </View>
            <Switch value={useWalletMoney} onValueChange={setUseWalletMoney} trackColor={{ true: p.gold }} accessibilityLabel="استفاده از کیف پول" />
          </Row>
        </Card>
      ) : null}

      {rest > 0 ? (
        <>
          <SectionTitle>درگاه پرداخت</SectionTitle>
          {options.isPending ? <Loading /> : options.error ? <ErrorBox error={options.error} onRetry={() => options.refetch()} /> : null}
          {gateways.map((g) => {
            const active = g.id === gatewayId;
            const gFee = gatewayFee(g, rest);
            return (
              <Pressable key={g.id} onPress={() => setGatewayId(g.id)} accessibilityRole="radio" accessibilityState={{ selected: active }}
                style={{ padding: space(3), borderRadius: radius.md, borderWidth: active ? 2 : 1, borderColor: active ? p.gold : p.border, backgroundColor: p.surface }}>
                <Row style={{ justifyContent: 'space-between' }}>
                  <T weight="medium">{g.name}</T>
                  <T muted size={13}>{gFee > 0 ? `کارمزد ${toman(gFee)}` : 'بدون کارمزد'}</T>
                </Row>
              </Pressable>
            );
          })}
          {noGateway ? <Notice tone="warning" text="پرداخت آنلاین این سالن فعلاً در دسترس نیست. با سالن تماس بگیرید." /> : null}
          <T muted size={12}>صفحه‌ی بانک در مرورگر گوشی باز می‌شود و بعد از پرداخت به اپ برمی‌گردید.</T>
        </>
      ) : null}

      {error ? <ErrorBox error={error} /> : null}
      <Button
        title={rest > 0 ? `پرداخت ${toman(rest + fee)}` : 'پرداخت از کیف پول'}
        onPress={pay}
        loading={busy}
        disabled={noGateway || (rest > 0 && !gatewayId)}
      />
    </Screen>
  );
}
