import { Linking } from 'react-native';

import type { PurchaseTerms } from '@/lib/customer/types';
import { faDigits } from '@/lib/format';
import { Card, T } from './kit';
import { usePalette } from './theme';

/** ارائه‌دهنده‌ی خدمت، شرایط لغو و برگشت پیش‌پرداخت و راه شکایت — پیش از ثبت نوبت (مواد ۳۳ تا ۳۵ قانون تجارت الکترونیکی) */
export function PurchaseTermsCard({ terms }: { terms: PurchaseTerms }) {
  const p = usePalette();
  const { provider } = terms;

  return (
    <Card>
      <T weight="medium">ارائه‌دهنده‌ی خدمت</T>
      <T size={14}>{provider.name} — از طریق {provider.platform}</T>
      {provider.address ? <T muted size={13}>{provider.address}</T> : null}
      {provider.phone ? <T muted size={13}>تلفن: {faDigits(provider.phone)}</T> : null}

      <T weight="medium" style={{ marginTop: 8 }}>شرایط لغو و برگشت پیش‌پرداخت</T>
      {terms.cancellation.map((line) => <T key={line} size={13}>• {line}</T>)}

      <T muted size={13} style={{ marginTop: 8 }}>{terms.complaints}</T>
      <T size={13} color={p.gold} onPress={() => Linking.openURL(terms.terms_url)}>قوانین و شرایط کامل</T>
    </Card>
  );
}
