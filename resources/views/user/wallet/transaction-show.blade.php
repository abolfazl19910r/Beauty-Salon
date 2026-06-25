@extends('layouts.app')

@section('title', 'جزئیات تراکنش')

@php
    $typeBadgeMap = [
        'deposit'    => ['label' => 'واریز',       'class' => 'background-color: rgba(111,207,151,0.12); color: #6FCF97;'],
        'payment'    => ['label' => 'پرداخت',       'class' => 'background-color: rgba(224,137,137,0.12); color: #E08989;'],
        'refund'     => ['label' => 'بازگشت وجه',    'class' => 'background-color: rgba(201,162,75,0.15); color: var(--rasta-gold-light);'],
        'adjustment' => ['label' => 'تعدیل',        'class' => 'background-color: rgba(248,243,233,0.08); color: var(--rasta-cream);'],
    ];
    $typeInfo = $typeBadgeMap[$transaction->type] ?? ['label' => $transaction->type_text, 'class' => 'background-color: rgba(248,243,233,0.08); color: var(--rasta-cream);'];
@endphp

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        <a href="{{ route('wallet.transactions') }}" class="inline-flex items-center mb-6 transition hover:opacity-80" style="color: var(--rasta-gold);">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
            </svg>
            بازگشت به تراکنش‌ها
        </a>

        <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">

            <div class="p-6 border-b text-center" style="border-color: rgba(201,162,75,0.15); background-color: rgba(201,162,75,0.05);">
                <span class="px-3 py-1 rounded-full text-xs font-medium" style="{{ $typeInfo['class'] }}">{{ $typeInfo['label'] }}</span>
                <p class="text-3xl font-bold persian-number mt-4" style="color: {{ $transaction->amount >= 0 ? '#6FCF97' : '#E08989' }};">
                    {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                    <span class="text-base font-normal" style="color: var(--rasta-cream); opacity: 0.6;">تومان</span>
                </p>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201,162,75,0.1);">
                    <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">شرح تراکنش</span>
                    <span class="text-sm font-medium" style="color: var(--rasta-cream);">{{ $transaction->description }}</span>
                </div>

                <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201,162,75,0.1);">
                    <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">تاریخ و ساعت</span>
                    <span class="text-sm font-medium persian-number" dir="ltr" style="color: var(--rasta-cream);">
                        {{ \Morilog\Jalali\Jalalian::forge($transaction->created_at)->format('Y/m/d - H:i') }}
                    </span>
                </div>

                <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201,162,75,0.1);">
                    <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">موجودی پس از تراکنش</span>
                    <span class="text-sm font-medium persian-number" style="color: var(--rasta-cream);">{{ number_format($transaction->balance_after) }} تومان</span>
                </div>

                @if($transaction->booking)
                    <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201,162,75,0.1);">
                        <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">شماره نوبت</span>
                        <span class="text-sm font-medium persian-number" style="color: var(--rasta-cream);">#{{ $transaction->booking_id }}</span>
                    </div>

                    @if($transaction->booking->payment_reference)
                        <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201,162,75,0.1);">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">شماره پیگیری</span>
                            <span class="text-sm font-medium persian-number" dir="ltr" style="color: var(--rasta-gold-light);">{{ $transaction->booking->payment_reference }}</span>
                        </div>
                    @endif

                    @if($transaction->booking->service)
                        <div class="flex justify-between items-center pb-3 border-b" style="border-color: rgba(201,162,75,0.1);">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">خدمت</span>
                            <span class="text-sm font-medium" style="color: var(--rasta-cream);">{{ $transaction->booking?->service?->name ?? '—' }}</span>
                        </div>
                    @endif

                    @if($transaction->booking->specialist)
                        <div class="flex justify-between items-center">
                            <span class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">متخصص</span>
                            <span class="text-sm font-medium" style="color: var(--rasta-cream);">{{ $transaction->booking->specialist?->name ?? '—' }}</span>
                        </div>
                    @endif
                @endif
            </div>

            @if($transaction->booking)
                <div class="p-6 pt-0">
                    <a href="{{ route('bookings.show', $transaction->booking_id) }}"
                       class="block text-center px-6 py-3 rounded-lg font-bold transition-opacity hover:opacity-90"
                       style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                        مشاهده جزئیات نوبت
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
