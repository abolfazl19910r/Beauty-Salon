import { Alert } from 'react-native';

import { faDigits } from '@/lib/format';
import { jalaliLabel } from '@/lib/jalali';
import { useTokenMutations, useTokens } from '@/lib/queries';
import { Badge, Button, Card, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';

const when = (iso: string | null) => (iso ? jalaliLabel(new Date(iso)) : '—');

export default function Devices() {
  const tokens = useTokens();
  const { revoke, revokeOthers } = useTokenMutations();

  if (tokens.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (tokens.error) {
    return <Screen><ErrorBox error={tokens.error} onRetry={() => tokens.refetch()} /></Screen>;
  }

  const others = tokens.data.filter((t) => !t.current).length;

  return (
    <Screen>
      {revoke.error || revokeOthers.error ? <ErrorBox error={revoke.error ?? revokeOthers.error} /> : null}
      {tokens.data.map((t) => (
        <Card key={t.id}>
          <Row style={{ justifyContent: 'space-between' }}>
            <T weight="bold">{t.name}</T>
            {t.current ? <Badge text="همین گوشی" tone="gold" /> : null}
          </Row>
          <T muted size={13}>آخرین استفاده: {when(t.last_used_at)} — ورود: {when(t.created_at)}</T>
          {!t.current ? (
            <Button small kind="danger" title="خروج این دستگاه" loading={revoke.isPending && revoke.variables === t.id} onPress={() => revoke.mutate(t.id)} />
          ) : null}
        </Card>
      ))}
      {others > 0 ? (
        <Button
          kind="danger"
          title={`خروج از ${faDigits(others)} دستگاه دیگر`}
          loading={revokeOthers.isPending}
          onPress={() => Alert.alert('خروج از همه‌ی دستگاه‌های دیگر؟', '', [
            { text: 'نه', style: 'cancel' },
            { text: 'بله', style: 'destructive', onPress: () => revokeOthers.mutate() },
          ])}
        />
      ) : null}
    </Screen>
  );
}
