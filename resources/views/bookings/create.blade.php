@extends('layouts.app')

@section('title', 'رزرو نوبت')

@section('content')
    <div id="booking-app" class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">رزرو نوبت جدید</h1>

        <div class="bg-white rounded-lg shadow p-6">
            <form @submit.prevent="submitBooking" class="space-y-6">
                <!-- انتخاب سرویس -->
                <div>
                    <label class="block text-gray-700 mb-2">انتخاب سرویس</label>
                    <select v-model="selectedService" @change="loadSpecialists" class="w-full border rounded-md p-2">
                        <option value="">انتخاب کنید</option>
                        <option v-for="service in services" :key="service.id" :value="service.id">
                            @{{ service.name }} - @{{ service.price }} تومان
                        </option>
                    </select>
                </div>

                <!-- انتخاب متخصص -->
                <div>
                    <label class="block text-gray-700 mb-2">انتخاب متخصص</label>
                    <select v-model="selectedSpecialist" @change="loadAvailableDates" class="w-full border rounded-md p-2">
                        <option value="">انتخاب کنید</option>
                        <option v-for="specialist in specialists" :key="specialist.id" :value="specialist.id">
                            @{{ specialist.name }}
                        </option>
                    </select>
                </div>

                <!-- انتخاب تاریخ -->
                <div>
                    <label class="block text-gray-700 mb-2">انتخاب تاریخ</label>
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
                <div v-if="selectedDate">
                    <label class="block text-gray-700 mb-2">انتخاب ساعت</label>
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

                <!-- خلاصه رزرو -->
                <div v-if="selectedTime" class="border-t pt-4 mt-4">
                    <h3 class="font-bold mb-2">خلاصه رزرو</h3>
                    <div class="text-sm space-y-2">
                        <p>سرویس: @{{ getServiceName }}</p>
                        <p>متخصص: @{{ getSpecialistName }}</p>
                        <p>تاریخ: @{{ formatDate(selectedDate) }}</p>
                        <p>ساعت: @{{ selectedTime }}</p>
                        <p class="font-bold">مبلغ پیش پرداخت: 50,000 تومان</p>
                    </div>
                </div>

                <button type="submit"
                        :disabled="!isFormValid"
                        class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 disabled:bg-gray-400">
                    ثبت رزرو
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
                        const response = await fetch('/api/services');
                        this.services = await response.json();
                    } catch (error) {
                        console.error('Error loading services:', error);
                    }
                },
                async loadSpecialists() {
                    if (!this.selectedService) return;
                    try {
                        const response = await fetch(`/api/specialists/${this.selectedService}`);
                        this.specialists = await response.json();
                    } catch (error) {
                        console.error('Error loading specialists:', error);
                    }
                },
                async loadAvailableDates() {
                    if (!this.selectedSpecialist) return;
                    try {
                        const response = await fetch(`/api/available-dates/${this.selectedSpecialist}`);
                        this.availableDates = await response.json();
                    } catch (error) {
                        console.error('Error loading dates:', error);
                    }
                },
                async loadTimeSlots() {
                    if (!this.selectedDate || !this.selectedSpecialist) return;
                    try {
                        const response = await fetch(`/api/time-slots/${this.selectedSpecialist}/${this.selectedDate}`);
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
                async submitBooking() {
                    if (!this.isFormValid) return;

                    try {
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
                    }
                }
            },
            mounted() {
                this.loadServices();
            }
        }).mount('#booking-app');
    </script>
@endpush
