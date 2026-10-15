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
    @endphp
    @if ($alerts)
        <div class="sa-card p-4 mb-6 flex flex-wrap gap-3 items-center" data-dashboard-alerts>
            <span class="text-sm font-bold">نیاز به رسیدگی:</span>
            @foreach ($alerts as $alert)
                <a href="{{ $alert['url'] }}" class="text-sm px-3 py-1 rounded-full" style="background: {{ $alert['bad'] ? 'var(--sa-danger-bg)' : 'var(--sa-soft)' }}; color: {{ $alert['bad'] ? 'var(--sa-danger)' : 'var(--sa-accent)' }};">
                    {{ to_persian_num((string) $alert['n']) }} {{ $alert['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="sa-card p-5">
            <div class="sa-label">درآمد اشتراک این ماه</div>
            <div class="text-2xl font-bold" style="color: var(--sa-accent);">{{ number_format($overview['subscription_revenue_month']) }}</div>
            <div class="text-xs" style="color: var(--sa-text-dim);">تومان</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">درآمد بسته‌ی پیامک این ماه</div>
            <div class="text-2xl font-bold" style="color: var(--sa-accent);">{{ number_format($overview['sms_revenue_month']) }}</div>
            <div class="text-xs" style="color: var(--sa-text-dim);">تومان</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">سالن تازه این ماه / در دوره‌ی آزمایشی</div>
            <div class="text-2xl font-bold">{{ $overview['new_salons_month'] }} <span style="color: var(--sa-text-dim);">/</span> {{ $overview['trial_salons'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">پیامک این ماه (همه‌ی سالن‌ها)</div>
            <div class="text-2xl font-bold">{{ number_format($overview['sms_parts_month']) }}</div>
            <div class="text-xs" style="color: var(--sa-text-dim);">قطعه + {{ number_format($overview['otp_month']) }} کد تأیید</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="sa-card p-5">
            <div class="sa-label">سالن‌های فعال</div>
            <div class="text-2xl font-bold">{{ $stats['active_salons'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">مجموع متخصصین</div>
            <div class="text-2xl font-bold">{{ $stats['total_specialists'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">نزدیک به انقضا (۷ روز)</div>
            <div class="text-2xl font-bold" style="color: var(--sa-accent);">{{ $stats['expiring_soon'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">منقضی‌شده</div>
            <div class="text-2xl font-bold" style="color: var(--sa-danger);">{{ $stats['expired'] }}</div>
        </div>
    </div>

    <div class="sa-card p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">سالن‌های اخیر</h2>
            <a href="{{ route('superadmin.salons.index') }}" class="text-sm" style="color: var(--sa-accent);">مشاهده همه ←</a>
        </div>
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
                        <td class="py-3" style="color: var(--sa-text-dim);">/s/{{ $salon->slug }}</td>
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
@endsection
