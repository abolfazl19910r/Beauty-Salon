import * as Crypto from 'expo-crypto';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Alert } from 'react-native';

import { ApiError } from '@/lib/api';
import { bookingTimeLabel } from '@/lib/customer/money';
import { useRefreshMoney, useServiceSpecialists } from '@/lib/customer/queries';
import type { CustomerBooking, PaymentInfo, Quote } from '@/lib/customer/types';
import { toman } from '@/lib/format';
import { useApi } from '@/lib/session';
import { Button, Card, ErrorBox, Field, KeyValue, Loading, Notice, Screen, T } from '@/ui/kit';
import { PurchaseTermsCard } from '@/ui/PurchaseTermsCard';

/** رزرو، قدم ۳: پیش‌فاکتور (کد تخفیف از باقی‌مانده کم می‌شود، نه از پیش‌پرداخت) و ثبت نوبت. */
export default function Checkout() {
  const params = useLocalSearchParams<{ service_id: string; specialist_id: string; booking_time: string }>();
  const api = useApi();
  const refresh = useRefreshMoney();
  const serviceId = Number(params.service_id);
  const specialists = useServiceSpecialists(serviceId);
  const specialist = specialists.data?.find((s) => s.id === Number(params.specialist_id));
  const [quote, setQuote] = useState<Quote | null>(null);
  const [code, setCode] = useState('');
  const [quoteError, setQuoteError] = useState<unknown>(null);
  const [checking, setChecking] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  // یک کلید برای این فرم: دو بار زدن یا تکرار بعد از قطعی شبکه نوبت دوم نمی‌سازد
  const idempotencyKey = useRef(Crypto.randomUUID());

  const loadQuote = async (discount: string | null) => {
    setChecking(true);
    setQuoteError(null);
    try {
      const { data } = await api.post<Quote>('/customer/bookings/quote', { service_id: serviceId, discount_code: discount || undefined });
      setQuote(data);
    } catch (e) {
      setQuoteError(e);
    } finally {
      setChecking(false);
    }
  };

  useEffect(() => {
    void loadQuote(null);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [serviceId]);

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      const { data, meta } = await api.post<CustomerBooking>('/customer/bookings', {
        service_id: serviceId,
        specialist_id: Number(params.specialist_id),
        booking_time: params.booking_time,
        discount_code: quote?.discount_code ?? undefined,
        idempotency_key: idempotencyKey.current,
      });
      refresh();
      const payment = meta.payment as PaymentInfo | undefined;
      if (payment?.required) {
        router.replace(`/pay/${data.id}`);
      } else {
        router.replace(`/booking/${data.id}`);
        Alert.alert('نوبت ثبت شد', data.status === 'pending' ? 'بعد از تأیید سالن خبرتان می‌کنیم.' : 'منتظر دیدارتان هستیم.');
      }
    } catch (e) {
      setError(e);
      if (e instanceof ApiError && e.code === 'booking_not_available') {
        refresh();
      }
    } finally {
      setBusy(false);
    }
  };

  // ساعت در همین فاصله گرفته شد (۴۰۹) — برگشت به انتخاب ساعت که تازه شده است
  const slotTaken = error instanceof ApiError && error.code === 'booking_not_available';

  return (
    <Screen>
      <Card>
        <T weight="bold">{quote?.service.name ?? '…'}</T>
        <T>{specialist?.name ?? ''}</T>
        <T muted>{params.booking_time ? bookingTimeLabel(params.booking_time) : ''}</T>
      </Card>

      {!quote && checking ? <Loading /> : null}
      {quoteError && !quote ? <ErrorBox error={quoteError} onRetry={() => loadQuote(null)} /> : null}

      {quote ? (
        <>
          <Card>
            <KeyValue label="قیمت خدمت" value={toman(quote.price)} />
            <KeyValue label="پیش‌پرداخت آنلاین" value={toman(quote.prepayment_amount)} />
            {quote.discount_amount > 0 ? <KeyValue label={`تخفیف (${quote.discount_code})`} value={`− ${toman(quote.discount_amount)}`} /> : null}
            <KeyValue label="پرداخت در سالن" value={toman(quote.remaining_amount)} />
          </Card>

          <Field label="کد تخفیف (اختیاری)" value={code} onChangeText={setCode} autoCapitalize="characters" autoCorrect={false} style={{ textAlign: 'left' }} />
          <Button small kind="secondary" title={quote.discount_code ? 'عوض کردن کد' : 'اعمال کد'} onPress={() => loadQuote(code.trim())} loading={checking} disabled={!code.trim()} />
          {quote.discount_code ? <Button small kind="ghost" title="حذف کد تخفیف" onPress={() => { setCode(''); void loadQuote(null); }} /> : null}
          {quoteError && quote ? <ErrorBox error={quoteError} /> : null}

          {quote.prepayment_amount > 0 ? (
            quote.online_payment
              ? <Notice text={`بعد از ثبت، ${toman(quote.prepayment_amount)} پیش‌پرداخت را آنلاین می‌پردازید؛ نوبت بدون پرداخت در مهلتش لغو می‌شود.`} />
              : <Notice tone="warning" text="این سالن فعلاً پرداخت آنلاین ندارد؛ رزرو نوبت‌هایی که پیش‌پرداخت دارند از اپ ممکن نیست. با سالن تماس بگیرید." />
          ) : null}

          {quote.terms ? <PurchaseTermsCard terms={quote.terms} /> : null}

          {error ? <ErrorBox error={error} /> : null}
          {slotTaken ? <Button kind="secondary" title="انتخاب ساعت دیگر" onPress={() => router.back()} /> : null}
          <Button title="ثبت نوبت" onPress={submit} loading={busy} disabled={checking || (quote.prepayment_amount > 0 && !quote.online_payment)} />
        </>
      ) : null}
    </Screen>
  );
}
