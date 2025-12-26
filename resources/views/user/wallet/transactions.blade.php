@extends('layouts.app')

@section('title', 'تراکنش‌های کیف پول')

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
    <div class="max-w-7xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <svg class="w-8 h-8 ml-2 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    تراکنش‌های کیف پول
                </h1>
                <p class="text-gray-600 mt-2">مشاهده تمام تراکنش‌های مالی</p>
            </div>
            <a href="{{ route('wallet.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                </svg>
                بازگشت به کیف پول
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">موجودی فعلی</p>
                        <p class="text-2xl font-bold text-gray-900 persian-number">{{ number_format($wallet->balance) }}</p>
                        <p class="text-xs text-gray-500 mt-1">تومان</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">کل واریزی‌ها</p>
                        <p class="text-2xl font-bold text-gray-900 persian-number">{{ number_format($wallet->total_deposited) }}</p>
                        <p class="text-xs text-gray-500 mt-1">تومان</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">کل پرداختی‌ها</p>
                        <p class="text-2xl font-bold text-gray-900 persian-number">{{ number_format($wallet->total_spent) }}</p>
                        <p class="text-xs text-gray-500 mt-1">تومان</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" action="{{ route('wallet.transactions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع تراکنش</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">همه تراکنش‌ها</option>
                        <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>واریز</option>
                        <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>پرداخت</option>
                        <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>بازگشت وجه</option>
                        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>تعدیل</option>
                    </select>
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                    <input type="text"
                           id="date_from"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           placeholder="YYYY/MM/DD"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-center cursor-pointer"
                           dir="ltr"
                           autocomplete="off">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                    <input type="text"
                           id="date_to"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           placeholder="YYYY/MM/DD"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-center cursor-pointer"
                           dir="ltr"
                           autocomplete="off">
                </div>

                <div class="flex items-end space-x-2 space-x-reverse">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-lg hover:opacity-90 transition flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        جستجو
                    </button>

                    @if(request()->hasAny(['type', 'date_from', 'date_to']))
                        <a href="{{ route('wallet.transactions') }}"
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition flex items-center justify-center"
                           title="حذف فیلترها">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-pink-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    لیست تراکنش‌ها
                    <span class="mr-2 px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm persian-number">
                    {{ $transactions->total() }} مورد
                </span>
                </h2>
            </div>

            <div class="overflow-x-auto">
                @forelse($transactions as $transaction)
                    <div class="flex items-center justify-between p-6 border-b border-gray-100 hover:bg-gray-50 transition">
                        <div class="flex items-center space-x-4 space-x-reverse flex-1">
                            <div class="flex-shrink-0">
                                @if($transaction->type === 'refund')
                                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                    </div>
                                @elseif($transaction->type === 'payment')
                                    <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                @elseif($transaction->type === 'deposit')
                                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-7 h-7 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center mb-1">
                                    <h3 class="text-base font-semibold text-gray-900">
                                        {{ $transaction->type_text }}
                                    </h3>
                                    <span class="mr-2 px-2 py-0.5 text-xs font-medium rounded-full
                                {{ $transaction->type === 'refund' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $transaction->type === 'payment' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $transaction->type === 'deposit' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $transaction->type === 'adjustment' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ $transaction->type_text }}
                            </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-2">{{ $transaction->description }}</p>

                                <div class="flex items-center text-xs text-gray-500 space-x-4 space-x-reverse">
                            <span class="flex items-center">
                                <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ \Morilog\Jalali\Jalalian::forge($transaction->created_at)->format('Y/m/d - H:i') }}
                            </span>

                                    @if($transaction->booking)
                                        <span class="flex items-center">
                                <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                نوبت #{{ $transaction->booking_id }}
                            </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-left mr-6">
                            <p class="text-2xl font-bold persian-number mb-1
                        {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                            </p>
                            <p class="text-xs text-gray-500">موجودی: <span class="persian-number">{{ number_format($transaction->balance_after) }}</span></p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-gray-500 text-xl font-medium mb-2">هیچ تراکنشی یافت نشد</p>
                        <p class="text-gray-400">با فیلترهای مختلف جستجو کنید یا تراکنش جدیدی انجام دهید</p>
                    </div>
                @endforelse
            </div>

            @if($transactions->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t">
                    {{ $transactions->links() }}
                </div>
            @endif
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

            $("#date_from").persianDatepicker({
                ...datePickerOptions,
                onSelect: function(unix) {
                    const pd = new persianDate(unix);
                    $("#date_to").persianDatepicker('destroy');
                    $("#date_to").persianDatepicker({
                        ...datePickerOptions,
                        minDate: pd
                    });
                }
            });

            $("#date_to").persianDatepicker({
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

            $('#date_from, #date_to').on('input', function() {
                formatDateInput($(this));
            });
        });
    </script>
@endpush
