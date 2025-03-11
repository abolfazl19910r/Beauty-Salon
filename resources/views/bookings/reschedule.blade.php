@extends('layouts.app')

@section('title', 'تغییر زمان نوبت')

@section('content')
    <div class="max-w-4xl mx-auto fade-in" id="booking-app">
        <div class="bg-white rounded-lg shadow-sm hover-shadow p-6">
            <div class="border-b pb-4 mb-6">
                <h1 class="text-2xl font-bold bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">تغییر زمان نوبت</h1>
                <p class="text-gray-500 mt-2">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        نوبت شماره: {{ $booking->id }} - خدمت: {{ $booking->service->name }}
                    </span>
                </p>
            </div>

            <div class="bg-purple-50 p-4 rounded-lg mb-6 border border-purple-100">
                <h2 class="font-bold mb-2 text-purple-800">زمان فعلی نوبت</h2>
                <div class="text-purple-700 flex items-center" dir="ltr">
                    <svg class="w-5 h-5 ml-1 text-purple-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span class="persian-number">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                </div>
            </div>

            <form @submit.prevent="submitReschedule">
                <div class="mb-6" v-if="availableDates.length > 0">
                    <label class="block text-gray-700 mb-2 font-medium">انتخاب تاریخ جدید</label>
                    <div class="grid grid-cols-7 gap-2 mb-4">
                        <div v-for="date in availableDates" :key="date"
                             @click="selectDate(date)"
                             class="text-center p-2 rounded-lg cursor-pointer transition-colors persian-number"
                             :class="selectedDate === date ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white' : 'bg-gray-100 hover:bg-gray-200'">
                            @{{ formatDate(date) }}
                        </div>
                    </div>
                </div>

                <div v-if="selectedDate && availableTimeSlots.length > 0" class="mb-6">
                    <label class="block text-gray-700 mb-2 font-medium">انتخاب ساعت جدید</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="time in availableTimeSlots" :key="time"
                                @click="selectTime(time)"
                                type="button"
                                class="p-2 rounded-lg transition-colors"
                                :class="selectedTime === time ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white' : 'bg-gray-100 hover:bg-gray-200'">
                            @{{ time }}
                        </button>
                    </div>
                </div>

                <div v-if="selectedTime" class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h3 class="font-bold mb-4 text-lg">خلاصه تغییرات</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <div class="text-gray-600 mb-1">زمان فعلی:</div>
                            <div dir="ltr" class="font-medium persian-number">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</div>
                        </div>
                        <div class="bg-pink-50 p-3 rounded-lg border border-pink-200">
                            <div class="text-gray-600 mb-1">زمان جدید:</div>
                            <div dir="ltr" class="font-medium persian-number">@{{ formatDate(selectedDate) }} @{{ selectedTime }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        انصراف
                    </a>

                    <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity"
                            :disabled="!isFormValid || loading">
                        <span v-if="loading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            در حال پردازش...
                        </span>
                        <span v-else>ثبت تغییرات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const { createApp } = Vue

        createApp({
            data() {
                return {
                    availableDates: [],
                    availableTimeSlots: [],
                    selectedDate: null,
                    selectedTime: null,
                    loading: false,
                    bookingId: '{{ $booking->id }}'
                }
            },
            computed: {
                isFormValid() {
                    return this.selectedDate && this.selectedTime;
                }
            },
            methods: {
                async loadAvailableDates() {
                    try {
                        this.loading = true;
                        const response = await fetch(`/api/specialists/{{ $booking->specialist_id }}/available-dates`);
                        this.availableDates = await response.json();
                    } catch (error) {
                        console.error('Error loading dates:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                async loadTimeSlots() {
                    if (!this.selectedDate) return;
                    try {
                        this.loading = true;
                        const response = await fetch(
                            `/api/specialists/{{ $booking->specialist_id }}/time-slots/${this.selectedDate}`
                        );
                        this.availableTimeSlots = await response.json();
                    } catch (error) {
                        console.error('Error loading time slots:', error);
                    } finally {
                        this.loading = false;
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
                async submitReschedule() {
                    if (!this.isFormValid) return;

                    try {
                        this.loading = true;
                        const response = await fetch(`/bookings/${this.bookingId}/reschedule`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                booking_date: this.selectedDate,
                                booking_time: this.selectedTime
                            })
                        });

                        if (response.ok) {
                            window.location.href = `/bookings/${this.bookingId}?success=1`;
                        } else {
                            const error = await response.json();
                            alert(error.message || 'خطا در تغییر زمان نوبت');
                        }
                    } catch (error) {
                        console.error('Error rescheduling:', error);
                        alert('خطا در ارتباط با سرور');
                    } finally {
                        this.loading = false;
                    }
                }
            },
            mounted() {
                this.loadAvailableDates();
            }
        }).mount('#booking-app')
    </script>
@endsection
