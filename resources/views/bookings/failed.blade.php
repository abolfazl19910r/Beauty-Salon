@extends('layouts.app')

@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        {{-- Error icon --}}
        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 rounded-full bg-red-400/15 animate-ping"></div>
            <div class="relative w-24 h-24 rounded-full bg-red-900/30 border-2 border-red-500/30 flex items-center justify-center">
                <svg class="w-12 h-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-3"
            style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
            پرداخت ناموفق
        </h1>
        <p class="text-[#F8F3E9]/60 mb-10">
            {{ session('error') ?? 'متأسفانه پرداخت با خطا مواجه شد. لطفاً دوباره تلاش کنید.' }}
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('payment.show', $booking) }}"
               class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                  hover:shadow-lg hover:shadow-[#C9A24B]/25">
                تلاش مجدد برای پرداخت
            </a>
            <a href="{{ route('home') }}"
               class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                  text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
