@extends('layouts.superadmin')

@section('title', 'داشبورد')

@section('content')
    {{-- بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): «نیاز به رسیدگی» بالای صفحه، سه کارت گروهی و جدول سالن‌های تازه با پلن و زمان باقی‌مانده --}}
    @php
        $items = $attention['items'];
        $clear = $attention['clear'];
        $joinFa = fn (array $parts) => count($parts) > 1 ? implode('، ', array_slice($parts, 0, -1)).' و '.end($parts) : implode('', $parts);
        $backupLabel = match ($backup['state']) {
            'ok' => 'آخرین پشتیبان موفق',
            'stale' => 'آخرین پشتیبان قدیمی است',
            'failed' => 'آخرین پشتیبان‌گیری ناموفق بود',
            default => 'هیچ پشتیبانی ثبت نشده',
        };
    @endphp

    <section class="sa-card mb-5" data-dashboard-alerts aria-labelledby="attention-title">
        <div class="sa-section-head">
            <h2 id="attention-title" class="sa-card-title">نیاز به رسیدگی</h2>
            <span class="text-sm" style="color: var(--sa-text-dim);">{{ $items ? fa_num(count($items)).' مورد' : 'موردی نیست' }}</span>
        </div>
        @if ($items)
            <ul class="sa-attention">
                @foreach ($items as $item)
                    <li data-attention="{{ $item['key'] }}">
                        <b class="sa-attention__n sa-attention__n--{{ $item['tone'] }}">{{ is_int($item['n']) ? fa_num($item['n']) : $item['n'] }}</b>
                        <span class="flex-1 min-w-0">{{ $item['text'] }}</span>
                        <a href="{{ $item['url'] }}" class="font-bold text-sm shrink-0" style="color: var(--sa-accent);">{{ $item['action'] }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="sa-section-foot">
            @if ($clear)
                <p>{{ $joinFa($clear) }}: موردی نیست.</p>
            @endif
            {{-- وضعیت آخرین پشتیبان‌گیری (deploy/backup/mahru-backup.sh → storage/app/backup-status.json)؛ کارت جدا حذف شد (تصمیم ۱۱-۲۴) --}}
            <p id="backup-status" data-backup-state="{{ $backup['state'] }}">
                <span style="color: {{ $backup['state'] === 'ok' ? 'var(--sa-success)' : 'var(--sa-danger)' }}; font-weight: 700;">{{ $backupLabel }}</span>@if ($backup['at']):
                    {{ fa_date($backup['at'], true) }} ({{ $backup['at']->diffForHumans() }})@if ($backup['size'])، حجم {{ $backup['size'] }}@endif
                    @if ($backup['encrypted'] === true)، <span data-backup-encrypted>رمزنگاری‌شده</span>
                    @elseif ($backup['encrypted'] === false)، <span data-backup-plain style="color: var(--sa-danger);">بدون رمزنگاری</span>@endif
                    @if ($backup['state'] === 'failed' && $backup['message'])، {{ $backup['message'] }}@endif
                @else
                    — اسکریپت <span class="sa-ltr">deploy/backup/mahru-backup.sh</span> هنوز روی سرور اجرا نشده یا cron آن تنظیم نیست (راهنما: <span class="sa-ltr">docs/deployment/BACKUP.md</span>).
                @endif
            </p>
        </div>
    </section>

    @php
        $revenue = $overview['subscription_revenue_month'] + $overview['sms_revenue_month'];
        $bar = [
            ['active', 'var(--sa-success)'],
            ['trial', 'var(--sa-accent)'],
            ['expiring_soon', 'var(--sa-warn)'],
            ['expired', 'var(--sa-danger)'],
            ['suspended', 'var(--sa-text-dim)'],
        ];
        $total = max(1, $stats['total']);
        $smsFrom = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $overview['sms_period'].'-01')->startOfDay();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5" data-dashboard-stats>
        <section class="sa-card sa-stat" data-stat="revenue">
            <h2 class="sa-stat__label">درآمد {{ $overview['month_name'] }}</h2>
            <div class="sa-stat__value">{{ fa_num($revenue) }} <span class="sa-stat__unit">تومان</span></div>
            @if ($revenue === 0)
                <p class="sa-stat__note">هنوز خرید اشتراک یا بسته‌ی پیامکی در این ماه ثبت نشده. اولین پرداخت این‌جا با سهم اشتراک و پیامک دیده می‌شود.</p>
            @else
                <p class="sa-stat__note">اشتراک <b>{{ fa_num($overview['subscription_revenue_month']) }}</b>، بسته‌ی پیامک <b>{{ fa_num($overview['sms_revenue_month']) }}</b> تومان</p>
            @endif
        </section>

        <section class="sa-card sa-stat" data-stat="salons">
            <h2 class="sa-stat__label">سالن‌ها</h2>
            <div class="sa-stat__value">{{ fa_num($stats['total']) }} <span class="sa-stat__unit">سالن، {{ fa_num($stats['total_specialists']) }} کارشناس</span></div>
            <div class="sa-bar" role="img" aria-label="{{ collect($bar)->map(fn ($b) => fa_num($stats[$b[0]]).' '.\App\Support\SuperAdmin\SalonStatus::label($b[0]))->implode('، ') }}">
                @foreach ($bar as [$key, $color])
                    @if ($stats[$key] > 0)<span style="width: {{ round(100 * $stats[$key] / $total, 2) }}%; background: {{ $color }};"></span>@endif
                @endforeach
            </div>
            <div class="sa-legend">
                @foreach ($bar as [$key, $color])
                    @continue($key === 'suspended' && $stats[$key] === 0)
                    <a href="{{ route('superadmin.salons.index', ['status' => $key]) }}" data-legend="{{ $key }}"><b style="color: {{ $stats[$key] > 0 ? $color : 'var(--sa-text)' }};">{{ fa_num($stats[$key]) }}</b> {{ \App\Support\SuperAdmin\SalonStatus::label($key) }}</a>
                @endforeach
            </div>
            @if ($overview['new_salons_month'] > 0)
                <p class="sa-stat__note">{{ fa_num($overview['new_salons_month']) }} سالن تازه در {{ $overview['month_name'] }}</p>
            @endif
        </section>

        <section class="sa-card sa-stat" data-stat="sms">
            <h2 class="sa-stat__label">پیامک دوره‌ی جاری، همه‌ی سالن‌ها</h2>
            <div class="sa-stat__value">{{ fa_num($overview['sms_parts_month']) }} <span class="sa-stat__unit">قطعه</span></div>
            <p class="sa-stat__note">کدهای ورود و تأیید شماره (هزینه‌ی پلتفرم): <b>{{ fa_num($overview['otp_month']) }}</b>
                <br>دوره‌ی سهمیه از {{ fa_date($smsFrom, false, false) }} تا {{ fa_date($smsFrom->copy()->endOfMonth(), false, false) }}</p>
        </section>
    </div>

    <section class="sa-card overflow-hidden" aria-labelledby="recent-title">
        <div class="sa-section-head">
            <h2 id="recent-title" class="sa-card-title">سالن‌های تازه</h2>
            <a href="{{ route('superadmin.salons.index', ['sort' => 'newest']) }}" class="text-sm font-bold" style="color: var(--sa-accent);">همه‌ی سالن‌ها</a>
        </div>
        <div class="sa-table-wrap">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim);">
                    <th scope="col" class="py-2.5 px-5 font-semibold">سالن</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">پلن</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">کارشناس</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">اشتراک</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">وضعیت</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentSalons as $salon)
                    <tr style="border-top: 1px solid var(--sa-border);">
                        <td class="py-3 px-5">
                            <a href="{{ route('superadmin.salons.edit', $salon) }}" class="font-bold" style="color: var(--sa-text);">{{ $salon->name }}</a>
                            <div><a href="{{ $salon->publicUrl() }}" target="_blank" rel="noopener" class="sa-ltr" style="color: var(--sa-accent);">{{ \App\Support\SalonAddress::short($salon) }}</a></div>
                        </td>
                        @include('superadmin.partials.salon-cells', ['cells' => ['plan', 'experts', 'subscription', 'status']])
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 px-5 text-center" style="color: var(--sa-text-dim);">هنوز سالنی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
@endsection
