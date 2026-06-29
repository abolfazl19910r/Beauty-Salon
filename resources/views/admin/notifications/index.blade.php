@extends('layouts.admin')
@section('title', 'مدیریت اعلانات')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                مدیریت اعلانات
            </h1>
            <div class="flex gap-2">
                <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                            style="background:var(--admin-accent)">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        خواندن همه
                    </button>
                </form>
                <form action="{{ route('admin.notifications.delete-all') }}" method="POST" id="delete-all-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" data-confirm-delete
                            data-confirm-message="آیا از حذف تمام اعلانات اطمینان دارید؟"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                            style="background:#dc2626">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        حذف همه
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg px-4 py-3 mb-4 text-sm" style="background:#f0fdf4;border:1px solid #86efac;color:#166534">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">

            @if($notifications->isEmpty())
                <div class="py-16 text-center" style="color:var(--admin-text-dim)">
                    <svg class="w-12 h-12 mx-auto mb-4" style="color:var(--admin-border)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <p>هیچ اعلانی وجود ندارد</p>
                </div>
            @else
                <div class="divide-y" style="border-color:var(--admin-border)">
                    @foreach($notifications as $notification)
                        @php
                            $data    = (array)$notification->data;
                            $link    = $data['link']    ?? '#';
                            $message = $data['message'] ?? 'اعلان ناشناس';
                            $isRead  = !is_null($notification->read_at);
                            $time    = function_exists('verta')
                                ? verta($notification->created_at)->format('Y/m/d H:i')
                                : $notification->created_at->format('Y/m/d H:i');
                        @endphp
                        <div id="notification-{{ $notification->id }}"
                             class="flex items-center gap-4 p-4 transition-colors"
                             style="{{ $isRead ? 'background:var(--admin-surface)' : 'background:var(--admin-accent-light)' }}">

                            {{-- Unread point --}}
                            <div class="w-2 h-2 rounded-full flex-shrink-0" style="{{ $isRead ? 'background:transparent' : 'background:var(--admin-accent)' }}"></div>

                            {{-- Text --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm message-text {{ $isRead ? '' : 'font-medium' }}" style="color:var(--admin-text)">
                                    {!! $message !!}
                                    @if(!$isRead)
                                        <span class="new-tag mr-2 px-2 py-0.5 text-xs rounded-full" style="background:var(--admin-accent);color:#fff">جدید</span>
                                    @endif
                                </p>
                                <p class="text-xs mt-1" style="color:var(--admin-text-light)">{{ $time }}</p>
                            </div>

                            {{-- Buttons --}}
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                   class="p-2 rounded-lg transition-colors" title="مشاهده"
                                   style="color:var(--admin-accent);background:var(--admin-accent-light)">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <button type="button"
                                        onclick="toggleReadStatus('{{ $notification->id }}', this)"
                                        class="toggle-btn p-2 rounded-lg transition-colors"
                                        title="{{ $isRead ? 'علامت‌گذاری خوانده‌نشده' : 'علامت‌گذاری خوانده‌شده' }}"
                                        style="{{ $isRead ? 'color:#d97706;background:#fffbeb' : 'color:#16a34a;background:#f0fdf4' }}">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </button>
                                <button type="button"
                                        onclick="deleteSingleNotification('{{ $notification->id }}')"
                                        class="p-2 rounded-lg transition-colors" title="حذف"
                                        style="color:#dc2626;background:#fef2f2">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4" style="border-top:1px solid var(--admin-border)">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function updateHeaderNotificationCount() {
            if (typeof window.refreshNotificationCount === 'function') window.refreshNotificationCount();
            try { localStorage.setItem('notification_updated', Date.now().toString()); } catch(e) {}
        }

        function toggleReadStatus(id, btn) {
            const item = document.getElementById('notification-' + id);
            const msg  = item ? item.querySelector('.message-text') : null;
            fetch('/admin/notifications/' + id + '/toggle', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const dot = item.querySelector('.w-2.h-2');
                    if (data.status === 'read') {
                        item.style.background = 'var(--admin-surface)';
                        if (dot) dot.style.background = 'transparent';
                        if (msg) { msg.classList.remove('font-medium'); const tag = item.querySelector('.new-tag'); if (tag) tag.remove(); }
                        btn.title = 'علامت‌گذاری خوانده‌نشده';
                        btn.style.color = '#d97706'; btn.style.background = '#fffbeb';
                    } else {
                        item.style.background = 'var(--admin-accent-light)';
                        if (dot) dot.style.background = 'var(--admin-accent)';
                        if (msg) msg.classList.add('font-medium');
                        btn.title = 'علامت‌گذاری خوانده‌شده';
                        btn.style.color = '#16a34a'; btn.style.background = '#f0fdf4';
                    }
                    updateHeaderNotificationCount();
                });
        }

        function deleteSingleNotification(id) {
            Swal.fire({
                title: 'حذف اعلان', text: 'آیا مطمئن هستید؟', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#334155',
                confirmButtonText: 'بله، حذف شود', cancelButtonText: 'انصراف'
            }).then(r => {
                if (!r.isConfirmed) return;
                fetch('/admin/notifications/' + id + '/delete', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const el = document.getElementById('notification-' + id);
                            if (el) el.remove();
                            updateHeaderNotificationCount();
                            Swal.fire({ icon:'success', title:'حذف شد!', text:data.message, timer:1500, showConfirmButton:false });
                        }
                    });
            });
        }
    </script>
@endpush
