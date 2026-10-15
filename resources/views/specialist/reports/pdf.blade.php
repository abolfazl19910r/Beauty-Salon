<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>گزارش عملکرد {{ $specialist->name }}</title>
    @include('partials.pdf-report-style')
</head>
<body>
{{-- ⭐ ظاهر تازه (۲۰۲۶-۱۰-۱۵): همان داده‌ها؛ رنگ‌ها و چیدمان از partials/pdf-report-style، فونت از App\Support\Pdf\ReportPdf. --}}
@php
    $salonName = $specialist->salon?->name ?? config('brand.name');
    $statusLabels = [
        'completed' => ['انجام شده', 'pill-good'],
        'confirmed' => ['تایید شده', 'pill-info'],
        'pending' => ['در انتظار', 'pill-warn'],
        'pending_payment' => ['در انتظار پرداخت', 'pill-warn'],
        'cancelled' => ['لغو شده', 'pill-bad'],
        'no_show' => ['نیامد', 'pill-bad'],
    ];
    $shareRate = 1 - ($commissionRate ?? 10) / 100;
@endphp

<htmlpagefooter name="reportFooter">
    <table class="foot"><tr>
        <td>این گزارش را سیستم نوبت‌دهی {{ $salonName }} به‌طور خودکار صادر کرده است.</td>
        <td style="text-align:left;">صفحه {PAGENO} از {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="reportFooter" value="on" />

<table class="masthead"><tr>
    <td>
        <div class="ms-kicker">گزارش عملکرد متخصص</div>
        <div class="ms-salon">{{ $specialist->name }}</div>
    </td>
    <td class="ms-doc">
        <div class="ms-title">{{ $salonName }}</div>
        صادر شده {{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i') }}
    </td>
</tr></table>
<div class="masthead-rule"></div>

<table class="meta"><tr>
    <td>بازه‌ی گزارش: <b>{{ $startDate }}</b> تا <b>{{ $endDate }}</b></td>
    <td style="text-align:left;">سهم متخصص: <b>{{ round($shareRate * 100) }}٪</b> از مبلغ پرداختی (کمیسیون سالن {{ $commissionRate ?? 10 }}٪)</td>
</tr></table>

<table class="kpis"><tr>
    <td class="lead">
        <div class="kpi-label">درآمد متخصص (پس از کمیسیون)</div>
        <div class="kpi-value">{{ number_format($totalRevenue) }}</div>
        <div class="kpi-unit">تومان</div>
    </td>
    <td class="info">
        <div class="kpi-label">کل نوبت‌ها</div>
        <div class="kpi-value">{{ number_format($totalBookings) }}</div>
        <div class="kpi-unit">نوبت</div>
    </td>
    <td class="good">
        <div class="kpi-label">انجام شده</div>
        <div class="kpi-value">{{ number_format($completedBookings) }}</div>
        <div class="kpi-unit">نوبت</div>
    </td>
    <td class="bad">
        <div class="kpi-label">لغو شده</div>
        <div class="kpi-value">{{ number_format($cancelledBookings) }}</div>
        <div class="kpi-unit">نوبت</div>
    </td>
</tr></table>

<div class="section">نوبت‌های بازه</div>
@if ($bookings->count())
    <table class="data">
        <thead>
        <tr>
            <th style="width:6%">ردیف</th>
            <th style="width:22%">مشتری</th>
            <th style="width:22%">خدمت</th>
            <th style="width:18%">تاریخ و ساعت</th>
            <th class="num" style="width:17%">درآمد متخصص (تومان)</th>
            <th style="width:15%">وضعیت</th>
        </tr>
        </thead>
        <tbody>
        @foreach($bookings as $booking)
            @php [$statusText, $statusPill] = $statusLabels[$booking->status] ?? [$booking->status, 'pill-mute']; @endphp
            <tr class="{{ $loop->odd ? '' : 'alt' }}">
                <td class="muted">{{ $loop->iteration }}</td>
                <td class="strong">{{ $booking->user?->name ?? '—' }}</td>
                <td>{{ $booking->service?->name ?? '—' }}</td>
                <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d H:i') }}</td>
                <td class="num">{{ number_format($booking->prepayment_amount * $shareRate) }}</td>
                <td><span class="pill {{ $statusPill }}">{{ $statusText }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="empty">در این بازه نوبتی ثبت نشده است.</div>
@endif

</body>
</html>
