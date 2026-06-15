@extends('layouts.app')

@section('title', 'جزئیات نوبت')

@section('content')
    <style>
        .info-row { border-bottom: 1px solid rgba(201,162,75,0.08); }
        .info-row:last-child { border-bottom: none; }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-pending    { background: rgba(251,191,36,0.15); color: #FCD34D; }
        .badge-confirmed  { background: rgba(52,211,153,0.15); color: #6EE7B7; }
        .badge-pending_payment { background: rgba(96,165,250,0.15); color: #93C5FD; }
        .badge-cancelled  { background: rgba(248,113,113,0.15); color: #FCA5A5; }
    </style>

    <div class="max-w-3xl mx-auto fade-in">

        {{-- هدر --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('bookings.index') }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase">نوبت #{{ $booking->id }}</p>
                <h1 class="text-2xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">جزئیات نوبت</h1>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

            {{-- اطلاعات نوبت --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات نوبت</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    @foreach([
                        ['label' => 'خدمت', 'value' => $booking->service->name],
                        ['label' => 'متخصص', 'value' => $booking->specialist->name],
                        ['label' => 'تاریخ و ساعت', 'value' => verta($booking->booking_time)->format('Y/m/d H:i'), 'persian' => true, 'ltr' => true],
                        ['label' => 'مدت زمان', 'value' => $booking->service->duration . ' دقیقه', 'persian' => true],
                    ] as $row)
                        <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">{{ $row['label'] }}</span>
                            <span class="font-medium text-[#F8F3E9] {{ isset($row['persian']) ? 'persian-number' : '' }}"
                              {{ isset($row['ltr']) ? 'dir=ltr' : '' }}>
                            {{ $row['value'] }}
                        </span>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">وضعیت</span>
                        @php
                            $statusMap = [
                                'pending' => ['class' => 'badge-pending', 'label' => 'در انتظار تایید'],
                                'confirmed' => ['class' => 'badge-confirmed', 'label' => 'تایید شده'],
                                'pending_payment' => ['class' => 'badge-pending_payment', 'label' => 'در انتظار پرداخت'],
                                'cancelled' => ['class' => 'badge-cancelled', 'label' => 'لغو شده'],
                            ];
                            $st = $statusMap[$booking->status] ?? ['class' => 'badge-pending', 'label' => $booking->status];
                        @endphp
                        <span class="status-badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                    </div>
                </div>
            </div>

            {{-- اطلاعات پرداخت --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات پرداخت</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">مبلغ کل</span>
                        <span class="font-medium text-[#F8F3E9] persian-number">{{ number_format($booking->service->price) }} تومان</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">پیش‌پرداخت</span>
                        <span class="font-medium text-[#F8F3E9] persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">وضعیت پرداخت</span>
                        @if($booking->payment_status == 'paid')
                            <span class="status-badge" style="background:rgba(52,211,153,0.15);color:#6EE7B7">✓ پرداخت شده</span>
                        @else
                            <span class="status-badge" style="background:rgba(248,113,113,0.15);color:#FCA5A5">✗ پرداخت نشده</span>
                        @endif
                    </div>
                    @if($booking->payment_ref)
                        <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">شماره پیگیری</span>
                            <span class="font-medium text-[#F8F3E9] text-xs" dir="ltr">{{ $booking->payment_ref }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- دکمه‌های عملیات --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('bookings.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm border border-[#C9A24B]/25
                  text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                بازگشت به لیست
            </a>

            @if($booking->payment_status == 'unpaid' && in_array($booking->status, ['pending_payment', 'confirmed']))
                <a href="{{ route('payment.show', $booking) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
                      bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                      hover:shadow-lg hover:shadow-[#C9A24B]/25 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    پرداخت نوبت
                </a>
            @endif

            @if($booking->status == 'confirmed' && $booking->booking_time > now())
                <a href="{{ route('bookings.reschedule', $booking) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm border border-yellow-400/25
                      text-yellow-400 hover:bg-yellow-400/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    تغییر زمان
                </a>
            @endif

            @if(in_array($booking->status, ['pending', 'confirmed', 'pending_payment']) && $booking->booking_time > now()->addHours(24))
                <form action="{{ route('bookings.cancel', $booking) }}" method="POST"
                      onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این نوبت را لغو کنید؟')">
                    @csrf @method('PUT')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm border border-red-400/25
                               text-red-400 hover:bg-red-400/10 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        لغو نوبت
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
