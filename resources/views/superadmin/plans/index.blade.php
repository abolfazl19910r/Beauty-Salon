@extends('layouts.superadmin')

@section('title', 'پلن‌ها و امکانات')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="font-bold text-lg">پلن‌ها</h2>
            <p class="text-sm mt-1" style="color: var(--sa-text-dim);">
                امکانات هر پلن را تیک بزنید و قیمت هر بازه را بگذارید. پلن غیرفعال به سالن تازه فروخته نمی‌شود ولی سالن‌های فعلی‌اش تمدیدش می‌کنند.
                فقط پلن‌های «عمومی» در صفحه‌ی فروش و صفحه‌ی صورتحساب سالن‌ها دیده می‌شوند.
            </p>
        </div>
        <a href="{{ route('superadmin.plans.create') }}" class="sa-btn whitespace-nowrap">+ پلن جدید</a>
    </div>

    @if ($noPlan > 0)
        <div class="sa-card p-4 mb-5 text-sm" style="border-color: var(--sa-accent);">
            {{ to_persian_num((string) $noPlan) }} سالن هنوز پلن ندارند (همه‌ی امکانات، قیمت از فایل تنظیمات). دستور
            <code dir="ltr">php artisan plans:sync-defaults</code> وصلشان می‌کند.
        </div>
    @endif

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="p-3">پلن</th>
                    <th class="p-3">ماهانه / ۳ / ۶ / ۱۲ ماهه (تومان)</th>
                    <th class="p-3">متخصص شامل</th>
                    <th class="p-3">پیامک ماهانه</th>
                    <th class="p-3">امکانات</th>
                    <th class="p-3">سالن‌ها</th>
                    <th class="p-3">درآمد</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    <tr style="border-bottom: 1px solid var(--sa-border);" data-plan="{{ $plan->key }}">
                        <td class="p-3">
                            <div class="font-bold">{{ $plan->name }}</div>
                            <div class="text-xs mt-1" style="color: var(--sa-text-dim);">
                                <span dir="ltr">{{ $plan->key }}</span>
                                @if ($plan->is_trial) · آزمایشی {{ to_persian_num((string) $plan->trial_days) }} روز @endif
                                · {{ $plan->is_active ? 'فعال' : 'غیرفعال' }}
                                · {{ $plan->is_public ? 'عمومی' : 'غیرعمومی' }}
                            </div>
                        </td>
                        <td class="p-3 persian-number">
                            @if ($plan->is_trial)
                                <span style="color: var(--sa-text-dim);">رایگان</span>
                            @else
                                {{ collect(['1m', '3m', '6m', '12m'])->map(fn ($p) => $plan->priceFor($p) === null ? '—' : number_format($plan->priceFor($p)))->implode(' / ') }}
                            @endif
                        </td>
                        <td class="p-3 persian-number">
                            {{ to_persian_num((string) $plan->included_specialists) }}
                            @unless ($plan->is_trial)
                                <div class="text-xs" style="color: var(--sa-text-dim);">+{{ number_format($plan->extra_specialist_price_per_month) }} هر نفر</div>
                            @endunless
                        </td>
                        <td class="p-3 persian-number">{{ number_format($plan->sms_quota_per_month) }}</td>
                        <td class="p-3 text-xs persian-number">{{ to_persian_num((string) count($plan->featureKeys())) }} از {{ to_persian_num((string) count(\App\Support\Features\Feature::cases())) }}</td>
                        <td class="p-3 persian-number">
                            <a href="{{ route('superadmin.salons.index', ['plan_id' => $plan->id]) }}" style="color: var(--sa-accent);">{{ to_persian_num((string) ($salonCounts[$plan->id] ?? 0)) }}</a>
                        </td>
                        <td class="p-3 persian-number">{{ number_format((int) ($revenue[$plan->id] ?? 0)) }}</td>
                        <td class="p-3"><a href="{{ route('superadmin.plans.edit', $plan) }}" style="color: var(--sa-accent);">ویرایش</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
