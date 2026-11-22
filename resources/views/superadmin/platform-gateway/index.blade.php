@extends('layouts.superadmin')

@section('title', 'درگاه پلتفرم سالن‌ها')

{{-- درگاه پرداخت پلتفرم برای سالن‌ها (۲۰۲۶-۱۱-۲۲) — App\Http\Controllers\SuperAdmin\PlatformGatewayController --}}
@php
    $toman = fn (int $rial) => to_persian_num(number_format(intdiv($rial, 10)));
    $pct = fn ($v) => to_persian_num(rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.')).'٪';
@endphp

@section('content')
    @unless ($enabled)
        <div class="sa-card sa-card-pad mb-4 text-sm leading-7" data-platform-gateway-disabled style="color: var(--sa-text);">
            <b>درگاه پلتفرم برای سالن‌ها خاموش است</b> (<span dir="ltr">PLATFORM_GATEWAY_ENABLED=false</span>): مالک‌ها فرم درخواست را نمی‌بینند و هیچ پرداخت تازه‌ای با
            درگاه پلتفرم شروع نمی‌شود. پیش از روشن کردن: متن «قوانین سالن‌ها» (بند ۸) و پاسخ زرین‌پال و اینماد درباره‌ی پرداخت برای سالن‌ها روی مرچنت پلتفرم.
        </div>
    @endunless
    @unless ($merchantReady)
        <div class="sa-card sa-card-pad mb-4 text-sm" style="color: var(--sa-bad, #c0392b);">مرچنت زرین‌پال پلتفرم (<span dir="ltr">ZARINPAL_MERCHANT_ID</span>) تنظیم نشده است؛ پرداخت با درگاه پلتفرم شروع نمی‌شود.</div>
    @endunless

    <div class="sa-card sa-card-pad mb-6" data-platform-gateway-totals>
        <div class="sa-card-head">
            <div class="sa-card-title">حساب همه‌ی سالن‌ها (تومان)</div>
            <div class="sa-card-desc">کل دریافتی از مشتری‌ها با درگاه پلتفرم = سهم پلتفرم (کارمزد) + سهم سالن‌ها. واریز بانکی سهم سالن بیرون از سیستم انجام و با شماره‌ی پیگیری ثبت می‌شود.</div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-sm">
            <div><div style="color: var(--sa-text-dim);">کل دریافتی</div><b>{{ $toman($totals['gross']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">سهم پلتفرم</div><b>{{ $toman($totals['platform']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">سهم سالن‌ها</div><b>{{ $toman($totals['salon']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">واریزشده</div><b>{{ $toman($totals['settled']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">منتظر واریز</div><b>{{ $toman($totals['pending']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">تسویه‌نشده</div><b>{{ $toman($totals['outstanding']) }}</b></div>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <form method="POST" action="{{ route('superadmin.platform-gateway.settle-all') }}" data-ui-confirm="برای هر سالنی که مانده‌ی تسویه‌نشده دارد یک تسویه‌ی «منتظر واریز» ساخته شود؟">
                @csrf
                <button type="submit" class="sa-btn" data-settle-all>ساخت تسویه برای همه‌ی سالن‌ها</button>
            </form>
            @if ($pendingSettlements > 0)
                <a href="{{ route('superadmin.platform-gateway.export') }}" class="sa-btn sa-btn-ghost" data-settlement-export>خروجی CSV واریز گروهی ({{ to_persian_num((string) $pendingSettlements) }} تسویه‌ی منتظر)</a>
            @endif
        </div>
    </div>

    <div class="sa-card overflow-x-auto mb-6">
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">سالن</th><th class="py-3 px-4">کارمزد</th><th class="py-3 px-4">کل دریافتی</th><th class="py-3 px-4">سهم پلتفرم</th>
                <th class="py-3 px-4">سهم سالن</th><th class="py-3 px-4">واریزشده</th><th class="py-3 px-4">منتظر واریز</th><th class="py-3 px-4">تسویه‌نشده</th><th class="py-3 px-4"></th>
            </tr></thead>
            <tbody>
            @forelse ($salons as $salon)
                @php($sum = $summaries[$salon->id] ?? ['gross' => 0, 'platform' => 0, 'salon' => 0, 'settled' => 0, 'pending' => 0, 'outstanding' => 0])
                <tr style="border-top: 1px solid var(--sa-border);" data-platform-salon-row="{{ $salon->id }}">
                    <td class="py-3 px-4">{{ $salon->name }} <span class="text-xs" dir="ltr" style="color: var(--sa-text-dim);">{{ $salon->slug }}</span></td>
                    <td class="py-3 px-4">{{ isset($active[$salon->id]) ? $pct($active[$salon->id]) : 'غیرفعال' }}</td>
                    <td class="py-3 px-4">{{ $toman($sum['gross']) }}</td>
                    <td class="py-3 px-4">{{ $toman($sum['platform']) }}</td>
                    <td class="py-3 px-4">{{ $toman($sum['salon']) }}</td>
                    <td class="py-3 px-4">{{ $toman($sum['settled']) }}</td>
                    <td class="py-3 px-4">{{ $toman($sum['pending']) }}</td>
                    <td class="py-3 px-4"><b>{{ $toman($sum['outstanding']) }}</b></td>
                    <td class="py-3 px-4"><a href="{{ route('superadmin.platform-gateway.salon', $salon->id) }}" style="color: var(--sa-accent);">حساب و تسویه</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">هنوز سالنی درگاه پلتفرم ندارد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="font-bold mb-3" style="color: var(--sa-text);">درخواست‌ها</h2>
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach (\App\Models\PlatformGatewayRequest::STATUSES as $key => $label)
            <a href="{{ route('superadmin.platform-gateway.index', ['status' => $key]) }}" class="sa-btn {{ $status === $key ? '' : 'sa-btn-ghost' }}" @if ($status === $key) aria-current="page" @endif>
                {{ $label }} ({{ to_persian_num((string) ($counts[$key] ?? 0)) }})
            </a>
        @endforeach
    </div>
    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">سالن</th><th class="py-3 px-4">مقصد واریز</th><th class="py-3 px-4">صاحب حساب</th><th class="py-3 px-4">ارسال</th><th class="py-3 px-4"></th>
            </tr></thead>
            <tbody>
            @forelse ($requests as $r)
                <tr style="border-top: 1px solid var(--sa-border);" data-platform-request-row>
                    <td class="py-3 px-4">{{ $r->salon?->name }} <span class="text-xs" dir="ltr" style="color: var(--sa-text-dim);">{{ $r->salon?->slug }}</span></td>
                    <td class="py-3 px-4" dir="ltr">{{ $r->maskedDestination() }}</td>
                    <td class="py-3 px-4">{{ $r->account_holder }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon($r->created_at)->format('Y/m/d H:i') }}</td>
                    <td class="py-3 px-4"><a href="{{ route('superadmin.platform-gateway.requests.show', $r) }}" style="color: var(--sa-accent);">بررسی</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
