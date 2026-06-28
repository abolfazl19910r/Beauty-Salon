<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>گزارش {{ $typeLabel ?? 'مدیریتی' }} — سالن زیبایی راستا</title>
    <style>
        @font-face {
            font-family: 'vazir';
            src: url('{{ storage_path("fonts/Vazirmatn-Regular.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'vazir-bold';
            src: url('{{ storage_path("fonts/Vazirmatn-Bold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        body {
            font-family: 'vazir', sans-serif;
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.8;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ── Letterhead ── */
        .report-header {
            border-bottom: 2px solid #334155;
            padding-bottom: 12px;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .report-header .brand {
            float: right;
            font-family: 'vazir-bold', sans-serif;
            font-size: 15pt;
            color: #0f172a;
            direction: rtl;
        }
        .report-header .doc-title {
            float: left;
            text-align: left;
            font-size: 9pt;
            color: #64748b;
            margin-top: 4px;
            direction: rtl;
        }
        .report-header .doc-title strong {
            display: block;
            font-size: 11pt;
            color: #334155;
            font-family: 'vazir-bold', sans-serif;
            margin-bottom: 3px;
        }

        /* ── Period ── */
        .period-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 9pt;
        }
        .period-table td {
            padding: 6px 12px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            direction: rtl;
        }

        /* ── Summary ── */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .stats-table td {
            width: 25%;
            border: 1px solid #e2e8f0;
            padding: 10px 8px;
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            direction: rtl;
        }
        .stat-label {
            font-size: 8pt;
            color: #64748b;
            display: block;
            margin-bottom: 4px;
        }
        .stat-value {
            font-family: 'vazir-bold', sans-serif;
            font-size: 13pt;
            color: #0f172a;
            display: block;
        }
        .stat-unit {
            font-size: 7.5pt;
            color: #94a3b8;
            display: block;
            margin-top: 1px;
        }

        /* ── Section title ── */
        .section-title {
            font-family: 'vazir-bold', sans-serif;
            font-size: 11pt;
            color: #334155;
            margin: 16px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            direction: rtl;
        }

        /* ── Data table ── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            direction: rtl;
        }
        table.data-table thead tr {
            background: #334155;
            color: #ffffff;
        }
        table.data-table th {
            padding: 8px 10px;
            text-align: right;
            font-family: 'vazir-bold', sans-serif;
            font-size: 9pt;
            border: 1px solid #2d3f55;
            color: #fff;
            direction: rtl;
        }
        table.data-table tbody tr:nth-child(even) { background: #f8fafc; }
        table.data-table tbody tr:nth-child(odd)  { background: #ffffff; }
        table.data-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 9.5pt;
            text-align: right;
            direction: rtl;
        }

        /* ── Footer ── */
        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            overflow: hidden;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .footer .f-right { float: right; direction: rtl; }
        .footer .f-left  { float: left; }
    </style>
</head>
<body>

{{-- Letterhead --}}
<div class="report-header">
    <div class="brand">سالن زیبایی راستا</div>
    <div class="doc-title">
        <strong>گزارش {{ $typeLabel ?? 'مدیریتی' }}</strong>
        تاریخ: {{ jalali_date(now(), 'Y/m/d') }} &nbsp; ساعت: {{ now()->format('H:i') }}
    </div>
</div>

{{-- Period --}}
@if(isset($period))
    <table class="period-table">
        <tr>
            <td>
                بازه زمانی: &nbsp;
                <strong>{{ jalali_date($period['start'], 'Y/m/d') }}</strong>
                &nbsp; تا &nbsp;
                <strong>{{ jalali_date($period['end'], 'Y/m/d') }}</strong>
            </td>
        </tr>
    </table>
@endif

{{-- Summary --}}
@php $s = $data['summary'] ?? []; @endphp
@if(!empty($s))
    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-label">درآمد کل</span>
                <span class="stat-value">{{ number_format($s['total_revenue'] ?? 0) }}</span>
                <span class="stat-unit">تومان</span>
            </td>
            <td>
                <span class="stat-label">کل نوبت‌ها</span>
                <span class="stat-value">{{ number_format($s['total_bookings'] ?? 0) }}</span>
            </td>
            <td>
                <span class="stat-label">انجام‌شده</span>
                <span class="stat-value">{{ number_format($s['completed_bookings'] ?? 0) }}</span>
            </td>
            <td>
                <span class="stat-label">لغو شده</span>
                <span class="stat-value">{{ number_format($s['cancelled_bookings'] ?? 0) }}</span>
            </td>
        </tr>
    </table>
@endif

{{-- Income table --}}
@php $rows = $data['rows'] ?? collect(); @endphp
@if($rows->count())
    <div class="section-title">جزئیات درآمد</div>
    <table class="data-table">
        <thead>
        <tr>
            @if(isset($rows[0]['date']))<th>تاریخ</th>@endif
            @if(isset($rows[0]['week_start']))<th>هفته از</th>@endif
            @if(isset($rows[0]['month']))<th>ماه</th>@endif
            <th>تعداد نوبت</th>
            <th>درآمد (تومان)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $jm = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        @endphp
        @foreach($rows as $row)
            <tr>
                @if(isset($row['date']))
                    <td>{{ jalali_date($row['date'], 'Y/m/d') }}</td>
                @endif
                @if(isset($row['week_start']))
                    <td>{{ jalali_date($row['week_start'], 'Y/m/d') }}</td>
                @endif
                @if(isset($row['month']))
                    <td>{{ $jm[($row['month'] - 1)] ?? '' }}</td>
                @endif
                <td>{{ number_format($row['total_bookings'] ?? 0) }}</td>
                <td>{{ number_format($row['revenue'] ?? 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

{{-- Experts table --}}
@php $specialists = $data['specialists'] ?? collect(); $hasSpecialists = $specialists->filter(fn($sp) => ($sp->total_bookings ?? 0) > 0)->count() > 0; @endphp
@if($hasSpecialists)
    <div class="section-title">عملکرد متخصصین</div>
    <table class="data-table">
        <thead>
        <tr>
            <th>نام متخصص</th>
            <th>تعداد نوبت</th>
            <th>درآمد (تومان)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($specialists as $sp)
            @if(($sp->total_bookings ?? 0) > 0)
                <tr>
                    <td>{{ $sp->name }}</td>
                    <td>{{ number_format($sp->total_bookings ?? 0) }}</td>
                    <td>{{ number_format($sp->total_revenue ?? 0) }}</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
@endif

{{-- Service table --}}
@php $services = $data['services'] ?? collect(); $hasServices = $services->filter(fn($svc) => ($svc->bookings_count ?? 0) > 0)->count() > 0; @endphp
@if($hasServices)
    <div class="section-title">خدمات پرطرفدار</div>
    <table class="data-table">
        <thead>
        <tr>
            <th>نام خدمت</th>
            <th>تعداد نوبت</th>
            <th>درآمد (تومان)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($services as $svc)
            @if(($svc->bookings_count ?? 0) > 0)
                <tr>
                    <td>{{ $svc->name }}</td>
                    <td>{{ number_format($svc->bookings_count ?? 0) }}</td>
                    <td>{{ number_format($svc->revenue ?? 0) }}</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
@endif

{{-- Footer --}}
<div class="footer">
    <div class="f-right">سیستم مدیریت سالن زیبایی راستا</div>
    <div class="f-left">صفحه {PAGENO} از {nbpg}</div>
</div>

</body>
</html>
