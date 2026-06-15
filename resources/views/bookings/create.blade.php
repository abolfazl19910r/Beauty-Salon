@extends('layouts.app')

@section('title', 'رزرو نوبت')

@section('content')
    <style>
        /* Override Vue reactive classes برای تم لوکس */
        .slot-btn {
            background: rgba(201,162,75,0.08);
            border: 1px solid rgba(201,162,75,0.2);
            color: rgba(248,243,233,0.8);
            transition: all 0.25s;
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

        /* spinner */
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <div id="booking-app" class="max-w-3xl mx-auto fade-in">
        {{-- هدر --}}
        <div class="mb-8">
            <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">سالن راستا</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">رزرو نوبت جدید</h1>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6 md:p-8">
            <form @submit.prevent="submitBooking" class="space-y-7">

                {{-- انتخاب سرویس --}}
                <div>
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-2">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        انتخاب خدمت
                    </span>
                    </label>
                    <select v-model="selectedService" @change="loadSpecialists" class="gold-select">
                        <option value="">خدمت مورد نظر را انتخاب کنید</option>
                        <option v-for="service in services" :key="service.id" :value="service.id">
                            @{{ service.name }} — @{{ formatPrice(service.price) }} تومان
                        </option>
                    </select>
                </div>

                {{-- انتخاب متخصص --}}
                <div v-if="selectedService">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-2">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        انتخاب متخصص
                    </span>
                    </label>
                    <select v-model="selectedSpecialist" @change="loadAvailableDates" class="gold-select">
                        <option value="">متخصص مورد نظر را انتخاب کنید</option>
                        <option v-for="specialist in specialists" :key="specialist.id" :value="specialist.id">
                            @{{ specialist.name }}
                        </option>
                    </select>
                </div>

                {{-- loading تاریخ --}}
                <div v-if="loadingDates" class="flex items-center gap-3 py-4 text-sm text-[#F8F3E9]/60">
                    <svg class="spin w-5 h-5 text-[#C9A24B]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    در حال بارگذاری تاریخ‌های موجود...
                </div>

                {{-- انتخاب تاریخ --}}
                <div v-if="availableDates.length > 0">
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
                    <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                        <div v-for="date in availableDates" :key="date"
                             @click="selectDate(date)"
                             class="date-btn text-center p-2.5 rounded-xl text-xs persian-number"
                             :class="{ 'selected': selectedDate === date }">
                            @{{ formatDate(date) }}
                        </div>
                    </div>
                </div>

                {{-- loading ساعت --}}
                <div v-if="loadingSlots" class="flex items-center gap-3 py-4 text-sm text-[#F8F3E9]/60">
                    <svg class="spin w-5 h-5 text-[#C9A24B]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    در حال بارگذاری ساعت‌های موجود...
                </div>

                {{-- انتخاب ساعت --}}
                <div v-if="selectedDate && availableTimeSlots.length > 0">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-3">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                        </svg>
                        انتخاب ساعت
                        <span v-if="getServiceDuration" class="mr-2 text-[#F8F3E9]/50 text-xs font-normal persian-number">
                            (هر نوبت @{{ getServiceDuration }} دقیقه)
                        </span>
                    </span>
                    </label>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        <button v-for="time in availableTimeSlots" :key="time"
                                @click="selectTime(time)"
                                type="button"
                                class="slot-btn p-3 rounded-xl text-center text-sm"
                                :class="{ 'selected': selectedTime === time }">
                            <div class="font-bold">@{{ time }}</div>
                            <div class="text-xs mt-0.5 opacity-70" v-if="getServiceDuration">
                                تا @{{ calculateEndTime(time, getServiceDuration) }}
                            </div>
                        </button>
                    </div>
                </div>

                {{-- خلاصه رزرو --}}
                <div v-if="selectedTime" class="summary-box p-5">
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
                            <span class="font-medium text-[#F8F3E9]">@{{ getServiceName }}</span>
                        </div>
                        <div class="summary-row flex justify-between pb-3" v-if="getServiceDuration">
                            <span class="text-[#F8F3E9]/60">مدت زمان</span>
                            <span class="font-medium text-[#F8F3E9] persian-number">@{{ getServiceDuration }} دقیقه</span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">متخصص</span>
                            <span class="font-medium text-[#F8F3E9]">@{{ getSpecialistName }}</span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">تاریخ</span>
                            <span class="font-medium text-[#F8F3E9] persian-number">@{{ formatDate(selectedDate) }}</span>
                        </div>
                        <div class="summary-row flex justify-between pb-3">
                            <span class="text-[#F8F3E9]/60">ساعت شروع</span>
                            <span class="font-medium text-[#F8F3E9]">@{{ selectedTime }}</span>
                        </div>
                        <div class="summary-row flex justify-between pb-3" v-if="getServiceDuration && selectedTime">
                            <span class="text-[#F8F3E9]/60">ساعت پایان (تقریبی)</span>
                            <span class="font-medium text-[#F8F3E9]">@{{ calculateEndTime(selectedTime, getServiceDuration) }}</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="font-bold text-[#F8F3E9]">مبلغ پیش‌پرداخت</span>
                            <span class="font-bold text-[#E6CD8A] persian-number">۵۰٬۰۰۰ تومان</span>
                        </div>
                    </div>
                </div>

                {{-- دکمه‌ها --}}
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('services.index') }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm border border-[#C9A24B]/25
                          text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                        بازگشت
                    </a>
                    <button type="submit"
                            :disabled="!isFormValid"
                            class="flex-1 py-3 rounded-xl text-sm font-bold transition-all duration-300
                               bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                               disabled:opacity-40 disabled:cursor-not-allowed
                               hover:shadow-lg hover:shadow-[#C9A24B]/30">
                    <span v-if="loading" class="flex items-center justify-center gap-2">
                        <svg class="spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        در حال پردازش...
                    </span>
                        <span v-else>ادامه و تایید رزرو</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Vue logic کاملاً دست‌نخورده است --}}
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    services: [],
                    specialists: [],
                    availableDates: [],
                    availableTimeSlots: [],
                    selectedService: '',
                    selectedSpecialist: '',
                    selectedDate: null,
                    selectedTime: null,
                    loading: false,
                    loadingDates: false,
                    loadingSlots: false,
                    serviceDuration: null
                }
            },
            computed: {
                isFormValid() {
                    return this.selectedService && this.selectedSpecialist &&
                        this.selectedDate && this.selectedTime;
                },
                getServiceName() {
                    const service = this.services.find(s => s.id == this.selectedService);
                    return service ? service.name : '';
                },
                getServiceDuration() {
                    const service = this.services.find(s => s.id == this.selectedService);
                    return service ? service.duration : null;
                },
                getSpecialistName() {
                    const specialist = this.specialists.find(s => s.id == this.selectedSpecialist);
                    return specialist ? specialist.name : '';
                }
            },
            methods: {
                formatPrice(price) {
                    return new Intl.NumberFormat('fa-IR').format(price);
                },
                async loadServices() {
                    try {
                        const response = await fetch('/api/services');
                        this.services = await response.json();
                        const urlParams = new URLSearchParams(window.location.search);
                        const serviceFromUrl = urlParams.get('service');
                        if (serviceFromUrl) {
                            this.selectedService = parseInt(serviceFromUrl);
                            await this.loadSpecialists();
                        }
                    } catch (error) {
                        console.error('خطا در بارگذاری سرویس‌ها:', error);
                    }
                },
                async loadSpecialists() {
                    if (!this.selectedService) return;
                    try {
                        const response = await fetch(`/api/specialists/${this.selectedService}`);
                        this.specialists = await response.json();
                        if (!this.selectedSpecialist) {
                            this.availableDates = [];
                            this.selectedDate = null;
                            this.availableTimeSlots = [];
                            this.selectedTime = null;
                        }
                    } catch (error) {
                        console.error('خطا در بارگذاری متخصصین:', error);
                    }
                },
                async loadAvailableDates() {
                    if (!this.selectedSpecialist) return;
                    try {
                        this.loadingDates = true;
                        const response = await fetch(`/api/available-dates/${this.selectedSpecialist}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const dates = await response.json();
                        this.availableDates = Array.isArray(dates) ? dates : [];
                        this.selectedDate = null;
                        this.availableTimeSlots = [];
                        this.selectedTime = null;
                    } catch (error) {
                        console.error('خطا در بارگذاری تاریخ‌ها:', error);
                        this.availableDates = [];
                    } finally {
                        this.loadingDates = false;
                    }
                },
                async loadTimeSlots() {
                    if (!this.selectedDate || !this.selectedSpecialist || !this.selectedService) return;
                    try {
                        this.loadingSlots = true;
                        const url = `/api/time-slots/${this.selectedSpecialist}/${this.selectedDate}?service_id=${this.selectedService}`;
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const data = await response.json();
                        this.availableTimeSlots = (data.slots && Array.isArray(data.slots)) ? data.slots : [];
                        if (data.message && !this.availableTimeSlots.length) alert(data.message);
                    } catch (error) {
                        console.error('خطا در بارگذاری ساعت‌ها:', error);
                        this.availableTimeSlots = [];
                    } finally {
                        this.loadingSlots = false;
                    }
                },
                formatDate(date) {
                    return new persianDate(new Date(date)).format('YYYY/MM/DD');
                },
                selectDate(date) {
                    this.selectedDate = date;
                    this.selectedTime = null;
                    this.loadTimeSlots();
                },
                selectTime(time) {
                    this.selectedTime = time;
                },
                calculateEndTime(startTime, duration) {
                    if (!startTime || !duration) return '';
                    const [hours, minutes] = startTime.split(':').map(Number);
                    const totalMinutes = hours * 60 + minutes + duration;
                    const endHours = Math.floor(totalMinutes / 60);
                    const endMinutes = totalMinutes % 60;
                    return `${String(endHours).padStart(2, '0')}:${String(endMinutes).padStart(2, '0')}`;
                },
                async submitBooking() {
                    if (!this.isFormValid) return;
                    try {
                        this.loading = true;
                        const bookingDateTime = `${this.selectedDate} ${this.selectedTime}:00`;
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/bookings/confirm';
                        form.style.display = 'none';
                        const fields = {
                            '_token': document.querySelector('meta[name="csrf-token"]').content,
                            'service_id': this.selectedService,
                            'specialist_id': this.selectedSpecialist,
                            'booking_time': bookingDateTime
                        };
                        Object.entries(fields).forEach(([name, value]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = value;
                            form.appendChild(input);
                        });
                        document.body.appendChild(form);
                        form.submit();
                    } catch (error) {
                        console.error('خطا در ثبت رزرو:', error);
                        alert('خطا در ثبت رزرو. لطفا دوباره تلاش کنید.');
                    } finally {
                        this.loading = false;
                    }
                }
            },
            mounted() {
                this.loadServices();
            }
        }).mount('#booking-app');
    </script>
@endpush
