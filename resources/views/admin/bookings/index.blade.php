@extends('layouts.admin')

@section('title', 'نوبت‌ها')

@push('styles')
    <style>
        /* بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): تب‌های زمانی، یک جستجو و دو فیلتر، کار هر ردیف با یک دکمه‌ی متنی + منوی «⋯» */
        .bk-card { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 1rem; }
        .bk-tab { display: inline-flex; align-items: center; gap: .35rem; min-height: 38px; padding: 0 1rem; border-radius: 999px; font-size: .88rem;
            border: 1px solid var(--admin-border); background: var(--admin-surface); color: var(--admin-text); }
        .bk-tab b { font-weight: 700; color: var(--admin-text-dim); }
        .bk-tab:hover { border-color: var(--admin-accent); }
        .bk-tab[aria-current] { background: var(--admin-text); border-color: var(--admin-text); color: var(--admin-surface); font-weight: 700; }
        .bk-tab[aria-current] b { color: inherit; }
        .bk-search { flex: 1 1 16rem; display: flex; align-items: center; gap: .5rem; height: 42px; padding: 0 .85rem; border-radius: .75rem; background: var(--admin-bg); border: 1px solid var(--admin-border); }
        .bk-search input { flex: 1; min-width: 0; border: 0 !important; background: transparent !important; box-shadow: none !important; padding: 0; font: inherit; font-size: .9rem; color: var(--admin-text); }
        .bk-select { height: 42px; padding: 0 .6rem; border-radius: .75rem; border: 1px solid var(--admin-border); background: var(--admin-surface); color: var(--admin-text); font: inherit; font-size: .88rem; }
    </style>
@endpush

