@extends('layouts.app')

@section('title', 'جزئیات نوبت')

@section('content')
    <div class="max-w-4xl mx-auto fade-in">
        <div class="rounded-xl p-6" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
            <div class="border-b pb-4 mb-6" style="border-color: rgba(201,162,75,0.15);">
                <h1 class="text-2xl font-bold" style="color: var(--rasta-gold-light);">جزئیات نوبت</h1>
                <p class="mt-2 flex items-center" style="color: var(--rasta-cream); opacity: 0.7;">
                    <svg class="w-5 h-5 ml-1" style="color: var(--rasta-gold);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    شماره نوبت: <span class="persian-number">{{ $booking->id }}</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-lg p-5" style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.15);">
                    <h2 class="text-lg font-bold mb-4 flex items-center" style="color: var(--rasta-gold-light);">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        اطلاعات نوبت
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">خدمت:</span>
                            <span class="font-medium" style="color: var(--rasta-cream);">{{ $booking->service?->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">متخصص:</span>
                            <span class="font-medium" style="color: var(--rasta-cream);">{{ $booking->specialist?->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">تاریخ و ساعت:</span>
                            <span class="font-medium persian-number" dir="ltr" style="color: var(--rasta-cream);">
                                {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">وضعیت:</span>
                            @switch($booking->status)
                                @case('pending')
                                    <span class="px-2.5 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(251,191,36,0.12); color: #FBBF24;">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        در انتظار تایید
                                    </span>
                                    @break
                                @case('confirmed')
                                    <span class="px-2.5 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(111,207,151,0.12); color: #6FCF97;">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        تایید شده
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="px-2.5 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(201,162,75,0.15); color: var(--rasta-gold-light);">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        انجام شده
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="px-2.5 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(224,137,137,0.12); color: #E08989;">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        لغو شده
                                    </span>
                                    @break
                                @default
                                    <span class="px-2.5 py-1 rounded-full text-xs" style="background-color: rgba(248,243,233,0.08); color: var(--rasta-cream);">
                                        {{ $booking->status }}
                                    </span>
                            @endswitch
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">مدت زمان:</span>
                            <span class="font-medium persian-number" style="color: var(--rasta-cream);">{{ $booking->service->duration }} دقیقه</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg p-5" style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.15);">
                    <h2 class="text-lg font-bold mb-4 flex items-center" style="color: var(--rasta-gold-light);">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        اطلاعات پرداخت
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">مبلغ کل:</span>
                            <span class="font-medium persian-number" style="color: var(--rasta-cream);">{{ number_format($booking->service->price) }} تومان</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">پیش پرداخت:</span>
                            <span class="font-medium persian-number" style="color: var(--rasta-cream);">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        @if($booking->discount_code)
                            <div class="flex justify-between">
                                <span style="color: var(--rasta-cream); opacity: 0.6;">کد تخفیف اعمال‌شده:</span>
                                <span class="font-medium persian-number" dir="ltr" style="color: #6FCF97;">{{ $booking->discount_code }} (-{{ number_format($booking->discount_amount) }} تومان)</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <span style="color: var(--rasta-cream); opacity: 0.6;">وضعیت پرداخت:</span>
                            @if($booking->payment_status == 'paid')
                                <span class="px-2.5 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(111,207,151,0.12); color: #6FCF97;">
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    پرداخت شده
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(224,137,137,0.12); color: #E08989;">
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    پرداخت نشده
                                </span>
                            @endif
                        </div>
                        @if($booking->payment_reference)
                            <div class="flex justify-between">
                                <span style="color: var(--rasta-cream); opacity: 0.6;">شماره پیگیری:</span>
                                <span class="font-medium persian-number" dir="ltr" style="color: var(--rasta-gold-light);">{{ $booking->payment_reference }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{--
                جایگزین کامل BookingActions.jsx — طبق تصمیم پروژه (React → Blade).
                باگ‌های نسخه‌ی React که اینجا رفع شدن:
                  ۱. کامپوننت قدیمی هیچ booking id ای نمی‌فرستاد و URL هاش با route های واقعی مطابقت نداشت
                     (مثلاً /api/bookings/apply-discount به‌جای /api/bookings/{booking}/apply-discount) → همیشه ۴۰۴.
                  ۲. modal «تغییر زمان» و «ثبت نظر» با صفحات جدای از قبل موجود (bookings.reschedule / reviews.create)
                     دوباره‌کاری داشتن؛ طبق تصمیم، حذف و به همون صفحات لینک داده شدن.
            --}}
            <div class="mt-8 space-y-4">
                <div class="flex gap-3 flex-wrap">
                    @if(in_array($booking->status, ['pending', 'confirmed']) && $booking->canBeRescheduled())
                        <a href="{{ route('bookings.reschedule', $booking) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition hover:opacity-90"
                           style="background-color: rgba(201,162,75,0.12); color: var(--rasta-gold-light); border: 1px solid rgba(201,162,75,0.25);">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            تغییر زمان
                        </a>
                    @endif

                    @if(in_array($booking->status, ['pending', 'confirmed']))
                        <form action="{{ route('bookings.cancel', $booking) }}" method="POST"
                              onsubmit="return confirm('آیا از لغو این نوبت اطمینان دارید؟');">
                            @csrf
                            @method('PUT')
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition hover:opacity-90"
                                    style="background-color: rgba(224,137,137,0.12); color: #E08989; border: 1px solid rgba(224,137,137,0.25);">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                لغو نوبت
                            </button>
                        </form>
                    @endif

                    @if($booking->status === 'completed')
                        <a href="{{ route('reviews.create', $booking) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition hover:opacity-90"
                           style="background-color: rgba(111,207,151,0.12); color: #6FCF97; border: 1px solid rgba(111,207,151,0.25);">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            ثبت نظر
                        </a>
                    @endif
                </div>

                @if($booking->payment_status === 'unpaid' && ! $booking->discount_code)
                    <form action="{{ route('bookings.apply-discount', $booking) }}" method="POST" class="flex gap-2 max-w-md">
                        @csrf
                        <input type="text" name="code" placeholder="کد تخفیف دارید؟" required
                               class="flex-1 px-4 py-2 rounded-lg bg-transparent"
                               style="border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);">
                        <button type="submit"
                                class="px-4 py-2 rounded-lg font-bold transition-opacity hover:opacity-90"
                                style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                            اعمال
                        </button>
                    </form>
                @endif

                <div class="flex justify-between flex-wrap gap-3 pt-2">
                    <a href="{{ route('bookings.index') }}"
                       class="inline-flex items-center px-5 py-2 rounded-lg transition hover:bg-white/5"
                       style="border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream); opacity: 0.85;">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        بازگشت به لیست نوبت‌ها
                    </a>

                    @if($booking->payment_status == 'unpaid')
                        <a href="{{ route('payment.show', $booking) }}"
                           class="px-5 py-2 rounded-lg transition-opacity hover:opacity-90 inline-flex items-center font-bold"
                           style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                            <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            پرداخت نوبت
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
