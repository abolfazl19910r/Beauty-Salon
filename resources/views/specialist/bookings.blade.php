@extends('layouts.specialist')

@section('title', 'مدیریت نوبت‌های رزرو شده')

@section('content')
    <div class="fade-in">
        @if(session('success'))
            <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($bookings->isEmpty())
                <div class="p-10 text-center text-gray-400 font-medium">هیچ نوبتی در لیست شما یافت نشد.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($bookings as $booking)
                        @php
                            $status = trim($booking->status);
                            $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;
                        @endphp
                        <div class="p-5 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
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

                                <div class="flex items-center gap-2">
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
                                            <button onclick="confirmBooking({{ $booking->id }}, '{{ $booking->user->name }}', '{{ $booking->service->name }}')"
                                                    class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-600 transition">
                                                ✅ تایید
                                            </button>

                                            <button onclick="showCancelModal({{ $booking->id }}, '{{ $booking->user->name }}', '{{ $booking->service->name }}')"
                                                    class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                                                ❌ لغو
                                            </button>
                                        @endif

                                        @if($status == 'confirmed')
                                            <button onclick="showCancelModal({{ $booking->id }}, '{{ $booking->user->name }}', '{{ $booking->service->name }}')"
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

    <script>
        function confirmBooking(id, name, service) {
            document.getElementById('confirmMessage').innerText = `آیا از تایید دستی نوبت "${service}" برای "${name}" اطمینان دارید؟`;
            document.getElementById('confirmForm').action = `/specialist/bookings/${id}/complete`;
            document.getElementById('confirmModal').classList.remove('hidden');
        }
        function hideConfirmModal() { document.getElementById('confirmModal').classList.add('hidden'); }

        function showCancelModal(id, name, service) {
            document.getElementById('cancelMessage').innerText = `آیا می‌خواهید نوبت "${service}" مشتری "${name}" را لغو کنید؟`;
            document.getElementById('cancelListForm').action = `/specialist/bookings/${id}/cancel`;
            document.getElementById('cancelListModal').classList.remove('hidden');
        }
        function hideCancelModal() { document.getElementById('cancelListModal').classList.add('hidden'); }
    </script>
@endsection
