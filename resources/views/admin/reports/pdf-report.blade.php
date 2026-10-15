<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>گزارش {{ $typeLabel ?? 'مدیریتی' }} — {{ $currentSalonName }}</title>
    @include('partials.pdf-report-style')
</head>
<body>
{{-- ⭐ ظاهر تازه (۲۰۲۶-۱۰-۱۵): همان داده‌ها و بخش‌های قبلی؛ رنگ‌ها و چیدمان از partials/pdf-report-style. --}}

<htmlpagefooter name="reportFooter">
    <table class="foot"><tr>
        <td>گزارش {{ $typeLabel ?? 'مدیریتی' }} · {{ $currentSalonName }} · ساخته‌شده با {{ config('brand.name') }}</td>
        <td style="text-align:left;">صفحه {PAGENO} از {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="reportFooter" value="on" />

<table class="masthead"><tr>
    <td>
        <div class="ms-kicker">گزارش مدیریتی سالن</div>
        <div class="ms-salon">{{ $currentSalonName }}</div>
    </td>
    <td class="ms-doc">
        <div class="ms-title">گزارش {{ $typeLabel ?? 'مدیریتی' }}</div>
        صادر شده {{ jalali_date(now(), 'Y/m/d') }} · ساعت {{ now()->format('H:i') }}
    </td>
</tr></table>
<div class="masthead-rule"></div>

@if(isset($period))
    <table class="meta"><tr>
        <td>بازه‌ی گزارش: <b>{{ jalali_date($period['start'], 'Y/m/d') }}</b> تا <b>{{ jalali_date($period['end'], 'Y/m/d') }}</b></td>
    </tr></table>
@endif

@php $s = $data['summary'] ?? []; @endphp
@if(!empty($s))
    <table class="kpis"><tr>
        <td class="lead">
            <div class="kpi-label">درآمد کل</div>
            <div class="kpi-value">{{ number_format($s['total_revenue'] ?? 0) }}</div>
            <div class="kpi-unit">تومان</div>
        </td>
        <td class="info">
            <div class="kpi-label">کل نوبت‌ها</div>
            <div class="kpi-value">{{ number_format($s['total_bookings'] ?? 0) }}</div>
            <div class="kpi-unit">نوبت</div>
        </td>
        <td class="good">
            <div class="kpi-label">انجام‌شده</div>
            <div class="kpi-value">{{ number_format($s['completed_bookings'] ?? 0) }}</div>
            <div class="kpi-unit">نوبت</div>
        </td>
        <td class="bad">
            <div class="kpi-label">لغو شده</div>
            <div class="kpi-value">{{ number_format($s['cancelled_bookings'] ?? 0) }}</div>
            <div class="kpi-unit">نوبت</div>
        </td>
    </tr></table>
@endif

{{-- نحوه‌ی پرداخت (کیف پول / درگاه / ثبت دستی) — همان داده‌ی admin/reports/index --}}
@php $pb = $data['paymentBreakdown'] ?? []; @endphp
@if(!empty($pb) && ($pb['total'] ?? 0) > 0)
    <div class="section">نحوه‌ی پرداخت نوبت‌های پرداخت‌شده</div>
    <table class="data">
        <thead><tr><th>روش</th><th class="num">تعداد نوبت</th><th class="num">سهم</th><th style="width:38%"></th></tr></thead>
        <tbody>
        @foreach ([['کیف پول', 'wallet'], ['درگاه بانکی', 'gateway'], ['ثبت دستی مدیر', 'admin_manual']] as $i => [$label, $key])
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="strong">{{ $label }}</td>
                <td class="num">{{ number_format($pb[$key] ?? 0) }}</td>
                <td class="num">{{ $pb[$key.'_percent'] ?? 0 }}٪</td>
                <td>@include('partials.pdf-bar', ['percent' => max(0, min(100, (float) ($pb[$key.'_percent'] ?? 0)))])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

@php $rows = $data['rows'] ?? collect(); @endphp
@if($rows->count())
    <div class="section">جزئیات درآمد</div>
    <table class="data">
        <thead>
        <tr>
            @if(isset($rows[0]['date']))<th>تاریخ</th>@endif
            @if($type !== 'daily')<th>دوره</th>@endif
            <th class="num">تعداد نوبت</th>
            <th class="num">درآمد (تومان)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr class="{{ $loop->odd ? '' : 'alt' }}">
                @if(isset($row['date']))<td>{{ jalali_date($row['date'], 'Y/m/d') }}</td>@endif
                @if($type !== 'daily')<td>{{ $row['label'] ?? '' }}</td>@endif
                <td class="num">{{ number_format($row['bookings'] ?? 0) }}</td>
                <td class="num strong">{{ number_format($row['revenue'] ?? 0) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            @if(isset($rows[0]['date']))<td>جمع</td>@endif
            @if($type !== 'daily')<td>{{ isset($rows[0]['date']) ? '' : 'جمع' }}</td>@endif
            <td class="num">{{ number_format($rows->sum(fn ($r) => $r['bookings'] ?? 0)) }}</td>
            <td class="num">{{ number_format($rows->sum(fn ($r) => $r['revenue'] ?? 0)) }}</td>
        </tr>
        </tbody>
    </table>
@endif

@php
    $specialists = ($data['specialists'] ?? collect())->filter(fn ($sp) => ($sp->total_bookings ?? 0) > 0)->values();
    $maxSpRevenue = max(1, (float) $specialists->max(fn ($sp) => $sp->total_revenue ?? 0));
@endphp
@if($specialists->count())
    <div class="section">عملکرد متخصص‌ها</div>
    <table class="data">
        <thead><tr><th>متخصص</th><th class="num">تعداد نوبت</th><th class="num">درآمد (تومان)</th><th style="width:30%"></th></tr></thead>
        <tbody>
        @foreach($specialists as $sp)
            <tr class="{{ $loop->odd ? '' : 'alt' }}">
                <td class="strong">{{ $sp->name }}</td>
                <td class="num">{{ number_format($sp->total_bookings ?? 0) }}</td>
                <td class="num">{{ number_format($sp->total_revenue ?? 0) }}</td>
                <td>@include('partials.pdf-bar', ['percent' => round(100 * ($sp->total_revenue ?? 0) / $maxSpRevenue)])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

@php
    $services = ($data['services'] ?? collect())->filter(fn ($svc) => ($svc->bookings_count ?? 0) > 0)->values();
    $maxSvcCount = max(1, (int) $services->max(fn ($svc) => $svc->bookings_count ?? 0));
@endphp
@if($services->count())
    <div class="section">خدمات پرطرفدار</div>
    <table class="data">
        <thead><tr><th>خدمت</th><th class="num">تعداد نوبت</th><th class="num">درآمد (تومان)</th><th style="width:30%"></th></tr></thead>
        <tbody>
        @foreach($services as $svc)
            <tr class="{{ $loop->odd ? '' : 'alt' }}">
                <td class="strong">{{ $svc->name }}</td>
                <td class="num">{{ number_format($svc->bookings_count ?? 0) }}</td>
                <td class="num">{{ number_format($svc->revenue ?? 0) }}</td>
                <td>@include('partials.pdf-bar', ['percent' => round(100 * ($svc->bookings_count ?? 0) / $maxSvcCount)])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

{{--
    پیوست: همه‌ی نوبت‌های بازه با هر وضعیت (برابر برگه‌ی «جزئیات خام نوبت‌ها»ی Excel) تا عددهای بالا با ردیف واقعی تطبیق داده شوند.
    افقی و فشرده چون ۲۰ ستون در A4 عمودی جا نمی‌شود.
--}}
@php $rawBookings = $data['rawBookings'] ?? collect(); @endphp
@if($rawBookings->count())
    <pagebreak orientation="L" />
    <div class="section">پیوست: جزئیات خام نوبت‌ها <span class="muted" style="font-weight:normal; font-size:8pt;">({{ number_format($rawBookings->count()) }} ردیف)</span></div>
    <table class="data compact">
        <thead>
        <tr>
            <th>تاریخ ثبت</th><th>ساعت ثبت</th><th>تاریخ نوبت</th><th>ساعت نوبت</th><th>مشتری</th><th>شماره تماس</th>
            <th>خدمت</th><th>متخصص</th><th>وضعیت نوبت</th><th>وضعیت پرداخت</th><th>روش پرداخت</th><th class="num">مبلغ (تومان)</th>
            <th class="num">سهم متخصص</th><th>کد تخفیف</th><th class="num">مبلغ تخفیف</th><th>کد پیگیری</th><th>امتیاز</th>
            <th>دلیل لغو</th><th>بازگشت وجه</th><th class="num">مبلغ بازگشتی</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rawBookings as $b)
            @php
                $b = (array) $b;
                $statusClass = match ($b['status'] ?? '') {
                    'در انتظار پرداخت', 'لغو شده', 'نیامد' => 'cell-bad',
                    'در انتظار' => 'cell-warn',
                    'تایید شده', 'انجام شده' => 'cell-good',
                    default => '',
                };
                $paymentStatusClass = match ($b['payment_status'] ?? '') {
                    'پرداخت‌نشده' => 'cell-bad',
                    'پرداخت‌شده' => 'cell-good',
                    default => '',
                };
            @endphp
            <tr class="{{ $loop->odd ? '' : 'alt' }}">
                <td>{{ $b['created_date'] ?? '' }}</td>
                <td>{{ $b['created_time'] ?? '' }}</td>
                <td>{{ $b['booking_date'] ?? '' }}</td>
                <td>{{ $b['booking_time'] ?? '' }}</td>
                <td>{{ $b['customer'] ?? '' }}</td>
                <td>{{ $b['customer_phone'] ?? '' }}</td>
                <td>{{ $b['service'] ?? '' }}</td>
                <td>{{ $b['specialist'] ?? '' }}</td>
                <td class="{{ $statusClass }}">{{ $b['status'] ?? '' }}</td>
                <td class="{{ $paymentStatusClass }}">{{ $b['payment_status'] ?? '' }}</td>
                <td>{{ $b['payment_method'] ?? '' }}</td>
                <td class="num">{{ number_format($b['amount'] ?? 0) }}</td>
                <td class="num">{{ $b['specialist_share'] !== null ? number_format($b['specialist_share']) : '—' }}</td>
                <td>{{ $b['discount_code'] ?? '' }}</td>
                <td class="num">{{ number_format($b['discount_amount'] ?? 0) }}</td>
                <td>{{ $b['payment_reference'] ?? '' }}</td>
                <td>{{ $b['rating'] ?? '' }}</td>
                <td>{{ $b['cancellation_reason'] ?? '' }}</td>
                <td>{{ $b['refund_status'] ?? '' }}</td>
                <td class="num">{{ $b['refunded_amount'] !== null ? number_format($b['refunded_amount']) : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
