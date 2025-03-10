@extends('layouts.admin')

@section('title', 'مدیریت نوبت‌ها')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold flex items-center">
                    <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    مدیریت نوبت‌ها
                </h1>

                <a href="{{ route('admin.bookings.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    افزودن نوبت جدید
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row gap-4 justify-between">
                    <div>
                        <h2 class="text-lg font-medium mb-4">فیلتر نوبت‌ها</h2>
                        <div class="flex flex-wrap gap-4">
                            <select class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    onchange="window.location.href=this.value">
                                <option value="{{ route('admin.bookings.index') }}"
                                    {{ request('status') == '' ? 'selected' : '' }}>
                                    همه وضعیت‌ها
                                </option>
                                <option value="{{ route('admin.bookings.index', ['status' => 'pending']) }}"
                                    {{ request('status') == 'pending' ? 'selected' : '' }}>
                                    در انتظار تایید
                                </option>
                                <option value="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}"
                                    {{ request('status') == 'confirmed' ? 'selected' : '' }}>
                                    تایید شده
                                </option>
                                <option value="{{ route('admin.bookings.index', ['status' => 'cancelled']) }}"
                                    {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                    لغو شده
                                </option>
                            </select>

                            <div class="relative">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                                <input type="text"
                                       id="date-picker"
                                       class="border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       value="{{ request('date') ? verta(request('date'))->format('Y/m/d') : verta()->format('Y/m/d') }}"
                                       readonly>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-blue-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300">
                                <span class="text-xs text-blue-500">کل نوبت‌ها</span>
                                <div class="text-xl font-bold text-blue-700 persian-number">{{ $totalBookings ?? 0 }}</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300">
                                <span class="text-xs text-green-500">تایید شده</span>
                                <div class="text-xl font-bold text-green-700 persian-number">{{ $confirmedBookings ?? 0 }}</div>
                            </div>
                            <div class="bg-red-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300">
                                <span class="text-xs text-red-500">لغو شده</span>
                                <div class="text-xl font-bold text-red-700 persian-number">{{ $cancelledBookings ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-x-auto">
            <table class="w-full text-sm" dir="rtl">
                <thead>
                <tr class="bg-gradient-to-r from-blue-50 to-blue-100 text-blue-800">
                    <th class="px-6 py-3 text-right">شماره</th>
                    <th class="px-6 py-3 text-right">مشتری</th>
                    <th class="px-6 py-3 text-right">خدمت</th>
                    <th class="px-6 py-3 text-right">متخصص</th>
                    <th class="px-6 py-3 text-right">تاریخ و ساعت</th>
                    <th class="px-6 py-3 text-right">وضعیت پرداخت</th>
                    <th class="px-6 py-3 text-right">وضعیت</th>
                    <th class="px-6 py-3 text-right">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($bookings as $booking)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-right font-medium">{{ $booking->id }}</td>
                        <td class="px-6 py-4 text-right">
                            @if(isset($booking->user))
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center ml-2">
                                        {{ mb_substr($booking->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="block">{{ $booking->user->name }}</span>
                                        <span class="text-xs text-gray-500" dir="ltr">{{ $booking->user->phone }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-400">کاربر نامشخص</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">{{ $booking->service->name }}</td>
                        <td class="px-6 py-4 text-right">{{ $booking->specialist?->name ?? 'متخصص نامشخص' }}</td>
                        <td class="px-6 py-4 text-right persian-number">
                            <div class="flex flex-col">
                                <span>{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                                <span class="text-xs text-gray-500">{{ verta($booking->booking_time)->format('H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($booking->payment_status == 'paid')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    پرداخت شده
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    پرداخت نشده
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @switch($booking->status)
                                @case('pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        در انتظار تایید
                                    </span>
                                    @break
                                @case('confirmed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        تایید شده
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                        لغو شده
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.bookings.show', $booking) }}"
                                   class="group inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                <a href="{{ route('admin.bookings.edit', $booking) }}"
                                   class="group inline-flex items-center text-purple-600 hover:text-purple-800 transition-colors">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>

                                @if($booking->status == 'pending')
                                    <form action="{{ route('admin.bookings.update', $booking) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="group inline-flex items-center text-green-600 hover:text-green-800 transition-colors">
                                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if($booking->status != 'cancelled')
                                    <form action="{{ route('admin.bookings.update', $booking) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="group inline-flex items-center text-red-600 hover:text-red-800 transition-colors"
                                                data-confirm-delete data-confirm-message="آیا از لغو این نوبت اطمینان دارید؟">
                                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#date-picker').persianDatepicker({
                    format: 'YYYY/MM/DD',
                    initialValueType: 'persian',
                    initialValue: true,
                    autoClose: true,
                    persianDigit: true,
                    onSelect: function(unix) {
                        const date = new persianDate(unix).toCalendar('gregorian').format('YYYY-MM-DD');
                        window.location.href = '{{ route("admin.bookings.index") }}?date=' + date;
                    }
                });
            });
        </script>
    @endpush
@endsection
