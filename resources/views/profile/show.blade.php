@extends('layouts.app')

@section('title', 'پروفایل کاربری')

@section('content')
    <style>
        .status-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }
        .badge-pending    { background:rgba(251,191,36,0.15); color:#FCD34D; }
        .badge-confirmed  { background:rgba(52,211,153,0.15); color:#6EE7B7; }
        .badge-cancelled  { background:rgba(248,113,113,0.15); color:#FCA5A5; }
        .badge-pending_payment { background:rgba(96,165,250,0.15); color:#93C5FD; }
    </style>

    <div class="max-w-6xl mx-auto fade-in">

        {{-- Header --}}
        <div class="mb-8">
            <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">حساب کاربری</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پروفایل من</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Left column: User information --}}
            <div class="md:col-span-1 space-y-5">

                {{-- Personal information card --}}
                <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#C9A24B]/10 flex items-center justify-between">
                        <h2 class="font-bold text-sm text-[#E6CD8A] flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            اطلاعات شخصی
                        </h2>
                        <a href="{{ route('profile.edit') }}"
                           class="text-xs text-[#C9A24B] hover:text-[#E6CD8A] transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            ویرایش
                        </a>
                    </div>

                    {{-- Avatar --}}
                    <div class="flex flex-col items-center py-6 border-b border-[#C9A24B]/8">
                        <div class="w-16 h-16 rounded-full bg-[#C9A24B]/15 border-2 border-[#C9A24B]/30
                                flex items-center justify-center mb-3
                                text-2xl font-bold text-[#E6CD8A]"
                             style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <p class="font-bold text-[#F8F3E9]">{{ auth()->user()->name }}</p>
                    </div>

                    <div class="divide-y divide-[#C9A24B]/8">
                        <div class="px-5 py-3.5">
                            <p class="text-xs text-[#F8F3E9]/50 mb-1">شماره موبایل</p>
                            <p class="font-medium text-[#F8F3E9] persian-number text-sm" dir="ltr">{{ auth()->user()->phone }}</p>
                        </div>
                        <div class="px-5 py-3.5">
                            <p class="text-xs text-[#F8F3E9]/50 mb-1">ایمیل</p>
                            <p class="font-medium text-[#F8F3E9]/80 text-sm" dir="ltr">
                                {{ auth()->user()->email ?: 'ثبت نشده' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Quick links --}}
                <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-[#C9A24B]/10">
                        <h3 class="font-bold text-sm text-[#E6CD8A]">دسترسی سریع</h3>
                    </div>
                    <div class="divide-y divide-[#C9A24B]/8">
                        @foreach([
                            ['route' => 'bookings.create', 'label' => 'رزرو نوبت جدید', 'icon' => 'M12 4v16m8-8H4'],
                            ['route' => 'bookings.index', 'label' => 'تاریخچه نوبت‌ها', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                            ['route' => 'wallet.index', 'label' => 'کیف پول', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                            ['route' => 'loyalty.index', 'label' => 'امتیازات وفاداری', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                        ] as $link)
                            <a href="{{ route($link['route']) }}"
                               class="flex items-center justify-between px-5 py-3.5 text-sm
                                  text-[#F8F3E9]/70 hover:text-[#E6CD8A] hover:bg-[#C9A24B]/5 transition-colors">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                                </svg>
                                {{ $link['label'] }}
                            </span>
                                <svg class="w-3.5 h-3.5 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right column: Recent turns --}}
            <div class="md:col-span-2">
                <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#C9A24B]/10 flex items-center justify-between">
                        <h2 class="font-bold text-sm text-[#E6CD8A] flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            نوبت‌های اخیر
                        </h2>
                        <a href="{{ route('bookings.create') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold
                              bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                              hover:shadow-md hover:shadow-[#C9A24B]/20 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            رزرو نوبت جدید
                        </a>
                    </div>

                    @if($bookings->isEmpty())
                        <div class="py-16 text-center">
                            <div class="w-14 h-14 rounded-full bg-[#C9A24B]/10 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-[#C9A24B]/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <p class="text-[#F8F3E9]/50 mb-3 text-sm">هنوز نوبتی رزرو نکرده‌اید</p>
                            <a href="{{ route('services.index') }}" class="text-sm text-[#E6CD8A] hover:underline">
                                مشاهده خدمات ←
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                <tr class="border-b border-[#C9A24B]/10 bg-[#1A1410]/40">
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-[#F8F3E9]/50">خدمت</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-[#F8F3E9]/50">متخصص</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-[#F8F3E9]/50">تاریخ</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-[#F8F3E9]/50">وضعیت</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-[#F8F3E9]/50">عملیات</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-[#C9A24B]/8">
                                @foreach($bookings as $booking)
                                    <tr class="hover:bg-[#C9A24B]/5 transition-colors">
                                        <td class="px-5 py-3.5 font-medium text-[#F8F3E9]">{{ $booking->service->name }}</td>
                                        <td class="px-5 py-3.5 text-[#F8F3E9]/70">{{ $booking->specialist->name }}</td>
                                        <td class="px-5 py-3.5 text-[#F8F3E9]/70 persian-number text-xs" dir="ltr">
                                            {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @php
                                                $st = ['pending'=>['badge-pending','در انتظار تایید'],'confirmed'=>['badge-confirmed','تایید شده'],'cancelled'=>['badge-cancelled','لغو شده'],'pending_payment'=>['badge-pending_payment','در انتظار پرداخت']];
                                                [$cls, $lbl] = $st[$booking->status] ?? ['badge-pending', $booking->status];
                                            @endphp
                                            <span class="status-badge {{ $cls }}">{{ $lbl }}</span>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex gap-2 text-xs">
                                                <a href="{{ route('bookings.show', $booking) }}"
                                                   class="text-[#E6CD8A]/70 hover:text-[#E6CD8A] transition-colors">جزئیات</a>

                                                @if($booking->canBeRescheduled())
                                                    <span class="text-[#C9A24B]/30">|</span>
                                                    <a href="{{ route('bookings.reschedule', $booking) }}"
                                                       class="text-yellow-400/70 hover:text-yellow-400 transition-colors">تغییر زمان</a>
                                                    <span class="text-[#C9A24B]/30">|</span>
                                                    <form action="{{ route('bookings.cancel', $booking) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('آیا از لغو نوبت اطمینان دارید؟')">
                                                        @csrf @method('PUT')
                                                        <button type="submit" class="text-red-400/70 hover:text-red-400 transition-colors">لغو</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($bookings->hasPages())
                            <div class="px-5 py-4 border-t border-[#C9A24B]/10">
                                {{ $bookings->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
