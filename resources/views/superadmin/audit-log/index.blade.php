@extends('layouts.superadmin')

@section('title', 'گزارش کارهای ابرمدیر')

@section('content')
    <form method="GET" class="sa-card p-4 mb-5 flex flex-wrap gap-3 items-end">
        <div>
            <label class="sa-label" for="f-action">نوع کار</label>
            <select id="f-action" name="action" class="sa-input" style="min-width: 14rem;">
                <option value="">همه</option>
                @foreach ($actions as $key => $label)
                    <option value="{{ $key }}" @selected($filters['action'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sa-label" for="f-salon">سالن</label>
            <select id="f-salon" name="salon" class="sa-input" style="min-width: 14rem;">
                <option value="">همه</option>
                @foreach ($salons as $id => $name)
                    <option value="{{ $id }}" @selected($filters['salon'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="sa-btn">نمایش</button>
    </form>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">زمان</th>
                <th class="py-3 px-4">ابرمدیر</th>
                <th class="py-3 px-4">کار</th>
                <th class="py-3 px-4">شرح</th>
                <th class="py-3 px-4">IP</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr style="border-top: 1px solid var(--sa-border);" data-audit-row>
                    <td class="py-3 px-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i') }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $log->user_name ?? '—' }}</td>
                    <td class="py-3 px-4 whitespace-nowrap"><span class="sa-badge">{{ $log->actionLabel() }}</span></td>
                    <td class="py-3 px-4">
                        <div>{{ $log->description }}</div>
                        @if ($log->salon)
                            <a href="{{ route('superadmin.salons.edit', $log->salon) }}" class="text-xs" style="color: var(--sa-accent);">{{ $log->salon->name }}</a>
                        @endif
                        @if ($log->changes)
                            <div class="mt-1 text-xs leading-6" style="color: var(--sa-text-dim);">
                                @foreach ($log->changes as $field => $pair)
                                    @php [$from, $to] = is_array($pair) && array_is_list($pair) && count($pair) === 2 ? $pair : [null, $pair]; @endphp
                                    <div><b style="color: var(--sa-text);">{{ $field }}</b>:
                                        <span dir="auto">{{ is_array($from) ? implode('، ', $from) : ($from ?? '—') }}</span> ←
                                        <span dir="auto">{{ is_array($to) ? implode('، ', $to) : ($to ?? '—') }}</span></div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-xs" dir="ltr" style="text-align: right; color: var(--sa-text-dim);">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 text-center" style="color: var(--sa-text-dim);">هنوز کاری ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
@endsection
