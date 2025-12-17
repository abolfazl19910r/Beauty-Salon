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
                            @{{ service.name }} - @{{ formatPrice(service.price) }} تومان
                        </option>
                    </select>
                </div>

                <div v-if="selectedService">
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
                    <div class="text-sm text-gray-600 mb-2" v-if="getServiceDuration">
                        <span class="font-medium">توجه:</span> هر نوبت <span class="font-bold persian-number">@{{ getServiceDuration }}</span> دقیقه طول می‌کشد
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="time in availableTimeSlots" :key="time"
                                @click="selectTime(time)"
                                type="button"
                                class="p-3 rounded-lg transition-colors text-center"
                                :class="selectedTime === time ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white' : 'bg-gray-100 hover:bg-gray-200'">
                            <div class="font-bold">@{{ time }}</div>
                            <div class="text-xs mt-1" v-if="getServiceDuration">
                                تا @{{ calculateEndTime(time, getServiceDuration) }}
                            </div>
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
                        <div class="flex justify-between" v-if="getServiceDuration">
                            <span class="text-gray-600">مدت زمان:</span>
                            <span class="font-medium persian-number">@{{ getServiceDuration }} دقیقه</span>
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
                            <span class="text-gray-600">ساعت شروع:</span>
                            <span class="font-medium">@{{ selectedTime }}</span>
                        </div>
                        <div class="flex justify-between" v-if="getServiceDuration && selectedTime">
                            <span class="text-gray-600">ساعت پایان (تقریبی):</span>
                            <span class="font-medium">@{{ calculateEndTime(selectedTime, getServiceDuration) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t mt-2">
                            <span class="font-bold">مبلغ پیش پرداخت:</span>
                            <span class="font-bold text-pink-600 persian-number">50,000 تومان</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <a href="{{ route('services.index') }}"
                       class="flex-1 bg-gray-200 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-300 transition-colors text-center">
                        بازگشت
                    </a>
                    <button type="submit"
                            :disabled="!isFormValid"
                            class="flex-1 bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="loading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
                        this.loading = true;
                        const response = await fetch(`/api/available-dates/${this.selectedSpecialist}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const dates = await response.json();
                        this.availableDates = Array.isArray(dates) ? dates : [];
                        this.selectedDate = null;
                        this.availableTimeSlots = [];
                        this.selectedTime = null;
                    } catch (error) {
                        console.error('خطا در بارگذاری تاریخ‌ها:', error);
                        this.availableDates = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async loadTimeSlots() {
                    if (!this.selectedDate || !this.selectedSpecialist || !this.selectedService) return;

                    try {
                        this.loading = true;
                        const url = `/api/time-slots/${this.selectedSpecialist}/${this.selectedDate}?service_id=${this.selectedService}`;

                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.slots && Array.isArray(data.slots)) {
                            this.availableTimeSlots = data.slots;

                            if (data.service_duration) {
                                console.log(`مدت زمان سرویس: ${data.service_duration} دقیقه`);
                            }
                        } else {
                            this.availableTimeSlots = [];
                            if (data.message) {
                                alert(data.message);
                            }
                        }
                    } catch (error) {
                        console.error('خطا در بارگذاری ساعت‌ها:', error);
                        this.availableTimeSlots = [];
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

                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                        form.appendChild(csrfInput);

                        const serviceInput = document.createElement('input');
                        serviceInput.type = 'hidden';
                        serviceInput.name = 'service_id';
                        serviceInput.value = this.selectedService;
                        form.appendChild(serviceInput);

                        const specialistInput = document.createElement('input');
                        specialistInput.type = 'hidden';
                        specialistInput.name = 'specialist_id';
                        specialistInput.value = this.selectedSpecialist;
                        form.appendChild(specialistInput);

                        const timeInput = document.createElement('input');
                        timeInput.type = 'hidden';
                        timeInput.name = 'booking_time';
                        timeInput.value = bookingDateTime;
                        form.appendChild(timeInput);

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
