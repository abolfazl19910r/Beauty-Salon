import { router } from 'expo-router';
import { RefreshControl } from 'react-native';

import { useWallet } from '@/lib/customer/queries';
import { toman } from '@/lib/format';
import { Button, Card, ErrorBox, KeyValue, Loading, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

export default function Wallet() {
  const p = usePalette();
  const wallet = useWallet();

  return (
    <Screen refreshControl={<RefreshControl refreshing={wallet.isRefetching} onRefresh={() => wallet.refetch()} colors={[p.gold]} />}>
      {wallet.isPending ? <Loading /> : wallet.error ? <ErrorBox error={wallet.error} onRetry={() => wallet.refetch()} /> : (
        <>
          <Card style={{ alignItems: 'center' }}>
            <T muted>موجودی</T>
            <T weight="bold" size={26} color={p.gold}>{toman(wallet.data.balance)}</T>
          </Card>
          <Card>
            <KeyValue label="مجموع واریز" value={toman(wallet.data.total_deposited)} />
            <KeyValue label="مجموع خرج" value={toman(wallet.data.total_spent)} />
          </Card>
          <T muted size={13}>پیش‌پرداخت نوبت‌های لغوشده به کیف پول برمی‌گردد و در پرداخت بعدی قابل استفاده است.</T>
        </>
      )}
      <Button title="شارژ کیف پول" onPress={() => router.push('/charge')} />
      <Button kind="secondary" title="تراکنش‌ها" onPress={() => router.push('/wallet-transactions')} />
    </Screen>
  );
}
