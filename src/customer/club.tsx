import * as Clipboard from 'expo-clipboard';
import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, RefreshControl, View } from 'react-native';

import { discountLabel } from '@/lib/customer/money';
import { useDiscountCodes, useLoyalty, useRedeem } from '@/lib/customer/queries';
import type { DiscountCode } from '@/lib/customer/types';
import { errorMessage } from '@/lib/api';
import { faDigits } from '@/lib/format';
import { Badge, Button, Card, Empty, ErrorBox, Loading, Row, Screen, SectionTitle, T } from '@/ui/kit';
import { radius, space, usePalette } from '@/ui/theme';

function CodeCard({ code, expired }: { code: DiscountCode; expired?: boolean }) {
  const [copied, setCopied] = useState(false);
  return (
    <Card style={expired ? { opacity: 0.6 } : undefined}>
      <Row style={{ justifyContent: 'space-between' }}>
        <T weight="bold" size={17} style={{ writingDirection: 'ltr' }}>{code.code}</T>
        <Badge text={discountLabel(code.type, code.amount)} tone={expired ? 'muted' : 'gold'} />
      </Row>
      <T muted size={13}>
        {code.expires_at_jalali ? `${expired ? 'منقضی شد' : 'اعتبار تا'} ${faDigits(code.expires_at_jalali)}` : 'بدون تاریخ انقضا'}
        {!expired ? ` — ${faDigits(code.remaining_uses)} بار قابل استفاده` : ''}
      </T>
      {!expired ? (
        <Button small kind="secondary" title={copied ? 'کپی شد' : 'کپی کد'} onPress={async () => { await Clipboard.setStringAsync(code.code); setCopied(true); }} />
      ) : null}
    </Card>
  );
}

/** باشگاه مشتریان: امتیاز (بدون امتیازهای منقضی، LoyaltyLedger سرور)، پاداش‌ها، کدهای تخفیف شخصی */
export default function Club() {
  const p = usePalette();
  const loyalty = useLoyalty();
  const codes = useDiscountCodes();
  const redeem = useRedeem();

  const ask = (rewardId: number, title: string, points: number) =>
    Alert.alert('دریافت پاداش؟', `«${title}» با ${faDigits(points)} امتیاز. کد تخفیف شخصی ساخته می‌شود.`, [
      { text: 'انصراف', style: 'cancel' },
      {
        text: 'دریافت',
        onPress: () => redeem.mutate(rewardId, {
          onSuccess: (r) => Alert.alert('کد تخفیف شما', `${r.code}\n${discountLabel(r.type, r.amount)} — هنگام رزرو وارد کنید.`, [
            { text: 'کپی کد', onPress: () => void Clipboard.setStringAsync(r.code) },
            { text: 'باشه' },
          ]),
          onError: (e) => Alert.alert('دریافت نشد', errorMessage(e)),
        }),
      },
    ]);

  return (
    <Screen refreshControl={<RefreshControl refreshing={loyalty.isRefetching} onRefresh={() => { void loyalty.refetch(); void codes.refetch(); }} colors={[p.gold]} />}>
      {loyalty.isPending ? <Loading /> : loyalty.error ? <ErrorBox error={loyalty.error} onRetry={() => loyalty.refetch()} /> : (
        <>
          <Card style={{ alignItems: 'center' }}>
            <T muted>امتیاز شما</T>
            <T weight="bold" size={32} color={p.gold}>{faDigits(loyalty.data.points)}</T>
            {loyalty.data.expiring_points > 0 ? <T size={13} color={p.warning}>{faDigits(loyalty.data.expiring_points)} امتیاز تا ۳۰ روز دیگر منقضی می‌شود.</T> : null}
          </Card>

          {loyalty.data.next_reward ? (
            <Card>
              <T weight="medium">تا «{loyalty.data.next_reward.title}» {faDigits(loyalty.data.next_reward.points_needed)} امتیاز مانده</T>
              <View style={{ height: 8, borderRadius: radius.sm, backgroundColor: p.surfaceMuted, overflow: 'hidden' }}>
                <View style={{ width: `${loyalty.data.next_reward.progress_percentage}%`, height: '100%', backgroundColor: p.gold }} />
              </View>
            </Card>
          ) : null}

          <SectionTitle>پاداش‌ها</SectionTitle>
          {loyalty.data.rewards.length === 0 ? <Empty text="این سالن فعلاً پاداشی تعریف نکرده است." /> : loyalty.data.rewards.map((r) => (
            <Card key={r.id}>
              <Row style={{ justifyContent: 'space-between' }}>
                <T weight="bold" style={{ flexShrink: 1 }}>{r.title}</T>
                <Badge text={`${faDigits(r.required_points)} امتیاز`} tone={r.can_redeem ? 'gold' : 'muted'} />
              </Row>
              {r.description ? <T muted size={13}>{r.description}</T> : null}
              <T size={13}>{discountLabel(r.discount_type, r.discount_amount)}</T>
              <Button small title="دریافت" disabled={!r.can_redeem} loading={redeem.isPending && redeem.variables === r.id} onPress={() => ask(r.id, r.title, r.required_points)} />
            </Card>
          ))}
        </>
      )}

      <SectionTitle>کدهای تخفیف من</SectionTitle>
      {codes.isPending ? <Loading /> : codes.error ? <ErrorBox error={codes.error} onRetry={() => codes.refetch()} /> : (
        <>
          {codes.data.active.length === 0 ? <Empty text="کد فعالی ندارید." /> : codes.data.active.map((c) => <CodeCard key={c.code} code={c} />)}
          {codes.data.expired.slice(0, 5).map((c) => <CodeCard key={c.code} code={c} expired />)}
        </>
      )}
      <Button kind="ghost" title="تاریخچه‌ی امتیاز" onPress={() => router.push('/club-history')} />
    </Screen>
  );
}
