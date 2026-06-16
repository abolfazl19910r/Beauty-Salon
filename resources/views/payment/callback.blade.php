@extends('layouts.app')
@section('title', 'نتیجه پرداخت')

@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        @if($success)
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 rounded-full bg-emerald-400/20 animate-ping"></div>
                <div class="relative w-24 h-24 rounded-full bg-emerald-900/40 border-2 border-emerald-500/40 flex items-center justify-center">
                    <svg class="w-12 h-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-6"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پرداخت با موفقیت انجام شد</h1>

            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-5 mb-6 text-right space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-[#F8F3E9]/55">شماره نوبت</span>
                    <span class="font-medium text-[#F8F3E9] persian-number">{{ $booking->id }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-[#F8F3E9]/55">مبلغ پرداخت شده</span>
                    <span class="font-bold text-emerald-400 persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-[#F8F3E9]/55">شماره پیگیری</span>
                    <span class="font-medium text-[#E6CD8A] persian-number" dir="ltr">{{ $booking->payment_reference }}</span>
                </div>
            </div>

            <p class="text-[#F8F3E9]/60 mb-8 text-sm">پیامک تأییدیه برای شما ارسال خواهد شد.</p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('bookings.show', $booking) }}"
                   class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                      bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                      hover:shadow-lg hover:shadow-[#C9A24B]/25">
                    مشاهده جزئیات نوبت
                </a>
                <a href="{{ route('bookings.index') }}"
                   class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                      text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                    لیست نوبت‌ها
                </a>
            </div>

        @else
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 rounded-full bg-red-400/15 animate-ping"></div>
                <div class="relative w-24 h-24 rounded-full bg-red-900/30 border-2 border-red-500/30 flex items-center justify-center">
                    <svg class="w-12 h-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-3"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">خطا در پرداخت</h1>
            <p class="text-[#F8F3E9]/60 mb-10 text-sm">{{ $error_message ?? 'متأسفانه پرداخت با خطا مواجه شد.' }}</p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('payment.show', $booking) }}"
                   class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                      bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                      hover:shadow-lg hover:shadow-[#C9A24B]/25">
                    تلاش مجدد
                </a>
                <a href="{{ route('bookings.index') }}"
                   class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                      text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                    لیست نوبت‌ها
                </a>
            </div>
        @endif
    </div>
@endsection
