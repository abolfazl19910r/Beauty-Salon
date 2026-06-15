@extends('layouts.app')

@section('title', 'نوبت‌های من')

@section('content')
    <style>
        .status-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
        }
        .badge-pending    { background: rgba(251,191,36,0.15); color: #FCD34D; }
        .badge-confirmed  { background: rgba(52,211,153,0.15); color: #6EE7B7; }
        .badge-pending_payment { background: rgba(96,165,250,0.15); color: #93C5FD; }
        .badge-cancelled  { background: rgba(248,113,113,0.15); color: #FCA5A5; }
        .badge-paid       { background: rgba(52,211,153,0.15); color: #6EE7B7; }
        .badge-unpaid     { background: rgba(248,113,113,0.15); color: #FCA5A5; }

        .gold-select {
            background: rgba(248,243,233,0.04); border: 1px solid rgba(201,162,75,0.2);
            color: #F8F3E9; border-radius: 0.625rem; padding: 0.5rem 0.875rem;
            font-size: 0.875rem; transition: border-color 0.2s; -webkit-appearance: none;
        }
        .gold-select:focus { outline: none; border-color: #C9A24B; }
        .gold-select option { background: #2E2117; }

        .gold-input {
            background: rgba(248,243,233,0.04); border: 1px solid rgba(201,162,75,0.2);
            color: #F8F3E9; border-radius: 0.625rem; padding: 0.5rem 0.875rem;
            font-size: 0.875rem; transition: border-color 0.2s;
        }
        .gold-input:focus { outline: none; border-color: #C9A24B; }

        .action-btn { transition: color 0.2s, transform 0.2s; }
        .action-btn:hover { transform: scale(1.15); }
    </style>

    <div class="max-w-7xl mx-auto fade-in">

        {{-- هدر --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">حساب کاربری</p>
                <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">نوبت‌های من</h1>
            </div>
            <a href="{{ route('bookings.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                  hover:shadow-lg hover:shadow-[#C9A24B]/25 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                رزرو نوبت جدید
            </a>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">

            {{-- فیلتر --}}
            <div class="px-5 py-4 border-b border-[#C9A24B]/10">
                <form action="{{ route('bookings.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <select name="status" class="gold-select">
                        <option value="">همه وضعیت‌ها</option>
                        @foreach([
                            'pending' => 'در انتظار تایید',
                            'confirmed' => 'تایید شده',
                            'pending_payment' => 'در انتظار پرداخت',
                            'cancelled' => 'لغو شده',
                        ] as $val => $label)
                            <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="date" value="{{ request('date') }}"
                           class="gold-input" placeholder="فیلتر تاریخ">

                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all
                               bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                               hover:shadow-md hover:shadow-[#C9A24B]/20">
                        اعمال فیلتر
                    </button>

                    @if(request('status') || request('date'))
                        <a href="{{ route('bookings.index') }}"
                           class="text-sm text-[#F8F3E9]/50 hover:text-[#F8F3E9] transition-colors self-center">
                            حذف فیلتر ×
                        </a>
                    @endif
                </form>
            </div>

            {{-- جدول / خالی --}}
            @if($bookings->isEmpty())
                <div class="py-20 text-center">
                    <div class="w-16 h-16 rounded-full bg-[#C9A24B]/10 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-[#C9A24B]/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <p class="text-[#F8F3E9]/50 mb-3">هنوز نوبتی ثبت نشده</p>
                    <a href="{{ route('bookings.create') }}" class="text-sm text-[#E6CD8A] hover:underline">
                        اولین نوبت خود را رزرو کنید ←
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b border-[#C9A24B]/10 bg-[#1A1410]/40">
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#F8F3E9]/50 uppercase tracking-wider">خدمت</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#F8F3E9]/50 uppercase tracking-wider">متخصص</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#F8F3E9]/50 uppercase tracking-wider">تاریخ و ساعت</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#F8F3E9]/50 uppercase tracking-wider">پرداخت</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#F8F3E9]/50 uppercase tracking-wider">وضعیت</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#F8F3E9]/50 uppercase tracking-wider">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-[#C9A24B]/8">
                        @foreach($bookings as $booking)
                            <tr class="hover:bg-[#C9A24B]/5 transition-colors">
                                <td class="px-5 py-4 font-medium text-[#F8F3E9]">{{ $booking->service->name }}</td>
                                <td class="px-5 py-4 text-[#F8F3E9]/75">{{ $booking->specialist->name }}</td>
                                <td class="px-5 py-4 persian-number text-[#F8F3E9]/75" dir="ltr">
                                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($booking->payment_status == 'paid')
                                        <span class="status-badge badge-paid">✓ پرداخت شده</span>
                                    @else
                                        <span class="status-badge badge-unpaid">✗ پرداخت نشده</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
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
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('bookings.show', $booking) }}"
                                           class="action-btn text-[#E6CD8A]/70 hover:text-[#E6CD8A]" title="جزئیات">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        @if($booking->payment_status == 'unpaid' && in_array($booking->status, ['pending_payment', 'confirmed']))
                                            <a href="{{ route('payment.show', $booking) }}"
                                               class="action-btn text-emerald-400 hover:text-emerald-300" title="پرداخت">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </a>
                                        @endif

                                        @if($booking->status == 'confirmed' && $booking->booking_time > now())
                                            <a href="{{ route('bookings.reschedule', $booking) }}"
                                               class="action-btn text-yellow-400 hover:text-yellow-300" title="تغییر زمان">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </a>
                                        @endif

                                        @if(in_array($booking->status, ['pending', 'confirmed', 'pending_payment']) && $booking->booking_time > now()->addHours(24))
                                            <form action="{{ route('bookings.cancel', $booking) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این نوبت را لغو کنید؟')">
                                                @csrf @method('PUT')
                                                <button type="submit" class="action-btn text-red-400/70 hover:text-red-400" title="لغو نوبت">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
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
@endsection
