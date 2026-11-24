@extends('layouts.superadmin')

@section('title', 'مدیریت سالن‌ها')

@section('content')
    {{-- بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): یک جستجو + تراشه‌های وضعیت با شمار + «فیلترهای بیشتر» + مرتب‌سازی؛ هر ردیف «ویرایش» + منوی «⋯» --}}
    @php
        $hasFilters = count(array_diff_key($filters, ['sort' => 1])) > 0;
        $moreKeys = ['plan_id', 'subscription_type', 'quota', 'started_from', 'started_to', 'ends_from', 'ends_to'];
        $moreCount = count(array_intersect_key($filters, array_flip($moreKeys)));
        $currentStatus = $filters['status'] ?? null;
        $chipUrl = fn (?string $status) => route('superadmin.salons.index', array_filter(['status' => $status] + array_diff_key($filters, ['status' => 1]), fn ($v) => $v !== null && $v !== ''));
        $periods = ['1m' => '۱ ماه', '3m' => '۳ ماه', '6m' => '۶ ماه', '12m' => '۱۲ ماه'];
    @endphp

    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <p class="text-sm" style="color: var(--sa-text-dim);"><b style="color: var(--sa-text); font-size: 1rem;">{{ fa_num($statusCounts['total']) }} سالن</b>@unless ($hasFilters)، {{ fa_num($totalSpecialists) }} کارشناس @endunless @if ($hasFilters) با این جستجو و فیلترها @endif</p>
        <a href="{{ route('superadmin.salons.create') }}" class="sa-btn">سالن جدید</a>
    </div>

    <form method="GET" action="{{ route('superadmin.salons.index') }}" class="sa-card p-4 mb-4 flex flex-col gap-3" data-salon-filters>
        @if ($currentStatus)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <div class="flex flex-wrap gap-2.5 items-center">
            <label class="sa-search sa-search--block">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--sa-text-dim);" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                <span class="sa-sr">جستجوی سالن</span>
                <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام سالن، آدرس، نام یا موبایل مدیر" maxlength="100">
            </label>
            <label class="flex items-center gap-2 text-sm" style="color: var(--sa-text-dim);">مرتب‌سازی
                <select id="sort" name="sort" class="sa-input" style="width: auto; padding-block: .5rem;" data-ui-autosubmit>
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? \App\Services\SuperAdmin\SalonListFilter::DEFAULT_SORT) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="sa-btn" style="padding: .55rem 1.1rem;">جستجو</button>
            @if ($hasFilters)
                <a href="{{ route('superadmin.salons.index') }}" class="text-sm" style="color: var(--sa-text-dim);">حذف همه‌ی فیلترها</a>
            @endif
        </div>

        <div role="group" aria-label="وضعیت" class="flex flex-wrap gap-2" data-status-chips>
            <a href="{{ $chipUrl(null) }}" class="sa-chip" @if (! $currentStatus) aria-current="true" @endif>همه <b>{{ fa_num($statusCounts['total']) }}</b></a>
            @foreach ($statuses as $value => $label)
                <a href="{{ $chipUrl($value) }}" class="sa-chip" data-chip="{{ $value }}" @if ($currentStatus === $value) aria-current="true" @endif>{{ $label }} <b>{{ fa_num($statusCounts[$value]) }}</b></a>
            @endforeach
        </div>

        <details class="sa-more" @if ($moreCount > 0 || $errors->any()) open @endif data-more-filters>
            <summary>فیلترهای بیشتر @if ($moreCount > 0)<span class="sa-badge" style="margin: 0;">{{ fa_num($moreCount) }}</span>@endif</summary>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-3">
                <div>
                    <label class="sa-label" for="plan_id">پلن</label>
                    <select id="plan_id" name="plan_id" class="sa-input">
                        <option value="">همه</option>
                        @foreach ($plans as $planOption)
                            <option value="{{ $planOption->id }}" @selected(($filters['plan_id'] ?? null) == $planOption->id)>{{ $planOption->name }}</option>
                        @endforeach
                        <option value="none" @selected(($filters['plan_id'] ?? null) === 'none')>بدون پلن</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label" for="subscription_type">بازه‌ی فعلی</label>
                    <select id="subscription_type" name="subscription_type" class="sa-input">
                        <option value="">همه</option>
                        @foreach (['1m' => 'یک‌ماهه', '3m' => 'سه‌ماهه', '6m' => 'شش‌ماهه', '12m' => 'یک‌ساله'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['subscription_type'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sa-label" for="quota">سقف کارشناس</label>
                    <select id="quota" name="quota" class="sa-input">
                        <option value="">همه</option>
                        <option value="full" @selected(($filters['quota'] ?? null) === 'full')>پر شده</option>
                        <option value="available" @selected(($filters['quota'] ?? null) === 'available')>ظرفیت خالی دارد</option>
                    </select>
                </div>
                <div></div>
                <div class="grid grid-cols-2 gap-2 md:col-span-2">
                    <div>
                        <label class="sa-label" for="started_from">شروع اشتراک از</label>
                        <input id="started_from" name="started_from" value="{{ $filters['started_from'] ?? '' }}" class="sa-input" placeholder="{{ fa_jdate(now()->subYear()) }}" dir="ltr">
                    </div>
                    <div>
                        <label class="sa-label" for="started_to">تا</label>
                        <input id="started_to" name="started_to" value="{{ $filters['started_to'] ?? '' }}" class="sa-input" placeholder="{{ fa_jdate(now()) }}" dir="ltr">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 md:col-span-2">
                    <div>
                        <label class="sa-label" for="ends_from">پایان اشتراک از</label>
                        <input id="ends_from" name="ends_from" value="{{ $filters['ends_from'] ?? '' }}" class="sa-input" placeholder="{{ fa_jdate(now()) }}" dir="ltr">
                    </div>
                    <div>
                        <label class="sa-label" for="ends_to">تا</label>
                        <input id="ends_to" name="ends_to" value="{{ $filters['ends_to'] ?? '' }}" class="sa-input" placeholder="{{ fa_jdate(now()->addMonth()) }}" dir="ltr">
                    </div>
                </div>
                <div class="md:col-span-4 flex gap-3 items-center">
                    <button type="submit" class="sa-btn" style="padding: .5rem 1.1rem;">اعمال فیلترها</button>
                </div>
            </div>
        </details>
        @if ($errors->any())
            <div class="text-sm" style="color: var(--sa-danger);">{{ $errors->first() }}</div>
        @endif
    </form>

    <section class="sa-card" aria-label="فهرست سالن‌ها">
        <div class="sa-table-wrap">
        <table class="w-full text-sm text-right" style="min-width: 56rem;">
            <thead>
                <tr style="color: var(--sa-text-dim);">
                    <th scope="col" class="py-2.5 px-5 font-semibold">سالن</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">مدیر</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">پلن</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">کارشناس</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">اشتراک</th>
                    <th scope="col" class="py-2.5 px-3 font-semibold">وضعیت</th>
                    <th scope="col" class="py-2.5 px-5"><span class="sa-sr">کارها</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salons as $salon)
                    @php $owner = $salon->admins->firstWhere('pivot.role', 'owner'); @endphp
                    <tr style="border-top: 1px solid var(--sa-border);" data-salon-row="{{ $salon->slug }}">
                        <td class="py-3 px-5">
                            <a href="{{ route('superadmin.salons.edit', $salon) }}" class="font-bold" style="color: var(--sa-text);">{{ $salon->name }}</a>
                            <div><a href="{{ $salon->publicUrl() }}" target="_blank" rel="noopener" class="sa-ltr" style="color: var(--sa-accent);">{{ \App\Support\SalonAddress::short($salon) }}</a></div>
                        </td>
                        <td class="py-3 px-3">
                            {{ $owner?->name ?? '—' }}
                            @if ($owner?->phone)<div class="text-xs" style="color: var(--sa-text-dim);">{{ \App\Support\Fa::digits($owner->phone) }}</div>@endif
                        </td>
                        @include('superadmin.partials.salon-cells', ['cells' => ['plan', 'experts', 'subscription', 'status'], 'withBar' => true])
                        <td class="py-3 px-5">
                            <div class="flex gap-1.5 justify-end items-center">
                                <a href="{{ route('superadmin.salons.edit', $salon) }}" class="sa-btn-ghost">ویرایش</a>
                                <x-sa.row-menu :label="'کارهای بیشتر برای '.$salon->name">
                                    <span class="sa-menu__group">تمدید اشتراک</span>
                                    @foreach ($periods as $period => $periodLabel)
                                        <form method="POST" action="{{ route('superadmin.salons.renew', $salon) }}"
                                              data-ui-confirm="تمدید {{ $periodLabel }} اشتراک «{{ \App\Support\Fa::digits($salon->name) }}»؟ یک فاکتور دستی (بی پرداخت آنلاین) ثبت می‌شود." data-ui-once>
                                            @csrf
                                            <input type="hidden" name="subscription_type" value="{{ $period }}">
                                            <button type="submit" role="menuitem" class="sa-menu__item" data-renew="{{ $period }}">{{ $periodLabel }}</button>
                                        </form>
                                    @endforeach
                                    @if ($salon->slug !== 'rasta')
                                        <span class="sa-menu__sep" role="separator"></span>
                                        <form method="POST" action="{{ route('superadmin.salons.toggle-suspend', $salon) }}"
                                              @unless ($salon->is_suspended) data-ui-confirm="تعلیق سالن «{{ \App\Support\Fa::digits($salon->name) }}»؟ سایت، رزرو و پنل‌های این سالن تا فعال‌سازی دوباره بسته می‌شوند." @endunless data-ui-once>
                                            @csrf
                                            @if ($salon->is_suspended)
                                                <button type="submit" role="menuitem" class="sa-menu__item" data-toggle-suspend="resume">فعال‌سازی دوباره‌ی سالن</button>
                                            @else
                                                <button type="submit" role="menuitem" class="sa-menu__item sa-menu__item--danger" data-toggle-suspend="suspend">تعلیق سالن…</button>
                                            @endif
                                        </form>
                                    @endif
                                </x-sa.row-menu>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">{{ $hasFilters ? 'هیچ سالنی با این جستجو و فیلترها پیدا نشد.' : 'هنوز سالنی ثبت نشده است.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if ($salons->total() > 0)
            <div class="sa-section-foot flex flex-wrap items-center justify-between gap-3">
                <span>نمایش {{ fa_num($salons->firstItem()) }} تا {{ fa_num($salons->lastItem()) }} از {{ fa_num($salons->total()) }}</span>
                @if ($salons->hasPages())<div>{{ $salons->links('pagination::simple-tailwind') }}</div>@endif
            </div>
        @endif
    </section>
@endsection
