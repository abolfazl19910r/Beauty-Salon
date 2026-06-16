@extends('layouts.app')
@section('title', $success ? 'پرداخت موفق' : 'پرداخت ناموفق')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        @if($success)
            <div class="text-center mb-8">
                <div class="relative w-24 h-24 mx-auto mb-6">
                    <div class="absolute inset-0 rounded-full bg-emerald-400/20 animate-ping"></div>
                    <div class="relative w-24 h-24 rounded-full bg-emerald-900/40 border-2 border-emerald-500/40 flex items-center justify-center">
                        <svg class="w-12 h-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-2"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پرداخت موفق!</h1>
                <p class="text-[#F8F3E9]/60">نوبت شما با موفقیت ثبت شد</p>
            </div>

            {{-- Payment information --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-5">
                <div class="px-5 py-3.5 border-b border-[#C9A24B]/10">
                    <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات پرداخت</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-5">
                    <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/8 p-4">
                        <p class="text-xs text-[#F8F3E9]/50 mb-1">شماره پیگیری</p>
                        <p class="font-bold text-[#E6CD8A] text-sm" dir="ltr">
                            {{ $booking->payment_reference ?: '#'.$booking->id }}
                        </p>
                    </div>
                    <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/8 p-4">
                        <p class="text-xs text-[#F8F3E9]/50 mb-1">مبلغ پرداختی</p>
                        <p class="font-bold text-emerald-400 persian-number">{{ number_format($booking->prepayment_amount) }} تومان</p>
                    </div>
                    <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/8 p-4">
                        <p class="text-xs text-[#F8F3E9]/50 mb-1">تاریخ پرداخت</p>
                        <p class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta($booking->paid_at)->format('Y/m/d H:i') }}</p>
                    </div>
                    <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/8 p-4">
                        <p class="text-xs text-[#F8F3E9]/50 mb-1">کد نوبت</p>
                        <p class="font-bold text-[#E6CD8A] persian-number">#{{ $booking->id }}</p>
                    </div>
                </div>
            </div>

            {{-- Appointment details --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-5">
                <div class="px-5 py-3.5 border-b border-[#C9A24B]/10">
                    <h2 class="font-bold text-sm text-[#E6CD8A]">جزئیات نوبت</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    <div class="flex justify-between px-5 py-3 text-sm">
                        <span class="text-[#F8F3E9]/55">خدمت</span>
                        <span class="font-medium text-[#F8F3E9]">{{ $booking->service->name }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3 text-sm">
                        <span class="text-[#F8F3E9]/55">متخصص</span>
                        <span class="font-medium text-[#F8F3E9]">{{ $booking->specialist->name }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3 text-sm">
                        <span class="text-[#F8F3E9]/55">تاریخ</span>
                        <span class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3 text-sm">
                        <span class="text-[#F8F3E9]/55">ساعت</span>
                        <span class="font-medium text-[#F8F3E9]" dir="ltr">{{ verta($booking->booking_time)->format('H:i') }}</span>
                    </div>
                    @if($booking->service->duration)
                        <div class="flex justify-between px-5 py-3 text-sm">
                            <span class="text-[#F8F3E9]/55">مدت زمان</span>
                            <span class="font-medium text-[#F8F3E9] persian-number">{{ $booking->service->duration }} دقیقه</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Approval status --}}
            <div class="rounded-2xl p-5 mb-6 flex items-start gap-3
                    {{ $booking->status === 'confirmed' ? 'bg-emerald-900/15 border border-emerald-600/25' : 'bg-yellow-900/15 border border-yellow-600/25' }}">
                @if($booking->status === 'confirmed')
                    <svg class="w-6 h-6 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <div>
                        <h3 class="font-bold text-emerald-300 text-sm">نوبت شما تایید شد!</h3>
                        <p class="text-xs text-emerald-300/70 mt-1">نوبت شما به‌صورت خودکار تایید شد. لطفاً ۱۵ دقیقه قبل از وقت حضور داشته باشید.</p>
                    </div>
                @else
                    <svg class="w-6 h-6 text-yellow-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        <h3 class="font-bold text-yellow-300 text-sm">در انتظار تایید متخصص</h3>
                        <p class="text-xs text-yellow-300/70 mt-1">نوبت شما ثبت شد و در انتظار تایید متخصص است. پس از تایید پیامک ارسال می‌شود.</p>
                    </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('bookings.show', $booking) }}"
                   class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-all
                      bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                      hover:shadow-lg hover:shadow-[#C9A24B]/25">
                    مشاهده جزئیات نوبت
                </a>
                <a href="{{ route('home') }}"
                   class="flex-1 text-center py-3 rounded-xl text-sm border border-[#C9A24B]/25
                      text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                    بازگشت به صفحه اصلی
                </a>
            </div>

        @else
            <div class="text-center mb-8">
                <div class="relative w-24 h-24 mx-auto mb-6">
                    <div class="absolute inset-0 rounded-full bg-red-400/15 animate-ping"></div>
                    <div class="relative w-24 h-24 rounded-full bg-red-900/30 border-2 border-red-500/30 flex items-center justify-center">
                        <svg class="w-12 h-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-2"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پرداخت ناموفق</h1>
                <p class="text-[#F8F3E9]/60">متأسفانه پرداخت شما انجام نشد</p>
            </div>

            <div class="bg-red-900/15 border border-red-600/25 rounded-xl px-5 py-4 mb-5 text-center text-red-300 text-sm">
                {{ $error_message ?? 'خطا در انجام پرداخت' }}
            </div>

            @if($booking)
                <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-6">
                    <div class="px-5 py-3.5 border-b border-[#C9A24B]/10">
                        <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات نوبت</h2>
                    </div>
                    <div class="divide-y divide-[#C9A24B]/8">
                        <div class="flex justify-between px-5 py-3 text-sm">
                            <span class="text-[#F8F3E9]/55">خدمت</span>
                            <span class="font-medium text-[#F8F3E9]">{{ $booking->service->name }}</span>
                        </div>
                        <div class="flex justify-between px-5 py-3 text-sm">
                            <span class="text-[#F8F3E9]/55">متخصص</span>
                            <span class="font-medium text-[#F8F3E9]">{{ $booking->specialist->name }}</span>
                        </div>
                        <div class="flex justify-between px-5 py-3 text-sm">
                            <span class="text-[#F8F3E9]/55">تاریخ و ساعت</span>
                            <span class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                        </div>
                        <div class="flex justify-between px-5 py-3 text-sm">
                            <span class="text-[#F8F3E9]/55">مبلغ قابل پرداخت</span>
                            <span class="font-bold text-red-400 persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3">
                @if($booking)
                    <a href="{{ route('payment.show', $booking) }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-all
                          bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                          hover:shadow-lg hover:shadow-[#C9A24B]/25">
                        تلاش مجدد برای پرداخت
                    </a>
                @endif
                <a href="{{ route('home') }}"
                   class="flex-1 text-center py-3 rounded-xl text-sm border border-[#C9A24B]/25
                      text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                    بازگشت به صفحه اصلی
                </a>
            </div>
        @endif
    </div>
@endsection
