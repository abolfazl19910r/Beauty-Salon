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
            font-family: 'vazir';
            src: url('{{ storage_path("fonts/Vazirmatn-Regular.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'vazir', sans-serif;
            direction: rtl;
            text-align: right;
            unicode-bidi: bidi-override;
            font-size: 10pt;
            color: #374151;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .report-header {
            background-color: #db2777;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .report-header h1 {
            margin: 0;
            font-size: 18pt;
        }
        .report-info {
            font-size: 10pt;
            margin-top: 5px;
            opacity: 0.9;
        }
        .stats-container {
            width: 100%;
            margin-bottom: 30px;
            clear: both;
        }
        .stat-card {
            float: right;
            width: 21%;
            margin-left: 2%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-right: 4px solid #db2777;
            padding: 10px;
            text-align: center;
            border-radius: 8px;
        }
        .stat-label {
            font-size: 8pt;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 12pt;
            font-weight: bold;
            color: #111827;
        }

        .table-container {
            clear: both;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th {
            background-color: #fce7f3;
            color: #9d174d;
            padding: 12px 8px;
            text-align: center;
            border-bottom: 2px solid #f9a8d4;
            font-weight: bold;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            text-align: center;
            color: #4b5563;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 8pt;
        }
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .status-pending { background-color: #fef9c3; color: #854d0e; }
        .footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .ltr {
            direction: ltr !important;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="report-header">
    <h1>گزارش عملکرد تخصصی</h1>
    <div class="report-info">
        نام متخصص: {{ $specialist->name }} | بازه گزارش: {{ $startDate }} تا {{ $endDate }}
    </div>
</div>

<div class="stats-container">
    <div class="stat-card" style="border-right-color: #10b981;">
        <div class="stat-label">درآمد حاصله</div>
        <div class="stat-value">{{ number_format($totalRevenue) }} <small style="font-size: 7pt">تومان</small></div>
    </div>
    <div class="stat-card" style="border-right-color: #3b82f6;">
        <div class="stat-label">کل نوبت‌ها</div>
        <div class="stat-value">{{ $totalBookings }}</div>
    </div>
    <div class="stat-card" style="border-right-color: #059669;">
        <div class="stat-label">انجام شده</div>
        <div class="stat-value">{{ $completedBookings }}</div>
    </div>
    <div class="stat-card" style="border-right-color: #ef4444; margin-left: 0;">
        <div class="stat-label">لغو شده</div>
        <div class="stat-value">{{ $cancelledBookings }}</div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
        <tr>
            <th style="width: 5%">ردیف</th>
            <th style="width: 20%">نام مشتری</th>
            <th style="width: 20%">خدمت</th>
            <th style="width: 20%">تاریخ و ساعت</th>
            <th style="width: 20%">بیعانه (تومان)</th>
            <th style="width: 15%">وضعیت</th>
        </tr>
        </thead>
        <tbody>
        @foreach($bookings as $index => $booking)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $booking->user->name }}</td>
                <td>{{ $booking->service->name ?? '-' }}</td>
                <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d H:i') }}</td>
                <td>{{ number_format($booking->prepayment_amount) }}</td>
                <td>
                        <span class="badge status-{{ $booking->status }}">
                            @if($booking->status == 'completed') انجام شده
                            @elseif($booking->status == 'cancelled') لغو شده
                            @elseif($booking->status == 'confirmed') تایید شده
                            @else در انتظار @endif
                        </span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    این گزارش توسط سیستم مدیریت آرایشگاه به صورت خودکار در تاریخ {{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i') }} صادر شده است.
</div>

</body>
</html>
