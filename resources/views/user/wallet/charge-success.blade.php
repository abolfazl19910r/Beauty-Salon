@extends('layouts.app')
@section('title', 'شارژ موفق')
@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 rounded-full bg-m-ok/20 animate-ping"></div>
            <div class="relative w-24 h-24 rounded-full bg-m-ok/40 border-2 border-m-ok/40 flex items-center justify-center">
                <svg class="w-12 h-12 text-m-ok" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-m-gold2 mb-2"
            style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">کیف پول شارژ شد</h1>
        <p class="text-m-tx/60 mb-8">موجودی کیف پول شما با موفقیت افزایش یافت</p>

        {{-- Charging information --}}
        <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden mb-5 text-right">
            <div class="px-5 py-3.5 border-b border-m-gold/10 flex items-center gap-2">
                <svg class="w-4 h-4 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="font-bold text-sm text-m-gold2">اطلاعات شارژ</h2>
            </div>
            <div class="divide-y divide-m-gold/8">
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">مبلغ شارژ</span>
                    <span class="font-bold text-m-ok text-lg persian-number">{{ number_format($amount) }} تومان</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">کد پیگیری</span>
                    <span class="font-medium text-m-tx text-xs" dir="ltr">{{ $refId }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">تاریخ و ساعت</span>
                    <span class="font-medium text-m-tx persian-number" dir="ltr">{{ verta()->format('Y/m/d H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- New inventory --}}
        <div class="bg-m-card rounded-2xl border border-m-gold/20 p-6 mb-8">
            <p class="text-xs text-m-tx/50 mb-2">موجودی جدید کیف پول</p>
            <p class="text-4xl font-bold text-m-gold2 persian-number">{{ number_format($newBalance) }}</p>
            <p class="text-sm text-m-tx/45 mt-1">تومان</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('wallet.index') }}"
               class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                  hover:shadow-lg hover:shadow-[#C9A24B]/25">
                مشاهده کیف پول
            </a>
            <a href="{{ route('bookings.create') }}"
               class="px-6 py-3 rounded-xl text-sm border border-m-gold/25
                  text-m-tx/70 hover:bg-m-gold/10 transition-colors">
                رزرو نوبت جدید
            </a>
            <a href="{{ route('home') }}"
               class="px-6 py-3 rounded-xl text-sm border border-m-gold/15
                  text-m-tx/50 hover:bg-m-gold/5 transition-colors">
                صفحه اصلی
            </a>
        </div>
    </div>
@endsection
