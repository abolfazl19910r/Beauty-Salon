@extends('layouts.superadmin')

@section('title', 'درخواست‌های دمو')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-lg">درخواست‌های دموی صفحه‌ی فروش</h2>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-5" style="max-width: 32rem;">
        <div class="sa-card">
            <p style="color: var(--sa-text-dim);">پیگیری‌نشده</p>
            <p class="text-xl font-bold mt-1" style="color: #D97706;">{{ to_persian_num((string) $stats['open']) }}</p>
        </div>
        <div class="sa-card">
            <p style="color: var(--sa-text-dim);">پیگیری‌شده</p>
            <p class="text-xl font-bold mt-1" style="color: var(--sa-success);">{{ to_persian_num((string) $stats['handled']) }}</p>
        </div>
    </div>

    <div class="flex gap-4 mb-4 text-sm">
        @foreach (['open' => 'پیگیری‌نشده', 'handled' => 'پیگیری‌شده', 'all' => 'همه'] as $key => $label)
            <a href="{{ route('superadmin.demo-requests.index', ['status' => $key]) }}"
               style="{{ $filter === $key ? 'color: var(--sa-accent); font-weight: 700;' : 'color: var(--sa-text-dim);' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="py-3 px-4">نام</th>
                    <th class="py-3 px-4">موبایل</th>
                    <th class="py-3 px-4">سالن</th>
                    <th class="py-3 px-4">نوع</th>
                    <th class="py-3 px-4">زمان</th>
                    <th class="py-3 px-4">پیگیری</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $demo)
                    <tr style="border-bottom: 1px solid var(--sa-border);" data-demo-request="{{ $demo->id }}">
                        <td class="py-3 px-4">{{ $demo->name }}</td>
                        <td class="py-3 px-4"><a href="tel:{{ $demo->phone }}" dir="ltr" style="color: var(--sa-accent);">{{ $demo->phone }}</a></td>
                        <td class="py-3 px-4">{{ $demo->salon_name ?: '—' }}</td>
                        <td class="py-3 px-4">{{ $demo->typeLabel() ?? '—' }}</td>
                        <td class="py-3 px-4" title="{{ $demo->updated_at }}">{{ \Morilog\Jalali\Jalalian::fromCarbon($demo->created_at)->format('Y/m/d H:i') }}</td>
                        <td class="py-3 px-4" style="min-width: 16rem;">
                            <form method="POST" action="{{ route('superadmin.demo-requests.update', $demo) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="handled" value="{{ $demo->handled_at ? 0 : 1 }}">
                                <input type="text" name="admin_note" value="{{ $demo->admin_note }}" placeholder="یادداشت (اختیاری)" maxlength="1000"
                                       class="rounded-lg px-2 py-1 flex-1" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text); min-width: 8rem;">
                                <button type="submit" class="text-sm" style="color: {{ $demo->handled_at ? 'var(--sa-text-dim)' : 'var(--sa-accent)' }};">
                                    {{ $demo->handled_at ? 'بازگرداندن' : 'پیگیری شد' }}
                                </button>
                            </form>
                            @if ($demo->handled_at)
                                <p class="mt-1" style="color: var(--sa-text-dim); font-size: .75rem;">{{ $demo->handler?->name ?? '—' }} — {{ \Morilog\Jalali\Jalalian::fromCarbon($demo->handled_at)->format('Y/m/d H:i') }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">درخواستی نیست.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
