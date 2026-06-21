@extends('layouts.specialist')

@section('title', 'کیف پول')

@php
    $transactionTypeMap = [
        'income'           => ['label' => 'درآمد',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'withdrawal'       => ['label' => 'برداشت',       'class' => 'bg-sky-400/10 text-sky-300'],
        'cancellation_fee' => ['label' => 'جریمه لغو',     'class' => 'bg-red-500/10 text-red-300'],
        'refund'           => ['label' => 'بازگشت وجه',    'class' => 'bg-amber-400/10 text-amber-300'],
        'adjustment'       => ['label' => 'تعدیل',        'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'],
    ];

    $withdrawalStatusMap = [
        'pending'    => ['label' => 'در انتظار بررسی', 'class' => 'bg-amber-400/10 text-amber-300'],
        'processing' => ['label' => 'در حال پردازش',   'class' => 'bg-sky-400/10 text-sky-300'],
        'completed'  => ['label' => 'تکمیل شده',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'failed'     => ['label' => 'ناموفق',          'class' => 'bg-red-500/10 text-red-300'],
        'cancelled'  => ['label' => 'لغو شده',          'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'],
    ];
@endphp

@section('content')
    <div class="fade-in space-y-6">

        {{-- Balance overview --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="specialist-cta rounded-xl p-6">
                <div class="bg-white/15 rounded-lg p-3 inline-flex mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <p class="text-sm opacity-80 mb-1">موجودی قابل برداشت</p>
                <p class="text-3xl font-bold persian-number">{{ number_format($wallet->balance) }}</p>
                <p class="text-xs opacity-70 mt-1">تومان</p>
            </div>

            <div class="specialist-card p-6">
                <div class="rounded-lg p-3 inline-flex mb-4" style="background-color: rgba(251, 191, 36, 0.12);">
                    <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">در انتظار تسویه</p>
                <p class="text-2xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($wallet->pending_amount) }}</p>
                <p class="text-xs text-[var(--specialist-text-dim)] mt-1 persian-number">تومان ({{ $settings->settlement_delay_days }} روز تاخیر)</p>
            </div>

            <div class="specialist-card p-6">
                <div class="rounded-lg p-3 inline-flex mb-4" style="background-color: rgba(216, 174, 224, 0.12);">
                    <svg class="w-7 h-7 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">کل درآمد</p>
                <p class="text-2xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($wallet->total_earned) }}</p>
                <p class="text-xs text-[var(--specialist-text-dim)] mt-1">تومان</p>
            </div>
        </div>

        {{-- This month --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="specialist-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">درآمد این ماه</p>
                        <p class="text-xl font-bold text-emerald-300 persian-number">{{ number_format($currentMonthIncome) }}</p>
                        <p class="text-xs text-[var(--specialist-text-dim)] mt-1">تومان</p>
                    </div>
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>

            <div class="specialist-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">برداشت این ماه</p>
                        <p class="text-xl font-bold text-sky-300 persian-number">{{ number_format(abs($currentMonthWithdrawals)) }}</p>
                        <p class="text-xs text-[var(--specialist-text-dim)] mt-1">تومان</p>
                    </div>
                    <svg class="w-8 h-8 text-sky-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('specialist.wallet.create-withdrawal') }}" class="specialist-cta rounded-xl p-6 text-center transition-opacity hover:opacity-90">
                <svg class="w-9 h-9 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-bold">درخواست برداشت</p>
                <p class="text-sm opacity-80 mt-1">برداشت وجه از کیف پول</p>
            </a>

            <a href="{{ route('specialist.wallet.transactions') }}" class="specialist-card rounded-xl p-6 text-center transition hover:bg-white/5">
                <svg class="w-9 h-9 mx-auto mb-3 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="font-bold text-[var(--specialist-text)]">تراکنش‌ها</p>
                <p class="text-sm text-[var(--specialist-text-dim)] mt-1">مشاهده تاریخچه تراکنش‌ها</p>
            </a>

            <a href="{{ route('specialist.wallet.edit-iban') }}" class="specialist-card rounded-xl p-6 text-center transition hover:bg-white/5">
                <svg class="w-9 h-9 mx-auto mb-3 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <p class="font-bold text-[var(--specialist-text)]">تنظیم شماره شبا</p>
                <p class="text-sm text-[var(--specialist-text-dim)] mt-1">{{ $wallet->iban ? 'ویرایش شماره شبا' : 'ثبت شماره شبا' }}</p>
            </a>
        </div>

        {{-- Bank info / IBAN warning --}}
        @if($wallet->iban)
            <div class="specialist-card p-6">
                <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    اطلاعات حساب بانکی
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">شماره شبا</p>
                        <p class="text-base font-semibold text-[var(--specialist-text)] persian-number" dir="ltr">{{ $wallet->formatted_iban ?? $wallet->iban }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">نام صاحب حساب</p>
                        <p class="text-base font-semibold text-[var(--specialist-text)]">{{ $wallet->account_holder_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">نام بانک</p>
                        <p class="text-base font-semibold text-[var(--specialist-text)]">{{ $wallet->bank_name ?? 'ثبت نشده' }}</p>
                    </div>
                </div>
                @if($wallet->iban_verified)
                    <div class="mt-4 flex items-center text-emerald-300">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">شماره شبا تایید شده است</span>
                    </div>
                @else
                    <div class="mt-4 flex items-center text-amber-300">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">شماره شبا در انتظار تایید است</span>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-lg p-6" style="background-color: rgba(251, 191, 36, 0.07); border: 1px solid var(--specialist-border);">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-amber-300 font-semibold mb-1">شماره شبا ثبت نشده است</h4>
                        <p class="text-[var(--specialist-text-dim)] text-sm mb-3">برای برداشت وجه، لطفاً ابتدا شماره شبا خود را ثبت کنید.</p>
                        <a href="{{ route('specialist.wallet.edit-iban') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors text-[#2B1A05]"
                           style="background: linear-gradient(135deg, #F5C56B, #D98A2B);">
                            ثبت شماره شبا
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Recent transactions --}}
        <div class="specialist-card overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b" style="border-color: var(--specialist-border);">
                <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">آخرین تراکنش‌ها</h3>
                <a href="{{ route('specialist.wallet.transactions') }}" class="text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] text-sm font-medium flex items-center">
                    مشاهده همه
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            </div>

            @if($recentTransactions->count() > 0)
                <div class="divide-y" style="border-color: var(--specialist-border);">
                    @foreach($recentTransactions as $transaction)
                        @php
                            $typeInfo = $transactionTypeMap[$transaction->type] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                        @endphp
                        <div class="p-4 flex items-center justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $typeInfo['class'] }}">{{ $typeInfo['label'] }}</span>
                                <span class="text-sm text-[var(--specialist-text-dim)]">{{ $transaction->description }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-[var(--specialist-plum-muted)] persian-number">{{ verta($transaction->created_at)->format('Y/m/d H:i') }}</span>
                                <span class="persian-number font-semibold {{ $transaction->amount >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                                    {{ $transaction->formatted_amount }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-[var(--specialist-inactive)]">
                    <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>تراکنشی ثبت نشده است</p>
                </div>
            @endif
        </div>

        {{-- Withdrawal requests --}}
        <div class="specialist-card overflow-hidden">
            <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">درخواست‌های برداشت</h3>
            </div>

            @if($withdrawalRequests->count() > 0)
                <div class="divide-y" style="border-color: var(--specialist-border);">
                    @foreach($withdrawalRequests as $request)
                        @php
                            $statusInfo = $withdrawalStatusMap[$request->status] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                        @endphp
                        <div class="p-5">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <span class="font-mono text-sm text-[var(--specialist-text)] persian-number" dir="ltr">{{ $request->reference_code }}</span>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                    </div>
                                    <div class="text-sm text-[var(--specialist-text-dim)] flex flex-wrap gap-x-4 gap-y-1">
                                        <span class="persian-number"><span class="text-[var(--specialist-plum-muted)]">مبلغ:</span> {{ number_format($request->amount) }} تومان</span>
                                        <span class="persian-number"><span class="text-[var(--specialist-plum-muted)]">کارمزد:</span> <span class="text-red-300">{{ number_format($request->fee) }}</span> تومان</span>
                                        <span class="persian-number"><span class="text-[var(--specialist-plum-muted)]">مبلغ خالص:</span> <span class="text-emerald-300 font-semibold">{{ number_format($request->net_amount) }}</span> تومان</span>
                                        <span><span class="text-[var(--specialist-plum-muted)]">روش:</span> {{ $request->method_text }}</span>
                                        <span class="persian-number"><span class="text-[var(--specialist-plum-muted)]">تاریخ:</span> {{ verta($request->created_at)->format('Y/m/d') }}</span>
                                    </div>
                                </div>

                                <div>
                                    @if($request->canBeCancelled())
                                        <form action="{{ route('specialist.wallet.cancel-withdrawal', $request) }}" method="POST" onsubmit="return confirm('آیا از لغو این درخواست اطمینان دارید؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-600/90 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">لغو</button>
                                        </form>
                                    @else
                                        <span class="text-[var(--specialist-inactive)] text-sm">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t" style="border-color: var(--specialist-border);">
                    {{ $withdrawalRequests->links() }}
                </div>
            @else
                <div class="text-center py-12 text-[var(--specialist-inactive)]">
                    <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>درخواست برداشتی ثبت نشده است</p>
                </div>
            @endif
        </div>
    </div>
@endsection
