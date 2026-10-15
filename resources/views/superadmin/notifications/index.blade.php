@extends('layouts.superadmin')

@section('title', 'اعلان‌ها')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('superadmin.notifications.index') }}" style="{{ ! $filters['kind'] && ! $filters['unread'] ? 'color: var(--sa-accent); font-weight: 700;' : 'color: var(--sa-text-dim);' }}">همه</a>
            <a href="{{ route('superadmin.notifications.index', ['unread' => 1]) }}" style="{{ $filters['unread'] ? 'color: var(--sa-accent); font-weight: 700;' : 'color: var(--sa-text-dim);' }}">خوانده‌نشده ({{ to_persian_num((string) $unreadCount) }})</a>
            <span style="color: var(--sa-border);">|</span>
            @foreach ($kinds as $key => $label)
                <a href="{{ route('superadmin.notifications.index', ['kind' => $key]) }}" style="{{ $filters['kind'] === $key ? 'color: var(--sa-accent); font-weight: 700;' : 'color: var(--sa-text-dim);' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('superadmin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="sa-btn">همه خوانده شد</button>
            </form>
        @endif
    </div>

    <div class="sa-card">
        @forelse ($notifications as $n)
            <a href="{{ route('superadmin.notifications.open', $n->id) }}" class="flex items-start gap-3 px-5 py-4" data-inbox-item
               style="border-top: 1px solid var(--sa-border); {{ $n->read_at ? '' : 'background: var(--sa-soft);' }}">
                <span class="mt-2 shrink-0" style="width: 8px; height: 8px; border-radius: 50%; background: {{ $n->read_at ? 'transparent' : 'var(--sa-accent)' }};"></span>
                <span class="flex-1 min-w-0">
                    <span class="sa-badge" style="margin: 0;">{{ $kinds[$n->data['kind'] ?? ''] ?? 'اعلان' }}</span>
                    <span class="block mt-1 {{ $n->read_at ? '' : 'font-bold' }}" style="color: var(--sa-text);">{{ $n->data['message'] ?? '' }}</span>
                    <span class="block text-xs mt-1" style="color: var(--sa-text-dim);">{{ \Morilog\Jalali\Jalalian::fromCarbon($n->created_at)->format('Y/m/d H:i') }}</span>
                </span>
            </a>
        @empty
            <div class="p-8 text-center" style="color: var(--sa-text-dim);">اعلانی نیست.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
