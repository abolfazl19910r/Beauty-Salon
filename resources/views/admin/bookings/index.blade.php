@extends('layouts.admin')

@section('title', 'مدیریت نوبت‌ها')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">مدیریت نوبت‌ها</h1>

            <div class="flex gap-4">
                <select class="border rounded px-3 py-2" onchange="window.location.href=this.value">
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

                <input type="text"
                       id="date-picker"
                       class="border rounded px-3 py-2"
                       value="{{ request('date') ? verta(request('date'))->format('Y/m/d') : verta()->format('Y/m/d') }}"
                       readonly>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto" dir="rtl">
            <table class="w-full" dir="rtl">
                <thead>
                <tr class="bg-gray-50 text-right">
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
                    <tr>
                        <td class="px-6 py-4 text-right">{{ $booking->id }}</td>
                        <td class="px-6 py-4 text-right">
                            @if(isset($booking->user))
                                {{ $booking->user->name }}
                            @else
                                کاربر نامشخص
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">{{ $booking->service->name }}</td>
                        <td class="px-6 py-4 text-right">{{ $booking->specialist?->name ?? 'متخصص نامشخص' }}</td>
                        <td class="px-6 py-4 text-right">
                            {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($booking->payment_status == 'paid')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                پرداخت شده
                            </span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">
                                پرداخت نشده
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @switch($booking->status)
                                @case('pending')
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">
                                    در انتظار تایید
                                </span>
                                    @break
                                @case('confirmed')
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                    تایید شده
                                </span>
                                    @break
                                @case('cancelled')
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">
                                    لغو شده
                                </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                               class="text-blue-500">جزئیات</a>

                            @if($booking->status == 'pending')
                                <form action="{{ route('admin.bookings.update', $booking) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="text-green-500 mr-4">
                                        تایید
                                    </button>
                                </form>
                            @endif

                            @if($booking->status != 'cancelled')
                                <form action="{{ route('admin.bookings.update', $booking) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="text-red-500 mr-4"
                                            onclick="return confirm('آیا مطمئن هستید؟')">
                                        لغو
                                    </button>
                                </form>
                            @endif
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
