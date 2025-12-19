@extends('layouts.specialist')

@section('title', 'مدیریت برنامه کاری')

@section('content')
    <div class="max-w-4xl mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">مدیریت برنامه کاری</h1>
                <p class="text-sm text-gray-500">تنظیم ساعات کاری هفتگی</p>
            </div>
            <a href="{{ route('specialist.profile.show') }}"
               class="flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                بازگشت
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg p-5 mb-6 shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mr-3 flex-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">تایید خودکار نوبت‌های رزرو شده</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        با فعال کردن این گزینه، نوبت‌های رزرو شده به صورت خودکار تایید می‌شوند و نیازی به تایید دستی هر نوبت ندارید.
                    </p>

                    <label class="inline-flex items-center cursor-pointer bg-white rounded-lg px-4 py-3 border-2 border-blue-300 hover:border-blue-400 transition-all">
                        <input type="checkbox"
                               id="auto_confirm_toggle"
                               name="auto_confirm_bookings"
                               value="1"
                               class="form-checkbox h-6 w-6 text-blue-600 rounded transition duration-150"
                            {{ $specialist->auto_confirm_bookings ? 'checked' : '' }}>
                        <span class="mr-3 font-medium text-gray-800">فعال‌سازی تایید خودکار نوبت‌ها</span>
                    </label>

                    <div class="mt-4 bg-white rounded-lg p-3 border border-blue-200">
                        <div class="flex items-start text-sm text-gray-600">
                            <svg class="w-5 h-5 text-blue-500 ml-2 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <div>
                                <strong>توجه:</strong> با فعال کردن این گزینه:
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>نوبت‌های جدید بلافاصله تایید می‌شوند</li>
                                    <li>پیامک ارسالی به شما لینک تایید/لغو نخواهد داشت</li>
                                    <li>همچنان می‌توانید از پنل خود نوبت‌ها را مدیریت کنید</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">ساعات کاری هفتگی</h2>
                <p class="text-sm text-gray-500 mt-1">روزها و ساعات فعالیت خود را تنظیم کنید</p>
            </div>

            <form method="POST" action="{{ route('specialist.schedule.update') }}" id="scheduleForm">
                @csrf
                @method('PUT')

                <input type="hidden" name="auto_confirm_bookings" id="auto_confirm_hidden" value="{{ $specialist->auto_confirm_bookings ? '1' : '0' }}">

                <div class="p-5">
                    <div class="grid gap-6">
                        @php
                            $days = [
                                0 => 'یکشنبه',
                                1 => 'دوشنبه',
                                2 => 'سه‌شنبه',
                                3 => 'چهارشنبه',
                                4 => 'پنج‌شنبه',
                                5 => 'جمعه',
                                6 => 'شنبه'
                            ];
                        @endphp

                        @foreach($days as $dayNumber => $dayName)
                            <div class="border border-gray-200 rounded-lg hover:border-pink-300 transition-colors {{ isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->is_active) && $schedules[$dayNumber]->first()->is_active ? 'bg-pink-50' : 'bg-white' }}">
                                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                                    <h3 class="text-lg font-medium flex items-center">
                                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-pink-100 text-pink-600 ml-3">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                        </span>
                                        {{ $dayName }}
                                    </h3>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               name="schedules[{{ $dayNumber }}][is_active]"
                                               value="1"
                                               class="form-checkbox h-5 w-5 text-pink-600 rounded transition duration-150"
                                            {{ isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->is_active) && $schedules[$dayNumber]->first()->is_active ? 'checked' : '' }}>
                                        <span class="mr-2 text-gray-700">فعال</span>
                                    </label>
                                </div>

                                <input type="hidden"
                                       name="schedules[{{ $dayNumber }}][day_of_week]"
                                       value="{{ $dayNumber }}">

                                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">ساعت شروع</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                            </div>
                                            <input type="time"
                                                   name="schedules[{{ $dayNumber }}][start_time]"
                                                   value="{{ isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->start_time) ? $schedules[$dayNumber]->first()->start_time : '' }}"
                                                   class="w-full pr-10 border-gray-300 focus:ring-pink-500 focus:border-pink-500 rounded-lg">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">ساعت پایان</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                            </div>
                                            <input type="time"
                                                   name="schedules[{{ $dayNumber }}][end_time]"
                                                   value="{{ isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->end_time) ? $schedules[$dayNumber]->first()->end_time : '' }}"
                                                   class="w-full pr-10 border-gray-300 focus:ring-pink-500 focus:border-pink-500 rounded-lg">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-5 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            ذخیره تغییرات
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <div class="mr-3">
                    <h3 class="text-sm font-medium text-blue-800">راهنمای تنظیم برنامه کاری</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        برای فعال کردن هر روز کاری، تیک مربوط به آن روز را فعال کنید و ساعات شروع و پایان را تنظیم نمایید.
                        روزهای غیرفعال به عنوان تعطیل در نظر گرفته می‌شوند.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name*="[is_active]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const container = this.closest('.border');
                    if (this.checked) {
                        container.classList.add('bg-pink-50');
                    } else {
                        container.classList.remove('bg-pink-50');
                    }
                });
            });

            const autoConfirmToggle = document.getElementById('auto_confirm_toggle');
            const autoConfirmHidden = document.getElementById('auto_confirm_hidden');

            autoConfirmToggle.addEventListener('change', function() {
                autoConfirmHidden.value = this.checked ? '1' : '0';
            });
        });
    </script>
@endpush
