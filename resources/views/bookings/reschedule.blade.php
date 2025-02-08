@extends('layouts.app')

@section('title', 'تغییر زمان نوبت')

@section('content')
    <div class="max-w-4xl mx-auto" id="booking-app">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="border-b pb-4 mb-6">
                <h1 class="text-2xl font-bold">تغییر زمان نوبت</h1>
                <p class="text-gray-500">
                    نوبت شماره: {{ $booking->id }} - خدمت: {{ $booking->service->name }}
                </p>
            </div>

            <!-- اطلاعات فعلی نوبت -->
            <div class="bg-blue-50 p-4 rounded mb-6">
                <h2 class="font-bold mb-2">زمان فعلی نوبت</h2>
                <div class="text-gray-600" dir="ltr">
                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                </div>
            </div>

            <!-- فرم تغییر زمان -->
            <form @submit.prevent="submitReschedule">
                <!-- انتخاب تاریخ -->
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">انتخاب تاریخ جدید</label>
                    <div class="grid grid-cols-7 gap-2 mb-4">
                        <div v-for="date in availableDates" :key="date"
                             @click="selectDate(date)"
                             class="text-center p-2 rounded cursor-pointer"
                             :class="selectedDate === date ? 'bg-blue-500 text-white' : 'bg-gray-100'">
                            @{{ formatDate(date) }}
                        </div>
                    </div>
                </div>

                <!-- انتخاب ساعت -->
                <div v-if="selectedDate" class="mb-6">
                    <label class="block text-gray-700 mb-2">انتخاب ساعت جدید</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="time in availableTimeSlots" :key="time"
                                @click="selectTime(time)"
                                type="button"
                                class="p-2 rounded"
                                :class="selectedTime === time ? 'bg-blue-500 text-white' : 'bg-gray-100'">
                            @{{ time }}
                        </button>
                    </div>
                </div>

                <!-- خلاصه تغییرات -->
                <div v-if="selectedTime" class="bg-gray-50 p-4 rounded mb-6">
                    <h3 class="font-bold mb-2">خلاصه تغییرات</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-gray-600">زمان فعلی:</div>
                            <div dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600">زمان جدید:</div>
                            <div dir="ltr">@{{ formatDate(selectedDate) }} @{{ selectedTime }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                        انصراف
                    </a>

                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded"
                            :disabled="!isFormValid">
                        ثبت تغییرات
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
                        const response = await fetch(`/api/specialists/{{ $booking->specialist_id }}/available-dates`);
                        this.availableDates = await response.json();
                    } catch (error) {
                        console.error('Error loading dates:', error);
                    }
                },
                async loadTimeSlots() {
                    if (!this.selectedDate) return;
                    try {
                        const response = await fetch(
                            `/api/specialists/{{ $booking->specialist_id }}/time-slots/${this.selectedDate}`
                        );
                        this.availableTimeSlots = await response.json();
                    } catch (error) {
                        console.error('Error loading time slots:', error);
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
                    }
                }
            },
            mounted() {
                this.loadAvailableDates();
            }
        }).mount('#booking-app')
    </script>
@endsection
