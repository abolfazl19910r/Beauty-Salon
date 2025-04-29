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

        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: right;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="title">{{ $title ?? 'گزارش سالن زیبایی' }}</div>
    <div class="subtitle">تاریخ: {{ jalali_date(now(), 'Y/m/d') }}</div>
    <div class="subtitle">بازه زمانی: {{ isset($period) ? (jalali_date(date('Y-m-d', strtotime($period['start'])), 'Y/m/d') . ' تا ' . jalali_date(date('Y-m-d', strtotime($period['end'])), 'Y/m/d')) : 'همه زمان‌ها' }}</div>
</div>

<div class="content">
    @yield('report_content')
</div>

<div class="footer">
    گزارش ایجاد شده توسط سیستم مدیریت سالن زیبایی - {{ jalali_date(now(), 'Y') }}
</div>
</body>
</html>
