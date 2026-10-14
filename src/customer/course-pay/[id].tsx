import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, Switch, View } from 'react-native';

import { enDigits, faDigits, toman } from '@/lib/format';
import { gatewayFee, walletShare } from '@/lib/customer/money';
import { openCheckout } from '@/lib/customer/payments';
import { useCourse, useCoursePayOptions, useRefreshMoney } from '@/lib/customer/queries';
import type { CoursePayStart } from '@/lib/customer/types';
import { useApi } from '@/lib/session';
import { Button, Card, Chip, ErrorBox, Field, KeyValue, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { radius, space, usePalette } from '@/ui/theme';

/**
 * پرداخت دوره (تصمیم ۸ بسته‌ی ب): کل مبلغ، یا پرداخت اول دست‌کم به اندازه‌ی پیش‌پرداخت بسته؛ بعداً باقی به هر مقدار. با هر
 * پرداخت فقط به اندازه‌ی پول پرداخت‌شده جلسه رزرو می‌شود. کیف پول + درگاه، مثل پیش‌پرداخت نوبت.
 */
export default function CoursePay() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const courseId = Number(id);
  const api = useApi();
  const p = usePalette();
  const refresh = useRefreshMoney();
  const course = useCourse(courseId);
  const options = useCoursePayOptions(courseId);
  const [amountText, setAmountText] = useState('');
  const [useWalletMoney, setUseWalletMoney] = useState(false);
  const [gatewayId, setGatewayId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);

  const due = options.data?.balance_due ?? 0;
  const min = options.data?.min_amount ?? 0;
  useEffect(() => {
    if (options.data && amountText === '') {
      setAmountText(String(Math.round(options.data.balance_due)));
    }
  }, [options.data, amountText]);

  const gateways = options.data?.gateways ?? [];
  useEffect(() => {
    if (gatewayId === null && gateways.length > 0) {
      setGatewayId(gateways[0].id);
    }
  }, [gateways, gatewayId]);

  if (course.isPending || options.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (course.error || options.error) {
    return <Screen><ErrorBox error={course.error ?? options.error} onRetry={() => { void course.refetch(); void options.refetch(); }} /></Screen>;
  }
  const c = course.data;
  if (!c.actions.pay) {
    return (
      <Screen>
        <Notice tone="success" text="این دوره پرداخت باقی‌مانده‌ای ندارد." />
        <Button title="دیدن دوره" onPress={() => router.replace(`/course/${c.id}`)} />
      </Screen>
    );
  }

  const amount = Math.min(due, Number(enDigits(amountText).replace(/[^0-9]/g, '')) || 0);
  const balance = options.data.wallet_balance;
  const fromWallet = walletShare(useWalletMoney, balance, amount);
  const rest = Math.max(0, amount - fromWallet);
  const gateway = gateways.find((g) => g.id === gatewayId) ?? null;
  const fee = gateway ? gatewayFee(gateway, rest) : 0;
  const tooSmall = amount + 0.5 < min;
  const noGateway = rest > 0 && (gateways.length === 0 || !options.data.online_payment);

  const pay = async () => {
    setBusy(true);
    setError(null);
    try {
      const { data } = await api.post<CoursePayStart>(`/customer/courses/${c.id}/pay`, {
        amount,
        use_wallet: fromWallet > 0,
        gateway_id: rest > 0 ? gatewayId ?? undefined : undefined,
      });
      refresh();
      if (data.state === 'paid') {
        router.replace(`/course/${c.id}`);
      } else {
        await openCheckout(data, 'course', null);
      }
    } catch (e) {
      setError(e);
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <Card>
        <T weight="bold">{c.name}</T>
        <KeyValue label="باقی‌مانده‌ی دوره" value={toman(due)} />
        {min < due ? <KeyValue label={c.status === 'pending_payment' ? 'کمترین پرداخت اول' : 'کمترین مبلغ'} value={toman(min)} /> : null}
      </Card>

      {min < due ? (
        <>
          <Field label="مبلغ این پرداخت (تومان)" value={faDigits(amountText)} onChangeText={setAmountText} keyboardType="number-pad"
            error={tooSmall ? `دست‌کم ${toman(min)}` : undefined} hint="با پرداخت بخشی از مبلغ، فقط همان تعداد جلسه رزرو می‌شود." />
          <Row>
            <Chip label="کل مبلغ" active={amount === due} onPress={() => setAmountText(String(Math.round(due)))} />
            <Chip label="کمترین مبلغ" active={amount === min} onPress={() => setAmountText(String(Math.round(min)))} />
          </Row>
        </>
      ) : null}

      <Card>
        <KeyValue label="این پرداخت" value={toman(amount)} />
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
        </>
      ) : null}

      {error ? <ErrorBox error={error} /> : null}
      <Button
        title={rest > 0 ? `پرداخت ${toman(rest + fee)}` : 'پرداخت از کیف پول'}
        onPress={pay}
        loading={busy}
        disabled={amount <= 0 || tooSmall || noGateway || (rest > 0 && !gatewayId)}
      />
    </Screen>
  );
}
