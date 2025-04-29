<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش {{ $type }}</title>
    <style>
        body {
            font-family: 'vazir', sans-serif;
            padding: 20px;
            color: #333;
            line-height: 1.6;
            direction: rtl;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 12px;
            text-align: right;
        }
        th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #4a5568;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #db2777;
            margin-bottom: 10px;
        }
        .date {
            text-align: left;
            margin-bottom: 20px;
            color: #4b5563;
        }
        .page-number {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>گزارش {{ $type }}</h1>
</div>

<div class="date">
    تاریخ: {{ jalali_date(now(), 'Y/m/d') }}
    @if(isset($period) && isset($period['start']) && isset($period['end']))
        <div>
            بازه زمانی: {{ jalali_date(date('Y-m-d', strtotime($period['start'])), 'Y/m/d') }} تا {{ jalali_date(date('Y-m-d', strtotime($period['end'])), 'Y/m/d') }}
        </div>
    @endif
</div>

<table>
    <thead>
    <tr>
        @foreach($headings as $heading)
            <th>{{ $heading }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($data as $row)
        <tr>
            @foreach($row as $value)
                <td>{{ is_numeric($value) ? number_format($value) : $value }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>

<div class="page-number">صفحه {PAGENO}</div>
</body>
</html>
