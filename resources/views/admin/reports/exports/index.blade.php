@extends('layouts.admin')
@section('title', 'گزارش‌های من')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
                گزارش‌های درخواست‌شده
            </h1>
            <a href="{{ route('admin.reports.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت به گزارشات
            </a>
        </div>

        <p class="text-xs mb-4" style="color:var(--admin-text-dim);">
            هر درخواست خروجی PDF/Excel در پس‌زمینه پردازش می‌شود؛ این صفحه را دوباره بارگذاری کنید تا وضعیت به‌روز را ببینید.
        </p>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <table class="w-full text-sm">
                <thead>
                <tr style="background:var(--admin-accent-light);">
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">نوع گزارش</th>
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">فرمت</th>
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">بازه</th>
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">درخواست‌دهنده</th>
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">تاریخ درخواست</th>
                    <th class="text-right px-4 py-3 font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($exports as $export)
                    @php
                        $badgeColors = [
                            'blue' => ['#EFF6FF', '#1D4ED8'],
                            'green' => ['#F0FDF4', '#166534'],
                            'red' => ['#FEF2F2', '#991B1B'],
                            'gray' => ['#F1F5F9', '#475569'],
                        ];
                        [$bg, $fg] = $badgeColors[$export->status_badge_color] ?? $badgeColors['gray'];
                    @endphp
                    <tr style="border-top:1px solid var(--admin-border);">
                        <td class="px-4 py-3" style="color:var(--admin-text);">{{ $export->report_type_text }}</td>
                        <td class="px-4 py-3 uppercase" style="color:var(--admin-text-dim);">{{ $export->format }}</td>
                        <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                            {{ $export->filters['start_date'] ?? '—' }} تا {{ $export->filters['end_date'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3" style="color:var(--admin-text-dim);">{{ $export->adminUser?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" style="background:{{ $bg }}; color:{{ $fg }};">
                                {{ $export->status_text }}
                            </span>
                            @if($export->status === 'failed' && $export->error_message)
                                <p class="text-xs mt-1" style="color:var(--admin-text-light);">{{ \Illuminate\Support\Str::limit($export->error_message, 60) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                            {{ verta($export->created_at)->format('Y/m/d H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($export->isDownloadable())
                                <a href="{{ route('admin.reports.exports.download', $export) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-white"
                                   style="background:var(--admin-accent);">
                                    دانلود
                                </a>
                            @else
                                <span class="text-xs" style="color:var(--admin-text-light);">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm" style="color:var(--admin-text-dim);">
                            هنوز هیچ خروجی گزارشی درخواست نشده است.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $exports->withQueryString()->links() }}
        </div>
    </div>
@endsection
