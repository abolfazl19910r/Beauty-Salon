@extends('layouts.app')

@section('title', 'کیف پول من')

@php
    $typeIconMap = [
        'refund'  => ['bg' => 'rgba(198, 162, 75, 0.12)', 'color' => 'var(--rasta-gold-light)'],
        'payment' => ['bg' => 'rgba(220, 90, 90, 0.12)',  'color' => '#E08989'],
        'default' => ['bg' => 'rgba(248, 243, 233, 0.08)','color' => 'var(--rasta-cream)'],
    ];
@endphp

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold flex items-center" style="color: var(--rasta-cream);">
                    <svg class="w-7 h-7 ml-2" style="color: var(--rasta-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    کیف پول من
                </h1>
                <p class="mt-2" style="color: var(--rasta-gold-light); opacity: 0.8;">مدیریت موجودی و تراکنش‌های مالی شما</p>
            </div>
            <a href="{{ route('wallet.charge') }}"
               class="px-6 py-3 rounded-xl flex items-center font-bold transition-opacity hover:opacity-90"
               style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                شارژ کیف پول
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="rounded-xl p-6" style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold opacity-90">موجودی فعلی</h3>
                    <svg class="w-8 h-8 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-3xl font-bold persian-number">{{ number_format($wallet->balance) }}</p>
                <p class="text-sm opacity-80 mt-1">تومان</p>
            </div>

            <div class="rounded-xl p-6" style="background-color: var(--rasta-brown); border: 1px solid rgba(201, 162, 75, 0.25);">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold" style="color: var(--rasta-cream); opacity: 0.85;">بازگشت وجه این ماه</h3>
                    <svg class="w-8 h-8" style="color: var(--rasta-gold-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </div>
                <p class="text-3xl font-bold persian-number" style="color: var(--rasta-gold-light);">{{ number_format($currentMonthRefunds) }}</p>
                <p class="text-sm mt-1" style="color: var(--rasta-cream); opacity: 0.6;">تومان</p>
            </div>

            <div class="rounded-xl p-6" style="background-color: var(--rasta-brown); border: 1px solid rgba(201, 162, 75, 0.25);">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold" style="color: var(--rasta-cream); opacity: 0.85;">پرداختی این ماه</h3>
                    <svg class="w-8 h-8" style="color: var(--rasta-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <p class="text-3xl font-bold persian-number" style="color: var(--rasta-cream);">{{ number_format($currentMonthSpent) }}</p>
                <p class="text-sm mt-1" style="color: var(--rasta-cream); opacity: 0.6;">تومان</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201, 162, 75, 0.2);">
                    <div class="p-6" style="background: linear-gradient(90deg, var(--rasta-dark), var(--rasta-brown));">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold flex items-center" style="color: var(--rasta-gold-light);">
                                <svg class="w-6 h-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                تراکنش‌های اخیر
                            </h2>
                            <a href="{{ route('wallet.transactions') }}"
                               class="text-sm flex items-center transition hover:opacity-80" style="color: var(--rasta-gold);">
                                مشاهده همه
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="p-6">
                        @forelse($recentTransactions as $transaction)
                            @php
                                $iconInfo = $typeIconMap[$transaction->type] ?? $typeIconMap['default'];
                            @endphp
                            <a href="{{ route('wallet.transactions.show', $transaction) }}"
                               class="flex items-center justify-between py-4 border-b last:border-0 transition rounded-lg px-3 -mx-3 hover:bg-white/5"
                               style="border-color: rgba(201, 162, 75, 0.15);">
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: {{ $iconInfo['bg'] }};">
                                            @if($transaction->type === 'refund')
                                                <svg class="w-6 h-6" style="color: {{ $iconInfo['color'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            @elseif($transaction->type === 'payment')
                                                <svg class="w-6 h-6" style="color: {{ $iconInfo['color'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6" style="color: {{ $iconInfo['color'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate" style="color: var(--rasta-cream);">
                                            {{ $transaction->description }}
                                        </p>
                                        <div class="flex items-center gap-3 mt-1 text-xs flex-wrap" style="color: var(--rasta-gold-light); opacity: 0.7;">
                                            <span class="flex items-center">
                                                <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ \Morilog\Jalali\Jalalian::forge($transaction->created_at)->format('Y/m/d - H:i') }}
                                            </span>
                                            @if($transaction->booking)
                                                <span class="persian-number">نوبت #{{ $transaction->booking_id }}</span>
                                                @if($transaction->booking->payment_reference)
                                                    <span class="persian-number" dir="ltr">پیگیری: {{ $transaction->booking->payment_reference }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-left mr-4">
                                    <p class="text-lg font-bold persian-number" style="color: {{ $transaction->amount >= 0 ? 'var(--rasta-gold-light)' : '#E08989' }};">
                                        {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                                    </p>
                                    <p class="text-xs" style="color: var(--rasta-cream); opacity: 0.5;">تومان</p>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto mb-4" style="color: var(--rasta-brown); opacity: 0.6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-lg font-medium" style="color: var(--rasta-cream); opacity: 0.7;">هیچ تراکنشی وجود ندارد</p>
                                <p class="text-sm mt-2" style="color: var(--rasta-cream); opacity: 0.5;">تراکنش‌های مالی شما اینجا نمایش داده می‌شود</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl p-6" style="background-color: var(--rasta-brown); border: 1px solid rgba(201, 162, 75, 0.2);">
                    <h3 class="text-lg font-bold mb-4 flex items-center" style="color: var(--rasta-gold-light);">
                        <svg class="w-5 h-5 ml-2" style="color: var(--rasta-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        اطلاعات کیف پول
                    </h3>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201, 162, 75, 0.15);">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.7;">موجودی کل</span>
                            <span class="text-lg font-bold persian-number" style="color: var(--rasta-cream);">{{ number_format($wallet->balance) }} تومان</span>
                        </div>

                        <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201, 162, 75, 0.15);">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.7;">کل واریزی‌ها</span>
                            <span class="text-sm font-semibold persian-number" style="color: var(--rasta-gold-light);">{{ number_format($wallet->total_deposited) }} تومان</span>
                        </div>

                        <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201, 162, 75, 0.15);">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.7;">کل پرداختی‌ها</span>
                            <span class="text-sm font-semibold persian-number" style="color: #E08989;">{{ number_format($wallet->total_spent) }} تومان</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.7;">آخرین بروزرسانی</span>
                            <span class="text-xs persian-number" style="color: var(--rasta-cream); opacity: 0.5;">
                                {{ \Morilog\Jalali\Jalalian::forge($wallet->updated_at)->format('Y/m/d H:i') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl p-6" style="background-color: rgba(201, 162, 75, 0.08); border: 1px solid rgba(201, 162, 75, 0.25);">
                    <h3 class="text-lg font-bold mb-3 flex items-center" style="color: var(--rasta-gold-light);">
                        <svg class="w-5 h-5 ml-2" style="color: var(--rasta-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        نکات مهم
                    </h3>
                    <ul class="space-y-2 text-sm" style="color: var(--rasta-cream); opacity: 0.85;">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 ml-2 mt-0.5 flex-shrink-0" style="color: var(--rasta-gold);" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            بازگشت وجه لغو نوبت‌ها به کیف پول شما واریز می‌شود
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 ml-2 mt-0.5 flex-shrink-0" style="color: var(--rasta-gold);" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            می‌توانید از موجودی برای پرداخت نوبت‌های بعدی استفاده کنید
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 ml-2 mt-0.5 flex-shrink-0" style="color: var(--rasta-gold);" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            تمام تراکنش‌ها در سیستم ثبت و قابل پیگیری هستند
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
