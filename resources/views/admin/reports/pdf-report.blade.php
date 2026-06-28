<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>گزارش {{ $typeLabel ?? 'مدیریتی' }} — سالن زیبایی راستا</title>
    <style>
        @font-face { font-family:'vazir'; src:url({{ storage_path('fonts/Vazirmatn-Regular.ttf') }}); font-weight:normal; }
        @font-face { font-family:'vazir'; src:url({{ storage_path('fonts/Vazirmatn-Bold.ttf') }}); font-weight:bold; }
        *{ box-sizing:border-box; margin:0; padding:0; }
        body{ font-family:'vazir',sans-serif; direction:rtl; text-align:right; color:#1e293b; line-height:1.7; padding:20px 28px; background:#fff; }

        /* Letterhead */
        .lh{ border-bottom:2px solid #334155; padding-bottom:14px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-start; }
        .salon-name{ font-size:18px; font-weight:bold; color:#0f172a; margin-bottom:3px; }
        .report-title{ font-size:13px; color:#334155; font-weight:bold; }
        .meta{ text-align:left; font-size:11px; color:#64748b; line-height:1.9; }

        /* Period */
        .period{ background:#f1f5f9; border:1px solid #e2e8f0; border-radius:5px; padding:5px 12px; font-size:11px; color:#475569; display:inline-block; margin-bottom:16px; }

        /* Summary */
        .summary{ display:flex; gap:10px; margin-bottom:18px; }
        .scard{ flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:5px; padding:10px 14px; }
        .scard .sl{ font-size:9px; color:#64748b; margin-bottom:3px; }
        .scard .sv{ font-size:15px; font-weight:bold; color:#0f172a; }

        /* Section title */
        .section-title{ font-size:13px; font-weight:bold; color:#334155; margin:16px 0 8px; padding-bottom:4px; border-bottom:1px solid #e2e8f0; }

        /* Table */
        table{ width:100%; border-collapse:collapse; font-size:11px; margin-bottom:14px; }
        thead tr{ background:#334155; color:#fff; }
        th{ padding:8px 10px; text-align:right; font-weight:bold; }
        tbody tr:nth-child(even){ background:#f8fafc; }
        td{ padding:7px 10px; border-bottom:1px solid #e2e8f0; }
        tbody tr:last-child td{ border-bottom:none; }

        /* Footer */
        .footer{ border-top:1px solid #e2e8f0; padding-top:8px; margin-top:20px; display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; }
    </style>
</head>
<body>

{{-- Letterhead --}}
<div class="lh">
    <div>
        <div class="salon-name">سالن زیبایی راستا</div>
        <div class="report-title">گزارش {{ $typeLabel ?? 'مدیریتی' }}</div>
    </div>
    <div class="meta">
        <div>تاریخ تهیه: {{ jalali_date(now(),'Y/m/d') }}</div>
        <div>ساعت: {{ now()->format('H:i') }}</div>
    </div>
</div>

{{-- Period --}}
@if(isset($period))
    <div class="period">
        بازه زمانی:
        {{ jalali_date($period['start'],'Y/m/d') }}
        تا
        {{ jalali_date($period['end'],'Y/m/d') }}
    </div>
@endif

{{-- Summary --}}
@php $s = $data['summary'] ?? []; @endphp
@if(!empty($s))
    <div class="summary">
        <div class="scard"><div class="sl">درآمد کل</div><div class="sv">{{ number_format($s['total_revenue']??0) }} ت</div></div>
        <div class="scard"><div class="sl">کل نوبت</div><div class="sv">{{ number_format($s['total_bookings']??0) }}</div></div>
        <div class="scard"><div class="sl">انجام‌شده</div><div class="sv">{{ number_format($s['completed_bookings']??0) }}</div></div>
        <div class="scard"><div class="sl">لغو شده</div><div class="sv">{{ number_format($s['cancelled_bookings']??0) }}</div></div>
    </div>
@endif

{{-- Income table (rows) --}}
@php $rows = $data['rows'] ?? collect(); @endphp
@if($rows->count())
    <div class="section-title">جزئیات درآمد</div>
    <table>
        <thead>
        <tr>
            @if(isset($rows[0]['date']))<th>تاریخ</th>@endif
            @if(isset($rows[0]['week_start']))<th>از</th><th>تا</th>@endif
            @if(isset($rows[0]['month']))<th>ماه</th>@endif
            <th>تعداد نوبت</th>
            <th>درآمد (تومان)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                @if(isset($row['date']))<td>{{ jalali_date($row['date'],'Y/m/d') }}</td>@endif
                @if(isset($row['week_start']))<td>{{ jalali_date($row['week_start'],'Y/m/d') }}</td><td>—</td>@endif
                @if(isset($row['month']))<td>{{ ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'][($row['month']-1)] }}</td>@endif
                <td>{{ number_format($row['total_bookings']??0) }}</td>
                <td>{{ number_format($row['revenue']??0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

{{-- Experts table --}}
@php $specialists = $data['specialists'] ?? collect(); @endphp
@if($specialists->count())
    <div class="section-title">عملکرد متخصصین</div>
    <table>
        <thead><tr><th>نام</th><th>نوبت</th><th>درآمد (تومان)</th><th>نرخ تکمیل</th></tr></thead>
        <tbody>
        @foreach($specialists as $sp)
            <tr>
                <td>{{ $sp->name }}</td>
                <td>{{ number_format($sp->total_bookings??0) }}</td>
                <td>{{ number_format($sp->total_revenue??0) }}</td>
                <td>{{ $sp->booking_completion_rate??0 }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

{{-- Service table --}}
@php $services = $data['services'] ?? collect(); @endphp
@if($services->count())
    <div class="section-title">خدمات پرطرفدار</div>
    <table>
        <thead><tr><th>خدمت</th><th>نوبت</th><th>درآمد (تومان)</th></tr></thead>
        <tbody>
        @foreach($services as $svc)
            <tr>
                <td>{{ $svc->name }}</td>
                <td>{{ number_format($svc->bookings_count??0) }}</td>
                <td>{{ number_format($svc->revenue??0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

{{-- Footer --}}
<div class="footer">
    <span>سیستم مدیریت سالن زیبایی راستا</span>
    <span>صفحه {PAGENO}</span>
</div>

</body>
</html>
