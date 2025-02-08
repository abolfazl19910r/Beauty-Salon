{{-- resources/views/exports/report.blade.php --}}
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش {{ $type }}</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url({{ storage_path('fonts/Vazir.ttf') }}) format('truetype');
        }
        body {
            font-family: 'Vazir', sans-serif;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f5f5f5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .date {
            text-align: left;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>گزارش {{ $type }}</h1>
</div>

<div class="date">
    تاریخ: {{ verta()->format('Y/m/d') }}
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
</body>
</html>
