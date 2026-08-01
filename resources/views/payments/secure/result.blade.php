@extends('layouts.app')
@section('title', $success ? 'پرداخت موفق' : 'پرداخت ناموفق')

@section('content')
    <div class="max-w-xl mx-auto fade-in">

        @if($success)
            <div class="bg-[#2E2117] rounded-2xl border border-emerald-500/20 overflow-hidden">
                <div class="p-8 text-center border-b border-emerald-500/15">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/15 mb-4">
                        <svg class="w-9 h-9 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پرداخت امن با موفقیت انجام شد</h1>
                    <p class="text-sm text-[#F8F3E9]/55 mt-2">نوبت شما با موفقیت تایید شد</p>
                </div>

                @if($booking)
                    <div class="divide-y divide-[#C9A24B]/8">
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">شماره پیگیری</span>
                            <span class="font-bold text-[#E6CD8A]" dir="ltr">{{ $booking->payment_reference ?? '#'.$booking->id }}</span>
                        </div>
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">مبلغ پرداختی</span>
                            <span class="font-bold text-emerald-400 persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">خدمت</span>
                            <span class="font-medium text-[#F8F3E9]">{{ $booking->service?->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center px-6 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">تاریخ و ساعت</span>
                            <span class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                        </div>
                    </div>
                @endif

                <div class="p-6 bg-[#1A1410]/40 flex flex-col sm:flex-row gap-3">
                    @if($booking)
                        <a href="{{ route('bookings.show', $booking) }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-bold transition-all
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410] hover:shadow-lg hover:shadow-[#C9A24B]/30">
                            مشاهده جزئیات نوبت
                        </a>
                    @endif
                    <a href="{{ route('home') }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-colors
                          border border-[#C9A24B]/25 text-[#F8F3E9]/80 hover:bg-white/5">
                        بازگشت به صفحه اصلی
                    </a>
                </div>
            </div>
        @else
            <div class="bg-[#2E2117] rounded-2xl border border-red-500/20 overflow-hidden">
                <div class="p-8 text-center border-b border-red-500/15">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/15 mb-4">
                        <svg class="w-9 h-9 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پرداخت ناموفق</h1>
                    <p class="text-sm text-red-300/80 mt-2">{{ $message ?? 'متأسفانه پرداخت شما انجام نشد.' }}</p>
                </div>

                <div class="p-6 bg-[#1A1410]/40 flex flex-col sm:flex-row gap-3">
                    @if($booking)
                        <a href="{{ route('payments.secure.checkout', $booking) }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-bold transition-all
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410] hover:shadow-lg hover:shadow-[#C9A24B]/30">
                            تلاش مجدد
                        </a>
                        <a href="{{ route('payment.show', $booking) }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-colors
                              border border-[#C9A24B]/25 text-[#F8F3E9]/80 hover:bg-white/5">
                            پرداخت معمولی
                        </a>
                    @else
                        <a href="{{ route('home') }}"
                           class="flex-1 text-center py-3 rounded-xl text-sm font-semibold transition-colors
                              border border-[#C9A24B]/25 text-[#F8F3E9]/80 hover:bg-white/5">
                            بازگشت به صفحه اصلی
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
