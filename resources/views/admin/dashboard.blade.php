@extends('layouts.admin')

@section('title', 'داشبورد')

@push('styles')
    <style>
        /* بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): داشبورد مدیر سالن — همان توکن‌های پوسته (--admin-*)، کم کردن شلوغی */
        .dash-card { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; }
        .dash-head { padding: .9rem 1.15rem; border-bottom: 1px solid var(--admin-border); display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .dash-head h2 { margin: 0; font-size: 1rem; font-weight: 800; color: var(--admin-text); }
        .dash-link { font-size: .85rem; font-weight: 700; color: var(--admin-accent); }
        .dash-stat { padding: 1rem 1.15rem; display: flex; flex-direction: column; gap: .25rem; }
        .dash-stat__label { font-size: .85rem; color: var(--admin-text-dim); }
        .dash-stat__value { font-size: 1.6rem; font-weight: 800; line-height: 1.3; color: var(--admin-text); }
        .dash-stat__unit { font-size: .8rem; font-weight: 500; color: var(--admin-text-dim); }
        .dash-stat__note { font-size: .78rem; color: var(--admin-text-dim); line-height: 1.7; }
        .dash-setup > summary { list-style: none; cursor: pointer; }
        .dash-setup > summary::-webkit-details-marker { display: none; }
        .dash-steps { display: flex; gap: 4px; }
        .dash-steps i { width: 22px; height: 6px; border-radius: 99px; background: var(--admin-border); }
        .dash-steps i.is-done { background: rgb(var(--m-ok)); }
        .dash-row { display: flex; align-items: center; gap: .9rem; padding: .75rem 1.15rem; }
        .dash-row + .dash-row { border-top: 1px solid var(--admin-border); }
        .dash-time { min-width: 3rem; font-weight: 800; font-size: .95rem; color: var(--admin-text); }
        .dash-bar { height: 6px; border-radius: 99px; background: var(--admin-border); overflow: hidden; }
        .dash-bar i { display: block; height: 100%; border-radius: 99px; background: var(--admin-accent); }
        .dash-code { direction: ltr; font-size: .85rem; padding: .45rem .7rem; border-radius: .6rem; background: var(--admin-bg); border: 1px solid var(--admin-border); color: var(--admin-text); }
    </style>
@endpush

@section('content')
    <div class="fade-in flex flex-col gap-4">
        {{-- ⭐ مواردی که پیگیری خودکار پرداخت/تسویه به نتیجه نرسید (۲۰۲۶-۰۹-۲۶) — فقط owner (صفحه‌هاش owner-only‌اند) --}}
        @if ($isOwner && ($__attention = app(\App\Services\Admin\AttentionCounts::class))->total() > 0)
            <div class="rounded-xl p-4 text-sm" style="background:rgb(var(--m-warn) / .1); border:1px solid rgb(var(--m-warn) / .35); color:rgb(var(--m-warn));" data-attention-alert>
                <p class="font-bold mb-1">پرداخت‌هایی که به بررسی شما نیاز دارند</p>
                <div class="flex flex-wrap gap-4">
                    @if ($__attention->payments() > 0)
                        <a href="{{ route('admin.payment-attention.index') }}" class="underline">{{ fa_num($__attention->payments()) }} پرداخت مشتری که پاسخ درگاهش نرسید</a>
                    @endif
                    @if ($__attention->withdrawals() > 0)
                        <a href="{{ route('admin.wallet.withdrawals', ['needs_check' => 1]) }}" class="underline">{{ fa_num($__attention->withdrawals()) }} تسویه‌ی کارشناس با نتیجه‌ی نامعلوم</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- عنوان امروز + یک جمله‌ی خلاصه --}}
        <div data-dash-today>
            <h1 class="text-xl font-extrabold" style="color: var(--admin-text);">امروز، {{ fa_date(now(), false, true) }}</h1>
            <p class="text-sm mt-1" style="color: var(--admin-text-dim);">
                @if ($todayBookingsCount === 0)
                    امروز نوبتی ندارید.
                @else
                    {{ fa_num($todayBookingsCount) }} نوبت دارید@if ($todayWaiting > 0)؛ {{ fa_num($todayWaiting) }} نوبت منتظر تأیید یا پرداخت است@endif.
                @endif
            </p>
        </div>

        {{-- راه‌اندازی سالن (جای بنر قرمز درگاه و کارت بزرگ لینک/QR) --}}
        @if ($salon)
            @php $publicUrl = $salon->publicUrl(); @endphp
            <details class="dash-card dash-setup" data-salon-setup @if ($isOwner && $setup && ! $setup['required_done']) open @endif>
                <summary class="dash-head" style="border-bottom: 0;">
                    <span class="flex items-center gap-3 flex-wrap">
                        <b style="color: var(--admin-text);">{{ $isOwner ? 'راه‌اندازی سالن' : 'لینک رزرو سالن' }}</b>
                        @if ($isOwner && $setup)
                            <span class="text-sm" style="color: var(--admin-text-dim);" data-setup-progress>{{ fa_num($setup['done']) }} از {{ fa_num($setup['total']) }} انجام شده</span>
                        @endif
                    </span>
                    @if ($isOwner && $setup)
                        <span class="dash-steps" aria-hidden="true">
                            @foreach ($setup['steps'] as $step)<i @class(['is-done' => $step['done']])></i>@endforeach
                        </span>
                    @endif
                </summary>
                <div style="border-top: 1px solid var(--admin-border);" class="grid grid-cols-1 lg:grid-cols-2">
                    <div class="p-4 flex flex-col gap-2" id="salon-public-link-card">
                        <b class="text-sm" style="color: var(--admin-text);">لینک رزرو را به مشتری‌ها بدهید</b>
                        <div class="flex gap-2 items-center flex-wrap">
                            <span class="dash-code font-mono" dir="ltr" data-salon-link>{{ \App\Support\SalonAddress::short($salon) }}</span>
                            <button type="button" data-copy-salon-link="{{ $publicUrl }}" class="adm-act">کپی لینک</button>
                            <a href="{{ route('admin.salon-qr.index') }}" class="adm-act" data-salon-qr-page-link>QR و پوستر</a>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="adm-act adm-act--ghost">دیدن صفحه</a>
                        </div>
                        @unless ($salon->hasActiveSubscription())
                            <p class="text-xs" style="color: rgb(var(--m-bad));">تا اشتراک سالن فعال نباشد، این لینک برای مشتری‌ها باز نمی‌شود.</p>
                        @endunless
                    </div>
                    @if ($isOwner && $setup)
                        <ul class="p-4 flex flex-col gap-2.5 lg:border-r" style="border-color: var(--admin-border);" data-setup-steps>
                            @foreach ($setup['steps'] as $step)
                                <li class="flex items-start gap-2.5 text-sm" data-setup-step="{{ $step['key'] }}" data-done="{{ $step['done'] ? '1' : '0' }}">
                                    <span class="mt-0.5 inline-flex w-5 h-5 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                          style="{{ $step['done'] ? 'background: rgb(var(--m-ok)); color: rgb(var(--m-on-solid));' : 'border: 1.5px solid var(--admin-border); color: var(--admin-text-light);' }}">{{ $step['done'] ? '✓' : '' }}</span>
                                    <span class="flex-1 min-w-0">
                                        <b style="color: var(--admin-text);">{{ fa_num($loop->iteration) }}. {{ $step['label'] }}</b>
                                        @if ($step['optional'])<span class="text-xs" style="color: var(--admin-text-light);">(اختیاری)</span>@endif
                                        @unless ($step['done'])
                                            <span class="block text-xs leading-6" style="color: var(--admin-text-dim);">{{ $step['hint'] }}</span>
                                            @if (filled($step['how'] ?? null))<span class="block text-xs leading-6 mt-0.5" style="color: var(--admin-text-light);" data-setup-how>{{ $step['how'] }}</span>@endif
                                            <a href="{{ $step['url'] }}" class="text-xs font-bold" style="color: var(--admin-accent);">{{ $step['cta'] }}</a>
                                        @endunless
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </details>
        @endif

        {{-- ۴ کارت عدد --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3" data-dash-stats>
            <div class="dash-card dash-stat" data-stat="today">
                <span class="dash-stat__label">نوبت‌های امروز</span>
                <b class="dash-stat__value">{{ fa_num($todayBookingsCount) }}</b>
                <span class="dash-stat__note" @if ($todayWaiting > 0) style="color: rgb(var(--m-warn));" @endif>
                    {{ $todayWaiting > 0 ? fa_num($todayWaiting).' منتظر تأیید یا پرداخت' : 'همه‌ی نوبت‌های امروز تعیین‌تکلیف شده‌اند' }}
                </span>
            </div>
            @if ($canViewFinancials)
                <div class="dash-card dash-stat" data-stat="revenue">
                    <span class="dash-stat__label">درآمد {{ $monthName }}</span>
                    <b class="dash-stat__value">{{ fa_num($monthRevenue) }} <span class="dash-stat__unit">تومان</span></b>
                    <span class="dash-stat__note">
                        @if (! $prepayMode && $monthRevenue === 0)
                            رزرو بدون پیش‌پرداخت است؛ پول آنلاین گرفته نمی‌شود و مبلغ در سالن پرداخت می‌شود.
                        @else
                            سهم سالن از پیش‌پرداخت‌ها ({{ fa_num($commissionRate) }}٪ کمیسیون)
                        @endif
                    </span>
                </div>
            @else
                <div class="dash-card dash-stat" data-stat="month-bookings">
                    <span class="dash-stat__label">نوبت‌های {{ $monthName }}</span>
                    <b class="dash-stat__value">{{ fa_num($monthBookings) }}</b>
                    <span class="dash-stat__note">بی نوبت‌های لغوشده</span>
                </div>
            @endif
            <div class="dash-card dash-stat" data-stat="customers">
                <span class="dash-stat__label">مشتری‌ها</span>
                <b class="dash-stat__value">{{ fa_num($customersCount) }}</b>
                <span class="dash-stat__note" @if ($newCustomersMonth > 0) style="color: rgb(var(--m-ok));" @endif>{{ $newCustomersMonth > 0 ? fa_num($newCustomersMonth).' مشتری تازه در '.$monthName : 'مشتری تازه‌ای در '.$monthName.' نیامده' }}</span>
            </div>
            <div class="dash-card dash-stat" data-stat="experts">
                <span class="dash-stat__label">کارشناسان</span>
                <b class="dash-stat__value">{{ fa_num($specialistsCount) }}@if ($specialistLimit) <span class="dash-stat__unit">از {{ fa_num($specialistLimit) }} ظرفیت</span>@endif</b>
                <span class="dash-stat__note">{{ $onLeaveToday > 0 ? fa_num($onLeaveToday).' نفر امروز مرخصی' : 'امروز کسی مرخصی نیست' }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
            {{-- نوبت‌های امروز با کار مستقیم --}}
            <section class="dash-card xl:col-span-2 overflow-hidden" aria-labelledby="today-title" data-dash-today-list>
                <div class="dash-head">
                    <h2 id="today-title">نوبت‌های امروز</h2>
                    <a href="{{ route('admin.bookings.index', ['tab' => 'today']) }}" class="dash-link">همه‌ی نوبت‌ها</a>
                </div>
                @forelse ($todayBookings as $booking)
                    <div class="dash-row" data-today-booking="{{ $booking->id }}">
                        <b class="dash-time">{{ fa_time($booking->booking_time) }}</b>
                        <span class="flex-1 min-w-0 flex flex-col">
                            <b class="text-sm truncate" style="color: var(--admin-text);">{{ $booking->user?->name ?? 'مشتری' }}</b>
                            <span class="text-xs truncate" style="color: var(--admin-text-dim);">{{ $booking->service?->name ?? '—' }} با {{ $booking->specialist?->name ?? '—' }}</span>
                            <span class="sm:hidden mt-1"><x-booking-status :status="$booking->status" /></span>
                        </span>
                        <span class="hidden sm:inline-flex"><x-booking-status :status="$booking->status" /></span>
                        @include('admin.bookings.partials.actions', ['booking' => $booking, 'withMenu' => false])
                    </div>
                @empty
                    <p class="p-6 text-center text-sm" style="color: var(--admin-text-dim);">امروز نوبتی ثبت نشده است.
                        @permission('create-bookings')<a href="{{ route('admin.bookings.create') }}" class="dash-link mr-1">ثبت نوبت</a>@endpermission
                    </p>
                @endforelse
            </section>

            {{-- خدمات پرطرفدار --}}
            <section class="dash-card" aria-labelledby="popular-title">
                <div class="dash-head"><h2 id="popular-title">خدمات پرطرفدار، ۳۰ روز اخیر</h2></div>
                <div class="p-4 flex flex-col gap-3.5 text-sm">
                    @php $maxPopular = max(1, (int) ($popularServices->max('bookings_count') ?? 0)); @endphp
                    @forelse ($popularServices as $service)
                        <div class="flex flex-col gap-1.5">
                            <span class="flex justify-between gap-2"><b style="color: var(--admin-text);">{{ $service->name }}</b><span style="color: var(--admin-text-dim);">{{ fa_num($service->bookings_count) }} نوبت</span></span>
                            <span class="dash-bar"><i style="width: {{ round(100 * $service->bookings_count / $maxPopular) }}%;"></i></span>
                        </div>
                    @empty
                        <p style="color: var(--admin-text-dim);">در ۳۰ روز اخیر نوبتی ثبت نشده است.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- کارشناسان، ۳۰ روز اخیر — ستون خنثی، بی برچسب «عملکرد ضعیف» --}}
        <section class="dash-card overflow-hidden" aria-labelledby="experts-title" data-dash-experts>
            <div class="dash-head">
                <h2 id="experts-title">کارشناسان، ۳۰ روز اخیر</h2>
                @permission('view-specialists')<a href="{{ route('admin.specialists.index') }}" class="dash-link">همه‌ی کارشناسان</a>@endpermission
            </div>
            <div class="overflow-x-auto relative">
                <table class="w-full text-sm text-right" style="min-width: 36rem;">
                    <thead>
                        <tr style="background: var(--admin-accent-light); color: var(--admin-text-dim);">
                            <th scope="col" class="px-4 py-2.5 font-semibold">کارشناس</th>
                            <th scope="col" class="px-3 py-2.5 font-semibold">نوبت</th>
                            <th scope="col" class="px-3 py-2.5 font-semibold">انجام‌شده</th>
                            @if ($canViewFinancials)<th scope="col" class="px-3 py-2.5 font-semibold">پیش‌پرداخت</th>@endif
                            <th scope="col" class="px-4 py-2.5 font-semibold">امتیاز</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topSpecialists as $specialist)
                            <tr style="border-top: 1px solid var(--admin-border);">
                                <td class="px-4 py-2.5 font-bold" style="color: var(--admin-text);">{{ $specialist->name }}</td>
                                <td class="px-3 py-2.5">{{ fa_num($specialist->bookings_count) }}</td>
                                <td class="px-3 py-2.5">{{ fa_num($specialist->successful_bookings) }}</td>
                                @if ($canViewFinancials)<td class="px-3 py-2.5">{{ fa_money((int) $specialist->revenue, true) }}</td>@endif
                                <td class="px-4 py-2.5" style="{{ $specialist->rating > 0 ? '' : 'color: var(--admin-text-dim);' }}">{{ $specialist->rating > 0 ? fa_num($specialist->rating, 1).' از ۵' : 'هنوز نظری نیست' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center" style="color: var(--admin-text-dim);">هنوز کارشناسی ثبت نشده است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script @nonce>
        // کپی لینک رزرو سالن
        document.querySelectorAll('[data-copy-salon-link]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-copy-salon-link'), label = btn.textContent;
                var done = function () { btn.textContent = 'کپی شد'; setTimeout(function () { btn.textContent = label; }, 1800); };
                if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(url).then(done, function () {}); return; }
                var t = document.createElement('textarea'); t.value = url; t.style.position = 'fixed'; t.style.opacity = '0';
                document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                t.remove();
            });
        });
    </script>
@endpush
