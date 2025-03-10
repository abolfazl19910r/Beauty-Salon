@extends('layouts.admin')

@section('title', 'مدیریت برنامه کاری')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">مدیریت برنامه کاری</h1>
                <p class="text-sm text-gray-500">تنظیم ساعات کاری متخصص {{ $specialist->name }}</p>
            </div>
            <a href="{{ route('admin.specialists.show', $specialist) }}"
               class="mt-3 md:mt-0 flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                بازگشت
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">ساعات کاری هفتگی</h2>
                <p class="text-sm text-gray-500 mt-1">روزها و ساعات فعالیت متخصص را تنظیم کنید</p>
            </div>

            <form action="{{ route('admin.specialists.schedules.update', $specialist) }}" method="POST">
                @csrf
                @method('PUT')

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
                            <div class="border border-gray-200 rounded-lg hover:border-blue-300 transition-colors {{ isset($schedules[$dayNumber]) && $schedules[$dayNumber]->first()->is_active ? 'bg-blue-50' : 'bg-white' }}">
                                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                                    <h3 class="text-lg font-medium flex items-center">
                                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 ml-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        {{ $dayName }}
                                    </h3>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox"
                                               name="schedules[{{ $dayNumber }}][is_active]"
                                               value="1"
                                               class="form-checkbox h-5 w-5 text-blue-600 rounded transition duration-150 ease-in-out"
                                            {{ isset($schedules[$dayNumber]) && $schedules[$dayNumber]->first()->is_active ? 'checked' : '' }}>
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
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <input type="time"
                                                   name="schedules[{{ $dayNumber }}][start_time]"
                                                   value="{{ optional(optional($schedules[$dayNumber])->first())->start_time }}"
                                                   class="w-full pr-10 border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg transition-colors"
                                                   placeholder="انتخاب زمان">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">ساعت پایان</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <input type="time"
                                                   name="schedules[{{ $dayNumber }}][end_time]"
                                                   value="{{ optional(optional($schedules[$dayNumber])->first())->end_time }}"
                                                   class="w-full pr-10 border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg transition-colors"
                                                   placeholder="انتخاب زمان">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-5 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-lg shadow hover:shadow-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            ذخیره تغییرات
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm mt-6 p-5">
            <div class="flex items-start">
                <div class="flex-shrink-0 pt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="mr-3">
                    <h3 class="text-sm font-medium text-gray-800">راهنمای تنظیم برنامه کاری</h3>
                    <p class="mt-1 text-sm text-gray-600">
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
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const container = this.closest('.border');
                    if (this.checked) {
                        container.classList.add('bg-blue-50');
                    } else {
                        container.classList.remove('bg-blue-50');
                    }
                });
            });
        });
    </script>
@endpush
