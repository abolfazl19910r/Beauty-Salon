@extends('layouts.app')

@section('title', 'پرداخت موفق')

@section('content')
    <div class="max-w-md mx-auto fade-in">
        <div class="rounded-xl p-6 text-center" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">

            <div class="mb-6" style="color: #6FCF97;">
                <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold mb-4" style="color: var(--rasta-gold-light);">پرداخت با موفقیت انجام شد</h1>
            <p class="mb-2" style="color: var(--rasta-cream); opacity: 0.85;">رزرو شما با موفقیت ثبت شد.</p>
            <p class="mb-6" style="color: var(--rasta-cream); opacity: 0.6;">پیامک تاییدیه برای شما ارسال خواهد شد.</p>

            <div class="rounded-lg p-5 mb-5 text-right" style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.15);">
                <h2 class="font-bold mb-4 text-center" style="color: var(--rasta-gold-light);">اطلاعات پرداخت</h2>
                <div class="space-y-2 persian-number">
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">شماره نوبت:</span>
                        <span class="font-medium" style="color: var(--rasta-cream);">#{{ $booking->id }}</span>
                    </div>
                    @if($booking->payment_reference)
                        <div class="flex justify-between items-center">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">شماره پیگیری:</span>
                            <span class="font-medium" dir="ltr" style="color: var(--rasta-gold-light);">{{ $booking->payment_reference }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">مبلغ پرداختی:</span>
                        <span class="font-medium" style="color: var(--rasta-cream);">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">باقی‌مانده (موقع نوبت به متخصص می‌دهید):</span>
                        <span class="font-medium" style="color: var(--rasta-cream); opacity: 0.85;">{{ number_format($booking->remaining_amount) }} تومان</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">تاریخ پرداخت:</span>
                        <span class="font-medium" dir="ltr" style="color: var(--rasta-cream);">{{ verta($booking->paid_at)->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg p-5 mb-6 text-right" style="background-color: rgba(201,162,75,0.07); border: 1px solid rgba(201,162,75,0.2);">
                <h2 class="font-bold mb-4 text-center" style="color: var(--rasta-gold-light);">جزئیات نوبت</h2>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">خدمت:</span>
                        <span class="font-medium" style="color: var(--rasta-cream);">{{ $booking->service?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">متخصص:</span>
                        <span class="font-medium" style="color: var(--rasta-cream);">{{ $booking->specialist?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">تاریخ:</span>
                        <span class="font-medium persian-number" dir="ltr" style="color: var(--rasta-cream);">{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color: var(--rasta-cream); opacity: 0.6;">ساعت:</span>
                        <span class="font-medium persian-number" dir="ltr" style="color: var(--rasta-cream);">{{ verta($booking->booking_time)->format('H:i') }}</span>
                    </div>
                    @if($booking->service->duration)
                        <div class="flex justify-between items-center">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">مدت زمان:</span>
                            <span class="font-medium persian-number" style="color: var(--rasta-cream);">{{ $booking->service->duration }} دقیقه</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-3 justify-center flex-wrap">
                <a href="{{ route('bookings.show', $booking) }}"
                   class="inline-block px-6 py-3 rounded-lg font-bold transition-opacity hover:opacity-90"
                   style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                    مشاهده جزئیات نوبت
                </a>
                <a href="{{ route('home') }}"
                   class="inline-block px-6 py-3 rounded-lg transition hover:bg-white/5"
                   style="border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream); opacity: 0.85;">
                    بازگشت به صفحه اصلی
                </a>
            </div>
        </div>
    </div>
@endsection
