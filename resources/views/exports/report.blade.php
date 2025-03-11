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
            color: #333;
            line-height: 1.6;
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
        tr:hover {
            background-color: #f1f5f9;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #db2777; /* pink-600 */
            margin-bottom: 10px;
        }
        .date {
            text-align: left;
            margin-bottom: 20px;
            color: #4b5563; /* gray-600 */
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #db2777; /* pink-600 */
        }
        .page-number {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280; /* gray-500 */
        }
        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f1f5f9;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280; /* gray-500 */
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>
<div class="logo">
    <div class="logo-text">سالن زیبایی</div>
</div>

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

<div class="page-number">صفحه 1</div>

<div class="footer">
    گزارش ایجاد شده توسط سیستم مدیریت سالن زیبایی - {{ verta()->format('Y') }}
</div>
</body>
</html>
