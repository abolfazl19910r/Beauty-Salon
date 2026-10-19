@extends('layouts.superadmin')

@section('title', 'پیامک سالن‌ها')

@section('content')
    {{-- ⭐ ۲۰۲۶-۱۰-۱۵: مصرف پیامک همه‌ی سالن‌ها (قبلاً فقط داخل ویرایش هر سالن) و خرید بسته‌ها/درآمدش (قبلاً هیچ‌جا). --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="sa-card sa-stat">
            <div class="sa-stat__label">درآمد بسته‌ی پیامک این ماه</div>
            <div class="sa-stat__value" style="color: var(--sa-accent);">{{ number_format($totals['revenue_month']) }} <span class="text-xs font-normal" style="color: var(--sa-text-dim);">تومان</span></div>
        </div>
        <div class="sa-card sa-stat">
            <div class="sa-stat__label">درآمد بسته‌ی پیامک تا امروز</div>
            <div class="sa-stat__value">{{ number_format($totals['revenue_all']) }} <span class="text-xs font-normal" style="color: var(--sa-text-dim);">تومان</span></div>
        </div>
        <div class="sa-card sa-stat">
            <div class="sa-stat__label">قطعه‌ی فروخته‌شده</div>
            <div class="sa-stat__value">{{ number_format($totals['parts_sold']) }}</div>
        </div>
        <div class="sa-card sa-stat">
            <div class="sa-stat__label">قطعه‌ی اعطای دستی</div>
            <div class="sa-stat__value">{{ number_format($totals['parts_granted']) }}</div>
        </div>
    </div>

    <div class="sa-card mb-6 overflow-x-auto">
        <div class="px-5 pt-5 pb-3 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-bold">مصرف این ماه</h2>
            <span class="text-xs" style="color: var(--sa-text-dim);">کدهای تأیید خرج پلتفرم‌اند و از سقف سالن کم نمی‌شوند.</span>
        </div>
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">سالن</th>
                <th class="py-3 px-4">پلن</th>
                <th class="py-3 px-4">مصرف از سقف</th>
                <th class="py-3 px-4" style="width: 22%;"></th>
                <th class="py-3 px-4">از اعتبار خریده</th>
                <th class="py-3 px-4">اعتبار باقی</th>
                <th class="py-3 px-4">کد تأیید</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($usage as $row)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-3 px-4"><a href="{{ route('superadmin.salons.edit', $row['salon']) }}" style="color: var(--sa-accent);">{{ $row['salon']->name }}</a></td>
                    <td class="py-3 px-4" style="color: var(--sa-text-dim);">{{ $row['salon']->plan?->name ?? '—' }}</td>
                    <td class="py-3 px-4" dir="ltr" style="text-align: right;">{{ number_format($row['used']) }} / {{ number_format($row['quota']) }}</td>
                    <td class="py-3 px-4">
                        <div style="height: 6px; border-radius: 99px; background: var(--sa-soft);">
                            <div data-sms-bar style="height: 6px; border-radius: 99px; width: {{ $row['percent'] }}%; background: {{ $row['percent'] >= 100 ? 'var(--sa-danger)' : ($row['percent'] >= 80 ? 'var(--sa-warn)' : 'var(--sa-accent)') }};"></div>
                        </div>
                    </td>
                    <td class="py-3 px-4">{{ number_format($row['credit_used']) }}</td>
                    <td class="py-3 px-4">{{ number_format($row['credit_left']) }}</td>
                    <td class="py-3 px-4" style="color: var(--sa-text-dim);">{{ number_format($row['otp']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center" style="color: var(--sa-text-dim);">سالنی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sa-card overflow-x-auto">
        <div class="px-5 pt-5 pb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-bold">خرید و اعطای بسته‌ی پیامک</h2>
            <div class="flex flex-wrap gap-3 text-sm">
                @foreach (['' => 'همه', 'online' => 'خرید آنلاین', 'grant' => 'اعطای دستی'] as $key => $label)
                    <a href="{{ route('superadmin.sms.index', array_filter(['source' => $key, 'status' => $filters['status']])) }}"
                       style="{{ ($filters['source'] ?? '') === $key ? 'color: var(--sa-accent); font-weight: 700;' : 'color: var(--sa-text-dim);' }}">{{ $label }}</a>
                @endforeach
                <span style="color: var(--sa-border);">|</span>
                @foreach (['' => 'هر وضعیت', 'paid' => 'پرداخت‌شده', 'pending' => 'در انتظار', 'failed' => 'ناموفق'] as $key => $label)
                    <a href="{{ route('superadmin.sms.index', array_filter(['source' => $filters['source'], 'status' => $key])) }}"
                       style="{{ ($filters['status'] ?? '') === $key ? 'color: var(--sa-accent); font-weight: 700;' : 'color: var(--sa-text-dim);' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">تاریخ</th>
                <th class="py-3 px-4">سالن</th>
                <th class="py-3 px-4">نوع</th>
                <th class="py-3 px-4">قطعه</th>
                <th class="py-3 px-4">مبلغ (تومان)</th>
                <th class="py-3 px-4">وضعیت</th>
                <th class="py-3 px-4">کد پیگیری / توضیح</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($purchases as $purchase)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-3 px-4">{{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($purchase->paid_at ?? $purchase->created_at))->format('Y/m/d H:i') }}</td>
                    <td class="py-3 px-4">{{ $purchase->salon?->name ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $purchase->source === 'grant' ? 'اعطای دستی'.($purchase->createdBy ? ' ('.$purchase->createdBy->name.')' : '') : 'خرید آنلاین' }}</td>
                    <td class="py-3 px-4">{{ number_format($purchase->parts) }}</td>
                    <td class="py-3 px-4">{{ $purchase->amount ? number_format($purchase->amount) : '—' }}</td>
                    <td class="py-3 px-4">
                        @php $statusColor = ['paid' => 'var(--sa-success)', 'pending' => 'var(--sa-warn)', 'failed' => 'var(--sa-danger)'][$purchase->status] ?? 'var(--sa-text-dim)'; @endphp
                        <span style="color: {{ $statusColor }};">{{ ['paid' => 'پرداخت‌شده', 'pending' => 'در انتظار', 'failed' => 'ناموفق'][$purchase->status] ?? $purchase->status }}</span>
                    </td>
                    <td class="py-3 px-4" style="color: var(--sa-text-dim);">{{ $purchase->ref_id ?: ($purchase->note ?: '—') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center" style="color: var(--sa-text-dim);">خریدی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $purchases->links() }}</div>
    </div>
@endsection
