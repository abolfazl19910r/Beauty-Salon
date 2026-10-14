import { router } from 'expo-router';
import { Image, View } from 'react-native';

import { useSpecialists } from '@/lib/customer/queries';
import { faDigits } from '@/lib/format';
import { Card, Empty, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

export default function Specialists() {
  const p = usePalette();
  const list = useSpecialists();

  if (list.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (list.error) {
    return <Screen><ErrorBox error={list.error} onRetry={() => list.refetch()} /></Screen>;
  }

  return (
    <Screen>
      {list.data.length === 0 ? <Empty text="متخصصی ثبت نشده است." /> : list.data.map((s) => (
        <Card key={s.id} onPress={() => router.push(`/specialist/${s.id}`)}>
          <Row gap={3}>
            {s.photo_url ? <Image source={{ uri: s.photo_url }} style={{ width: 52, height: 52, borderRadius: 26, backgroundColor: p.surfaceMuted }} /> : null}
            <View style={{ flex: 1 }}>
              <T weight="bold">{s.name}</T>
              {s.services?.length ? <T muted size={13} numberOfLines={2}>{s.services.map((x) => x.name).join('، ')}</T> : null}
              {s.rating_avg ? <T muted size={12}>امتیاز {faDigits(s.rating_avg)} از ۵</T> : null}
            </View>
          </Row>
        </Card>
      ))}
    </Screen>
  );
}
