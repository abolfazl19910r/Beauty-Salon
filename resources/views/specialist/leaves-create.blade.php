@extends('layouts.specialist')

@section('title', 'ثبت مرخصی جدید')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
    <style>
        .datepicker-container {
            z-index: 99999 !important;
            position: absolute !important;
        }

        .datepicker-plot-area {
            font-family: 'vazir', sans-serif !important;
        }
    </style>
@endpush

@section('content')
    <div class="max-w-3xl mx-auto py-6 px-4">
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('specialist.leaves') }}"
                   class="text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">ثبت مرخصی جدید</h1>
            </div>
            <p class="text-sm text-gray-500 mr-9">درخواست مرخصی خود را ثبت کنید</p>
        </div>

        <div class="bg-white rounded-lg shadow-md relative">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-pink-50 to-purple-50">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <svg class="w-6 h-6 text-pink-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">اطلاعات مرخصی</h2>
                        <p class="text-sm text-gray-600">تاریخ و دلیل مرخصی را مشخص کنید</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('specialist.leaves.store') }}" method="POST" class="p-6 relative">
                @csrf

                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium mb-1">نکات مهم:</p>
                            <ul class="mr-4 space-y-1 list-disc">
                                <li>درخواست مرخصی شما پس از ثبت منتظر تایید مدیریت خواهد بود</li>
                                <li>در صورت تایید، نوبت‌های این بازه زمانی لغو خواهند شد</li>
                                <li>فقط درخواست‌های تایید نشده قابل حذف هستند</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                تاریخ شروع مرخصی
                                <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <input type="text"
                               id="start_date_jalali"
                               name="start_date_jalali"
                               required
                               autocomplete="off"
                               class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-all cursor-pointer bg-white"
                               placeholder="انتخاب کنید...">
                        @error('start_date_jalali')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="relative">
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                تاریخ پایان مرخصی
                                <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <input type="text"
                               id="end_date_jalali"
                               name="end_date_jalali"
                               required
                               autocomplete="off"
                               class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-all cursor-pointer bg-white"
                               placeholder="انتخاب کنید...">
                        @error('end_date_jalali')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                دلیل مرخصی
                                <span class="text-gray-400 text-xs">(اختیاری)</span>
                            </span>
                        </label>
                        <textarea name="reason"
                                  rows="4"
                                  class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-all resize-none"
                                  placeholder="توضیحاتی درباره دلیل مرخصی خود بنویسید..."></textarea>
                        @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-3 mt-8 pt-6 border-t border-gray-100">
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition-all font-medium flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        ثبت درخواست مرخصی
                    </button>
                    <a href="{{ route('specialist.leaves') }}"
                       class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        انصراف
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-6 bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-600 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <div class="text-sm text-gray-600">
                    <p class="font-medium mb-1">نیاز به راهنمایی دارید؟</p>
                    <p>در صورت بروز هرگونه مشکل با پشتیبانی تماس بگیرید.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://unpkg.com/persian-date@latest/dist/persian-date.min.js"></script>
    <script src="https://unpkg.com/persian-datepicker@latest/dist/js/persian-datepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            const datePickerOptions = {
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                observer: true,
                calendar: {
                    persian: {
                        locale: 'fa'
                    }
                },
                responsive: true,
                position: 'auto',
            };

            const startPicker = $("#start_date_jalali").persianDatepicker({
                ...datePickerOptions,
                minDate: new persianDate(),
                onSelect: function(unix) {
                    const pd = new persianDate(unix);
                    $("#end_date_jalali").persianDatepicker('destroy');
                    $("#end_date_jalali").persianDatepicker({
                        ...datePickerOptions,
                        minDate: pd,
                        onSelect: function() {
                        }
                    });
                }
            });

            $("#end_date_jalali").persianDatepicker({
                ...datePickerOptions,
                minDate: new persianDate()
            });

            function formatDateInput(input) {
                const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                const englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

                let value = input.val();

                persianDigits.forEach((digit, index) => {
                    value = value.split(digit).join(englishDigits[index]);
                });

                value = value.replace(/[^0-9]/g, '');

                if (value.length >= 4) {
                    value = value.substring(0, 4) + '/' + value.substring(4);
                }
                if (value.length >= 7) {
                    value = value.substring(0, 7) + '/' + value.substring(7, 9);
                }

                input.val(value);
            }

            $('#start_date_jalali, #end_date_jalali').on('input', function() {
                formatDateInput($(this));
            });
        });
    </script>
@endpush
