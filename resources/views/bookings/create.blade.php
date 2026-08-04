@extends('layouts.app')

@section('title', 'رزرو نوبت')

@section('content')
    <style>
        .slot-btn {
            background: rgba(201,162,75,0.08);
            border: 1px solid rgba(201,162,75,0.2);
            color: rgba(248,243,233,0.8);
            transition: all 0.25s;
            cursor: pointer;
        }
        .slot-btn:hover { background: rgba(201,162,75,0.18); color: #E6CD8A; }
        .slot-btn.selected {
            background: linear-gradient(135deg, #E6CD8A, #C9A24B);
            color: #1A1410;
            font-weight: 700;
            border-color: transparent;
        }
        .date-btn {
            background: rgba(201,162,75,0.08);
            border: 1px solid rgba(201,162,75,0.15);
            color: rgba(248,243,233,0.75);
            transition: all 0.25s;
            cursor: pointer;
        }
        .date-btn:hover { background: rgba(201,162,75,0.18); color: #E6CD8A; }
        .date-btn.selected {
            background: linear-gradient(135deg, #E6CD8A, #C9A24B);
            color: #1A1410;
            font-weight: 700;
            border-color: transparent;
        }
        .gold-select {
            background: rgba(248,243,233,0.04);
            border: 1px solid rgba(201,162,75,0.25);
            color: #F8F3E9;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: border-color 0.2s;
            -webkit-appearance: none;
        }
        .gold-select:focus { outline: none; border-color: #C9A24B; box-shadow: 0 0 0 3px rgba(201,162,75,0.15); }
        .gold-select option { background: #2E2117; color: #F8F3E9; }

        .summary-box { background: rgba(201,162,75,0.06); border: 1px solid rgba(201,162,75,0.15); border-radius: 1rem; }
        .summary-row { border-bottom: 1px solid rgba(201,162,75,0.08); }
        .summary-row:last-child { border-bottom: none; }

        .hidden-block { display: none !important; }

        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <div class="max-w-3xl mx-auto fade-in">
        {{-- Header --}}
        <div class="mb-8">
            <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">سالن راستا</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">رزرو نوبت جدید</h1>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6 md:p-8">
            <form id="booking-form" method="POST" action="{{ route('bookings.confirm') }}" class="space-y-7">
                @csrf
                <input type="hidden" name="service_id" id="input-service-id">
                <input type="hidden" name="specialist_id" id="input-specialist-id">
                <input type="hidden" name="booking_time" id="input-booking-time">

                {{-- Select service --}}
                <div>
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-2">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                            انتخاب خدمت
                        </span>
                    </label>
                    <select id="service-select" class="gold-select">
                        <option value="">خدمت مورد نظر را انتخاب کنید</option>
                    </select>
                </div>

                {{-- Expert selection --}}
                <div id="specialist-section" class="hidden-block">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-2">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            انتخاب متخصص
                        </span>
                    </label>
                    <select id="specialist-select" class="gold-select">
                        <option value="">متخصص مورد نظر را انتخاب کنید</option>
                    </select>
                </div>

                {{-- loading dates --}}
                <div id="dates-loading" class="hidden-block flex items-center gap-3 py-4 text-sm text-[#F8F3E9]/60">
                    <svg class="spin w-5 h-5 text-[#C9A24B]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    در حال بارگذاری تاریخ‌های موجود...
                </div>

                {{-- Select date --}}
                <div id="dates-section" class="hidden-block">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-3">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            انتخاب تاریخ
                        </span>
                    </label>
                    <div id="dates-empty" class="hidden-block text-center py-4 rounded-lg" style="background-color: rgba(201,162,75,0.05); border: 1px dashed rgba(201,162,75,0.3);">
                        <p class="text-sm text-[#F8F3E9]/60">تاریخ خالی برای این متخصص یافت نشد</p>
                    </div>
                    <div id="dates-grid" class="grid grid-cols-4 sm:grid-cols-7 gap-2"></div>
                </div>

                {{-- loading hours --}}
                <div id="slots-loading" class="hidden-block flex items-center gap-3 py-4 text-sm text-[#F8F3E9]/60">
                    <svg class="spin w-5 h-5 text-[#C9A24B]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    در حال بارگذاری ساعت‌های موجود...
                </div>

                {{-- Choose a time --}}
                <div id="slots-section" class="hidden-block">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-3">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                            </svg>
                            انتخاب ساعت
                            <span id="duration-label" class="mr-2 text-[#F8F3E9]/50 text-xs font-normal persian-number"></span>
                        </span>
                    </label>
                    <div id="slots-empty" class="hidden-block text-center py-4 rounded-lg" style="background-color: rgba(201,162,75,0.05); border: 1px dashed rgba(201,162,75,0.3);">
                        <p id="slots-empty-message" class="text-sm text-[#F8F3E9]/60">ساعت خالی در این روز وجود ندارد</p>
                    </div>
                    <div id="slots-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
                </div>

                {{-- Reservation Summary --}}
                <div id="summary-section" class="hidden-block summary-box p-5">
                    <h3 class="font-bold text-[#E6CD8A] mb-4 flex items-center gap-1.5"
                        style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        خلاصه رزرو
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">خدمت</span>
                            <span id="summary-service" class="font-medium text-[#F8F3E9]"></span>
                        </div>
                        <div id="summary-duration-row" class="summary-row flex justify-between pb-3 hidden-block">
                            <span class="text-[#F8F3E9]/60">مدت زمان</span>
                            <span id="summary-duration" class="font-medium text-[#F8F3E9] persian-number"></span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">متخصص</span>
                            <span id="summary-specialist" class="font-medium text-[#F8F3E9]"></span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">تاریخ</span>
                            <span id="summary-date" class="font-medium text-[#F8F3E9] persian-number"></span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">ساعت شروع</span>
                            <span id="summary-time" class="font-medium text-[#F8F3E9]"></span>
                        </div>
                        <div id="summary-end-row" class="summary-row flex justify-between pb-3 hidden-block">
                            <span class="text-[#F8F3E9]/60">ساعت پایان (تقریبی)</span>
                            <span id="summary-end-time" class="font-medium text-[#F8F3E9]"></span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">قیمت کل خدمت</span>
                            <span id="summary-total-price" class="font-medium text-[#F8F3E9] persian-number">—</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="font-bold text-[#F8F3E9]">مبلغ پیش‌پرداخت</span>
                            <span id="summary-prepayment" class="font-bold text-[#E6CD8A] persian-number">—</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-[#F8F3E9]/60">باقی‌مانده (موقع نوبت به متخصص می‌دهید)</span>
                            <span id="summary-remaining" class="font-medium text-[#F8F3E9]/80 persian-number">—</span>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('services.index') }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm border border-[#C9A24B]/25
                          text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                        بازگشت
                    </a>
                    <button type="submit" id="submit-btn" disabled
                            class="flex-1 py-3 rounded-xl text-sm font-bold transition-all duration-300
                               bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                               disabled:opacity-40 disabled:cursor-not-allowed
                               hover:shadow-lg hover:shadow-[#C9A24B]/30">
                        <span id="submit-spinner" class="hidden-block items-center justify-center gap-2">
                            <svg class="spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            در حال پردازش...
                        </span>
                        <span id="submit-label">ادامه و تایید رزرو</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            // --------------------------------------------------------------
            // Self-contained Gregorian↔solar conversion — no external libraries/CDN
            // (same jcal algorithm used in the rest of the project pages)
            // ---------------------------------------------------------------
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

            const jWeekdays = ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش']; // According to JavaScript getDay() (0=Sunday)

            function formatJalaliFull(isoDate) {
                const d = new Date(isoDate + 'T00:00:00');
                const [jy, jm, jd] = gregorianToJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
                return jy + '/' + String(jm).padStart(2, '0') + '/' + String(jd).padStart(2, '0');
            }

            function formatJalaliDayLabel(isoDate) {
                const d = new Date(isoDate + 'T00:00:00');
                const [, , jd] = gregorianToJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
                return '<span class="block text-[10px] opacity-60">' + jWeekdays[d.getDay()] + '</span>'
                    + '<span class="block font-bold persian-number">' + jd + '</span>';
            }

            // ---------------------------------------------------------------
            // Page status
            // ---------------------------------------------------------------
            let services = [];
            let specialists = [];
            let selectedService = null;
            let selectedSpecialist = null;
            let selectedDate = null;
            let selectedTime = null;

            function showEl(id) { document.getElementById(id).classList.remove('hidden-block'); }
            function hideEl(id) { document.getElementById(id).classList.add('hidden-block'); }

            function formatPrice(price) {
                return new Intl.NumberFormat('fa-IR').format(price);
            }

            function currentServiceObj() {
                return services.find(s => String(s.id) === String(selectedService)) || null;
            }
            function currentSpecialistObj() {
                return specialists.find(s => String(s.id) === String(selectedSpecialist)) || null;
            }

            function calculateEndTime(startTime, duration) {
                if (!startTime || !duration) return '';
                const [hours, minutes] = startTime.split(':').map(Number);
                const totalMinutes = hours * 60 + minutes + duration;
                const endHours = Math.floor(totalMinutes / 60);
                const endMinutes = totalMinutes % 60;
                return String(endHours).padStart(2, '0') + ':' + String(endMinutes).padStart(2, '0');
            }

            function updateSubmitState() {
                const ready = selectedService && selectedSpecialist && selectedDate && selectedTime;
                document.getElementById('submit-btn').disabled = !ready;
            }

            function updateSummary() {
                const service = currentServiceObj();
                const specialist = currentSpecialistObj();

                if (!selectedTime || !service || !specialist) {
                    hideEl('summary-section');
                    updateSubmitState();
                    return;
                }

                document.getElementById('summary-service').textContent = service.name;
                document.getElementById('summary-specialist').textContent = specialist.name;
                document.getElementById('summary-date').textContent = formatJalaliFull(selectedDate);
                document.getElementById('summary-time').textContent = selectedTime;
                // prepayment_amount comes from the server (ServiceController::list(), computed via
                // WalletSetting::calculatePrepaymentAmount()) — was previously a hardcoded ۵۰٬۰۰۰
                // placeholder here regardless of the actual service price/admin settings.
                const totalPrice = Number(service.price || 0);
                const prepayment = Number(service.prepayment_amount || 0);
                const remaining = Math.max(0, totalPrice - prepayment);
                document.getElementById('summary-total-price').textContent =
                    totalPrice.toLocaleString('fa-IR') + ' تومان';
                document.getElementById('summary-prepayment').textContent =
                    prepayment.toLocaleString('fa-IR') + ' تومان';
                document.getElementById('summary-remaining').textContent =
                    remaining.toLocaleString('fa-IR') + ' تومان';

                if (service.duration) {
                    document.getElementById('summary-duration').textContent = service.duration + ' دقیقه';
                    showEl('summary-duration-row');
                    document.getElementById('summary-end-time').textContent = calculateEndTime(selectedTime, service.duration);
                    showEl('summary-end-row');
                } else {
                    hideEl('summary-duration-row');
                    hideEl('summary-end-row');
                }

                showEl('summary-section');
                updateSubmitState();
            }

            // ---------------------------------------------------------------
            // Load the service
            // ---------------------------------------------------------------
            async function loadServices() {
                try {
                    const response = await fetch('/api/services', { headers: { 'Accept': 'application/json' } });
                    services = await response.json();

                    const select = document.getElementById('service-select');
                    services.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = service.name + ' — ' + formatPrice(service.price) + ' تومان';
                        select.appendChild(option);
                    });

                    const urlParams = new URLSearchParams(window.location.search);
                    const serviceFromUrl = urlParams.get('service');
                    if (serviceFromUrl && services.some(s => String(s.id) === String(serviceFromUrl))) {
                        select.value = serviceFromUrl;
                        onServiceChange();
                    }
                } catch (error) {
                    console.error('خطا در بارگذاری سرویس‌ها:', error);
                }
            }

            async function onServiceChange() {
                selectedService = document.getElementById('service-select').value || null;
                selectedSpecialist = null;
                selectedDate = null;
                selectedTime = null;

                document.getElementById('input-service-id').value = selectedService || '';
                document.getElementById('specialist-select').value = '';

                hideEl('dates-section');
                hideEl('slots-section');
                hideEl('summary-section');
                updateSubmitState();

                if (!selectedService) {
                    hideEl('specialist-section');
                    return;
                }

                showEl('specialist-section');
                await loadSpecialists();
            }

            // ---------------------------------------------------------------
            // Loading service-related specialists
            // ---------------------------------------------------------------
            async function loadSpecialists() {
                try {
                    const response = await fetch(`/api/specialists/${selectedService}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    specialists = await response.json();

                    const select = document.getElementById('specialist-select');
                    select.innerHTML = '<option value="">متخصص مورد نظر را انتخاب کنید</option>';
                    specialists.forEach(specialist => {
                        const option = document.createElement('option');
                        option.value = specialist.id;
                        option.textContent = specialist.name;
                        select.appendChild(option);
                    });
                } catch (error) {
                    console.error('خطا در بارگذاری متخصصین:', error);
                }
            }

            async function onSpecialistChange() {
                selectedSpecialist = document.getElementById('specialist-select').value || null;
                selectedDate = null;
                selectedTime = null;

                document.getElementById('input-specialist-id').value = selectedSpecialist || '';

                hideEl('slots-section');
                hideEl('summary-section');
                updateSubmitState();

                if (!selectedSpecialist) {
                    hideEl('dates-section');
                    return;
                }

                await loadAvailableDates();
            }

            // ---------------------------------------------------------------
            // Loading available dates
            // ---------------------------------------------------------------
            async function loadAvailableDates() {
                showEl('dates-loading');
                hideEl('dates-section');
                try {
                    const response = await fetch(`/api/available-dates/${selectedSpecialist}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const dates = await response.json();
                    renderDates(Array.isArray(dates) ? dates : []);
                } catch (error) {
                    console.error('خطا در بارگذاری تاریخ‌ها:', error);
                    renderDates([]);
                } finally {
                    hideEl('dates-loading');
                    showEl('dates-section');
                }
            }

            function renderDates(dates) {
                const grid = document.getElementById('dates-grid');
                grid.innerHTML = '';

                if (!dates.length) {
                    showEl('dates-empty');
                    return;
                }
                hideEl('dates-empty');

                dates.forEach(date => {
                    const btn = document.createElement('div');
                    btn.className = 'date-btn text-center p-2.5 rounded-xl text-xs persian-number';
                    btn.innerHTML = formatJalaliDayLabel(date);
                    btn.addEventListener('click', () => selectDate(date, btn));
                    grid.appendChild(btn);
                });
            }

            function selectDate(date, btnEl) {
                selectedDate = date;
                selectedTime = null;

                document.querySelectorAll('#dates-grid .date-btn').forEach(el => el.classList.remove('selected'));
                btnEl.classList.add('selected');

                hideEl('summary-section');
                updateSubmitState();
                loadTimeSlots();
            }

            // ---------------------------------------------------------------
            // Loading available hours
            // ---------------------------------------------------------------
            async function loadTimeSlots() {
                showEl('slots-loading');
                hideEl('slots-section');
                try {
                    const url = `/api/time-slots/${selectedSpecialist}/${selectedDate}?service_id=${selectedService}`;
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const data = await response.json();
                    renderTimeSlots((data.slots && Array.isArray(data.slots)) ? data.slots : [], data.message || 'ساعت خالی در این روز وجود ندارد');
                } catch (error) {
                    console.error('خطا در بارگذاری ساعت‌ها:', error);
                    renderTimeSlots([], 'خطا در بارگذاری ساعت‌ها. لطفا دوباره تلاش کنید.');
                } finally {
                    hideEl('slots-loading');
                    showEl('slots-section');
                }
            }

            function renderTimeSlots(slots, emptyMessage) {
                const grid = document.getElementById('slots-grid');
                grid.innerHTML = '';

                const service = currentServiceObj();
                document.getElementById('duration-label').textContent = service && service.duration
                    ? `(هر نوبت ${service.duration} دقیقه)` : '';

                if (!slots.length) {
                    document.getElementById('slots-empty-message').textContent = emptyMessage;
                    showEl('slots-empty');
                    return;
                }
                hideEl('slots-empty');

                slots.forEach(time => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'slot-btn p-3 rounded-xl text-center text-sm';

                    let inner = `<div class="font-bold">${time}</div>`;
                    if (service && service.duration) {
                        inner += `<div class="text-xs mt-0.5 opacity-70">تا ${calculateEndTime(time, service.duration)}</div>`;
                    }
                    btn.innerHTML = inner;

                    btn.addEventListener('click', () => selectTime(time, btn));
                    grid.appendChild(btn);
                });
            }

            function selectTime(time, btnEl) {
                selectedTime = time;
                document.querySelectorAll('#slots-grid .slot-btn').forEach(el => el.classList.remove('selected'));
                btnEl.classList.add('selected');
                updateSummary();
            }

            // ---------------------------------------------------------------
            // Form registration
            // ---------------------------------------------------------------
            document.getElementById('booking-form').addEventListener('submit', function(e) {
                if (!selectedService || !selectedSpecialist || !selectedDate || !selectedTime) {
                    e.preventDefault();
                    return;
                }
                document.getElementById('input-booking-time').value = `${selectedDate} ${selectedTime}:00`;

                document.getElementById('submit-btn').disabled = true;
                document.getElementById('submit-label').classList.add('hidden-block');
                const spinner = document.getElementById('submit-spinner');
                spinner.classList.remove('hidden-block');
                spinner.classList.add('flex');
            });

            // ---------------------------------------------------------------
            // Connecting Events
            // ---------------------------------------------------------------
            document.getElementById('service-select').addEventListener('change', onServiceChange);
            document.getElementById('specialist-select').addEventListener('change', onSpecialistChange);

            loadServices();
        })();
    </script>
@endpush
