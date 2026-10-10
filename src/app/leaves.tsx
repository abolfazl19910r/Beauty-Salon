import { router } from 'expo-router';
import { Alert, RefreshControl } from 'react-native';

import { faDigits } from '@/lib/format';
import { useLeaveMutations, useLeaves } from '@/lib/queries';
import { Badge, Button, Card, Empty, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

const STATUS = {
  pending: { label: 'در انتظار تأیید', tone: 'warning' },
  approved: { label: 'تأیید شد', tone: 'success' },
  rejected: { label: 'رد شد', tone: 'danger' },
} as const;

export default function Leaves() {
  const p = usePalette();
  const leaves = useLeaves();
  const { remove } = useLeaveMutations();

  return (
    <Screen refreshControl={<RefreshControl refreshing={leaves.isRefetching} onRefresh={() => leaves.refetch()} colors={[p.gold]} />}>
      <Button title="درخواست مرخصی تازه" onPress={() => router.push('/leave-new')} />
      {remove.error ? <ErrorBox error={remove.error} /> : null}
      {leaves.isPending ? <Loading /> : leaves.error ? <ErrorBox error={leaves.error} onRetry={() => leaves.refetch()} /> : leaves.data.length === 0 ? <Empty text="مرخصی ثبت نشده است." /> : (
        leaves.data.map((l) => (
          <Card key={l.id}>
            <Row style={{ justifyContent: 'space-between' }}>
              <T weight="bold">{l.start_date === l.end_date ? faDigits(l.start_date_jalali) : `${faDigits(l.start_date_jalali)} تا ${faDigits(l.end_date_jalali)}`}</T>
              <Badge text={STATUS[l.status].label} tone={STATUS[l.status].tone} />
            </Row>
            {l.reason ? <T muted size={13}>{l.reason}</T> : null}
            {l.reject_reason ? <T size={13} color={p.danger}>دلیل رد: {l.reject_reason}</T> : null}
            {l.status === 'pending' ? (
              <Button
                small
                kind="danger"
                title="حذف درخواست"
                loading={remove.isPending && remove.variables === l.id}
                onPress={() => Alert.alert('حذف درخواست مرخصی؟', '', [
                  { text: 'نه', style: 'cancel' },
                  { text: 'حذف', style: 'destructive', onPress: () => remove.mutate(l.id) },
                ])}
              />
            ) : null}
          </Card>
        ))
      )}
    </Screen>
  );
}
