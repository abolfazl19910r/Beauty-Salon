{{--
    اطلاعات پیش از خرید / رسید (بسته‌ی مجوز 5د؛ مواد ۳۳ تا ۳۵ قانون تجارت الکترونیکی): ارائه‌دهنده‌ی خدمت (سالن)، شرایط لغو و برگشت
    پیش‌پرداخت و راه شکایت. ورودی: $termsSalon (Salon). App\Support\Booking\PurchaseTerms
--}}
@php($provider = \App\Support\Booking\PurchaseTerms::provider($termsSalon))
<div class="rounded-2xl border border-m-gold/15 bg-m-card p-5 text-sm leading-7 space-y-3" data-purchase-terms>
    <div>
        <h3 class="font-bold text-m-gold2 mb-1">ارائه‌دهنده‌ی خدمت</h3>
        <p>{{ $provider['name'] }}@if ($provider['address']) — {{ $provider['address'] }}@endif</p>
        @if ($provider['phone'])
            <p>تلفن: <a href="tel:{{ $provider['phone'] }}" dir="ltr" class="persian-number underline">{{ $provider['phone'] }}</a></p>
        @endif
        <p class="text-xs text-m-tx/55">نوبت‌دهی و پرداخت روی سکوی {{ $provider['platform'] }}؛ خدمت را خود سالن ارائه می‌کند.</p>
    </div>
    <div>
        <h3 class="font-bold text-m-gold2 mb-1">شرایط لغو و برگشت پیش‌پرداخت</h3>
        <ul class="list-disc pr-5 space-y-1 text-m-tx/80" data-cancellation-terms>
            @foreach (\App\Support\Booking\PurchaseTerms::cancellation() as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
    <p class="text-xs text-m-tx/60">
        شکایت: <a href="{{ route('complaints.create') }}" class="underline">شکایت و پیگیری</a> (پاسخ حداکثر ظرف {{ to_persian_num((string) config('legal.complaint_response_hours', 72)) }} ساعت) ·
        <a href="{{ \App\Support\CentralUrl::route('legal.terms') }}" target="_blank" class="underline">قوانین و شرایط</a>
    </p>
</div>
