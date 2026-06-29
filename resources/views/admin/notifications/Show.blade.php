@extends('layouts.admin')
@section('title', 'جزئیات اعلان')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                جزئیات اعلان
            </h1>
            <a href="{{ route('admin.notifications.index') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        @php
            $data       = (array)$notification->data;
            $message    = $data['message'] ?? 'پیام اعلان موجود نیست';
            $type       = $data['type']    ?? 'عمومی';
            $link       = $data['link']    ?? null;
            $details    = $data['details'] ?? null;
            $isRead     = !is_null($notification->read_at);
            $createdTime = function_exists('verta') ? verta($notification->created_at)->format('Y/m/d - H:i:s') : $notification->created_at->format('Y/m/d - H:i:s');
            $readTime    = $notification->read_at ? (function_exists('verta') ? verta($notification->read_at)->format('Y/m/d - H:i:s') : $notification->read_at->format('Y/m/d - H:i:s')) : null;
        @endphp

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">

            {{-- Top bar --}}
            <div class="px-6 py-4 flex items-center justify-between" style="background:var(--admin-accent)">
                <span class="text-white font-semibold">اعلان سیستم</span>
                <span id="read-status-badge" class="px-3 py-1 rounded-full text-xs font-medium"
                      style="{{ $isRead ? 'background:rgba(255,255,255,.2);color:#fff' : 'background:#fff;color:var(--admin-accent)' }}">
                {{ $isRead ? '✓ خوانده شده' : '🔔 خوانده نشده' }}
            </span>
            </div>

            <div class="p-6 space-y-6">

                {{-- Message text --}}
                <div>
                    <h3 class="text-sm font-medium mb-2" style="color:var(--admin-text-dim)">پیام اعلان</h3>
                    <div class="p-4 rounded-lg text-sm leading-relaxed" style="background:var(--admin-bg);border-right:3px solid var(--admin-accent);color:var(--admin-text)">
                        {!! nl2br(e($message)) !!}
                    </div>
                </div>

                @if($details)
                    <div>
                        <h3 class="text-sm font-medium mb-2" style="color:var(--admin-text-dim)">جزئیات بیشتر</h3>
                        <div class="p-4 rounded-lg text-sm" style="background:var(--admin-accent-light);border:1px solid var(--admin-border);color:var(--admin-text)">
                            {{ $details }}
                        </div>
                    </div>
                @endif

                {{-- Metadata --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $meta = [
                            ['نوع اعلان', $type],
                            ['زمان دریافت', $createdTime],
                        ];
                        if ($readTime) $meta[] = ['زمان مشاهده', $readTime];
                    @endphp
                    @foreach($meta as [$label, $val])
                        <div class="p-4 rounded-lg" style="background:var(--admin-bg);border:1px solid var(--admin-border)">
                            <p class="text-xs mb-1" style="color:var(--admin-text-dim)">{{ $label }}</p>
                            <p class="text-sm font-medium" style="color:var(--admin-text)">{{ $val }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Buttons --}}
                <div class="flex flex-wrap gap-3 pt-4" style="border-top:1px solid var(--admin-border)">
                    @if($link)
                        <a href="{{ $link }}"
                           class="inline-flex items-center gap-2 px-5 py-2 text-sm text-white rounded-lg"
                           style="background:var(--admin-accent)">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            مشاهده مورد مرتبط
                        </a>
                    @endif

                    <button type="button" id="toggle-btn"
                            onclick="toggleNotification('{{ $notification->id }}')"
                            data-read="{{ $isRead ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm text-white rounded-lg"
                            style="background:{{ $isRead ? '#d97706' : '#16a34a' }}">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span id="toggle-btn-text">{{ $isRead ? 'علامت‌گذاری خوانده‌نشده' : 'علامت‌گذاری خوانده‌شده' }}</span>
                    </button>

                    <button type="button"
                            onclick="deleteNotification('{{ $notification->id }}')"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm text-white rounded-lg"
                            style="background:#dc2626">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        حذف اعلان
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function updateHeaderNotificationCount() {
            if (typeof window.refreshNotificationCount === 'function') window.refreshNotificationCount();
            try { localStorage.setItem('notification_updated', Date.now().toString()); } catch(e) {}
        }

        function toggleNotification(id) {
            const btn  = document.getElementById('toggle-btn');
            const text = document.getElementById('toggle-btn-text');
            const badge = document.getElementById('read-status-badge');
            fetch('/admin/notifications/' + id + '/toggle', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json', 'Content-Type': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    if (data.status === 'read') {
                        badge.textContent = '✓ خوانده شده';
                        badge.style.background = 'rgba(255,255,255,.2)'; badge.style.color = '#fff';
                        btn.style.background = '#d97706'; text.textContent = 'علامت‌گذاری خوانده‌نشده';
                        btn.setAttribute('data-read', 'true');
                    } else {
                        badge.textContent = '🔔 خوانده نشده';
                        badge.style.background = '#fff'; badge.style.color = 'var(--admin-accent)';
                        btn.style.background = '#16a34a'; text.textContent = 'علامت‌گذاری خوانده‌شده';
                        btn.setAttribute('data-read', 'false');
                    }
                    updateHeaderNotificationCount();
                    Swal.fire({ icon:'success', title:'انجام شد', text:data.message, timer:1500, showConfirmButton:false, toast:true, position:'top-end' });
                });
        }

        function deleteNotification(id) {
            Swal.fire({
                title:'حذف اعلان', text:'آیا مطمئن هستید؟', icon:'warning',
                showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#334155',
                confirmButtonText:'بله، حذف شود', cancelButtonText:'انصراف'
            }).then(r => {
                if (!r.isConfirmed) return;
                fetch('/admin/notifications/' + id + '/delete', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            updateHeaderNotificationCount();
                            Swal.fire({ icon:'success', title:'حذف شد!', timer:1200, showConfirmButton:false })
                                .then(() => { window.location.href = '{{ route("admin.notifications.index") }}'; });
                        }
                    });
            });
        }
    </script>
@endpush
