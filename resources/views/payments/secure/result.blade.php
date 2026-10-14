@extends('layouts.app')
@section('title', $success ? 'پرداخت موفق' : 'پرداخت ناموفق')

@section('content')
    <div class="max-w-xl mx-auto fade-in">

        @if($success)
            <div class="bg-m-card rounded-2xl border border-m-ok/20 overflow-hidden">
                <div class="p-8 text-center border-b border-m-ok/15">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-m-ok/15 mb-4">
                        <svg class="w-9 h-9 text-m-ok" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">پرداخت امن با موفقیت انجام شد</h1>
                    <p class="text-sm text-m-tx/55 mt-2">نوبت شما با موفقیت تایید شد</p>
                </div>

                @if($booking)
                    <div class="divide-y divide-m-gold/8">
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-m-tx/55">شماره پیگیری</span>
                            <span class="font-bold text-m-gold2" dir="ltr">{{ $booking->payment_reference ?? '#'.$booking->id }}</span>
                        </div>
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-m-tx/55">مبلغ پرداختی</span>
                            <span class="font-bold text-m-ok persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-m-tx/55">خدمت</span>
                            <span class="font-medium text-m-tx">{{ $booking->service?->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-m-tx/55">تاریخ و ساعت</span>
                            <span class="font-medium text-m-tx persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                        </div>
                    </div>
                @endif

                <div class="p-6 bg-m-bg/40 flex flex-col sm:flex-row gap-3">
                    @if($booking)
                        <a href="{{ route('bookings.show', $booking) }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-bold transition-all
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30">
                            مشاهده جزئیات نوبت
                        </a>
                    @endif
                    <a href="{{ route('home') }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-colors
                          border border-m-gold/25 text-m-tx/80 hover:bg-m-tx/5">
                        بازگشت به صفحه اصلی
                    </a>
                </div>
            </div>
        @else
            <div class="bg-m-card rounded-2xl border border-m-bad/20 overflow-hidden">
                <div class="p-8 text-center border-b border-m-bad/15">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-m-bad/15 mb-4">
                        <svg class="w-9 h-9 text-m-bad" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">پرداخت ناموفق</h1>
                    <p class="text-sm text-m-bad/80 mt-2">{{ $message ?? 'متأسفانه پرداخت شما انجام نشد.' }}</p>
                </div>

                <div class="p-6 bg-m-bg/40 flex flex-col sm:flex-row gap-3">
                    @if($booking)
                        <a href="{{ route('payments.secure.checkout', $booking) }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-bold transition-all
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30">
                            تلاش مجدد
                        </a>
                        <a href="{{ route('payment.show', $booking) }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-colors
                              border border-m-gold/25 text-m-tx/80 hover:bg-m-tx/5">
                            پرداخت معمولی
                        </a>
                    @else
                        <a href="{{ route('home') }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-colors
                              border border-m-gold/25 text-m-tx/80 hover:bg-m-tx/5">
                            بازگشت به صفحه اصلی
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
