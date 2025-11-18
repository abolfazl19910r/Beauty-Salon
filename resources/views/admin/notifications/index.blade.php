@extends('layouts.admin')

@section('title', 'مدیریت اعلانات')

@section('content')

    <div class="p-6">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">مدیریت اعلانات 🔔</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-4">

            <div class="flex justify-between items-center mb-4 border-b pb-4">
                <h2 class="text-xl font-medium text-gray-700">لیست کامل اعلانات</h2>
                <div class="flex space-x-2 space-x-reverse">

                    <form action="{{ route('admin.notifications.delete-all') }}" method="POST" id="delete-all-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-confirm-delete data-confirm-message="آیا از حذف **تمام** اعلانات اطمینان دارید؟ این عمل غیر قابل بازگشت است."
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-150 text-sm">
                            حذف همه
                        </button>
                    </form>

                    <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-150 text-sm">
                            خواندن همه
                        </button>
                    </form>
                </div>
            </div>

            @if ($notifications->isEmpty())
                <p class="text-gray-500 text-center py-10">هیچ اعلانی برای نمایش وجود ندارد.</p>
            @else
                <div class="divide-y divide-gray-200">
                    @foreach ($notifications as $notification)
                        @php
                            $data = (array) $notification->data;
                            $link = $data['link'] ?? '#';
                            $message = $data['message'] ?? 'اعلان ناشناس (اطلاعات نامعتبر)';

                            $time = function_exists('verta')
                                ? verta($notification->created_at)->format('Y/m/d H:i')
                                : $notification->created_at->format('Y/m/d H:i');
                        @endphp

                        <div id="notification-{{ $notification->id }}" class="notification-item flex items-center p-4 transition duration-200
                                  {{ $notification->read_at ? 'bg-gray-50 hover:bg-gray-100' : 'bg-blue-50 hover:bg-blue-100 font-medium' }}">

                            <div class="flex-1">
                                <p class="message-text text-sm {{ $notification->read_at ? 'text-gray-700' : 'text-blue-800' }}">
                                    {!! $message !!}
                                    @if (!$notification->read_at)
                                        <span class="new-tag mr-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-200 text-blue-800">
                                            جدید
                                        </span>
                                    @endif
                                </p>
                                <span class="text-xs text-gray-400 mt-1 block">زمان: {{ $time }}</span>
                            </div>

                            <div class="flex space-x-2 space-x-reverse ml-4 flex-shrink-0">

                                <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                   title="مشاهده جزئیات"
                                   class="p-2 text-indigo-600 hover:text-white bg-indigo-100 hover:bg-indigo-600 rounded-full transition duration-150">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>

                                <button type="button"
                                        onclick="toggleReadStatus('{{ $notification->id }}', this)"
                                        title="{{ $notification->read_at ? 'علامت‌گذاری به عنوان خوانده‌نشده' : 'علامت‌گذاری به عنوان خوانده‌شده' }}"
                                        class="toggle-btn p-2 {{ $notification->read_at ? 'text-amber-600 hover:text-white bg-amber-100 hover:bg-amber-600' : 'text-green-600 hover:text-white bg-green-100 hover:bg-green-600' }} rounded-full transition duration-150">

                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>

                                <button type="button"
                                        onclick="deleteSingleNotification('{{ $notification->id }}')"
                                        title="حذف این اعلان"
                                        class="p-2 text-red-600 hover:text-white bg-red-100 hover:bg-red-600 rounded-full transition duration-150">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            @endif
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

        function toggleReadStatus(id, buttonElement) {
            const toggleRoute = `/admin/notifications/${id}/toggle`;
            const itemElement = document.getElementById('notification-' + id);
            const messageText = itemElement ? itemElement.querySelector('.message-text') : null;

            fetch(toggleRoute, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
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
                            itemElement.classList.remove('bg-blue-50', 'hover:bg-blue-100', 'font-medium');
                            itemElement.classList.add('bg-gray-50', 'hover:bg-gray-100');
                            if (messageText) {
                                messageText.classList.remove('text-blue-800');
                                messageText.classList.add('text-gray-700');
                                const newTag = itemElement.querySelector('.new-tag');
                                if (newTag) newTag.remove();
                            }
                            buttonElement.title = 'علامت‌گذاری به عنوان خوانده‌نشده';
                            buttonElement.classList.remove('text-green-600', 'bg-green-100', 'hover:bg-green-600');
                            buttonElement.classList.add('text-amber-600', 'bg-amber-100', 'hover:bg-amber-600');
                        } else {
                            itemElement.classList.remove('bg-gray-50', 'hover:bg-gray-100');
                            itemElement.classList.add('bg-blue-50', 'hover:bg-blue-100', 'font-medium');
                            if (messageText) {
                                messageText.classList.remove('text-gray-700');
                                messageText.classList.add('text-blue-800');
                                if (!itemElement.querySelector('.new-tag')) {
                                    const newTagHtml = '<span class="new-tag mr-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-200 text-blue-800">جدید</span>';
                                    messageText.innerHTML = messageText.innerHTML.trim() + newTagHtml;
                                }
                            }
                            buttonElement.title = 'علامت‌گذاری به عنوان خوانده‌شده';
                            buttonElement.classList.remove('text-amber-600', 'bg-amber-100', 'hover:bg-amber-600');
                            buttonElement.classList.add('text-green-600', 'bg-green-100', 'hover:bg-green-600');
                        }

                        updateHeaderNotificationCount();
                    }
                })
                .catch(error => {
                    console.error('Error toggling read status:', error);
                    Swal.fire('خطا!', 'مشکلی در به‌روزرسانی وضعیت رخ داد.', 'error');
                });
        }

        function deleteSingleNotification(id) {
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
                    const deleteRoute = `/admin/notifications/${id}/delete`;

                    fetch(deleteRoute, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const notificationItem = document.getElementById('notification-' + id);
                                if (notificationItem) {
                                    notificationItem.remove();
                                }

                                updateHeaderNotificationCount();

                                Swal.fire('حذف شد!', data.message, 'success');
                            } else {
                                Swal.fire('خطا!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting notification:', error);
                            Swal.fire('خطا!', 'مشکلی در حذف اعلان رخ داد.', 'error');
                        });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const deleteAllButton = document.querySelector('#delete-all-form button');
            if (deleteAllButton) {
                deleteAllButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    const message = deleteAllButton.getAttribute('data-confirm-message') || 'آیا از حذف این آیتم اطمینان دارید؟';

                    Swal.fire({
                        title: 'هشدار',
                        html: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'بله، حذف شود',
                        cancelButtonText: 'انصراف'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('delete-all-form').submit();
                        }
                    });
                });
            }
        });
    </script>
@endpush
