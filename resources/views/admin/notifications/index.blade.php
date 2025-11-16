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

                        <a href="{{ $link }}"
                           class="flex items-center justify-between p-4 transition duration-200
                                  {{ $notification->read_at ? 'bg-gray-50 hover:bg-gray-100' : 'bg-blue-50 hover:bg-blue-100 font-medium' }}"
                            {{ $notification->read_at ? '' : 'onclick="markAsReadAndNavigate(event, \''.$notification->id.'\', \''.$link.'\')"' }}>

                            <div class="flex-1">
                                <p class="text-sm {{ $notification->read_at ? 'text-gray-700' : 'text-blue-800' }}">
                                    {!! $message !!}
                                    @if (!$notification->read_at)
                                        <span class="mr-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-200 text-blue-800">
                                            جدید
                                        </span>
                                    @endif
                                </p>
                                <span class="text-xs text-gray-400 mt-1 block">زمان: {{ $time }}</span>
                            </div>

                            <svg class="h-5 w-5 {{ $notification->read_at ? 'text-gray-400' : 'text-blue-500' }} mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7l-3-3m0 0l-3 3m3-3v8" />
                            </svg>
                        </a>
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
    <script>
        function markAsReadAndNavigate(event, id, link) {
            event.preventDefault();

            fetch('{{ url("admin/notifications") }}/' + id + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(() => {
                    window.location.href = link;
                })
                .catch(error => {
                    console.error('Error marking as read:', error);
                    window.location.href = link;
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
