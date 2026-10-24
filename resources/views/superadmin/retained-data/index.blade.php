@extends('layouts.superadmin')

@section('title', 'داده‌های نگهداری‌شده')

@section('content')
    @php $jalali = fn ($at) => $at ? \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($at))->format('Y/m/d H:i') : '—'; @endphp

    <div class="sa-card p-4 mb-5 text-sm leading-7" style="color: var(--sa-text-dim);">
        فقط برای پاسخ به درخواست مرجع قضایی (ماده‌ی ۶۶۹ و ۶۷۰ آیین دادرسی کیفری). IP و user agent ورودها و کارهای کلیدی
        {{ to_persian_num((string) $retentionDays) }} روز، و هویت و IP حساب‌های حذف‌شده {{ to_persian_num((string) $archiveDays) }} روز پس از
        حذف نگه داشته و بعد خودکار پاک می‌شوند. هر جست‌وجو در «گزارش کارهای ابرمدیر» ثبت می‌شود.
        بایگانی‌های فعلی: {{ to_persian_num((string) $archiveCount) }}.
    </div>

    <form method="GET" class="sa-card p-4 mb-5 flex flex-wrap gap-3 items-end">
        <div>
            <label class="sa-label" for="f-phone">شماره‌ی موبایل</label>
            <input id="f-phone" name="phone" value="{{ $filters['phone'] }}" class="sa-input" dir="ltr" inputmode="numeric" style="min-width: 12rem;">
        </div>
        <div>
            <label class="sa-label" for="f-ip">IP</label>
            <input id="f-ip" name="ip" value="{{ $filters['ip'] }}" class="sa-input" dir="ltr" style="min-width: 12rem;">
        </div>
        <button type="submit" class="sa-btn">جست‌وجو</button>
        @error('ip')<div class="text-xs" style="color: var(--sa-bad);">{{ $message }}</div>@enderror
    </form>

    @if ($logs !== null)
        <h2 class="font-bold mb-3">حساب‌های حذف‌شده</h2>
        <div class="sa-card overflow-x-auto mb-6">
            <table class="w-full text-sm text-right">
                <thead><tr style="color: var(--sa-text-dim);">
                    <th class="py-3 px-4">حذف</th><th class="py-3 px-4">نام و شماره</th><th class="py-3 px-4">سالن</th>
                    <th class="py-3 px-4">IPها</th><th class="py-3 px-4">پاک‌سازی</th>
                </tr></thead>
                <tbody>
                @forelse ($archives as $archive)
                    <tr style="border-top: 1px solid var(--sa-border);" data-archive-row>
                        <td class="py-3 px-4 whitespace-nowrap">{{ $jalali($archive->deleted_at) }}<div class="text-xs" dir="ltr">{{ $archive->deletion_ip ?? '—' }}</div></td>
                        <td class="py-3 px-4">{{ $archive->name }}<div class="text-xs" dir="ltr">{{ $archive->phone }}</div></td>
                        <td class="py-3 px-4">{{ $archive->salon?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-xs leading-6" dir="ltr">
                            @foreach ($archive->ip_history ?? [] as $row)
                                <div>{{ $row['ip'] }} × {{ $row['count'] }} — {{ $jalali($row['first_seen']) }} … {{ $jalali($row['last_seen']) }}</div>
                            @endforeach
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">{{ $jalali($archive->purge_after) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">موردی نیست</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="font-bold mb-3">ورودها و کارهای کلیدی</h2>
        <div class="sa-card overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead><tr style="color: var(--sa-text-dim);">
                    <th class="py-3 px-4">زمان</th><th class="py-3 px-4">کار</th><th class="py-3 px-4">کاربر</th>
                    <th class="py-3 px-4">سالن</th><th class="py-3 px-4">IP و دستگاه</th>
                </tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr style="border-top: 1px solid var(--sa-border);">
                        <td class="py-3 px-4 whitespace-nowrap">{{ $jalali($log->created_at) }}</td>
                        <td class="py-3 px-4 whitespace-nowrap"><span class="sa-badge">{{ $log->event_label }}</span>
                            @if ($log->subject_type)<div class="text-xs" style="color: var(--sa-text-dim);">{{ $log->subject_type }} #{{ $log->subject_id }}</div>@endif</td>
                        <td class="py-3 px-4">{{ $log->user?->name ?? '—' }}<div class="text-xs" dir="ltr">{{ $log->user?->phone }}</div></td>
                        <td class="py-3 px-4">{{ $log->salon?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-xs" dir="ltr">{{ $log->ip_address }}<div style="color: var(--sa-text-dim);">{{ \Illuminate\Support\Str::limit((string) $log->user_agent, 80) }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">موردی نیست</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    @endif
@endsection
