@extends('layouts.app')

@section('title', 'تغییر زمان نوبت')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('bookings.show', $booking) }}"
               class="transition hover:opacity-70" style="color: var(--rasta-gold);">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
            <div>
                <p class="text-xs persian-number" style="color: var(--rasta-gold); opacity: 0.7;">نوبت #{{ $booking->id }}</p>
                <h1 class="text-xl font-bold" style="color: var(--rasta-cream);">تغییر زمان نوبت</h1>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">

            {{-- سرویس و زمان فعلی --}}
            <div class="p-5 border-b" style="border-color: rgba(201,162,75,0.15);">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="text-xs mb-1" style="color: var(--rasta-gold-light); opacity: 0.7;">خدمت</p>
                        <p class="font-semibold" style="color: var(--rasta-cream);">{{ $booking->service->name }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-xs mb-1" style="color: var(--rasta-gold-light); opacity: 0.7;">زمان فعلی نوبت</p>
                        <p class="font-semibold persian-number" dir="ltr" style="color: var(--rasta-gold-light);">
                            {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">

                {{-- انتخاب تاریخ --}}
                <div>
                    <label class="block mb-3 font-medium flex items-center gap-2" style="color: var(--rasta-cream);">
                        <svg class="w-5 h-5" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        انتخاب تاریخ جدید
                    </label>

                    <div id="dates-loading" class="text-center py-6">
                        <div class="inline-block w-8 h-8 border-2 border-t-transparent rounded-full animate-spin"
                             style="border-color: var(--rasta-gold); border-top-color: transparent;"></div>
                        <p class="text-sm mt-2" style="color: var(--rasta-cream); opacity: 0.6;">در حال بارگذاری تاریخ‌ها...</p>
                    </div>

                    <div id="dates-empty" class="hidden text-center py-6 rounded-lg" style="background-color: rgba(201,162,75,0.05); border: 1px dashed rgba(201,162,75,0.3);">
                        <p class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">تاریخ خالی یافت نشد</p>
                    </div>

                    <div id="dates-grid" class="hidden grid grid-cols-4 sm:grid-cols-7 gap-2"></div>
                </div>

                {{-- انتخاب ساعت --}}
                <div id="time-section" class="hidden">
                    <label class="block mb-3 font-medium flex items-center gap-2" style="color: var(--rasta-cream);">
                        <svg class="w-5 h-5" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        انتخاب ساعت جدید
                    </label>

                    <div id="times-loading" class="hidden text-center py-4">
                        <div class="inline-block w-6 h-6 border-2 border-t-transparent rounded-full animate-spin"
                             style="border-color: var(--rasta-gold); border-top-color: transparent;"></div>
                    </div>

                    <div id="times-empty" class="hidden text-center py-4 rounded-lg" style="background-color: rgba(201,162,75,0.05); border: 1px dashed rgba(201,162,75,0.3);">
                        <p class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">ساعت خالی در این روز وجود ندارد</p>
                    </div>

                    <div id="times-grid" class="hidden grid grid-cols-4 gap-2"></div>
                </div>

                {{-- خلاصه تغییرات --}}
                <div id="summary" class="hidden rounded-lg p-5" style="background-color: rgba(201,162,75,0.07); border: 1px solid rgba(201,162,75,0.2);">
                    <h3 class="font-bold mb-4" style="color: var(--rasta-gold-light);">خلاصه تغییرات</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg p-3" style="background-color: var(--rasta-bg); border: 1px solid rgba(201,162,75,0.15);">
                            <p class="text-xs mb-1" style="color: var(--rasta-cream); opacity: 0.6;">زمان فعلی:</p>
                            <p class="font-medium persian-number" dir="ltr" style="color: var(--rasta-cream);">
                                {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                            </p>
                        </div>
                        <div class="rounded-lg p-3" style="background: linear-gradient(135deg, rgba(201,162,75,0.2), rgba(230,205,138,0.15)); border: 1px solid rgba(201,162,75,0.3);">
                            <p class="text-xs mb-1" style="color: var(--rasta-gold-light); opacity: 0.8;">زمان جدید:</p>
                            <p id="summary-new-time" class="font-bold persian-number" dir="ltr" style="color: var(--rasta-gold-light);">—</p>
                        </div>
                    </div>
                </div>

                {{-- دکمه‌ها --}}
                <div class="flex gap-3 pt-2">
                    <button id="submit-btn"
                            disabled
                            class="flex-1 py-3 rounded-lg font-bold transition-opacity flex items-center justify-center gap-2"
                            style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark); opacity: 0.4; cursor: not-allowed;">
                        <svg id="submit-spinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="submit-label">ثبت تغییرات</span>
                    </button>
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="px-6 py-3 rounded-lg font-medium transition hover:bg-white/5"
                       style="border: 1px solid rgba(201,162,75,0.3); color: var(--rasta-cream); opacity: 0.7;">
                        انصراف
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/persian-date@latest/dist/persian-date.min.js"></script>
    <script>
        (function() {
            const SPECIALIST_ID = {{ $booking->specialist_id }};
            const BOOKING_ID    = {{ $booking->id }};
            const CSRF          = document.querySelector('meta[name="csrf-token"]').content;

            let selectedDate = null;
            let selectedTime = null;

            // ---- helpers ----
            function formatPersianDate(isoStr) {
                try {
                    return new persianDate(new Date(isoStr)).format('YYYY/MM/DD');
                } catch(e) {
                    return isoStr;
                }
            }

            function formatDayLabel(isoStr) {
                try {
                    const pd = new persianDate(new Date(isoStr));
                    const days = ['ی','د','س','چ','پ','ج','ش'];
                    return '<span class="block text-xs opacity-60">' + days[pd.day()] + '</span>'
                        + '<span class="block font-bold persian-number">' + pd.date() + '</span>';
                } catch(e) {
                    return isoStr;
                }
            }

            function showEl(id)  { document.getElementById(id).classList.remove('hidden'); }
            function hideEl(id)  { document.getElementById(id).classList.add('hidden'); }

            function setSubmit(enabled) {
                const btn = document.getElementById('submit-btn');
                btn.disabled = !enabled;
                btn.style.opacity = enabled ? '1' : '0.4';
                btn.style.cursor  = enabled ? 'pointer' : 'not-allowed';
            }

            function updateSummary() {
                if (selectedDate && selectedTime) {
                    document.getElementById('summary-new-time').textContent =
                        formatPersianDate(selectedDate) + ' ' + selectedTime;
                    showEl('summary');
                    setSubmit(true);
                } else {
                    hideEl('summary');
                    setSubmit(false);
                }
            }

            // ---- date grid ----
            function renderDates(dates) {
                hideEl('dates-loading');
                if (!dates.length) { showEl('dates-empty'); return; }

                const grid = document.getElementById('dates-grid');
                grid.innerHTML = '';
                dates.forEach(d => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.date = d;
                    btn.innerHTML = formatDayLabel(d);
                    btn.className = 'p-2 rounded-lg text-center transition';
                    btn.style.cssText = 'border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);';

                    btn.addEventListener('click', () => selectDate(d));
                    grid.appendChild(btn);
                });
                showEl('dates-grid');
            }

            function selectDate(date) {
                selectedDate = date;
                selectedTime = null;
                updateSummary();

                document.querySelectorAll('#dates-grid button').forEach(btn => {
                    const active = btn.dataset.date === date;
                    btn.style.background = active
                        ? 'linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold))'
                        : '';
                    btn.style.color  = active ? 'var(--rasta-dark)' : 'var(--rasta-cream)';
                    btn.style.border = active
                        ? '1px solid var(--rasta-gold)'
                        : '1px solid rgba(201,162,75,0.25)';
                });

                showEl('time-section');
                hideEl('times-grid');
                hideEl('times-empty');
                showEl('times-loading');
                loadTimeSlots(date);
            }

            // ---- time grid ----
            function renderTimes(times) {
                hideEl('times-loading');
                if (!times.length) { showEl('times-empty'); return; }

                const grid = document.getElementById('times-grid');
                grid.innerHTML = '';
                times.forEach(t => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.time = t;
                    btn.textContent = t;
                    btn.className = 'p-2 rounded-lg text-center transition font-medium';
                    btn.style.cssText = 'border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);';

                    btn.addEventListener('click', () => selectTime(t));
                    grid.appendChild(btn);
                });
                showEl('times-grid');
            }

            function selectTime(time) {
                selectedTime = time;
                updateSummary();

                document.querySelectorAll('#times-grid button').forEach(btn => {
                    const active = btn.dataset.time === time;
                    btn.style.background = active
                        ? 'linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold))'
                        : '';
                    btn.style.color  = active ? 'var(--rasta-dark)' : 'var(--rasta-cream)';
                    btn.style.border = active
                        ? '1px solid var(--rasta-gold)'
                        : '1px solid rgba(201,162,75,0.25)';
                });
            }

            // ---- API calls ----
            async function loadAvailableDates() {
                try {
                    const res = await fetch(`/api/available-dates/${SPECIALIST_ID}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    renderDates(Array.isArray(data) ? data : (data.dates || []));
                } catch(e) {
                    hideEl('dates-loading');
                    showEl('dates-empty');
                    console.error('Error loading dates:', e);
                }
            }

            async function loadTimeSlots(date) {
                try {
                    const res = await fetch(`/api/time-slots/${SPECIALIST_ID}/${date}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    renderTimes(Array.isArray(data) ? data : (data.slots || []));
                } catch(e) {
                    hideEl('times-loading');
                    showEl('times-empty');
                    console.error('Error loading time slots:', e);
                }
            }

            // ---- submit ----
            document.getElementById('submit-btn').addEventListener('click', async function() {
                if (!selectedDate || !selectedTime) return;

                const spinner = document.getElementById('submit-spinner');
                const label   = document.getElementById('submit-label');
                spinner.classList.remove('hidden');
                label.textContent = 'در حال پردازش...';
                setSubmit(false);

                try {
                    const res = await fetch(`/bookings/${BOOKING_ID}/reschedule`, {
                        method : 'PUT',
                        headers: {
                            'Content-Type' : 'application/json',
                            'Accept'       : 'application/json',
                            'X-CSRF-TOKEN' : CSRF
                        },
                        body: JSON.stringify({
                            booking_time: selectedDate + ' ' + selectedTime
                        })
                    });

                    if (res.ok) {
                        const data = await res.json().catch(() => ({}));
                        window.location.href = data.redirect || `/bookings/${BOOKING_ID}`;
                    } else {
                        const err = await res.json().catch(() => ({}));
                        alert(err.message || 'خطا در تغییر زمان نوبت');
                        spinner.classList.add('hidden');
                        label.textContent = 'ثبت تغییرات';
                        setSubmit(true);
                    }
                } catch(e) {
                    console.error(e);
                    alert('خطا در ارتباط با سرور');
                    spinner.classList.add('hidden');
                    label.textContent = 'ثبت تغییرات';
                    setSubmit(true);
                }
            });

            // ---- init ----
            loadAvailableDates();
        })();
    </script>
@endpush
