@extends('layouts.app')

@section('title', $success ? 'پرداخت موفق' : 'پرداخت ناموفق')

{{-- ⭐ ۲۰۲۶-۱۰-۱۱: هم‌سبک با بقیه‌ی صفحات مشتری (تم تیره‌ی طلایی، مثل bookings/create)؛
     قبلاً کارت سفید و دکمه‌های صورتی/بنفش قالب قدیمی را داشت. داده‌ها و لینک‌ها بدون تغییر. --}}
@section('content')
    <style>
        .result-card { background: rgb(var(--m-card)); border: 1px solid rgb(var(--m-gold) / 0.12); border-radius: 1rem; }
        .result-row { border-bottom: 1px solid rgb(var(--m-gold) / 0.08); }
        .result-row:last-child { border-bottom: none; }
        .result-tile { background: rgb(var(--m-gold) / 0.06); border: 1px solid rgb(var(--m-gold) / 0.15); border-radius: 0.75rem; }
        .btn-ghost-gold { border: 1px solid rgb(var(--m-gold) / 0.35); color: rgb(var(--m-gold2)); transition: background 0.2s; }
        .btn-ghost-gold:hover { background: rgb(var(--m-gold) / 0.1); }
    </style>

    <div class="max-w-2xl mx-auto py-8 fade-in">
        <div class="mb-8 text-center">
            @if ($success)
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.4);">
                    <svg class="w-8 h-8 text-m-ok" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            @else
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.4);">
                    <svg class="w-8 h-8 text-m-bad" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            @endif
            <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-1">{{ $currentSalonName ?? '' }}</p>
            <h1 class="text-2xl md:text-3xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                {{ $success ? 'پرداخت موفق' : 'پرداخت ناموفق' }}
            </h1>
            <p class="mt-2 text-sm text-m-tx/70">
                {{ $success ? 'نوبت شما با موفقیت ثبت شد.' : 'متأسفانه پرداخت شما انجام نشد.' }}
            </p>
        </div>

        @if ($success)
            <div class="result-card p-6 md:p-8 space-y-7">
                <section>
                    <h2 class="text-base font-bold text-m-gold2 mb-4">اطلاعات پرداخت</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="result-tile p-4">
                            <span class="text-xs text-m-tx/60 block mb-1">شماره پیگیری</span>
                            <span class="font-bold text-m-tx" dir="ltr">{{ $booking->payment_reference ?: '#'.$booking->id }}</span>
                        </div>
                        <div class="result-tile p-4">
                            <span class="text-xs text-m-tx/60 block mb-1">مبلغ پرداختی</span>
                            <span class="font-bold text-m-ok persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        <div class="result-tile p-4">
                            <span class="text-xs text-m-tx/60 block mb-1">تاریخ پرداخت</span>
                            <span class="font-medium text-m-tx persian-number" dir="ltr">{{ $booking->paid_at ? verta($booking->paid_at)->format('Y/m/d H:i') : '—' }}</span>
                        </div>
                        <div class="result-tile p-4">
                            <span class="text-xs text-m-tx/60 block mb-1">کد نوبت</span>
                            <span class="font-bold text-m-gold2 persian-number">#{{ $booking->id }}</span>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-base font-bold text-m-gold2 mb-3">جزئیات نوبت</h2>
                    <div class="text-sm">
                        <div class="result-row flex justify-between py-3">
                            <span class="text-m-tx/60">خدمت</span>
                            <span class="font-medium text-m-tx">{{ $booking->service?->name ?? '—' }}</span>
                        </div>
                        <div class="result-row flex justify-between py-3">
                            <span class="text-m-tx/60">متخصص</span>
                            <span class="font-medium text-m-tx">{{ $booking->specialist?->name ?? '—' }}</span>
                        </div>
                        <div class="result-row flex justify-between py-3">
                            <span class="text-m-tx/60">تاریخ</span>
                            <span class="font-medium text-m-tx persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                        </div>
                        <div class="result-row flex justify-between py-3">
                            <span class="text-m-tx/60">ساعت</span>
                            <span class="font-medium text-m-tx persian-number" dir="ltr">{{ verta($booking->booking_time)->format('H:i') }}</span>
                        </div>
                        @if ($booking->service?->duration)
                            <div class="result-row flex justify-between py-3">
                                <span class="text-m-tx/60">مدت زمان</span>
                                <span class="font-medium text-m-tx persian-number">{{ $booking->durationMinutes() }} دقیقه</span>
                            </div>
                        @endif
                    </div>
                </section>

                @if ($booking->status === 'confirmed')
                    <div class="rounded-xl p-4 text-sm leading-7" style="background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.3);">
                        <p class="font-bold text-m-ok">نوبت شما تأیید شد</p>
                        <p class="text-m-tx/80">لطفاً ۱۵ دقیقه قبل از وقت نوبت در سالن باشید.</p>
                    </div>
                @else
                    <div class="rounded-xl p-4 text-sm leading-7" style="background: rgb(var(--m-gold) / 0.08); border: 1px solid rgb(var(--m-gold) / 0.3);">
                        <p class="font-bold text-m-gold2">در انتظار تأیید متخصص</p>
                        <p class="text-m-tx/80">نوبت ثبت شد. بعد از تأیید متخصص برایتان پیامک فرستاده می‌شود.</p>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('bookings.show', $booking) }}" class="btn-gold flex-1 text-center rounded-xl px-6 py-3 font-semibold">مشاهده‌ی جزئیات نوبت</a>
                    <a href="{{ route('home') }}" class="btn-ghost-gold flex-1 text-center rounded-xl px-6 py-3">بازگشت به صفحه‌ی اصلی</a>
                </div>
            </div>
        @else
            <div class="result-card p-6 md:p-8 space-y-7">
                <div class="rounded-xl p-4 text-center text-sm leading-7 text-m-bad" style="background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.35);" role="alert">
                    {{ $error_message ?? 'خطا در انجام پرداخت' }}
                </div>

                @if ($booking)
                    <section>
                        <h2 class="text-base font-bold text-m-gold2 mb-3">اطلاعات نوبت</h2>
                        <div class="text-sm">
                            <div class="result-row flex justify-between py-3">
                                <span class="text-m-tx/60">خدمت</span>
                                <span class="font-medium text-m-tx">{{ $booking->service?->name ?? '—' }}</span>
                            </div>
                            <div class="result-row flex justify-between py-3">
                                <span class="text-m-tx/60">متخصص</span>
                                <span class="font-medium text-m-tx">{{ $booking->specialist?->name ?? '—' }}</span>
                            </div>
                            <div class="result-row flex justify-between py-3">
                                <span class="text-m-tx/60">تاریخ و ساعت</span>
                                <span class="font-medium text-m-tx persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                            </div>
                            <div class="result-row flex justify-between py-3">
                                <span class="text-m-tx/60">مبلغ قابل پرداخت</span>
                                <span class="font-bold text-m-bad persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                            </div>
                        </div>
                    </section>
                @endif

                <div class="flex flex-col sm:flex-row gap-3">
                    @if ($booking)
                        <a href="{{ route('payment.show', $booking) }}" class="btn-gold flex-1 text-center rounded-xl px-6 py-3 font-semibold">تلاش دوباره برای پرداخت</a>
                    @endif
                    <a href="{{ route('home') }}" class="btn-ghost-gold flex-1 text-center rounded-xl px-6 py-3">بازگشت به صفحه‌ی اصلی</a>
                </div>
            </div>
        @endif
    </div>
@endsection
