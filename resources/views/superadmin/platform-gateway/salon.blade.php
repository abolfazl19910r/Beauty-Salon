@extends('layouts.superadmin')

@section('title', 'حساب درگاه پلتفرم — '.$salon->name)

@php
    $toman = fn (int $rial) => fa_num(intdiv($rial, 10));
    $pct = fn ($v) => to_persian_num(rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.')).'٪';
    $txStatus = ['paid' => 'پرداخت‌شده', 'refunded' => 'به کیف پول مشتری', 'pending' => 'در جریان', 'failed' => 'ناموفق', 'cancelled' => 'لغو', 'expired' => 'منقضی', 'reversing' => 'در حال برگشت', 'reversed' => 'برگشت به کارت', 'reconciling' => 'در حال بررسی'];
@endphp

@section('content')
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <a href="{{ route('superadmin.platform-gateway.index') }}" class="sa-btn sa-btn-ghost">همه‌ی سالن‌ها</a>
        <a href="{{ route('superadmin.salons.edit', $salon) }}" class="sa-btn sa-btn-ghost">صفحه‌ی سالن</a>
    </div>

    <div class="sa-card sa-card-pad mb-6" data-platform-salon-summary>
        <div class="sa-card-head">
            <div class="sa-card-title">{{ $salon->name }} <span class="text-xs" dir="ltr" style="color: var(--sa-text-dim);">{{ $salon->slug }}</span></div>
            <div class="sa-card-desc">
                @if ($active)
                    درگاه پلتفرم فعال · کارمزد فعلی <b>{{ $pct($active->fee_percent) }}</b> · واریز به <span dir="ltr" class="select-all">{{ \App\Support\PayoutDestination::label($active->payout_method) }} {{ $active->destination() }}</span> ({{ $active->account_holder }}@if ($active->payout_bank)، {{ $active->payout_bank }}@endif)
                @else
                    درگاه پلتفرم فعال نیست@if ($latest) (آخرین درخواست: {{ $latest->statusLabel() }})@endif.
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-sm">
            <div><div style="color: var(--sa-text-dim);">کل دریافتی (تومان)</div><b data-sum-gross>{{ $toman($summary['gross']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">سهم پلتفرم</div><b data-sum-platform>{{ $toman($summary['platform']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">سهم سالن</div><b data-sum-salon>{{ $toman($summary['salon']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">واریزشده</div><b>{{ $toman($summary['settled']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">منتظر واریز</div><b>{{ $toman($summary['pending']) }}</b></div>
            <div><div style="color: var(--sa-text-dim);">تسویه‌نشده</div><b data-sum-outstanding>{{ $toman($summary['outstanding']) }}</b> ({{ to_persian_num((string) $summary['unsettled_count']) }} تراکنش)</div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <form method="POST" action="{{ route('superadmin.platform-gateway.settlements.store', $salon->id) }}" class="sa-card sa-card-pad space-y-3">
            @csrf
            <div class="sa-card-title">ساخت تسویه</div>
            <div class="text-xs leading-6" style="color: var(--sa-text-dim);">همه‌ی تراکنش‌های تسویه‌نشده (پرداخت‌شده یا برگشته به کیف پول مشتری) در یک تسویه‌ی «منتظر واریز» جمع می‌شوند.</div>
            <input name="note" class="sa-input" placeholder="یادداشت (اختیاری)">
            <button type="submit" class="sa-btn" @disabled($summary['outstanding'] <= 0) data-create-settlement>ساخت تسویه ({{ $toman($summary['outstanding']) }} تومان)</button>
        </form>
        @if ($active)
            <form method="POST" action="{{ route('superadmin.platform-gateway.fee', $salon->id) }}" class="sa-card sa-card-pad space-y-3">
                @csrf @method('PUT')
                <div class="sa-card-title">کارمزد پلتفرم</div>
                <input name="fee_percent" value="{{ old('fee_percent', rtrim(rtrim(number_format((float) $active->fee_percent, 2, '.', ''), '0'), '.')) }}" class="sa-input" dir="ltr" inputmode="decimal">
                @error('fee_percent')<div class="text-xs" style="color: var(--sa-bad, #c0392b);">{{ $message }}</div>@enderror
                <div class="text-xs" style="color: var(--sa-text-dim);">حداکثر {{ to_persian_num((string) $maxFee) }}٪؛ فقط پرداخت‌های بعدی.</div>
                <button type="submit" class="sa-btn sa-btn-ghost">ذخیره‌ی کارمزد</button>
            </form>
            <form method="POST" action="{{ route('superadmin.platform-gateway.revoke', $salon->id) }}" class="sa-card sa-card-pad space-y-3" data-ui-confirm="درگاه پلتفرم این سالن خاموش شود؟">
                @csrf
                <div class="sa-card-title">لغو درگاه پلتفرم</div>
                <textarea name="reason" rows="2" class="sa-input" placeholder="دلیل (برای مالک پیامک می‌شود)"></textarea>
                @error('reason')<div class="text-xs" style="color: var(--sa-bad, #c0392b);">{{ $message }}</div>@enderror
                <button type="submit" class="sa-btn sa-btn-ghost">لغو</button>
            </form>
        @endif
    </div>

    <div class="sa-card overflow-x-auto mb-6">
        <div class="sa-card-pad"><div class="sa-card-title">تسویه‌ها</div></div>
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">#</th><th class="py-3 px-4">بازه</th><th class="py-3 px-4">تراکنش</th><th class="py-3 px-4">کل</th><th class="py-3 px-4">سهم پلتفرم</th>
                <th class="py-3 px-4">سهم سالن</th><th class="py-3 px-4">مقصد</th><th class="py-3 px-4">وضعیت</th><th class="py-3 px-4"></th>
            </tr></thead>
            <tbody>
            @forelse ($settlements as $s)
                <tr style="border-top: 1px solid var(--sa-border);" data-settlement-row="{{ $s->id }}">
                    <td class="py-3 px-4">{{ to_persian_num((string) $s->id) }}@if ($s->automatic) <span class="text-xs" style="color: var(--sa-text-dim);">خودکار</span>@endif</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $s->period_from ? fa_date($s->period_from) : '' }} تا {{ $s->period_to ? fa_date($s->period_to) : '' }}</td>
                    <td class="py-3 px-4">{{ to_persian_num((string) $s->transactions_count) }}</td>
                    <td class="py-3 px-4">{{ $toman($s->gross_rial) }}</td>
                    <td class="py-3 px-4">{{ $toman($s->platform_share_rial) }}</td>
                    <td class="py-3 px-4"><b>{{ $toman($s->salon_share_rial) }}</b></td>
                    <td class="py-3 px-4" dir="ltr"><span class="select-all">{{ $s->payout_destination }}</span><br><span class="text-xs">{{ $s->account_holder }}</span></td>
                    <td class="py-3 px-4">{{ $s->statusLabel() }}@if ($s->bank_reference)<br><span class="text-xs" dir="ltr">{{ $s->bank_reference }}</span>@endif @if ($s->paid_at)<br><span class="text-xs">{{ fa_date($s->paid_at) }}</span>@endif</td>
                    <td class="py-3 px-4">
                        @if ($s->status === 'pending')
                            <form method="POST" action="{{ route('superadmin.platform-gateway.settlements.paid', $s) }}" class="flex gap-1 mb-1">
                                @csrf
                                <input name="bank_reference" class="sa-input" style="min-width: 9rem;" placeholder="پیگیری بانک" dir="ltr">
                                <button type="submit" class="sa-btn">واریز شد</button>
                            </form>
                            <form method="POST" action="{{ route('superadmin.platform-gateway.settlements.cancel', $s) }}" data-ui-confirm="تسویه لغو شود؟">
                                @csrf
                                <button type="submit" class="text-xs" style="color: var(--sa-text-dim);">لغو</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">هنوز تسویه‌ای نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
        @error('bank_reference')<div class="sa-card-pad text-xs" style="color: var(--sa-bad, #c0392b);">{{ $message }}</div>@enderror
    </div>

    <div class="flex gap-2 mb-3">
        <a href="{{ route('superadmin.platform-gateway.salon', ['salon' => $salon->id, 'tx' => 'all']) }}" class="sa-btn {{ $filter === 'all' ? '' : 'sa-btn-ghost' }}">همه‌ی تراکنش‌ها</a>
        <a href="{{ route('superadmin.platform-gateway.salon', ['salon' => $salon->id, 'tx' => 'unsettled']) }}" class="sa-btn {{ $filter === 'unsettled' ? '' : 'sa-btn-ghost' }}">تسویه‌نشده</a>
    </div>
    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">زمان</th><th class="py-3 px-4">نوع</th><th class="py-3 px-4">وضعیت</th><th class="py-3 px-4">کل (تومان)</th><th class="py-3 px-4">کارمزد</th>
                <th class="py-3 px-4">سهم پلتفرم</th><th class="py-3 px-4">سهم سالن</th><th class="py-3 px-4">پیگیری</th><th class="py-3 px-4">تسویه</th>
            </tr></thead>
            <tbody>
            @forelse ($transactions as $tx)
                @php($counts = in_array($tx->status, \App\Payments\PlatformGateway::SETTLEABLE_STATUSES, true))
                <tr style="border-top: 1px solid var(--sa-border); {{ $counts ? '' : 'opacity: .55;' }}" data-platform-tx="{{ $tx->id }}">
                    <td class="py-3 px-4 whitespace-nowrap">{{ fa_date($tx->created_at, true) }}</td>
                    <td class="py-3 px-4">{{ $tx->purpose }}</td>
                    <td class="py-3 px-4">{{ $txStatus[$tx->status] ?? $tx->status }}</td>
                    <td class="py-3 px-4">{{ $toman((int) $tx->amount_rial) }}</td>
                    <td class="py-3 px-4">{{ $tx->platform_fee_percent !== null ? $pct($tx->platform_fee_percent) : '—' }}</td>
                    <td class="py-3 px-4">{{ $counts ? $toman((int) $tx->platform_share_rial) : '—' }}</td>
                    <td class="py-3 px-4">{{ $counts ? $toman($tx->salonShareRial()) : '—' }}</td>
                    <td class="py-3 px-4" dir="ltr">{{ $tx->ref_id ?: '—' }}</td>
                    <td class="py-3 px-4">{{ $tx->platform_settlement_id ? '#'.to_persian_num((string) $tx->platform_settlement_id) : ($counts ? 'تسویه‌نشده' : '—') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">تراکنشی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
@endsection
