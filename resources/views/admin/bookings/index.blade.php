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
        .desk-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: .8rem; align-items: start; }
        @media (min-width: 1100px) { .desk-layout { grid-template-columns: minmax(0, 1fr) 20rem; } }
        .desk-panel { position: sticky; top: 4.5rem; padding: 1rem; display: flex; flex-direction: column; gap: .8rem; font-size: 13.5px; min-height: 18rem; }
        .desk-panel:focus { outline: none; }
        .desk-panel__body { display: flex; flex-direction: column; gap: .8rem; }
        .desk-panel__grid { display: grid; grid-template-columns: auto 1fr; gap: .4rem .9rem; padding: .75rem; border-radius: .75rem; background: var(--admin-bg); }
        .desk-panel__grid dt { color: var(--admin-text-dim); }
        .desk-panel__grid dd { color: var(--admin-text); margin: 0; }
        .desk-panel .adm-act--primary kbd { background: transparent; border-color: currentColor; color: inherit; opacity: .75; font-size: 11px; padding: 0 5px; }
        .desk-panel__empty { color: var(--admin-text-dim); line-height: 1.9; }
        .desk-panel__keys { margin-top: auto; padding-top: .75rem; border-top: 1px solid var(--admin-border); font-size: 12px; line-height: 2.1; color: var(--admin-text-dim); }
        [data-desk-row] { cursor: pointer; }
        [data-desk-row]:hover { background: var(--admin-accent-light); }
        [data-desk-row].is-selected { background: rgb(var(--m-gold) / .1); box-shadow: inset -3px 0 0 rgb(var(--m-gold)); }
        [data-desk-row]:focus { outline: none; }
        [data-desk-row]:focus-visible { outline: 2px solid rgb(var(--m-gold)); outline-offset: -2px; }
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
        // پوسته‌ی «میز پذیرش» (بسته‌ی ۲ب): فهرست + پنل جزئیات نوبت انتخاب‌شده کنار آن
        $desk = app(\App\Support\Themes\CurrentAppearance::class)->get()->admin === 'dense';
    @endphp
    <div class="fade-in flex flex-col gap-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-extrabold" style="color: var(--admin-text);">نوبت‌ها</h1>
                <p class="text-sm mt-1" style="color: var(--admin-text-dim);" data-bookings-summary>
                    {{ fa_num($totalBookings) }} نوبت فعال@if ($waitingCount > 0)؛ {{ fa_num($waitingCount) }} نوبت منتظر کار شما@endif
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
                        <option value="">همه‌ی فعال‌ها</option>
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

        <div class="{{ $desk ? 'desk-layout' : '' }}">
        <section class="bk-card min-w-0" aria-label="فهرست نوبت‌ها" @if ($desk) data-desk-list @endif>
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
                            <tr style="border-top: 1px solid var(--admin-border);" data-booking-row="{{ $booking->id }}"
                                @if ($desk) data-desk-row data-panel-url="{{ route('admin.bookings.panel', $booking) }}" tabindex="-1" @endif>
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
        @if ($desk)
            <aside class="desk-panel bk-card" aria-label="جزئیات نوبت انتخاب‌شده" aria-live="polite" data-desk-panel>
                <div data-desk-panel-slot>
                    <p class="desk-panel__empty">{{ $bookings->isEmpty() ? 'نوبتی برای نمایش نیست.' : 'روی یک نوبت بزنید یا با J و K بین ردیف‌ها بروید.' }}</p>
                </div>
                <p class="desk-panel__keys">
                    <kbd dir="ltr">J</kbd>/<kbd dir="ltr">K</kbd> ردیف بعد و قبل، <kbd dir="ltr">Enter</kbd> جزئیات، <kbd dir="ltr">T</kbd> تأیید (با پرسش)،
                    <kbd dir="ltr">N</kbd> نوبت تازه، <kbd dir="ltr">/</kbd> جستجو
                </p>
            </aside>
        @endif
        </div>
    </div>
@endsection

@if ($desk ?? false)
@push('scripts')
<script @nonce>
    // «میز پذیرش» (بسته‌ی ۲ب): انتخاب ردیف با کلیک یا J/K، پنل جزئیات با fetch بی بارگذاری صفحه، Enter فوکوس به پنل،
    // T تأیید نوبت منتظر — همیشه با پرسش. فقط وقتی فوکوس در فیلد متنی نیست و میانبرها روشن‌اند.
    (function () {
        var rows = Array.prototype.slice.call(document.querySelectorAll('[data-desk-row]'));
        var slot = document.querySelector('[data-desk-panel-slot]');
        if (!rows.length || !slot) return;
        var current = -1, timer = null, request = 0;

        function load(row) {
            var id = ++request;
            slot.setAttribute('aria-busy', 'true');
            fetch(row.getAttribute('data-panel-url'), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                .then(function (html) { if (id === request) { slot.innerHTML = html; slot.removeAttribute('aria-busy'); } })
                .catch(function () { if (id === request) { slot.innerHTML = '<p class="desk-panel__empty">بار کردن جزئیات نشد؛ دوباره امتحان کنید.</p>'; slot.removeAttribute('aria-busy'); } });
        }
        function select(index, immediate) {
            if (index < 0 || index >= rows.length) return;
            if (current >= 0) { rows[current].classList.remove('is-selected'); rows[current].removeAttribute('aria-selected'); }
            current = index;
            var row = rows[index];
            row.classList.add('is-selected'); row.setAttribute('aria-selected', 'true');
            row.focus({ preventScroll: true });
            row.scrollIntoView({ block: 'nearest' });
            clearTimeout(timer);
            if (immediate) load(row); else timer = setTimeout(function () { load(row); }, 180);
        }

        rows.forEach(function (row, index) {
            row.addEventListener('click', function (e) {
                if (e.target.closest('a,button,form,summary,details,input,select')) return;
                select(index, true);
            });
        });

        function typing(el) {
            return el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
        }
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey || typing(e.target)) return;
            if (window.mahruShortcuts && !window.mahruShortcuts.enabled()) return;
            if (document.querySelector('dialog[open]')) return;
            if (e.code === 'KeyJ') { e.preventDefault(); select(current < 0 ? 0 : Math.min(current + 1, rows.length - 1)); }
            else if (e.code === 'KeyK') { e.preventDefault(); select(current < 0 ? 0 : Math.max(current - 1, 0)); }
            else if (e.key === 'Enter' && current >= 0 && e.target === rows[current]) {
                e.preventDefault(); clearTimeout(timer); load(rows[current]);
                var panel = document.querySelector('[data-desk-panel]');
                if (panel) { panel.setAttribute('tabindex', '-1'); panel.focus({ preventScroll: true }); }
            }
            else if (e.code === 'KeyT' && current >= 0) {
                var id = rows[current].getAttribute('data-booking-row');
                var form = document.querySelector('[data-desk-panel-body="' + id + '"] form[data-desk-confirm]');
                if (!form) return;
                e.preventDefault();
                form.requestSubmit(); // data-ui-confirm پیش از ارسال می‌پرسد
            }
        });
    })();
</script>
@endpush
@endif
