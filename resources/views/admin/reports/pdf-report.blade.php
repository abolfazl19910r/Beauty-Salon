<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'گزارش سالن زیبایی' }}</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url({{ storage_path('fonts/Vazirmatn-Regular.ttf') }});
            font-weight: normal;
        }
        @font-face {
            font-family: 'Vazir';
            src: url({{ storage_path('fonts/Vazirmatn-Bold.ttf') }});
            font-weight: bold;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
            line-height: 1.7;
            color: #1e293b;
            margin: 0;
            padding: 24px 32px;
            background: #fff;
        }

        /* ── Header ── */
        .header {
            border-bottom: 2px solid #334155;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .salon-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .report-meta {
            text-align: left;
            font-size: 11px;
            color: #64748b;
            line-height: 1.8;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #334155;
            margin-top: 4px;
        }
        .period-badge {
            display: inline-block;
            font-size: 11px;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 2px 8px;
            margin-top: 4px;
        }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        thead tr {
            background: #334155;
            color: #fff;
        }
        th {
            padding: 10px 12px;
            text-align: right;
            font-weight: bold;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:nth-child(odd)  { background: #fff; }
        td {
            padding: 9px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        tbody tr:last-child td { border-bottom: none; }

        /* ── Summary boxes ── */
        .summary-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .summary-box {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            background: #f8fafc;
        }
        .summary-box .label {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .summary-box .value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 32px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-top">
        <div>
            <div class="salon-name">سالن زیبایی راستا</div>
            <div class="report-title">{{ $title ?? 'گزارش مدیریتی' }}</div>
            <div class="period-badge">
                بازه زمانی:
                @if(isset($period))
                    {{ jalali_date(date('Y-m-d', strtotime($period['start'])), 'Y/m/d') }}
                    تا
                    {{ jalali_date(date('Y-m-d', strtotime($period['end'])), 'Y/m/d') }}
                @else
                    همه زمان‌ها
                @endif
            </div>
        </div>
        <div class="report-meta">
            <div>تاریخ تهیه: {{ jalali_date(now(), 'Y/m/d') }}</div>
            <div>ساعت: {{ now()->format('H:i') }}</div>
        </div>
    </div>
</div>

<div class="content">
    @yield('report_content')
</div>

<div class="footer">
    <span>سیستم مدیریت سالن زیبایی راستا</span>
    <span>© {{ jalali_date(now(), 'Y') }}</span>
</div>

</body>
</html>
