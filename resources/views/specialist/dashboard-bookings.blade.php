@extends('layouts.app')

@section('title', 'پنل من')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">پنل من</h1>
                <p class="text-sm text-gray-500">خوش آمدید {{ $specialist->name }}</p>
            </div>

            <a href="{{ route('specialist.profile.show') }}"
               class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                پروفایل من
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <div class="flex items-center">
                    <svg class="w-6 h-6 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <div class="flex items-center">
                    <svg class="w-6 h-6 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <div class="flex items-center">
                    <svg class="w-6 h-6 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="font-medium">{{ session('info') }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">مدیریت نوبت‌های رزرو شده</h2>
            </div>

            @if($bookings->isEmpty())
                <div class="p-6 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p class="text-gray-500">نوبت فعالی برای انجام وجود ندارد.</p>
                </div>
            @else
                <div class="divide-y">
                    @foreach($bookings as $booking)
                        <div class="p-5 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-lg font-bold text-gray-900">{{ $booking->service->name }}</h3>
                                        @switch($booking->status)
                                            @case('pending')
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                                    در انتظار تایید
                                                </span>
                                                @break
                                            @case('confirmed')
                                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                                    تایید شده
                                                </span>
                                                @break
                                            @case('completed')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                                    ✓ انجام شده
                                                </span>
                                                @break
                                            @case('cancelled')
                                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                                    ✗ لغو شده
                                                </span>
                                                @break
                                            @default
                                                <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                                                    {{ $booking->status }}
                                                </span>
                                        @endswitch
                                    </div>

                                    <div class="space-y-1 text-sm text-gray-600">
                                        <p class="flex items-center">
                                            <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <span class="font-medium">مشتری:</span> {{ $booking->user->name }}
                                        </p>

                                        <p class="flex items-center">
                                            <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                            <span class="font-medium">تاریخ:</span>
                                            @if(isset($booking->booking_time))
                                                {{ \Morilog\Jalali\Jalalian::forge($booking->booking_time)->format('%A، %d %B Y') }}
                                            @else
                                                تاریخ نامشخص
                                            @endif
                                        </p>

                                        <p class="flex items-center">
                                            <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <span class="font-medium">ساعت:</span>
                                            @if(isset($booking->booking_time))
                                                {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                            @else
                                                نامشخص
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                                    @if($booking->status === 'pending')
                                        <button type="button"
                                                onclick="confirmBooking({{ $booking->id }}, '{{ $booking->user->name }}', '{{ $booking->service->name }}')"
                                                class="w-full sm:w-auto bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            تایید نوبت
                                        </button>

                                        <button type="button"
                                                onclick="showCancelModal({{ $booking->id }}, '{{ $booking->user->name }}', '{{ $booking->service->name }}')"
                                                class="w-full sm:w-auto bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                            لغو نوبت
                                        </button>

                                    @elseif($booking->status === 'confirmed')
                                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg flex items-center gap-2">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                            <span class="text-sm font-medium">تایید شده</span>
                                        </div>

                                        <button type="button"
                                                onclick="showCancelModal({{ $booking->id }}, '{{ $booking->user->name }}', '{{ $booking->service->name }}')"
                                                class="w-full sm:w-auto bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                            لغو نوبت
                                        </button>

                                    @elseif($booking->status === 'cancelled')
                                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg">
                                            <span class="text-sm font-medium">✗ این نوبت لغو شده است</span>
                                        </div>

                                    @elseif($booking->status === 'completed')
                                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                            <span class="text-sm font-medium">این نوبت با موفقیت انجام شده است</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($bookings->hasPages())
                    <div class="p-4 border-t">
                        {{ $bookings->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-center mb-2">تایید نوبت</h3>
            <p class="text-gray-600 text-center mb-6" id="confirmMessage"></p>
            <form id="confirmForm" method="POST">
                @csrf
                @method('PUT')
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium transition">
                        بله، تایید کن
                    </button>
                    <button type="button" onclick="hideConfirmModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg font-medium transition">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-center mb-2">لغو نوبت</h3>
            <p class="text-gray-600 text-center mb-4" id="cancelMessage"></p>
            <form id="cancelForm" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">دلیل لغو (اختیاری)</label>
                    <textarea name="cancel_reason"
                              class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              rows="3"
                              placeholder="مثال: متخصص مرخصی دارد، تغییر برنامه کاری، ..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium transition">
                        بله، لغو کن
                    </button>
                    <button type="button" onclick="hideCancelModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg font-medium transition">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmBooking(bookingId, customerName, serviceName) {
            const message = `آیا می‌خواهید نوبت "${serviceName}" برای "${customerName}" را تایید کنید؟`;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmForm').action = `{{ url('/specialist/bookings') }}/${bookingId}/complete`;
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function showCancelModal(bookingId, customerName, serviceName) {
            const message = `آیا می‌خواهید نوبت "${serviceName}" برای "${customerName}" را لغو کنید؟`;
            document.getElementById('cancelMessage').textContent = message;
            document.getElementById('cancelForm').action = `{{ url('/specialist/bookings') }}/${bookingId}/cancel`;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function hideCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) hideConfirmModal();
        });

        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) hideCancelModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideConfirmModal();
                hideCancelModal();
            }
        });
    </script>
@endsection
