@extends('layouts.app')

@section('title', 'تغییر زمان نوبت')

@section('content')
    <style>
        .date-btn { background: rgba(201,162,75,0.08); border: 1px solid rgba(201,162,75,0.15); color: rgba(248,243,233,0.75); transition: all 0.25s; cursor: pointer; border-radius: 0.75rem; }
        .date-btn:hover { background: rgba(201,162,75,0.18); color: #E6CD8A; }
        .date-btn.selected { background: linear-gradient(135deg, #E6CD8A, #C9A24B); color: #1A1410; font-weight: 700; border-color: transparent; }
        .slot-btn { background: rgba(201,162,75,0.08); border: 1px solid rgba(201,162,75,0.2); color: rgba(248,243,233,0.8); transition: all 0.25s; border-radius: 0.75rem; }
        .slot-btn:hover { background: rgba(201,162,75,0.18); color: #E6CD8A; }
        .slot-btn.selected { background: linear-gradient(135deg, #E6CD8A, #C9A24B); color: #1A1410; font-weight: 700; border-color: transparent; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <div class="max-w-2xl mx-auto fade-in" id="booking-app">

        {{-- هدر --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('bookings.show', $booking) }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase">نوبت #{{ $booking->id }}</p>
                <h1 class="text-2xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">تغییر زمان نوبت</h1>
            </div>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6 md:p-8 space-y-7">

            {{-- خدمت فعلی --}}
            <div class="flex items-center gap-3 bg-[#1A1410]/50 rounded-xl border border-[#C9A24B]/10 px-4 py-3 text-sm">
                <svg class="w-4 h-4 text-[#C9A24B] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span class="text-[#F8F3E9]/60">خدمت:</span>
                <span class="font-medium text-[#F8F3E9]">{{ $booking->service->name }}</span>
            </div>

            {{-- زمان فعلی --}}
            <div class="bg-[#C9A24B]/10 border border-[#C9A24B]/20 rounded-xl px-5 py-4">
                <p class="text-xs font-semibold text-[#C9A24B] mb-2 uppercase tracking-wider">زمان فعلی نوبت</p>
                <p class="text-lg font-bold text-[#E6CD8A] persian-number" dir="ltr">
                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                </p>
            </div>

            <form @submit.prevent="submitReschedule" class="space-y-6">

                {{-- انتخاب تاریخ جدید --}}
                <div v-if="availableDates.length > 0">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        انتخاب تاریخ جدید
                    </label>
                    <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                        <div v-for="date in availableDates" :key="date"
                             @click="selectDate(date)"
                             class="date-btn text-center p-2.5 text-xs persian-number"
                             :class="{ 'selected': selectedDate === date }">
                            @{{ formatDate(date) }}
                        </div>
                    </div>
                </div>

                {{-- انتخاب ساعت جدید --}}
                <div v-if="selectedDate && availableTimeSlots.length > 0">
                    <label class="block text-sm font-medium text-[#E6CD8A] mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                        </svg>
                        انتخاب ساعت جدید
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="time in availableTimeSlots" :key="time"
                                @click="selectTime(time)" type="button"
                                class="slot-btn p-3 text-center text-sm font-medium"
                                :class="{ 'selected': selectedTime === time }">
                            @{{ time }}
                        </button>
                    </div>
                </div>

                {{-- مقایسه زمان --}}
                <div v-if="selectedTime" class="grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/10 p-4">
                        <p class="text-[#F8F3E9]/50 text-xs mb-1">زمان فعلی</p>
                        <p class="font-medium text-[#F8F3E9] persian-number" dir="ltr">
                            {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                        </p>
                    </div>
                    <div class="bg-[#C9A24B]/10 rounded-xl border border-[#C9A24B]/20 p-4">
                        <p class="text-[#C9A24B] text-xs mb-1">زمان جدید</p>
                        <p class="font-bold text-[#E6CD8A] persian-number" dir="ltr">@{{ formatDate(selectedDate) }} @{{ selectedTime }}</p>
                    </div>
                </div>

                {{-- دکمه‌ها --}}
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm border border-[#C9A24B]/25
                          text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                        انصراف
                    </a>
                    <button type="submit"
                            :disabled="!isFormValid || loading"
                            class="flex-1 py-3 rounded-xl text-sm font-bold transition-all
                               bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                               disabled:opacity-40 disabled:cursor-not-allowed
                               hover:shadow-lg hover:shadow-[#C9A24B]/25">
                    <span v-if="loading" class="flex items-center justify-center gap-2">
                        <svg class="spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        در حال پردازش...
                    </span>
                        <span v-else>ثبت تغییرات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Vue logic دست‌نخورده --}}
    <script>
        const { createApp } = Vue;
        createApp({
            data() {
                return { availableDates: [], availableTimeSlots: [], selectedDate: null, selectedTime: null, loading: false, bookingId: '{{ $booking->id }}' }
            },
            computed: {
                isFormValid() { return this.selectedDate && this.selectedTime; }
            },
            methods: {
                async loadAvailableDates() {
                    try {
                        this.loading = true;
                        const r = await fetch(`/api/specialists/{{ $booking->specialist_id }}/available-dates`);
                        this.availableDates = await r.json();
                    } catch(e) { console.error(e); } finally { this.loading = false; }
                },
                async loadTimeSlots() {
                    if (!this.selectedDate) return;
                    try {
                        this.loading = true;
                        const r = await fetch(`/api/specialists/{{ $booking->specialist_id }}/time-slots/${this.selectedDate}`);
                        this.availableTimeSlots = await r.json();
                    } catch(e) { console.error(e); } finally { this.loading = false; }
                },
                formatDate(date) { return new persianDate(new Date(date)).format('YYYY/MM/DD'); },
                selectDate(date) { this.selectedDate = date; this.selectedTime = null; this.loadTimeSlots(); },
                selectTime(time) { this.selectedTime = time; },
                async submitReschedule() {
                    if (!this.isFormValid) return;
                    try {
                        this.loading = true;
                        const response = await fetch(`/bookings/${this.bookingId}/reschedule`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ booking_date: this.selectedDate, booking_time: this.selectedTime })
                        });
                        if (response.ok) { window.location.href = `/bookings/${this.bookingId}?success=1`; }
                        else { const e = await response.json(); alert(e.message || 'خطا در تغییر زمان نوبت'); }
                    } catch(e) { console.error(e); alert('خطا در ارتباط با سرور'); } finally { this.loading = false; }
                }
            },
            mounted() { this.loadAvailableDates(); }
        }).mount('#booking-app');
    </script>
@endsection
