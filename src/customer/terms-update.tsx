import { useMutation, useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Linking, Switch } from 'react-native';

import { errorMessage } from '@/lib/api';
import { faDigits } from '@/lib/format';
import { useSession } from '@/lib/session';
import type { LegalPendingDocument } from '@/lib/types';
import { Badge, Button, Card, ErrorBox, Loading, Row, Screen, T } from '@/ui/kit';
import { usePalette } from '@/ui/theme';

const jalali = (version: string) => faDigits(version.replace(/-/g, '/'));

/**
 * «قوانین به‌روز شد» (پذیرش دوباره، ۲۰۲۶-۱۰-۲۸): خلاصه‌ی تغییرهای مهم، متن کامل روی دامنه‌ی اصلی و پذیرش (سرور نسخه، زمان، IP و
 * نسخه‌ی اپ را ثبت می‌کند). کسی که نپذیرد می‌تواند خارج شود یا حسابش را حذف کند؛ «فعلاً نه» فقط نوبت‌های فعلی را نشان می‌دهد و
 * نوبت یا دوره‌ی تازه تا پذیرش بسته می‌ماند.
 */
export default function TermsUpdate() {
  const { api, signOut, setTermsPending, deferTerms } = useSession();
  const p = usePalette();
  const [accepted, setAccepted] = useState(false);

  const legal = useQuery({
    queryKey: ['customer', 'legal'],
    queryFn: async () => (await api.get<{ pending: LegalPendingDocument[] }>('/customer/legal')).data.pending,
  });
  const accept = useMutation({
    mutationFn: async () => (await api.post('/customer/legal/accept', { accept_terms: true })).data,
    onSuccess: () => {
      setTermsPending(false);
      router.replace('/home');
    },
  });

  // چیزی در انتظار نیست (مثلاً از دستگاه دیگری پذیرفته شده) → خانه
  const nothingPending = legal.data?.length === 0;
  useEffect(() => {
    if (nothingPending) {
      setTermsPending(false);
      router.replace('/home');
    }
  }, [nothingPending, setTermsPending]);

  if (legal.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (legal.error) {
    return <Screen><ErrorBox error={legal.error} onRetry={() => legal.refetch()} /></Screen>;
  }
  if (nothingPending) {
    return null;
  }

  return (
    <Screen>
      <T muted>بخشی از قوانین ماهرو تغییر مهمی کرده است. خلاصه را ببینید، متن کامل را بخوانید و اگر موافقید بپذیرید.</T>
      {legal.data.map((doc) => (
        <Card key={doc.document}>
          <Row style={{ justifyContent: 'space-between' }}>
            <T weight="bold" style={{ flexShrink: 1 }}>{doc.title}</T>
            <T muted size={12}>نسخه‌ی {jalali(doc.version)}</T>
          </Row>
          {doc.changes.map((change) => (
            <Row key={change.version} style={{ alignItems: 'flex-start' }}>
              {change.major ? <Badge text="مهم" tone="warning" /> : null}
              <T size={14} style={{ flexShrink: 1 }}>{`${jalali(change.version)}: ${change.summary}`}</T>
            </Row>
          ))}
          {doc.url ? <T size={14} color={p.gold} onPress={() => Linking.openURL(doc.url as string)}>متن کامل {doc.title}</T> : null}
        </Card>
      ))}

      <Row style={{ alignItems: 'center' }}>
        <Switch value={accepted} onValueChange={setAccepted} trackColor={{ true: p.gold }} accessibilityLabel="پذیرش نسخه‌ی تازه" testID="accept-new-terms" />
        <T size={14} style={{ flexShrink: 1 }}>متن تازه را خواندم و می‌پذیرم.</T>
      </Row>
      {accept.error ? <T size={13} color={p.danger}>{errorMessage(accept.error)}</T> : null}
      <Button title="پذیرش و ادامه" onPress={() => accept.mutate()} loading={accept.isPending} disabled={!accepted} />

      <Card>
        <T muted size={14}>اگر نمی‌پذیرید، نوبت یا دوره‌ی تازه نمی‌توانید بگیرید؛ نوبت‌های فعلی، لغو و حذف حساب در دسترس است.</T>
        <Button small kind="secondary" title="فعلاً نه — نوبت‌های فعلی" onPress={() => { deferTerms(); router.replace('/bookings'); }} />
        <Button small kind="ghost" title="حذف حساب" onPress={() => router.push('/delete-account')} />
        <Button small kind="ghost" title="خروج" onPress={() => void signOut({ revoke: true })} />
      </Card>
    </Screen>
  );
}
