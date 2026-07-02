@extends('layouts.specialist')

@section('title', 'مدیریت نوبت‌های رزرو شده')

@push('styles')
    <style>
        .jcal-wrapper { position: relative; }
        .jcal-popup {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 9999;
            width: 280px;
            background-color: var(--specialist-surface);
            border: 1px solid var(--specialist-border);
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.6);
            padding: 12px;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; color: var(--specialist-text); cursor: pointer; padding: 4px 8px; border-radius: 6px; }
        .jcal-header button:hover { background-color: rgba(216,174,224,0.12); }
        .jcal-title { color: var(--specialist-plum-light); font-weight: bold; font-size: 13px; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
        .jcal-weekday { font-size: 10px; color: var(--specialist-text-dim); opacity: 0.7; padding: 4px 0; }
        .jcal-day { font-size: 12px; color: var(--specialist-text); padding: 6px 0; border-radius: 6px; cursor: pointer; }
        .jcal-day:hover { background-color: rgba(216,174,224,0.15); }
        .jcal-day.jcal-empty { cursor: default; }
        .jcal-day.jcal-empty:hover { background-color: transparent; }
        .jcal-day.jcal-selected { background-color: var(--specialist-plum-mid); color: #250D2B; font-weight: bold; }
        .jcal-day.jcal-today { border: 1px solid var(--specialist-plum-mid); }
    </style>
@endpush

@php
    $statusBadgeMap = [
        'pending'         => ['label' => 'در انتظار تایید', 'class' => 'bg-amber-400/10 text-amber-300'],
        'confirmed'       => ['label' => 'تایید شده',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'completed'       => ['label' => 'انجام شده',        'class' => 'bg-[var(--specialist-plum-mid)]/15 text-[var(--specialist-plum-light)]'],
        'cancelled'       => ['label' => 'لغو شده',          'class' => 'bg-red-500/10 text-red-300'],
        'pending_payment' => ['label' => 'در انتظار پرداخت', 'class' => 'bg-orange-400/10 text-orange-300'],
    ];

    $paymentBadgeMap = [
        'paid'            => ['label' => 'پرداخت شده',       'class' => 'text-emerald-300'],
        'pending_payment' => ['label' => 'در انتظار پرداخت',  'class' => 'text-amber-300'],
        'unpaid'          => ['label' => 'پرداخت نشده',       'class' => 'text-red-300'],
    ];
@endphp

@section('content')
    <div class="fade-in space-y-6">

        {{-- Filter card --}}
        <div class="specialist-card p-5">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    فیلتر نوبت‌ها
                </h3>
                <button onclick="toggleFilters()" type="button" class="text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] text-sm font-medium transition-colors">
                    <span id="filterToggleText">نمایش فیلترها</span>
                </button>
            </div>

            <form method="GET" action="{{ route('specialist.bookings.index') }}" id="filterForm" class="hidden mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">از تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" name="date_from" id="date_from"
                                   value="{{ request('date_from') }}"
                                   class="w-full rounded-lg px-4 py-2 text-center cursor-pointer text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="مثال: 1403/09/15"
                                   dir="ltr"
                                   autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-date_from"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">تا تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" name="date_to" id="date_to"
                                   value="{{ request('date_to') }}"
                                   class="w-full rounded-lg px-4 py-2 text-center cursor-pointer text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="مثال: 1403/09/20"
                                   dir="ltr"
                                   autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-date_to"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">زمان</label>
                        <input type="time" name="time"
                               value="{{ request('time') }}"
                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">وضعیت سرویس</label>
                        <select name="status" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار تایید</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>انجام شده</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                            <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>در انتظار پرداخت</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">وضعیت مالی</label>
                        <select name="payment_status" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                            <option value="pending_payment" {{ request('payment_status') == 'pending_payment' ? 'selected' : '' }}>در انتظار پرداخت</option>
                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>پرداخت نشده</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">شماره تماس</label>
                        <input type="text" name="phone"
                               value="{{ request('phone') }}"
                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                               dir="ltr"
                               placeholder="09123456789">
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">نام مشتری</label>
                        <input type="text" name="customer_name"
                               value="{{ request('customer_name') }}"
                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                               placeholder="نام مشتری">
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">مرتب‌سازی</label>
                        <select name="sort_by" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>جدیدترین</option>
                            <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>قدیمی‌ترین</option>
                            <option value="date_asc" {{ request('sort_by') == 'date_asc' ? 'selected' : '' }}>تاریخ صعودی</option>
                            <option value="date_desc" {{ request('sort_by') == 'date_desc' ? 'selected' : '' }}>تاریخ نزولی</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="specialist-cta px-6 py-2 rounded-lg font-bold transition-opacity hover:opacity-90 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        اعمال فیلتر
                    </button>
                    <a href="{{ route('specialist.bookings.index') }}" class="px-6 py-2 rounded-lg font-medium text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition flex items-center gap-2" style="border: 1px solid var(--specialist-border);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        حذف فیلترها
                    </a>
                </div>
            </form>

            @if(request()->hasAny(['date_from', 'date_to', 'time', 'status', 'payment_status', 'phone', 'customer_name']))
                <div class="mt-4 p-3 rounded-lg" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <span class="text-sm text-[var(--specialist-plum-light)] font-medium">
                            فیلترهای فعال:
                        </span>
                        <div class="flex flex-wrap gap-2">
                            @if(request('date_from'))
                                <span class="specialist-badge px-3 py-1 text-xs font-medium">از تاریخ: {{ request('date_from') }}</span>
                            @endif
                            @if(request('date_to'))
                                <span class="specialist-badge px-3 py-1 text-xs font-medium">تا تاریخ: {{ request('date_to') }}</span>
                            @endif
                            @if(request('status'))
                                <span class="specialist-badge px-3 py-1 text-xs font-medium">وضعیت سرویس</span>
                            @endif
                            @if(request('payment_status'))
                                <span class="specialist-badge px-3 py-1 text-xs font-medium">وضعیت مالی</span>
                            @endif
                            @if(request('phone'))
                                <span class="specialist-badge px-3 py-1 text-xs font-medium">شماره تماس</span>
                            @endif
                            @if(request('customer_name'))
                                <span class="specialist-badge px-3 py-1 text-xs font-medium">نام مشتری</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Bookings list --}}
        <div class="specialist-card overflow-hidden">
            @if($bookings->isEmpty())
                <div class="p-12 text-center text-[var(--specialist-inactive)]">
                    @if(request()->hasAny(['date_from', 'date_to', 'time', 'status', 'payment_status', 'phone', 'customer_name']))
                        <svg class="w-14 h-14 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>نوبتی با فیلترهای انتخابی یافت نشد.</p>
                    @else
                        <svg class="w-14 h-14 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p>هیچ نوبتی در لیست شما یافت نشد.</p>
                    @endif
                </div>
            @else
                <div class="divide-y" style="border-color: var(--specialist-border);">
                    @foreach($bookings as $booking)
                        @php
                            $status = trim($booking->status);
                            $statusInfo = $statusBadgeMap[$status] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                            $paymentInfo = $paymentBadgeMap[$booking->payment_status] ?? ['label' => $booking->payment_status, 'class' => 'text-[var(--specialist-text-dim)]'];
                        @endphp
                        <div class="p-5 booking-row" style="border-color: var(--specialist-border);">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <h3 class="font-bold text-[var(--specialist-text)]">{{ $booking->service?->name ?? '—' }}</h3>
                                        <span class="text-xs px-3 py-1 rounded-full font-bold {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                    </div>

                                    <div class="text-sm text-[var(--specialist-text-dim)] space-y-1">
                                        <p><span class="text-[var(--specialist-plum-muted)]">مشتری:</span> {{ $booking->user->name }} | <span dir="ltr">{{ $booking->user->phone }}</span></p>
                                        <p class="persian-number"><span class="text-[var(--specialist-plum-muted)]">زمان:</span> {{ verta($booking->booking_time)->format('Y/m/d ساعت H:i') }}</p>
                                        <p><span class="text-[var(--specialist-plum-muted)]">وضعیت مالی:</span> <span class="font-medium {{ $paymentInfo['class'] }}">{{ $paymentInfo['label'] }}</span></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 booking-actions">
                                    <a href="{{ route('specialist.bookings.show', $booking->id) }}"
                                       class="p-2 rounded-lg transition text-[var(--specialist-plum-mid)] hover:bg-white/5"
                                       title="مشاهده جزئیات">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(!in_array($status, ['completed', 'cancelled', 'pending_payment']))

                                        @if($status == 'pending')
                                            <button onclick="confirmBooking('{{ route('specialist.bookings.complete', $booking->id) }}', '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service?->name ?? '—') }}')"
                                                    class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-500 transition">
                                                تایید
                                            </button>

                                            <button onclick="showCancelModal({{ $booking->id }}, '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service?->name ?? '—') }}')"
                                                    class="bg-red-600/90 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                                                لغو
                                            </button>
                                        @endif

                                        @if($status == 'confirmed')
                                            <button onclick="markCompleted({{ $booking->id }}, '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service?->name ?? '—') }}')"
                                                    class="specialist-cta px-4 py-2 rounded-lg text-sm font-bold transition-opacity hover:opacity-90">
                                                انجام شد
                                            </button>

                                            <button onclick="showCancelModal({{ $booking->id }}, '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service?->name ?? '—') }}')"
                                                    class="bg-red-600/90 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                                                لغو نوبت
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 border-t" style="border-color: var(--specialist-border);">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Confirm modal --}}
    <div id="confirmModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="specialist-card p-6 max-w-md w-full border" style="border-color: var(--specialist-border);">
            <h3 class="text-lg font-bold mb-4 text-emerald-300 font-serif-fa">تایید نوبت رزرو شده</h3>
            <p class="text-[var(--specialist-text-dim)] mb-6" id="confirmMessage"></p>
            <form id="confirmForm" method="POST">
                @csrf @method('PUT')
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-emerald-600 text-white py-2 rounded-lg font-bold hover:bg-emerald-500 transition">بله، تایید شود</button>
                    <button type="button" onclick="hideConfirmModal()" class="flex-1 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 transition" style="border: 1px solid var(--specialist-border);">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Mark as completed modal --}}
    <div id="completedModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="specialist-card p-6 max-w-md w-full border" style="border-color: var(--specialist-border);">
            <h3 class="text-lg font-bold mb-4 text-[var(--specialist-plum-light)] font-serif-fa">انجام شده</h3>
            <p class="text-[var(--specialist-text-dim)] mb-6" id="completedMessage"></p>
            <form id="completedForm" method="POST">
                @csrf @method('PUT')
                <div class="flex gap-3">
                    <button type="submit" class="specialist-cta flex-1 py-2 rounded-lg font-bold transition-opacity hover:opacity-90">بله، انجام شد</button>
                    <button type="button" onclick="hideCompletedModal()" class="flex-1 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 transition" style="border: 1px solid var(--specialist-border);">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cancel modal --}}
    <div id="cancelListModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="specialist-card p-6 max-w-md w-full border" style="border-color: var(--specialist-border);">
            <h3 class="text-lg font-bold mb-4 text-red-300 font-serif-fa">لغو نوبت</h3>
            <p class="text-[var(--specialist-text-dim)] mb-4" id="cancelMessage"></p>
            <form id="cancelListForm" method="POST">
                @csrf @method('PUT')
                <textarea name="cancel_reason" required rows="3"
                          class="w-full rounded-lg p-3 mb-4 text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                          style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                          placeholder="دلیل لغو را بنویسید..."></textarea>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-500 transition">لغو نوبت</button>
                    <button type="button" onclick="hideCancelModal()" class="flex-1 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 transition" style="border: 1px solid var(--specialist-border);">انصراف</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            function div(a, b) { return Math.trunc(a / b); }

            function gregorianToJalali(gy, gm, gd) {
                const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
                let jy;
                if (gy > 1600) { jy = 979; gy -= 1600; } else { jy = 0; gy -= 621; }
                const gy2 = (gm > 2) ? (gy + 1) : gy;
                let days = (365 * gy) + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) - 80 + gd + g_d_m[gm - 1];
                jy += 33 * div(days, 12053);
                days %= 12053;
                jy += 4 * div(days, 1461);
                days %= 1461;
                if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
                const jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days - 186, 30);
                const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
                return [jy, jm, jd];
            }

            function jalaliToGregorian(jy, jm, jd) {
                let gy;
                if (jy > 979) { gy = 1600; jy -= 979; } else { gy = 621; }
                let days = (365 * jy) + (div(jy, 33) * 8) + div((jy % 33) + 3, 4) + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
                gy += 400 * div(days, 146097);
                days %= 146097;
                if (days > 36524) {
                    gy += 100 * div(--days, 36524);
                    days %= 36524;
                    if (days >= 365) days++;
                }
                gy += 4 * div(days, 1461);
                days %= 1461;
                if (days > 365) { gy += div(days - 1, 365); days = (days - 1) % 365; }
                const gd = days + 1;
                const isLeap = (gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0);
                const sal_a = [0, 31, isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let gm = 0, remaining = gd;
                for (gm = 1; gm <= 12; gm++) {
                    if (remaining <= sal_a[gm]) break;
                    remaining -= sal_a[gm];
                }
                return [gy, gm, remaining];
            }

            const jMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
            const jWeekdays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

            function jalaliMonthLength(jy, jm) {
                if (jm <= 6) return 31;
                if (jm <= 11) return 30;
                const g1 = jalaliToGregorian(jy, jm, 29);
                const g2 = jalaliToGregorian(jy + 1, 1, 1);
                const d1 = new Date(g1[0], g1[1] - 1, g1[2]);
                const d2 = new Date(g2[0], g2[1] - 1, g2[2]);
                const diffDays = Math.round((d2 - d1) / 86400000);
                return 28 + diffDays;
            }

            function buildCalendar(input, popup) {
                const today = new Date();
                const [tjy, tjm, tjd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());

                let viewYear = tjy, viewMonth = tjm;
                let selectedValue = input.value || '';

                if (selectedValue.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/)) {
                    const parts = selectedValue.split('/').map(Number);
                    viewYear = parts[0];
                    viewMonth = parts[1];
                }

                function render() {
                    const firstDayGregorian = jalaliToGregorian(viewYear, viewMonth, 1);
                    const firstDate = new Date(firstDayGregorian[0], firstDayGregorian[1] - 1, firstDayGregorian[2]);
                    const jsDay = firstDate.getDay();
                    const startOffset = (jsDay + 1) % 7;

                    const monthLength = jalaliMonthLength(viewYear, viewMonth);

                    let html = '<div class="jcal-header">';
                    html += '<button type="button" data-nav="prev">&#9658;</button>';
                    html += '<span class="jcal-title persian-number">' + jMonths[viewMonth - 1] + ' ' + viewYear + '</span>';
                    html += '<button type="button" data-nav="next">&#9664;</button>';
                    html += '</div>';
                    html += '<div class="jcal-grid">';
                    jWeekdays.forEach(w => { html += '<div class="jcal-weekday">' + w + '</div>'; });

                    for (let i = 0; i < startOffset; i++) {
                        html += '<div class="jcal-day jcal-empty"></div>';
                    }
                    for (let d = 1; d <= monthLength; d++) {
                        const isToday = (viewYear === tjy && viewMonth === tjm && d === tjd);
                        const dayValue = viewYear + '/' + String(viewMonth).padStart(2, '0') + '/' + String(d).padStart(2, '0');
                        const isSelected = (dayValue === selectedValue);
                        html += '<div class="jcal-day persian-number' + (isToday ? ' jcal-today' : '') + (isSelected ? ' jcal-selected' : '') + '" data-day="' + d + '">' + d + '</div>';
                    }
                    html += '</div>';
                    popup.innerHTML = html;

                    popup.querySelector('[data-nav="prev"]').addEventListener('click', function(e) {
                        e.stopPropagation();
                        viewMonth--;
                        if (viewMonth < 1) { viewMonth = 12; viewYear--; }
                        render();
                    });
                    popup.querySelector('[data-nav="next"]').addEventListener('click', function(e) {
                        e.stopPropagation();
                        viewMonth++;
                        if (viewMonth > 12) { viewMonth = 1; viewYear++; }
                        render();
                    });
                    popup.querySelectorAll('.jcal-day[data-day]').forEach(function(el) {
                        el.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const d = parseInt(this.dataset.day, 10);
                            selectedValue = viewYear + '/' + String(viewMonth).padStart(2, '0') + '/' + String(d).padStart(2, '0');
                            input.value = selectedValue;
                            popup.classList.remove('open');
                            input.dispatchEvent(new Event('change'));
                        });
                    });
                }

                render();
            }

            function initCalendar(inputId, popupId) {
                const input = document.getElementById(inputId);
                const popup = document.getElementById(popupId);
                if (!input || !popup) return;

                input.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.querySelectorAll('.jcal-popup.open').forEach(p => { if (p !== popup) p.classList.remove('open'); });
                    buildCalendar(input, popup);
                    popup.classList.add('open');
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                initCalendar('date_from', 'jcal-popup-date_from');
                initCalendar('date_to', 'jcal-popup-date_to');

                document.addEventListener('click', function() {
                    document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                });
            });
        })();

        function toggleFilters() {
            const filterForm = document.getElementById('filterForm');
            const toggleText = document.getElementById('filterToggleText');

            if (filterForm.classList.contains('hidden')) {
                filterForm.classList.remove('hidden');
                toggleText.textContent = 'پنهان کردن فیلترها';
            } else {
                filterForm.classList.add('hidden');
                toggleText.textContent = 'نمایش فیلترها';
            }
        }

        @if(request()->hasAny(['date_from', 'date_to', 'time', 'status', 'payment_status', 'phone', 'customer_name']))
        document.addEventListener('DOMContentLoaded', function() {
            toggleFilters();
        });
        @endif

        function confirmBooking(url, name, service) {
            document.getElementById('confirmMessage').innerText = `آیا از تایید نوبت "${service}" برای مشتری "${name}" اطمینان دارید؟`;
            document.getElementById('confirmForm').action = url;
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function markCompleted(id, name, service) {
            document.getElementById('completedMessage').innerText = `آیا می‌خواهید نوبت "${service}" برای "${name}" را به عنوان انجام شده علامت‌گذاری کنید؟`;
            document.getElementById('completedForm').action = `/specialist/bookings/${id}/mark-completed`;
            document.getElementById('completedModal').classList.remove('hidden');
        }

        function hideCompletedModal() {
            document.getElementById('completedModal').classList.add('hidden');
        }

        function showCancelModal(id, name, service) {
            document.getElementById('cancelMessage').innerText = `آیا می‌خواهید نوبت "${service}" مشتری "${name}" را لغو کنید؟`;
            document.getElementById('cancelListForm').action = `/specialist/bookings/${id}/cancel`;
            document.getElementById('cancelListModal').classList.remove('hidden');
        }

        function hideCancelModal() {
            document.getElementById('cancelListModal').classList.add('hidden');
        }
    </script>
@endpush