@section('content')
    @php
        $tabs = \App\Services\Admin\Booking\AdminBookingListFilter::TABS;
        $currentTab = $filters['tab'];
        $tabUrl = fn (string $tab) => route('admin.bookings.index', array_filter(['tab' => $tab] + array_diff_key($filters, ['tab' => 1]), fn ($v) => $v !== null && $v !== ''));
        $hasFilters = count(array_intersect_key($filters, array_flip(['q', 'status', 'specialist_id']))) > 0;
        $noShowLabels = app(\App\Services\Booking\NoShowRecord::class)->labelsFor($bookings->pluck('user_id'));
    @endphp
    <div class="fade-in flex flex-col gap-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-extrabold" style="color: var(--admin-text);">نوبت‌ها</h1>
                <p class="text-sm mt-1" style="color: var(--admin-text-dim);" data-bookings-summary>
                    {{ fa_num($totalBookings) }} نوبت@if ($waitingCount > 0)؛ {{ fa_num($waitingCount) }} نوبت منتظر کار شما@endif
                </p>
            </div>
            @permission('create-bookings')
                <a href="{{ route('admin.bookings.create') }}" class="adm-new-booking">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    ثبت نوبت
                </a>
            @endpermission
        </div>

        <form method="GET" action="{{ route('admin.bookings.index') }}" class="bk-card p-4 flex flex-col gap-3" data-bookings-filters>
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            <nav class="flex gap-2 flex-wrap" aria-label="بازه‌ی زمانی" data-bookings-tabs>
                @foreach ($tabs as $tab => $label)
                    <a href="{{ $tabUrl($tab) }}" class="bk-tab" data-tab="{{ $tab }}" @if ($currentTab === $tab) aria-current="page" @endif>{{ $label }} <b>{{ fa_num($tabCounts[$tab]) }}</b></a>
                @endforeach
            </nav>
            <div class="flex gap-2.5 flex-wrap items-center">
                <label class="bk-search">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--admin-text-dim);" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <span class="sr-only">جستجوی مشتری</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام یا موبایل مشتری" maxlength="100">
                </label>
                <label class="flex items-center gap-1.5 text-sm" style="color: var(--admin-text-dim);">وضعیت
                    <select name="status" class="bk-select" data-ui-autosubmit>
                        <option value="">همه</option>
                        @foreach (\App\Support\Bookings\BookingLabels::statusOptions() as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected(($filters['status'] ?? null) === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-1.5 text-sm" style="color: var(--admin-text-dim);">کارشناس
                    <select name="specialist_id" class="bk-select" data-ui-autosubmit>
                        <option value="">همه</option>
                        @foreach ($specialists as $specialist)
                            <option value="{{ $specialist->id }}" @selected((int) ($filters['specialist_id'] ?? 0) === $specialist->id)>{{ $specialist->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="adm-act">جستجو</button>
                @if ($hasFilters)
                    <a href="{{ route('admin.bookings.index', ['tab' => $currentTab]) }}" class="text-sm" style="color: var(--admin-text-dim);">حذف فیلترها</a>
                @endif
            </div>
            @if ($errors->any())
                <p class="text-sm" style="color: rgb(var(--m-bad));">{{ $errors->first() }}</p>
            @endif
        </form>

        <section class="bk-card" aria-label="فهرست نوبت‌ها">
            <div class="overflow-x-auto relative">
                <table class="w-full text-sm text-right" style="min-width: 54rem;" data-bookings-table>
                    <thead>
                        <tr style="background: var(--admin-accent-light); color: var(--admin-text-dim);">
                            <th scope="col" class="px-4 py-3 font-semibold">زمان</th>
                            <th scope="col" class="px-3 py-3 font-semibold">مشتری</th>
                            <th scope="col" class="px-3 py-3 font-semibold">خدمت و کارشناس</th>
                            <th scope="col" class="px-3 py-3 font-semibold">وضعیت</th>
                            <th scope="col" class="px-3 py-3 font-semibold">مبلغ</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">کارها</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr style="border-top: 1px solid var(--admin-border);" data-booking-row="{{ $booking->id }}">
                                <td class="px-4 py-3">
                                    <b style="color: var(--admin-text);">{{ fa_time($booking->booking_time) }}</b>
                                    <span class="block text-xs" style="color: var(--admin-text-dim);">{{ fa_date($booking->booking_time) }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($booking->user)
                                        <b style="color: var(--admin-text);">{{ $booking->user->name }}</b>
                                        <span class="block text-xs" dir="ltr" style="text-align: right; color: var(--admin-text-dim);">{{ \App\Support\Fa::digits($booking->user->phone) }}</span>
                                        <x-no-show-badge :label="$noShowLabels[$booking->user_id] ?? null" class="mt-1" />
                                    @else
                                        <span style="color: var(--admin-text-light);">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3" style="color: var(--admin-text);">
                                    {{ $booking->service?->name ?? '—' }}
                                    <span class="block text-xs" style="color: var(--admin-text-dim);">{{ $booking->specialist?->name ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-3"><x-booking-status :status="$booking->status" /></td>
                                <td class="px-3 py-3">
                                    <span style="color: var(--admin-text);">{{ $booking->service ? fa_num($booking->service->price) : '—' }}</span>
                                    <span class="block mt-1"><x-payment-status :booking="$booking" /></span>
                                </td>
                                <td class="px-4 py-3">
                                    @include('admin.bookings.partials.actions', ['booking' => $booking])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm" style="color: var(--admin-text-dim);">
                                    {{ $hasFilters ? 'نوبتی با این جستجو و فیلترها پیدا نشد.' : 'در این بازه نوبتی نیست.' }}
                                    @if ($currentTab !== 'all')<a href="{{ $tabUrl('all') }}" class="font-bold mr-1" style="color: var(--admin-accent);">دیدن همه‌ی نوبت‌ها</a>@endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($bookings->total() > 0)
                <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-sm" style="border-top: 1px solid var(--admin-border); color: var(--admin-text-dim);">
                    <span>نمایش {{ fa_num($bookings->firstItem()) }} تا {{ fa_num($bookings->lastItem()) }} از {{ fa_num($bookings->total()) }} نوبت {{ $currentTab === 'all' ? '' : $tabs[$currentTab] }}</span>
                    @if ($bookings->hasPages())<div>{{ $bookings->links('pagination::simple-tailwind') }}</div>@endif
                </div>
            @endif
        </section>
    </div>
@endsection
