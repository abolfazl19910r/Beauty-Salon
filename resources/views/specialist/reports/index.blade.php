@extends('layouts.specialist')

@section('title', 'گزارش عملکرد')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
    <style>
        .datepicker-container {
            z-index: 99999 !important;
            position: absolute !important;
        }
        .datepicker-plot-area {
            font-family: 'vazir', sans-serif !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }
        .datepicker-header {
            background-color: #f3f4f6 !important;
            border-bottom: 1px solid #e5e7eb !important;
            border-radius: 8px 8px 0 0 !important;
            padding: 5px 0 !important;
        }
        .datepicker-header .btn-next,
        .datepicker-header .btn-prev {
            color: #374151 !important;
        }
        .datepicker-header .btn-switch {
            color: #1f2937 !important;
            font-weight: bold !important;
            background-color: transparent !important;
        }
        .datepicker-year-view .year-item,
        .datepicker-month-view .month-item {
            color: #374151 !important;
        }
    </style>
@endpush

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                فیلتر گزارشات
            </h2>

            <form action="{{ route('specialist.reports.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">از تاریخ</label>
                        <input type="text" id="start_date_filter" name="start_date" value="{{ $startDate }}"
                               class="w-full rounded-lg border-gray-300 focus:border-pink-500 text-center cursor-pointer" dir="ltr" autocomplete="off">
                    </div>

                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">تا تاریخ</label>
                        <input type="text" id="end_date_filter" name="end_date" value="{{ $endDate }}"
                               class="w-full rounded-lg border-gray-300 focus:border-pink-500 text-center cursor-pointer" dir="ltr" autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع خدمت</label>
                        <select name="service_id" class="w-full rounded-lg border-gray-300 focus:border-pink-500">
                            <option value="all" {{ $serviceId == 'all' ? 'selected' : '' }}>همه خدمات</option>
                            @foreach($specialistServices as $service)
                                <option value="{{ $service->id }}" {{ $serviceId == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">وضعیت نوبت</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 focus:border-pink-500">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>همه وضعیت‌ها</option>
                            <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>انجام شده</option>
                            <option value="confirmed" {{ $status == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>در انتظار</option>
                            <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>اعمال فیلتر</span>
                    </button>

                    <button type="submit" name="export" value="excel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>خروجی اکسل</span>
                    </button>

                    <button type="submit" name="export" value="pdf" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>خروجی PDF</span>
                    </button>

                    @if(request()->has('start_date') || request()->has('end_date') || request()->get('service_id') != 'all' || request()->get('status') != 'all')
                        <a href="{{ route('specialist.reports.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>حذف فیلتر</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border-r-4 border-green-500">
                <div class="text-gray-500 text-sm mb-1 font-medium">درآمد حاصله (بیعانه)</div>
                <div class="text-2xl font-bold text-gray-800 persian-number">
                    {{ number_format($totalRevenue) }}
                    <span class="text-xs text-gray-400 font-normal mr-1">تومان</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border-r-4 border-blue-500">
                <div class="text-gray-500 text-sm mb-1 font-medium">کل نوبت‌های دریافتی</div>
                <div class="text-2xl font-bold text-gray-800 persian-number">{{ $totalBookings }}</div>
                <div class="text-[10px] text-gray-400 mt-1">شامل همه وضعیت‌ها در این بازه</div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border-r-4 border-emerald-500">
                <div class="text-gray-500 text-sm mb-1 font-medium">خدمات ارائه شده</div>
                <div class="text-2xl font-bold text-emerald-600 persian-number">{{ $completedBookings }}</div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border-r-4 border-red-500">
                <div class="text-gray-500 text-sm mb-1 font-medium">نوبت‌های لغو شده</div>
                <div class="text-2xl font-bold text-red-600 persian-number">{{ $cancelledBookings }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">ریز تراکنش‌ها و نوبت‌ها</h3>
                <span class="text-xs text-gray-500">نمایش {{ $bookings->count() }} مورد</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead class="bg-gray-50 text-gray-600 text-sm">
                    <tr>
                        <th class="p-4">شماره نوبت</th>
                        <th class="p-4">مشتری</th>
                        <th class="p-4">خدمت</th>
                        <th class="p-4">تاریخ و ساعت</th>
                        <th class="p-4">مبلغ (تومان)</th>
                        <th class="p-4">وضعیت</th>
                        <th class="p-4">تاریخ ثبت</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($bookings as $booking)
                        <tr class="hover:bg-gray-50 transition-colors text-sm">
                            <td class="p-4 font-mono text-gray-500">#{{ $booking->id }}</td>
                            <td class="p-4">
                                <div class="font-medium text-gray-800">{{ $booking->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $booking->user->phone }}</div>
                            </td>
                            <td class="p-4">{{ $booking->service->name ?? 'حذف شده' }}</td>
                            <td class="p-4">
                                <div class="font-medium">{{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d') }}</div>
                                <div class="text-xs text-gray-500">{{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('H:i') }}</div>
                            </td>
                            <td class="p-4 font-bold text-gray-700">{{ number_format($booking->prepayment_amount) }}</td>
                            <td class="p-4">
                                @php
                                    $statusClass = match($booking->status) {
                                        'completed' => 'bg-green-100 text-green-700',
                                        'confirmed' => 'bg-blue-100 text-blue-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    $statusText = match($booking->status) {
                                        'completed' => 'انجام شده',
                                        'confirmed' => 'تایید شده',
                                        'pending' => 'در انتظار',
                                        'cancelled' => 'لغو شده',
                                        default => 'نامشخص'
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                            </td>
                            <td class="p-4 text-gray-500 text-xs">
                                {{ \Morilog\Jalali\Jalalian::fromCarbon($booking->created_at)->format('Y/m/d H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">
                                موردی با این مشخصات یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100">
                {{ $bookings->links() }}
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

            $("#start_date_filter").persianDatepicker({
                ...datePickerOptions,
                onSelect: function(unix) {
                    const pd = new persianDate(unix);
                    $("#end_date_filter").persianDatepicker('destroy');
                    $("#end_date_filter").persianDatepicker({
                        ...datePickerOptions,
                        minDate: pd
                    });
                }
            });

            $("#end_date_filter").persianDatepicker({
                ...datePickerOptions
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

            $('#start_date_filter, #end_date_filter').on('input', function() {
                formatDateInput($(this));
            });
        });
    </script>
@endpush
