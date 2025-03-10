@extends('layouts.admin')

@section('title', 'ویرایش نوبت')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-6 pb-4 border-b">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <h1 class="text-2xl font-bold">ویرایش نوبت #{{ $booking->id }}</h1>
            </div>

            <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="user_id" class="block mb-2 text-sm font-medium text-gray-700">مشتری</label>
                        <select id="user_id" name="user_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $booking->user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->phone }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="service_id" class="block mb-2 text-sm font-medium text-gray-700">خدمت</label>
                        <select id="service_id" name="service_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ $booking->service_id == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }} - {{ number_format($service->price) }} تومان
                                </option>
                            @endforeach
                        </select>
                        @error('service_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="specialist_id" class="block mb-2 text-sm font-medium text-gray-700">متخصص</label>
                        <select id="specialist_id" name="specialist_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            @foreach($specialists as $specialist)
                                <option value="{{ $specialist->id }}" {{ $booking->specialist_id == $specialist->id ? 'selected' : '' }}>
                                    {{ $specialist->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('specialist_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="booking_time" class="block mb-2 text-sm font-medium text-gray-700">تاریخ و زمان</label>
                        <input type="text" id="booking_time" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" readonly>
                        <input type="hidden" name="booking_time" id="booking_time_hidden" value="{{ $booking->booking_time }}">
                        @error('booking_time')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block mb-2 text-sm font-medium text-gray-700">وضعیت نوبت</label>
                        <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="pending" {{ $booking->status == 'pending' ? 'selected' : '' }}>در انتظار تایید</option>
                            <option value="confirmed" {{ $booking->status == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                            <option value="cancelled" {{ $booking->status == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                        </select>
                        @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_status" class="block mb-2 text-sm font-medium text-gray-700">وضعیت پرداخت</label>
                        <select id="payment_status" name="payment_status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="unpaid" {{ $booking->payment_status == 'unpaid' ? 'selected' : '' }}>پرداخت نشده</option>
                            <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                        </select>
                        @error('payment_status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700">یادداشت</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">{{ $booking->notes }}</textarea>
                    @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-between pt-4 border-t">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        ذخیره تغییرات
                    </button>
                    <a href="{{ route('admin.bookings.show', $booking) }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5"></path>
                            <path d="M12 19l-7-7 7-7"></path>
                        </svg>
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                const bookingDate = new Date('{{ $booking->booking_time }}');

                $('#booking_time').persianDatepicker({
                    initialValue: true,
                    initialValueType: 'gregorian',
                    initialValue: bookingDate,
                    format: 'YYYY/MM/DD HH:mm',
                    timePicker: {
                        enabled: true,
                        step: 15
                    },
                    onSelect: function(unix) {
                        const date = new persianDate(unix).toCalendar('gregorian').format('YYYY-MM-DD HH:mm:ss');
                        $('#booking_time_hidden').val(date);
                    }
                });
            });
        </script>
    @endpush
@endsection
