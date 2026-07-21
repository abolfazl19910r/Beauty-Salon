@extends('layouts.specialist')

@section('title', 'اعلانات')

@section('content')
    <div class="fade-in max-w-5xl mx-auto space-y-6">

        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa flex items-center">
                <svg class="w-6 h-6 ml-2 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                اعلانات من
            </h1>

            @if($notifications->total() > 0)
                <form method="POST" action="{{ route('specialist.notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit"
                            class="text-sm text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] transition-colors">
                        علامت‌گذاری همه به عنوان خوانده شده
                    </button>
                </form>
            @endif
        </div>

        <div class="specialist-card overflow-hidden">
            @if($notifications->isEmpty())
                <div class="text-center py-16 text-[var(--specialist-inactive)]">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5"
                         viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <p>هیچ اعلانی وجود ندارد</p>
                </div>
            @else
                <div class="divide-y" style="border-color: var(--specialist-border);">
                    @foreach($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $text = $data['message'] ?? $data['description'] ?? 'اعلان جدید';

                            // ✅ Destination link
                            $targetLink = route('specialist.my-dashboard');
                            if (in_array($notification->type, [
                                'App\\Notifications\\Loyalty\\PointsEarned',
                                'App\\Notifications\\Loyalty\\PointsEarned',
                            ], true)) {
                                $targetLink = route('specialist.loyalty');
                            } elseif (!empty($data['booking_id'])) {
                                $targetLink = route('specialist.bookings.show', $data['booking_id']);
                            } elseif (!empty($data['leave_id'])) {
                                $targetLink = route('specialist.leaves');
                            }

                            // ✅ URL for mark as read
                            $readUrl = route('specialist.notifications.read', $notification->id);
                        @endphp
                        <div id="notification-row-{{ $notification->id }}"
                             class="flex items-start px-6 py-4 transition-colors hover:bg-white/5 {{ $notification->read_at ? '' : 'bg-[var(--specialist-plum-mid)]/5' }}">
                            <div class="flex-shrink-0 mt-2">
                                <span id="notification-dot-{{ $notification->id }}"
                                      class="inline-block w-2 h-2 rounded-full"
                                      style="background-color: {{ $notification->read_at ? 'var(--specialist-inactive)' : 'var(--specialist-plum-mid)' }};"></span>
                            </div>

                            {{-- ✅ FIX: Direct href to destination link --}}
                            <a href="{{ $targetLink }}"
                               data-notification-id="{{ $notification->id }}"
                               data-read-url="{{ $readUrl }}"
                               class="notification-link mr-3 flex-1 block">
                                <p id="notification-text-{{ $notification->id }}"
                                   class="text-sm {{ $notification->read_at ? 'text-[var(--specialist-text-dim)]' : 'text-[var(--specialist-text)] font-semibold' }}">
                                    {{ $text }}
                                </p>
                                <p class="text-xs text-[var(--specialist-plum-muted)] mt-1 persian-number">
                                    {{ \Morilog\Jalali\Jalalian::fromCarbon($notification->created_at)->format('Y/m/d H:i') }}
                                </p>
                            </a>

                            @if(!$notification->read_at)
                                <button type="button"
                                        id="notification-read-btn-{{ $notification->id }}"
                                        onclick="markNotificationAsReadOnPage('{{ $notification->id }}')"
                                        class="mr-2 text-xs text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] flex-shrink-0">
                                    خوانده شد
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="px-6 py-4 border-t" style="border-color: var(--specialist-border);">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            // ✅ Mark as read (and wait for it) before navigating to the target link
            document.querySelectorAll('.notification-link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    const notificationId = this.dataset.notificationId;
                    const readUrl = this.dataset.readUrl;
                    const targetHref = this.getAttribute('href');

                    fetch(readUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    })
                        .catch(function (err) {
                            console.error('خطا در علامت‌گذاری:', err);
                        })
                        .finally(function () {
                            window.location.href = targetHref;
                        });
                });
            });

            function markNotificationAsReadOnPage(id) {
                const url = '{{ route("specialist.notifications.read", ":id") }}'.replace(':id', id);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const dot = document.getElementById('notification-dot-' + id);
                            const text = document.getElementById('notification-text-' + id);
                            const row = document.getElementById('notification-row-' + id);
                            const btn = document.getElementById('notification-read-btn-' + id);

                            if (dot) dot.style.backgroundColor = 'var(--specialist-inactive)';
                            if (text) {
                                text.classList.remove('text-[var(--specialist-text)]', 'font-semibold');
                                text.classList.add('text-[var(--specialist-text-dim)]');
                            }
                            if (row) row.classList.remove('bg-[var(--specialist-plum-mid)]/5');
                            if (btn) btn.remove();

                            if (typeof fetchUnreadCount === 'function') {
                                fetchUnreadCount();
                            }
                        }
                    })
                    .catch(error => console.error('خطا در علامت‌گذاری اعلان:', error));
            }
        </script>
    @endpush
@endsection
