@extends('layouts.app')

@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        {{-- Error icon --}}
        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 rounded-full bg-m-bad/15 animate-ping"></div>
            <div class="relative w-24 h-24 rounded-full bg-m-bad/30 border-2 border-m-bad/30 flex items-center justify-center">
                <svg class="w-12 h-12 text-m-bad" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-m-gold2 mb-3"
            style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
            پرداخت ناموفق
        </h1>
        <p class="text-m-tx/60 mb-10">
            {{ session('error') ?? 'متأسفانه پرداخت با خطا مواجه شد. لطفاً دوباره تلاش کنید.' }}
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            {{-- ⭐ (۲۰۲۶-۰۹-۲۷) نوبت ممکنه نباشه (باز کردن مستقیم، session منقضی) یا دیگه قابل پرداخت نباشه (لغو شده) --}}
            @if ($booking && $booking->status === 'pending_payment' && $booking->payment_status !== 'paid')
            <a href="{{ route('payment.show', $booking) }}"
               class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                  hover:shadow-lg hover:shadow-[#C9A24B]/25">
                تلاش مجدد برای پرداخت
            </a>
            @endif
            <a href="{{ route('bookings.index') }}"
               class="px-6 py-3 rounded-xl text-sm border border-m-gold/25
                  text-m-tx/70 hover:bg-m-gold/10 transition-colors">
                نوبت‌های من
            </a>
            <a href="{{ route('home') }}"
               class="px-6 py-3 rounded-xl text-sm border border-m-gold/25
                  text-m-tx/70 hover:bg-m-gold/10 transition-colors">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
