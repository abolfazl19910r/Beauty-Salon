@extends('layouts.admin')

@section('title', 'جزئیات اعلان')

@section('content')
    <div class="p-6">
        <div class="mb-6">
            <a href="{{ route('admin.notifications.index') }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-800 transition">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                بازگشت به لیست اعلانات
            </a>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-8 h-8 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        جزئیات اعلان
                    </h1>
                    <span id="read-status-badge" class="px-4 py-2 rounded-full text-sm font-medium">
                        @if (is_null($notification->read_at))
                            <span class="bg-white text-blue-600">🔔 خوانده نشده</span>
                        @else
                            <span class="bg-blue-400 text-white">✓ خوانده شده</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="p-6">
                @php
                    $data = (array) $notification->data;
                    $message = $data['message'] ?? 'پیام اعلان موجود نیست';
                    $type = $data['type'] ?? 'عمومی';
                    $link = $data['link'] ?? null;
                    $details = $data['details'] ?? null;

                    $createdTime = function_exists('verta')
                        ? verta($notification->created_at)->format('Y/m/d - H:i:s')
                        : $notification->created_at->format('Y/m/d - H:i:s');

                    $readTime = $notification->read_at
                        ? (function_exists('verta')
                            ? verta($notification->read_at)->format('Y/m/d - H:i:s')
                            : $notification->read_at->format('Y/m/d - H:i:s'))
                        : null;
                @endphp

                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-700 mb-3">پیام اعلان:</h2>
                    <div class="bg-gray-50 border-r-4 border-blue-500 p-4 rounded-lg">
                        <p class="text-gray-800 text-base leading-relaxed">
                            {!! nl2br(e($message)) !!}
                        </p>
                    </div>
                </div>

                @if($details)
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-700 mb-3">جزئیات بیشتر:</h2>
                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                            <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                                {{ $details }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <div>
                                <p class="text-sm text-gray-500">نوع اعلان</p>
                                <p class="font-medium text-gray-800">{{ $type }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p class="text-sm text-gray-500">زمان دریافت</p>
                                <p class="font-medium text-gray-800">{{ $createdTime }}</p>
                            </div>
                        </div>
                    </div>

                    <div id="read-time-box" class="bg-gray-50 p-4 rounded-lg {{ $readTime ? '' : 'hidden' }}">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-purple-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <div>
                                <p class="text-sm text-gray-500">زمان مشاهده</p>
                                <p id="read-time-text" class="font-medium text-gray-800">{{ $readTime ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500">شناسه</p>
                                <p class="font-mono text-xs text-gray-600 break-all">{{ $notification->id }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-6 border-t border-gray-200">
                    @if($link)
                        <a href="{{ $link }}"
                           class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 flex items-center">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            مشاهده مورد مرتبط
                        </a>
                    @endif

                    <button type="button" id="toggle-btn"
                            onclick="toggleNotification('{{ $notification->id }}')"
                            data-read="{{ $notification->read_at ? 'true' : 'false' }}"
                            class="px-6 py-3 text-white rounded-lg transition duration-150 flex items-center {{ $notification->read_at ? 'bg-amber-600 hover:bg-amber-700' : 'bg-green-600 hover:bg-green-700' }}">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span id="toggle-btn-text">
                            {{ $notification->read_at ? 'علامت‌گذاری به عنوان خوانده‌نشده' : 'علامت‌گذاری به عنوان خوانده‌شده' }}
                        </span>
                    </button>

                    <button type="button"
                            onclick="deleteNotification('{{ $notification->id }}')"
                            class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-150 flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        حذف اعلان
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>

        function updateHeaderNotificationCount() {

            if (typeof window.refreshNotificationCount === 'function') {
                window.refreshNotificationCount();
            }
            try {
                localStorage.setItem('notification_updated', Date.now().toString());
            } catch (e) {
                console.warn('localStorage not available:', e);
            }
        }

        function toggleNotification(id) {
            const toggleBtn = document.getElementById('toggle-btn');
            const toggleBtnText = document.getElementById('toggle-btn-text');
            const statusBadge = document.getElementById('read-status-badge');
            const readTimeBox = document.getElementById('read-time-box');
            const readTimeText = document.getElementById('read-time-text');
            const isCurrentlyRead = toggleBtn.getAttribute('data-read') === 'true';

            fetch(`/admin/notifications/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (data.status === 'read') {
                            statusBadge.innerHTML = '<span class="bg-blue-400 text-white px-4 py-2 rounded-full text-sm font-medium">✓ خوانده شده</span>';
                            toggleBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                            toggleBtn.classList.add('bg-amber-600', 'hover:bg-amber-700');
                            toggleBtnText.textContent = 'علامت‌گذاری به عنوان خوانده‌نشده';
                            toggleBtn.setAttribute('data-read', 'true');

                            const now = new Date();
                            const timeString = now.toLocaleString('fa-IR');
                            readTimeText.textContent = timeString;
                            readTimeBox.classList.remove('hidden');

                        } else {
                            statusBadge.innerHTML = '<span class="bg-white text-blue-600 px-4 py-2 rounded-full text-sm font-medium">🔔 خوانده نشده</span>';
                            toggleBtn.classList.remove('bg-amber-600', 'hover:bg-amber-700');
                            toggleBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                            toggleBtnText.textContent = 'علامت‌گذاری به عنوان خوانده‌شده';
                            toggleBtn.setAttribute('data-read', 'false');

                            readTimeBox.classList.add('hidden');
                        }

                        updateHeaderNotificationCount();

                        Swal.fire({
                            icon: 'success',
                            title: 'انجام شد!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا!',
                        text: 'مشکلی در به‌روزرسانی وضعیت رخ داد.',
                        confirmButtonText: 'باشه'
                    });
                });
        }

        function deleteNotification(id) {
            Swal.fire({
                title: 'هشدار حذف',
                text: 'آیا از حذف این اعلان اطمینان دارید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/notifications/${id}/delete`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateHeaderNotificationCount();

                                Swal.fire({
                                    icon: 'success',
                                    title: 'حذف شد!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = '{{ route("admin.notifications.index") }}';
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطا!', 'مشکلی در حذف اعلان رخ داد.', 'error');
                        });
                }
            });
        }
    </script>
@endpush
