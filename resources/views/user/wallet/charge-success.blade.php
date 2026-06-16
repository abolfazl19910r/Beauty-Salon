@extends('layouts.app')
@section('title', 'شارژ موفق')
@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 rounded-full bg-emerald-400/20 animate-ping"></div>
            <div class="relative w-24 h-24 rounded-full bg-emerald-900/40 border-2 border-emerald-500/40 flex items-center justify-center">
                <svg class="w-12 h-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-2"
            style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">کیف پول شارژ شد</h1>
        <p class="text-[#F8F3E9]/60 mb-8">موجودی کیف پول شما با موفقیت افزایش یافت</p>

        {{-- Charging information --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-5 text-right">
            <div class="px-5 py-3.5 border-b border-[#C9A24B]/10 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات شارژ</h2>
            </div>
            <div class="divide-y divide-[#C9A24B]/8">
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">مبلغ شارژ</span>
                    <span class="font-bold text-emerald-400 text-lg persian-number">{{ number_format($amount) }} تومان</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">کد پیگیری</span>
                    <span class="font-medium text-[#F8F3E9] text-xs" dir="ltr">{{ $refId }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">تاریخ و ساعت</span>
                    <span class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta()->format('Y/m/d H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- New inventory --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/20 p-6 mb-8">
            <p class="text-xs text-[#F8F3E9]/50 mb-2">موجودی جدید کیف پول</p>
            <p class="text-4xl font-bold text-[#E6CD8A] persian-number">{{ number_format($newBalance) }}</p>
            <p class="text-sm text-[#F8F3E9]/45 mt-1">تومان</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('wallet.index') }}"
               class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                  hover:shadow-lg hover:shadow-[#C9A24B]/25">
                مشاهده کیف پول
            </a>
            <a href="{{ route('bookings.create') }}"
               class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                  text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                رزرو نوبت جدید
            </a>
            <a href="{{ route('home') }}"
               class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/15
                  text-[#F8F3E9]/50 hover:bg-[#C9A24B]/5 transition-colors">
                صفحه اصلی
            </a>
        </div>
    </div>
@endsection
