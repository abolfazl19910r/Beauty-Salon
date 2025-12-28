@extends('layouts.specialist')

@section('title', 'مدیریت نوبت‌های رزرو شده')

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

        #filterForm {
            transition: all 0.3s ease-in-out;
            overflow: hidden;
        }

        #filterForm.hidden {
            max-height: 0;
            opacity: 0;
        }

        #filterForm:not(.hidden) {
            max-height: 2000px;
            opacity: 1;
        }

        .booking-row {
            transition: all 0.2s ease;
        }

        .booking-row:hover {
            transform: translateX(-2px);
        }

        @media (max-width: 768px) {
            #filterForm .grid {
                grid-template-columns: 1fr;
            }

            .booking-actions {
                flex-direction: column;
                width: 100%;
            }

            .booking-actions button,
            .booking-actions a {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="fade-in">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    🔍 فیلتر نوبت‌ها
                </h3>
                <button onclick="toggleFilters()" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                    <span id="filterToggleText">نمایش فیلترها</span>
                </button>
            </div>

            <form method="GET" action="{{ route('specialist.bookings') }}" id="filterForm" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📅 از تاریخ</label>
                        <input type="text" name="date_from" id="date_from"
                               value="{{ request('date_from') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center cursor-pointer"
                               placeholder="مثال: 1403/09/15"
                               dir="ltr"
                               autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📅 تا تاریخ</label>
                        <input type="text" name="date_to" id="date_to"
                               value="{{ request('date_to') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center cursor-pointer"
                               placeholder="مثال: 1403/09/20"
                               dir="ltr"
                               autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🕐 زمان</label>
                        <input type="time" name="time"
                               value="{{ request('time') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📊 وضعیت سرویس</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار تایید</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>انجام شده</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                            <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>در انتظار پرداخت</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">💳 وضعیت مالی</label>
                        <select name="payment_status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                            <option value="pending_payment" {{ request('payment_status') == 'pending_payment' ? 'selected' : '' }}>در انتظار پرداخت</option>
                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>پرداخت نشده</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📱 شماره تماس</label>
                        <input type="text" name="phone"
                               value="{{ request('phone') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent dir-ltr"
                               placeholder="09123456789">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">👤 نام مشتری</label>
                        <input type="text" name="customer_name"
                               value="{{ request('customer_name') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="نام مشتری">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🔄 مرتب‌سازی</label>
                        <select name="sort_by" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>جدیدترین</option>
                            <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>قدیمی‌ترین</option>
                            <option value="date_asc" {{ request('sort_by') == 'date_asc' ? 'selected' : '' }}>تاریخ صعودی</option>
                            <option value="date_desc" {{ request('sort_by') == 'date_desc' ? 'selected' : '' }}>تاریخ نزولی</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        اعمال فیلتر
                    </button>
                    <a href="{{ route('specialist.bookings') }}" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-200 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        حذف فیلترها
                    </a>
                </div>
            </form>

            @if(request()->hasAny(['date_from', 'date_to', 'time', 'status', 'payment_status', 'phone', 'customer_name']))
                <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <span class="text-sm text-blue-800 font-medium">
                            🔍 فیلترهای فعال:
                        </span>
                        <div class="flex flex-wrap gap-2">
                            @if(request('date_from'))
                                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">از تاریخ: {{ request('date_from') }}</span>
                            @endif
                            @if(request('date_to'))
                                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">تا تاریخ: {{ request('date_to') }}</span>
                            @endif
                            @if(request('status'))
                                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">وضعیت سرویس</span>
                            @endif
                            @if(request('payment_status'))
                                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">وضعیت مالی</span>
                            @endif
                            @if(request('phone'))
                                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">شماره تماس</span>
                            @endif
                            @if(request('customer_name'))
                                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">نام مشتری</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($bookings->isEmpty())
                <div class="p-10 text-center text-gray-400 font-medium">
                    @if(request()->hasAny(['date_from', 'date_to', 'time', 'status', 'payment_status', 'phone', 'customer_name']))
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        🔍 نوبتی با فیلترهای انتخابی یافت نشد.
                    @else
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        هیچ نوبتی در لیست شما یافت نشد.
                    @endif
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($bookings as $booking)
                        @php
                            $status = trim($booking->status);
                            $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;
                        @endphp
                        <div class="p-5 booking-row hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <h3 class="font-bold text-gray-900">{{ $booking->service->name }}</h3>

                                        @if($status == 'pending')
                                            <span class="text-xs bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-bold">⏳ در انتظار تایید</span>
                                        @elseif($status == 'confirmed')
                                            <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold">✅ تایید شده</span>
                                        @elseif($status == 'completed')
                                            <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold">✔️ انجام شده</span>
                                        @elseif($status == 'cancelled')
                                            <span class="text-xs bg-red-100 text-red-800 px-3 py-1 rounded-full font-bold">❌ لغو شده</span>
                                        @elseif($status == 'pending_payment')
                                            <span class="text-xs bg-orange-100 text-orange-800 px-3 py-1 rounded-full font-bold">💳 در انتظار پرداخت</span>
                                        @endif
                                    </div>

                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p><strong>مشتری:</strong> {{ $booking->user->name }} | <span class="dir-ltr">{{ $booking->user->phone }}</span></p>
                                        <p><strong>زمان:</strong> {{ verta($booking->booking_time)->format('Y/m/d ساعت H:i') }}</p>
                                        <p><strong>وضعیت مالی:</strong>
                                            @if($booking->payment_status == 'paid')
                                                <span class="text-green-600 font-medium">✅ پرداخت شده</span>
                                            @elseif($booking->payment_status == 'pending_payment')
                                                <span class="text-yellow-600 font-medium">⏳ در انتظار پرداخت</span>
                                            @else
                                                <span class="text-red-500 font-medium">❌ پرداخت نشده</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 booking-actions">
                                    <a href="{{ route('specialist.bookings.show', $booking->id) }}"
                                       class="bg-blue-50 text-blue-600 p-2 rounded hover:bg-blue-100 transition"
                                       title="مشاهده جزئیات">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(!in_array($status, ['completed', 'cancelled', 'pending_payment']))

                                        @if($status == 'pending')
                                            <button onclick="confirmBooking('{{ route('specialist.bookings.complete', $booking->id) }}', '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service->name) }}')"
                                                    class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-600 transition">
                                                ✅ تایید
                                            </button>

                                            <button onclick="showCancelModal({{ $booking->id }}, '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service->name) }}')"
                                                    class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                                                ❌ لغو
                                            </button>
                                        @endif

                                        @if($status == 'confirmed')
                                            <button onclick="markCompleted({{ $booking->id }}, '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service->name) }}')"
                                                    class="bg-purple-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-purple-600 transition">
                                                ✔️ انجام شد
                                            </button>

                                            <button onclick="showCancelModal({{ $booking->id }}, '{{ addslashes($booking->user->name) }}', '{{ addslashes($booking->service->name) }}')"
                                                    class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                                                ❌ لغو نوبت
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 border-t bg-gray-50">{{ $bookings->links() }}</div>
            @endif
        </div>
    </div>

    <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">✅ تایید نوبت رزرو شده</h3>
            <p class="text-gray-600 mb-6" id="confirmMessage"></p>
            <form id="confirmForm" method="POST">
                @csrf @method('PUT')
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-2 rounded-lg font-bold hover:bg-green-700">بله، تایید شود</button>
                    <button type="button" onclick="hideConfirmModal()" class="flex-1 bg-gray-100 py-2 rounded-lg hover:bg-gray-200">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div id="completedModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4 text-purple-600">✔️ انجام شده</h3>
            <p class="text-gray-600 mb-6" id="completedMessage"></p>
            <form id="completedForm" method="POST">
                @csrf @method('PUT')
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-purple-600 text-white py-2 rounded-lg font-bold hover:bg-purple-700">بله، انجام شد</button>
                    <button type="button" onclick="hideCompletedModal()" class="flex-1 bg-gray-100 py-2 rounded-lg hover:bg-gray-200">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div id="cancelListModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4 text-red-600">❌ لغو نوبت</h3>
            <p class="text-gray-600 mb-4" id="cancelMessage"></p>
            <form id="cancelListForm" method="POST">
                @csrf @method('PUT')
                <textarea name="cancel_reason" required class="w-full border rounded p-2 mb-4" rows="3" placeholder="دلیل لغو را بنویسید..."></textarea>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-700">لغو نوبت</button>
                    <button type="button" onclick="hideCancelModal()" class="flex-1 bg-gray-100 py-2 rounded-lg hover:bg-gray-200">انصراف</button>
                </div>
            </form>
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

        function toggleFilters() {
            const filterForm = document.getElementById('filterForm');
            const toggleText = document.getElementById('filterToggleText');

            if (filterForm.classList.contains('hidden')) {
                filterForm.classList.remove('hidden');
                toggleText.textContent = 'پنهان کردن فیلترها';
            } else {
                filterForm.classList.add('hidden');
                toggleText.textContent = 'نمایش فیلترها';
            }
        }

        @if(request()->hasAny(['date_from', 'date_to', 'time', 'status', 'payment_status', 'phone', 'customer_name']))
        document.addEventListener('DOMContentLoaded', function() {
            toggleFilters();
        });
        @endif

        function confirmBooking(url, name, service) {
            document.getElementById('confirmMessage').innerText = `آیا از تایید نوبت "${service}" برای مشتری "${name}" اطمینان دارید؟`;
            document.getElementById('confirmForm').action = url;
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function markCompleted(id, name, service) {
            document.getElementById('completedMessage').innerText = `آیا می‌خواهید نوبت "${service}" برای "${name}" را به عنوان انجام شده علامت‌گذاری کنید؟`;
            document.getElementById('completedForm').action = `/specialist/bookings/${id}/mark-completed`;
            document.getElementById('completedModal').classList.remove('hidden');
        }

        function hideCompletedModal() {
            document.getElementById('completedModal').classList.add('hidden');
        }

        function showCancelModal(id, name, service) {
            document.getElementById('cancelMessage').innerText = `آیا می‌خواهید نوبت "${service}" مشتری "${name}" را لغو کنید؟`;
            document.getElementById('cancelListForm').action = `/specialist/bookings/${id}/cancel`;
            document.getElementById('cancelListModal').classList.remove('hidden');
        }

        function hideCancelModal() {
            document.getElementById('cancelListModal').classList.add('hidden');
        }
    </script>
@endpush
