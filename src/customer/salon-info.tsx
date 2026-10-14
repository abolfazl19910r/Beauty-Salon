import { Linking } from 'react-native';

import { useSalonDetail } from '@/lib/customer/queries';
import { faDigits, toman } from '@/lib/format';
import { SalonHeader } from '@/ui/customer';
import { Button, Card, ErrorBox, KeyValue, Loading, Screen, SectionTitle, T } from '@/ui/kit';

/** درباره‌ی سالن: معرفی، آدرس، تماس، ساعت کاری و قوانین پیش‌پرداخت/لغو (GET customer/salon) */
export default function SalonInfo() {
  const salon = useSalonDetail();

  if (salon.isPending) {
    return <Screen><Loading /></Screen>;
  }
  if (salon.error) {
    return <Screen><ErrorBox error={salon.error} onRetry={() => salon.refetch()} /></Screen>;
  }
  const s = salon.data;
  const r = s.rules;

  return (
    <Screen>
      <SalonHeader salon={s} />
      {s.bio ? <Card><T>{s.bio}</T></Card> : null}

      {s.address || s.phone ? (
        <Card>
          {s.address ? <T>{s.address}</T> : null}
          {s.address ? <Button small kind="secondary" title="نمایش روی نقشه" onPress={() => Linking.openURL(`geo:0,0?q=${encodeURIComponent(s.address ?? '')}`)} /> : null}
          {s.phone ? <Button small kind="secondary" title={`تماس: ${faDigits(s.phone)}`} onPress={() => Linking.openURL(`tel:${s.phone}`)} /> : null}
        </Card>
      ) : null}

      {s.working_hours.length > 0 ? (
        <>
          <SectionTitle>ساعت کاری</SectionTitle>
          <Card>{s.working_hours.map((line) => <T key={line}>{faDigits(line)}</T>)}</Card>
        </>
      ) : null}
      {s.experience_years ? <T muted>{faDigits(s.experience_years)} سال تجربه</T> : null}

      <SectionTitle>قوانین نوبت</SectionTitle>
      <Card>
        <KeyValue label="پیش‌پرداخت" value={`${faDigits(r.prepayment_percentage)}٪ قیمت خدمت`} />
        {r.minimum_prepayment > 0 ? <KeyValue label="کمترین پیش‌پرداخت" value={toman(r.minimum_prepayment)} /> : null}
        <T muted size={13}>
          {`پیش‌پرداخت باید تا ${faDigits(r.payment_window_minutes)} دقیقه بعد از رزرو پرداخت شود، وگرنه نوبت لغو می‌شود. `}
          {`لغو یا تغییر زمان از اپ تا ${faDigits(r.cancel_deadline_hours)} ساعت پیش از نوبت ممکن است. `}
          {r.cancellation_fee_percentage > 0
            ? `لغو کمتر از ${faDigits(r.cancellation_fee_within_hours)} ساعت پیش از نوبت ${faDigits(r.cancellation_fee_percentage)}٪ پیش‌پرداخت جریمه دارد.`
            : 'لغو در مهلت جریمه ندارد و پیش‌پرداخت به کیف پول برمی‌گردد.'}
        </T>
      </Card>
      {!s.online_payment ? <T muted size={13}>این سالن فعلاً پرداخت آنلاین ندارد.</T> : null}
    </Screen>
  );
}
