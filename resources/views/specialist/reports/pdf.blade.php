<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
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
            unicode-bidi: bidi-override;
            font-size: 10pt;
            color: #2b2b2b;
            line-height: 1.7;
            margin: 0;
            padding: 0;
        }

        /* --- letterhead header --- */
        .report-header {
            border-bottom: 2px solid #2b2b2b;
            padding-bottom: 14px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .report-header .brand {
            float: right;
            font-family: 'vazir-bold', sans-serif;
            font-size: 16pt;
            color: #1a1a1a;
        }
        .report-header .doc-title {
            float: left;
            text-align: left;
            font-size: 9pt;
            color: #555555;
            margin-top: 4px;
        }
        .report-header .doc-title strong {
            display: block;
            font-size: 11pt;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .report-meta {
            clear: both;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            font-size: 9pt;
        }
        .report-meta td {
            padding: 4px 0;
            color: #444444;
        }
        .report-meta td.label {
            color: #888888;
            width: 18%;
        }

        /* --- summary stats --- */
        .stats-container {
            width: 100%;
            margin-bottom: 26px;
            clear: both;
            border-collapse: collapse;
        }
        .stats-container td {
            width: 25%;
            border: 1px solid #d9d9d9;
            padding: 12px 8px;
            text-align: center;
            vertical-align: top;
        }
        .stat-label {
            font-size: 8pt;
            color: #777777;
            margin-bottom: 6px;
        }
        .stat-value {
            font-family: 'vazir-bold', sans-serif;
            font-size: 13pt;
            color: #1a1a1a;
        }

        /* --- table --- */
        .table-container {
            clear: both;
            margin-top: 10px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
        }
        .data-table th {
            background-color: #f2f2f2;
            color: #1a1a1a;
            padding: 9px 6px;
            text-align: center;
            border: 1px solid #d9d9d9;
            font-family: 'vazir-bold', sans-serif;
            font-size: 9pt;
        }
        .data-table td {
            padding: 8px 6px;
            border: 1px solid #e6e6e6;
            text-align: center;
            color: #333333;
            font-size: 9pt;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border: 1px solid #999999;
            border-radius: 3px;
            font-size: 8pt;
            color: #333333;
        }

        .footer {
            position: fixed;
            bottom: 16px;
            width: 100%;
            text-align: center;
            font-size: 7.5pt;
            color: #999999;
            border-top: 1px solid #e6e6e6;
            padding-top: 8px;
        }
    </style>
</head>
<body>

<div class="report-header">
    <div class="brand">راستا — سالن زیبایی</div>
    <div class="doc-title">
        <strong>گزارش عملکرد متخصص</strong>
        صادر شده در: {{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i') }}
    </div>
</div>

<table class="report-meta">
    <tr>
        <td class="label">نام متخصص:</td>
        <td>{{ $specialist->name }}</td>
        <td class="label">بازه گزارش:</td>
        <td>{{ $startDate }} تا {{ $endDate }}</td>
    </tr>
</table>

<table class="stats-container">
    <tr>
        <td>
            <div class="stat-label">درآمد متخصص (پس از کسر کمیسیون)</div>
            <div class="stat-value">{{ number_format($totalRevenue) }} <span style="font-size: 8pt; font-family: 'vazir';">تومان</span></div>
        </td>
        <td>
            <div class="stat-label">کل نوبت‌ها</div>
            <div class="stat-value">{{ $totalBookings }}</div>
        </td>
        <td>
            <div class="stat-label">انجام شده</div>
            <div class="stat-value">{{ $completedBookings }}</div>
        </td>
        <td>
            <div class="stat-label">لغو شده</div>
            <div class="stat-value">{{ $cancelledBookings }}</div>
        </td>
    </tr>
</table>

<div class="table-container">
    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 5%">ردیف</th>
            <th style="width: 20%">نام مشتری</th>
            <th style="width: 20%">خدمت</th>
            <th style="width: 20%">تاریخ و ساعت</th>
            <th style="width: 20%">درآمد متخصص (تومان)</th>
            <th style="width: 15%">وضعیت</th>
        </tr>
        </thead>
        <tbody>
        @php
            $statusLabels = [
                'completed'       => 'انجام شده',
                'cancelled'       => 'لغو شده',
                'confirmed'       => 'تایید شده',
                'pending'         => 'در انتظار',
                'pending_payment' => 'در انتظار پرداخت',
            ];
        @endphp
        @foreach($bookings as $index => $booking)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $booking->user->name }}</td>
                <td>{{ $booking->service->name ?? '-' }}</td>
                <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d H:i') }}</td>
                <td>{{ number_format($booking->prepayment_amount * (1 - ($commissionRate ?? 10) / 100)) }}</td>
                <td><span class="badge">{{ $statusLabels[$booking->status] ?? $booking->status }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    این گزارش توسط سیستم مدیریت سالن زیبایی راستا به صورت خودکار صادر شده است.
</div>

</body>
</html>
