@extends('layouts.admin')
@section('title', $campaign->name)

@section('content')
    <div class="fade-in" style="max-width: 64rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $campaign->name }}</h1>
                <p class="text-sm mt-1 persian-number" style="{{ $dim }}">
                    {{ $campaign->statusLabel() }}@if ($campaign->pause_reason) — {{ $campaign->pauseReasonLabel() }}@endif
                    · آخرین مراجعه {{ to_persian_num((string) $campaign->last_visit_from_days) }} تا {{ to_persian_num((string) $campaign->last_visit_to_days) }} روز پیش
                    @if ($campaign->hasDiscount()) · کد {{ $campaign->discountLabel() }}@endif
                    · شروع {{ verta($campaign->scheduled_at)->format('Y/m/d H:i') }}
                </p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('admin.winback.campaigns.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">کمپین‌ها</a>
                @if (in_array($campaign->status, ['scheduled', 'sending'], true))
                    <form method="POST" action="{{ route('admin.winback.campaigns.pause', $campaign) }}">@csrf<button class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">توقف</button></form>
                @endif
                @if ($campaign->status === 'paused')
                    <form method="POST" action="{{ route('admin.winback.campaigns.resume', $campaign) }}">@csrf<button class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">ادامه</button></form>
                @endif
                @if ($campaign->isActive())
                    <form method="POST" action="{{ route('admin.winback.campaigns.cancel', $campaign) }}" onsubmit="return confirm('کمپین لغو شود؟ به باقی مشتری‌ها پیامکی نمی‌رود.')">@csrf<button class="px-4 py-2 rounded-lg text-sm" style="border:1px solid #DC2626; color:#DC2626;">لغو</button></form>
                @endif
            </div>
        </div>

        @if ($campaign->isActive() && ! $insideHours)
            <div class="rounded-lg p-3 text-sm mb-5" style="background:var(--admin-accent-light); color:var(--admin-accent);">بیرون از ساعت ۹ تا ۲۰ پیامکی نمی‌رود؛ ارسال صبح ادامه پیدا می‌کند.</div>
        @endif

        @php $done = $campaign->sent + $campaign->skipped; $percent = $campaign->targeted > 0 ? (int) round($done * 100 / $campaign->targeted) : 0; @endphp
        <div class="rounded-xl p-4 mb-5" style="{{ $box }}">
            <div class="flex justify-between text-sm mb-2 persian-number"><span>پیشرفت</span><span>{{ to_persian_num((string) $percent) }}٪</span></div>
            <div style="height:8px; background:var(--admin-bg); border-radius:99px; overflow:hidden;"><div style="height:100%; width:{{ $percent }}%; background:var(--admin-accent);"></div></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5 persian-number">
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">گیرنده</div><div class="text-2xl font-bold mt-1">{{ to_persian_num(number_format($campaign->targeted)) }}</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">فرستاده‌شده</div><div class="text-2xl font-bold mt-1">{{ to_persian_num(number_format($campaign->sent)) }}</div><div class="text-xs" style="{{ $dim }}">{{ to_persian_num(number_format($campaign->parts_used)) }} قطعه</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">رد شده</div><div class="text-2xl font-bold mt-1">{{ to_persian_num(number_format($campaign->skipped)) }}</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">در صف</div><div class="text-2xl font-bold mt-1">{{ to_persian_num(number_format($pending)) }}</div></div>
        </div>

        <div class="rounded-xl p-4 mb-5" style="{{ $box }}">
            <div class="font-bold text-sm mb-3">نتیجه (تا ۳۰ روز بعد از هر پیامک)</div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm persian-number">
                <div><div class="text-xs" style="{{ $dim }}">نوبت گرفتند</div><b style="color:var(--admin-accent);">{{ to_persian_num(number_format($results['booked'])) }}</b>@if ($campaign->sent > 0) <span class="text-xs" style="{{ $dim }}">({{ to_persian_num((string) round($results['booked'] * 100 / $campaign->sent)) }}٪)</span>@endif</div>
                <div><div class="text-xs" style="{{ $dim }}">جمع مبلغ آن نوبت‌ها</div><b>{{ to_persian_num(number_format($results['revenue'])) }}</b> تومان</div>
                <div><div class="text-xs" style="{{ $dim }}">کد تخفیف استفاده‌شده</div><b>{{ to_persian_num(number_format($results['codes_used'])) }}</b></div>
                <div><div class="text-xs" style="{{ $dim }}">انصراف</div><b>{{ to_persian_num(number_format($results['opted_out'])) }}</b></div>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="{{ $box }}">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light);">آخرین گیرنده‌ها</div>
            @forelse ($recent as $r)
                <div class="px-4 py-2.5 text-sm flex justify-between gap-3" style="border-top:1px solid var(--admin-border);">
                    <span>{{ $r->user?->name }} <span dir="ltr" style="{{ $dim }}">{{ $r->user?->phone }}</span></span>
                    <span class="text-xs persian-number" style="{{ $dim }}">
                        @if ($r->status === 'sent') فرستاده شد {{ verta($r->sent_at)->format('m/d H:i') }}@if ($r->discountCode) · کد {{ $r->discountCode->code }}{{ $r->discountCode->used_count > 0 ? ' (استفاده شد)' : '' }}@endif
                        @else رد شد: {{ \App\Models\WinbackCampaignRecipient::SKIP_REASONS[$r->skip_reason] ?? $r->skip_reason }}@endif
                    </span>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm" style="{{ $dim }}">هنوز پیامکی نرفته.</p>
            @endforelse
        </div>
    </div>
@endsection
