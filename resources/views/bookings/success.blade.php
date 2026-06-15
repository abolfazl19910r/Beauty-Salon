@extends('layouts.app')

@section('title', 'پرداخت موفق')

@section('content')
    <div class="max-w-lg mx-auto fade-in text-center">

        {{-- آیکون موفقیت --}}
        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 rounded-full bg-emerald-400/20 animate-ping"></div>
            <div class="relative w-24 h-24 rounded-full bg-emerald-900/40 border-2 border-emerald-500/40 flex items-center justify-center">
                <svg class="w-12 h-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-3"
            style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
            پرداخت با موفقیت انجام شد
        </h1>
        <p class="text-[#F8F3E9]/60 mb-2">رزرو شما با موفقیت ثبت شد.</p>
        <p class="text-[#F8F3E9]/60 mb-8">پیامک تأییدیه برای شما ارسال خواهد شد.</p>

        <div class="grid grid-cols-1 gap-4 mb-8 text-right">

            {{-- اطلاعات پرداخت --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-5 py-3 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات پرداخت</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">شماره پیگیری</span>
                        <span class="font-medium text-[#E6CD8A] text-xs" dir="ltr">{{ $booking->payment_ref }}</span>
                    </div>
                    <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">مبلغ پرداختی</span>
                        <span class="font-bold text-[#E6CD8A] persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                    <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">تاریخ پرداخت</span>
                        <span class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta($booking->paid_at)->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- جزئیات نوبت --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-5 py-3 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">جزئیات نوبت</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    @foreach([
                        ['label' => 'خدمت', 'value' => $booking->service->name],
                        ['label' => 'متخصص', 'value' => $booking->specialist->name],
                        ['label' => 'تاریخ', 'value' => verta($booking->booking_time)->format('Y/m/d'), 'persian' => true, 'ltr' => true],
                        ['label' => 'ساعت', 'value' => verta($booking->booking_time)->format('H:i'), 'ltr' => true],
                    ] as $row)
                        <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">{{ $row['label'] }}</span>
                            <span class="font-medium text-[#F8F3E9] {{ isset($row['persian']) ? 'persian-number' : '' }}"
                              {{ isset($row['ltr']) ? 'dir=ltr' : '' }}>
                            {{ $row['value'] }}
                        </span>
                        </div>
                    @endforeach
                    @if($booking->service->duration)
                        <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">مدت زمان</span>
                            <span class="font-medium text-[#F8F3E9] persian-number">{{ $booking->service->duration }} دقیقه</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('bookings.show', $booking) }}"
               class="px-6 py-3 rounded-xl text-sm font-semibold transition-all
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                  hover:shadow-lg hover:shadow-[#C9A24B]/25">
                مشاهده جزئیات نوبت
            </a>
            <a href="{{ route('home') }}"
               class="px-6 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                  text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
