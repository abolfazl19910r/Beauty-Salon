@extends('layouts.app')
@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        <div class="relative w-24 h-24 mx-auto mb-6">
            <div class="absolute inset-0 rounded-full bg-red-400/15 animate-ping"></div>
            <div class="relative w-24 h-24 rounded-full bg-red-900/30 border-2 border-red-500/30 flex items-center justify-center">
                <svg class="w-12 h-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-3"
            style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">تراکنش ناموفق</h1>
        <p class="text-[#F8F3E9]/60 mb-6 text-sm">
            {{ $error_message ?? 'متأسفانه در پردازش پرداخت شما مشکلی پیش آمده است.' }}
        </p>

        {{-- Guidance --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-5 mb-8 text-right">
            <h2 class="font-bold text-sm text-[#E6CD8A] mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                راهنمایی
            </h2>
            <ul class="space-y-2 text-sm text-[#F8F3E9]/65">
                <li class="flex items-start gap-2">
                    <span class="text-[#C9A24B] mt-0.5">•</span>
                    از اتصال اینترنت خود اطمینان حاصل کنید
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#C9A24B] mt-0.5">•</span>
                    موجودی کافی در حساب خود را بررسی کنید
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#C9A24B] mt-0.5">•</span>
                    در صورت کسر وجه، تا ۷۲ ساعت آینده به حساب شما برگشت داده می‌شود
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#C9A24B] mt-0.5">•</span>
                    در صورت نیاز به پیگیری با پشتیبانی تماس بگیرید
                </li>
            </ul>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('payment.show', $booking) }}"
               class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                  hover:shadow-lg hover:shadow-[#C9A24B]/25">
                تلاش مجدد
            </a>
            <a href="{{ route('bookings.show', $booking) }}"
               class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                  text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                بازگشت به جزئیات نوبت
            </a>
        </div>
    </div>
@endsection
