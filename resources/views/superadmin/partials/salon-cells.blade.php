{{-- سلول‌های مشترک ردیف سالن در داشبورد و فهرست سالن‌ها (بسته‌ی ۱ ظاهری). نیاز: $salon با specialists_count، plan و has_paid_invoice --}}
@php
    $status = \App\Support\SuperAdmin\SalonStatus::of($salon);
    $remaining = \App\Support\SuperAdmin\SalonStatus::remaining($salon->subscription_ends_at);
    $limit = $salon->specialistLimit();
    $used = (int) $salon->specialists_count;
    $usage = $limit > 0 ? min(100, (int) round(100 * $used / $limit)) : 0;
@endphp
@if (in_array('plan', $cells, true))
    <td class="py-3 px-3" data-cell-plan>
        @if ($salon->plan)
            <x-sa.pill tone="accent">{{ $salon->plan->name }}</x-sa.pill>
        @else
            <x-sa.pill tone="muted">بدون پلن</x-sa.pill>
        @endif
    </td>
@endif
@if (in_array('experts', $cells, true))
    <td class="py-3 px-3" data-cell-experts>
        {{ fa_num($used) }} از {{ fa_num($limit) }}
        @if (! empty($withBar))
            <div class="sa-meter" role="img" aria-label="{{ fa_num($used) }} از {{ fa_num($limit) }} کارشناس">
                <span style="width: {{ $usage }}%;" @class(['is-full' => $used >= $limit])></span>
            </div>
        @endif
    </td>
@endif
@if (in_array('subscription', $cells, true))
    <td class="py-3 px-3" data-cell-subscription @if ($salon->subscription_started_at) title="شروع اشتراک: {{ fa_date($salon->subscription_started_at) }}" @endif>
        @if ($salon->subscription_ends_at)
            تا {{ fa_date($salon->subscription_ends_at, false, false) }}
            @if ($remaining)
                <div class="text-xs mt-0.5" style="color: {{ $remaining['urgent'] ? 'var(--sa-warn)' : 'var(--sa-text-dim)' }}; {{ $remaining['urgent'] ? 'font-weight: 700;' : '' }}">{{ $remaining['text'] }}</div>
            @endif
        @else
            —
        @endif
    </td>
@endif
@if (in_array('status', $cells, true))
    <td class="py-3 px-3" data-cell-status="{{ $status }}">
        <x-sa.pill :tone="\App\Support\SuperAdmin\SalonStatus::tone($status)">{{ \App\Support\SuperAdmin\SalonStatus::label($status) }}@if ($status === 'trial') ({{ fa_num($salon->trialDaysLeft()) }} روز)@endif</x-sa.pill>
    </td>
@endif
