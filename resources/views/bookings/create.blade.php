@extends('layouts.app')

@section('title', 'رزرو نوبت')

@section('content')
    <div id="booking-app" class="max-w-3xl mx-auto fade-in">
        <h1 class="text-2xl font-bold mb-6 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">رزرو نوبت جدید</h1>

        <div class="bg-white rounded-lg shadow-sm hover-shadow p-6">
            <form @submit.prevent="submitBooking" class="space-y-6">
                <div>
                    <label class="block text-gray-700 mb-2 font-medium">انتخاب سرویس</label>
                    <select v-model="selectedService" @change="loadSpecialists" class="w-full border rounded-lg p-2 focus:border-pink-500 focus:ring focus:ring-pink-200 transition-colors">
                        <option value="">انتخاب کنید</option>
                        <option v-for="service in services" :key="service.id" :value="service.id">
                            @{{ service.name }} - @{{ service.price }} تومان
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 mb-2 font-medium">انتخاب متخصص</label>
                    <select v-model="selectedSpecialist" @change="loadAvailableDates" class="w-full border rounded-lg p-2 focus:border-pink-500 focus:ring focus:ring-pink-200 transition-colors">
                        <option value="">انتخاب کنید</option>
                        <option v-for="specialist in specialists" :key="specialist.id" :value="specialist.id">
                            @{{ specialist.name }}
                        </option>
                    </select>
                </div>

                <div v-if="availableDates.length > 0">
                    <label class="block text-gray-700 mb-2 font-medium">انتخاب تاریخ</label>
                    <div class="grid grid-cols-7 gap-2 mb-4">
                        <div v-for="date in availableDates" :key="date"
                             @click="selectDate(date)"
                             class="text-center p-2 rounded-lg cursor-pointer transition-colors persian-number"
                             :class="selectedDate === date ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white' : 'bg-gray-100 hover:bg-gray-200'">
                            @{{ formatDate(date) }}
                        </div>
                    </div>
                </div>

                <div v-if="selectedDate && availableTimeSlots.length > 0">
                    <label class="block text-gray-700 mb-2 font-medium">انتخاب ساعت</label>
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

                <div v-if="selectedTime" class="border-t pt-4 mt-4">
                    <h3 class="font-bold mb-4 text-lg">خلاصه رزرو</h3>
                    <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">سرویس:</span>
                            <span class="font-medium">@{{ getServiceName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">متخصص:</span>
                            <span class="font-medium">@{{ getSpecialistName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">تاریخ:</span>
                            <span class="font-medium persian-number">@{{ formatDate(selectedDate) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ساعت:</span>
                            <span class="font-medium">@{{ selectedTime }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t mt-2">
                            <span class="font-bold">مبلغ پیش پرداخت:</span>
                            <span class="font-bold text-pink-600 persian-number">50,000 تومان</span>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        :disabled="!isFormValid"
                        class="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="loading" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        در حال پردازش...
                    </span>
                    <span v-else>ثبت رزرو</span>
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
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
                    loading: false
                }
            },
            computed: {
                isFormValid() {
                    return this.selectedService && this.selectedSpecialist &&
                        this.selectedDate && this.selectedTime;
                },
                getServiceName() {
                    const service = this.services.find(s => s.id === this.selectedService);
                    return service ? service.name : '';
                },
                getSpecialistName() {
                    const specialist = this.specialists.find(s => s.id === this.selectedSpecialist);
                    return specialist ? specialist.name : '';
                }
            },
            methods: {
                async loadServices() {
                    try {
                        this.loading = true;
                        const response = await fetch('/api/services');
                        this.services = await response.json();
                    } catch (error) {
                        console.error('Error loading services:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                async loadSpecialists() {
                    if (!this.selectedService) return;
                    try {
                        this.loading = true;
                        const response = await fetch(`/api/specialists/${this.selectedService}`);
                        this.specialists = await response.json();
                        this.selectedSpecialist = '';
                        this.selectedDate = null;
                        this.selectedTime = null;
                        this.availableDates = [];
                        this.availableTimeSlots = [];
                    } catch (error) {
                        console.error('Error loading specialists:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                async loadAvailableDates() {
                    if (!this.selectedSpecialist) return;
                    try {
                        this.loading = true;
                        const response = await fetch(`/api/available-dates/${this.selectedSpecialist}`);
                        this.availableDates = await response.json();
                        this.selectedDate = null;
                        this.selectedTime = null;
                        this.availableTimeSlots = [];
                    } catch (error) {
                        console.error('Error loading dates:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                async loadTimeSlots() {
                    if (!this.selectedDate || !this.selectedSpecialist) return;
                    try {
                        this.loading = true;
                        const response = await fetch(`/api/time-slots/${this.selectedSpecialist}/${this.selectedDate}`);
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
                async submitBooking() {
                    if (!this.isFormValid) return;

                    try {
                        this.loading = true;
                        const response = await fetch('/api/bookings', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                service_id: this.selectedService,
                                specialist_id: this.selectedSpecialist,
                                booking_date: this.selectedDate,
                                booking_time: this.selectedTime
                            })
                        });

                        if (response.ok) {
                            window.location.href = '/bookings/success';
                        } else {
                            const error = await response.json();
                            alert(error.message);
                        }
                    } catch (error) {
                        console.error('Error submitting booking:', error);
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
