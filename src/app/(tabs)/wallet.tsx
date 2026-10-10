import { router } from 'expo-router';
import { Alert, RefreshControl } from 'react-native';

import { faDigits, toman } from '@/lib/format';
import { useWallet, useWalletMutations, useWithdrawals } from '@/lib/queries';
import { Badge, Button, Card, Empty, ErrorBox, KeyValue, Loading, Notice, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

const WITHDRAWAL_STATUS: Record<string, { label: string; tone: 'warning' | 'success' | 'danger' | 'muted' | 'info' }> = {
  pending: { label: 'در انتظار بررسی', tone: 'warning' },
  processing: { label: 'در حال پرداخت', tone: 'info' },
  completed: { label: 'پرداخت شد', tone: 'success' },
  failed: { label: 'رد/ناموفق', tone: 'danger' },
  cancelled: { label: 'لغو شد', tone: 'muted' },
};

export default function WalletTab() {
  const p = usePalette();
  const wallet = useWallet();
  const withdrawals = useWithdrawals();
  const { cancel } = useWalletMutations();

  const refresh = () => {
    void wallet.refetch();
    void withdrawals.refetch();
  };

  return (
    <Screen refreshControl={<RefreshControl refreshing={wallet.isRefetching} onRefresh={refresh} colors={[p.gold]} />}>
      {wallet.isPending ? <Loading /> : wallet.error ? <ErrorBox error={wallet.error} onRetry={refresh} /> : (
        <>
          <Card>
            <T muted size={13}>موجودی قابل برداشت</T>
            <T weight="bold" size={24}>{toman(wallet.data.balance)}</T>
            <KeyValue label="درآمد این ماه" value={toman(wallet.data.month_income)} />
            <KeyValue label="برداشت این ماه" value={toman(wallet.data.month_withdrawals)} />
            <KeyValue label="کل درآمد" value={toman(wallet.data.total_earned)} />
          </Card>
          <Card>
            <Row style={{ justifyContent: 'space-between' }}>
              <T weight="medium">شبا</T>
              {wallet.data.iban ? <Badge text={wallet.data.iban.verified ? 'تأییدشده' : 'منتظر تأیید مالک'} tone={wallet.data.iban.verified ? 'success' : 'warning'} /> : null}
            </Row>
            {wallet.data.iban ? (
              <>
                <T style={{ writingDirection: 'ltr', textAlign: 'right' }}>{wallet.data.iban.masked}</T>
                <T muted size={13}>{wallet.data.iban.account_holder_name} — {wallet.data.iban.bank_name}</T>
              </>
            ) : <T muted>هنوز شبا ثبت نشده است.</T>}
            {wallet.data.iban && !wallet.data.iban.verified ? <Notice tone="warning" text="تا مالک سالن شبا را تأیید نکند، تسویه‌ی خودکار انجام نمی‌شود." /> : null}
            <Button small kind="secondary" title={wallet.data.iban ? 'تغییر شبا' : 'ثبت شبا'} onPress={() => router.push('/iban')} />
          </Card>
          <Row>
            <Button title="درخواست برداشت" onPress={() => router.push('/withdraw')} disabled={wallet.data.balance <= 0 || !wallet.data.iban} />
            <Button kind="secondary" title="تراکنش‌ها" onPress={() => router.push('/transactions')} />
          </Row>
        </>
      )}

      <SectionTitle>درخواست‌های برداشت</SectionTitle>
      {cancel.error ? <ErrorBox error={cancel.error} /> : null}
      {withdrawals.isPending ? <Loading /> : withdrawals.error ? <ErrorBox error={withdrawals.error} /> : withdrawals.data.length === 0 ? <Empty text="درخواستی ثبت نشده است." /> : (
        withdrawals.data.map((w) => {
          const s = WITHDRAWAL_STATUS[w.status] ?? { label: w.status, tone: 'muted' as const };
          return (
            <Card key={w.id}>
              <Row style={{ justifyContent: 'space-between' }}>
                <T weight="bold">{toman(w.amount)}</T>
                <Badge text={s.label} tone={s.tone} />
              </Row>
              <T muted size={13}>{faDigits(w.created_at_jalali)} — کد {w.reference_code ?? '—'} — خالص {toman(w.net_amount)}</T>
              {w.rejection_reason ? <T size={13} color={p.danger}>{w.rejection_reason}</T> : null}
              {w.can_cancel ? (
                <Button
                  small
                  kind="danger"
                  title="لغو درخواست"
                  loading={cancel.isPending && cancel.variables === w.id}
                  onPress={() => Alert.alert('لغو درخواست برداشت؟', 'مبلغ به موجودی برمی‌گردد.', [
                    { text: 'نه', style: 'cancel' },
                    { text: 'بله', onPress: () => cancel.mutate(w.id) },
                  ])}
                />
              ) : null}
            </Card>
          );
        })
      )}
    </Screen>
  );
}
