@extends('layouts.superadmin')

@section('title', 'داشبورد')

@section('content')
    {{-- ⭐ ۲۰۲۶-۱۰-۱۵: هشدارها و درآمد ماه (PlatformOverview) بالای عددهای قبلی --}}
    @php
        $alerts = array_filter([
            ['n' => $overview['payments_attention'], 'label' => 'پرداخت پلتفرم نیازمند بررسی', 'url' => route('superadmin.payment-attention.index'), 'bad' => true],
            ['n' => $overview['failed_jobs'], 'label' => 'کار ناموفق در صف', 'url' => route('superadmin.failed-jobs.index'), 'bad' => true],
            ['n' => $overview['open_tickets'], 'label' => 'تیکت باز', 'url' => route('superadmin.support-tickets.index'), 'bad' => false],
            ['n' => $overview['open_demo_requests'], 'label' => 'درخواست دموی پیگیری‌نشده', 'url' => route('superadmin.demo-requests.index'), 'bad' => false],
        ], fn ($a) => $a['n'] > 0);
        $backupLabel = match ($backup['state']) {
            'ok' => 'آخرین پشتیبان موفق',
            'stale' => 'آخرین پشتیبان قدیمی است',
            'failed' => 'آخرین پشتیبان‌گیری ناموفق بود',
            default => 'هیچ پشتیبانی ثبت نشده',
        };
        if ($backup['state'] !== 'ok') {
            $alerts[] = ['n' => '!', 'label' => $backupLabel, 'url' => '#backup-status', 'bad' => true];
        }
    @endphp
    @if ($alerts)
        <div class="sa-card p-4 mb-6 flex flex-wrap gap-3 items-center" data-dashboard-alerts>
            <span class="text-sm font-bold">نیاز به رسیدگی:</span>
            @foreach ($alerts as $alert)
                <a href="{{ $alert['url'] }}" class="text-sm px-3 py-1 rounded-full" style="background: {{ $alert['bad'] ? 'var(--sa-danger-bg)' : 'var(--sa-soft)' }}; color: {{ $alert['bad'] ? 'var(--sa-danger)' : 'var(--sa-accent)' }};">
                    {{ $alert['n'] === '!' ? '' : to_persian_num((string) $alert['n']) }} {{ $alert['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- بسته‌ی «ح»، ۲: هشت کارت در یک شبکه‌ی یک‌دست (پیش‌تر ردیف دوم روی موبایل تک‌ستونه می‌شد و ارتفاع کارت‌ها برابر نبود) --}}
    @php
        $statCards = [
            ['درآمد اشتراک این ماه', number_format($overview['subscription_revenue_month']), 'تومان', 'var(--sa-accent)'],
            ['درآمد بسته‌ی پیامک این ماه', number_format($overview['sms_revenue_month']), 'تومان', 'var(--sa-accent)'],
            ['سالن تازه‌ی این ماه', number_format($overview['new_salons_month']), 'در دوره‌ی آزمایشی: '.$overview['trial_salons'], null],
            ['پیامک این ماه (همه‌ی سالن‌ها)', number_format($overview['sms_parts_month']), 'قطعه + '.number_format($overview['otp_month']).' کد تأیید', null],
            ['سالن‌های فعال', number_format($stats['active_salons']), null, null],
            ['مجموع متخصصین', number_format($stats['total_specialists']), null, null],
            ['نزدیک به انقضا (۷ روز)', number_format($stats['expiring_soon']), null, 'var(--sa-accent)'],
            ['منقضی‌شده', number_format($stats['expired']), null, 'var(--sa-danger)'],
        ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6" data-dashboard-stats>
        @foreach ($statCards as [$label, $value, $unit, $color])
            <div class="sa-card sa-stat">
                <div class="sa-stat__label">{{ $label }}</div>
                <div class="sa-stat__value" @if ($color) style="color: {{ $color }};" @endif>{{ $value }}</div>
                @if ($unit)<div class="sa-stat__unit">{{ $unit }}</div>@endif
            </div>
        @endforeach
    </div>

    {{-- ⭐ وضعیت آخرین پشتیبان‌گیری (deploy/backup/mahru-backup.sh → storage/app/backup-status.json) --}}
    <div class="sa-card p-5 mb-6 flex flex-wrap items-center gap-4" id="backup-status" data-backup-state="{{ $backup['state'] }}">
        <span class="inline-flex w-10 h-10 items-center justify-center rounded-full shrink-0"
              style="background: {{ $backup['state'] === 'ok' ? 'var(--sa-success-bg)' : 'var(--sa-danger-bg)' }}; color: {{ $backup['state'] === 'ok' ? 'var(--sa-success)' : 'var(--sa-danger)' }};">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
        </span>
        <div class="flex-1 min-w-0">
            <div class="font-bold">{{ $backupLabel }}</div>
            <div class="text-sm" style="color: var(--sa-text-dim);">
                @if ($backup['at'])
                    {{ \Morilog\Jalali\Jalalian::fromCarbon($backup['at'])->format('Y/m/d H:i') }} ({{ $backup['at']->diffForHumans() }})
                    @if ($backup['size']) · حجم {{ $backup['size'] }} @endif
                    @if ($backup['state'] === 'failed' && $backup['message']) · {{ $backup['message'] }} @endif
                @else
                    اسکریپت <span class="sa-ltr">deploy/backup/mahru-backup.sh</span> هنوز روی سرور اجرا نشده یا cron آن تنظیم نیست (راهنما: <span class="sa-ltr">docs/deployment/BACKUP.md</span>).
                @endif
            </div>
        </div>
    </div>

    <div class="sa-card p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">سالن‌های اخیر</h2>
            <a href="{{ route('superadmin.salons.index') }}" class="text-sm" style="color: var(--sa-accent);">مشاهده همه ←</a>
        </div>
        <div class="sa-table-wrap">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim);" class="border-b" style="border-color: var(--sa-border);">
                    <th class="py-2">نام سالن</th>
                    <th class="py-2">آدرس</th>
                    <th class="py-2">متخصص</th>
                    <th class="py-2">از تاریخ</th>
                    <th class="py-2">اشتراک تا</th>
                    <th class="py-2">وضعیت</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentSalons as $salon)
                    <tr class="border-b" style="border-color: var(--sa-border);">
                        <td class="py-3">{{ $salon->name }}</td>
                        <td class="py-3" style="color: var(--sa-text-dim);"><a href="{{ $salon->publicUrl() }}" target="_blank" rel="noopener" class="sa-ltr" style="color: var(--sa-accent);">{{ \App\Support\SalonAddress::short($salon) }}</a></td>
                        <td class="py-3">{{ $salon->specialists_count }} / {{ $salon->max_specialists_count }}</td>
                        <td class="py-3">{{ $salon->subscription_started_at ? jalali_date($salon->subscription_started_at) : '—' }}</td>
                        <td class="py-3">{{ jalali_date($salon->subscription_ends_at) }}</td>
                        <td class="py-3">
                            @if ($salon->is_suspended)
                                <span style="color: var(--sa-danger);">تعلیق‌شده</span>
                            @elseif ($salon->subscription_ends_at->isPast())
                                <span style="color: var(--sa-danger);">منقضی</span>
                            @else
                                <span style="color: var(--sa-success);">فعال</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endsection
