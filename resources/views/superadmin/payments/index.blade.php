@extends('layouts.superadmin')

@section('title', 'کیف پول و درآمد اشتراک')

{{--
    ⭐ بخش «کیف پول / درآمد اشتراک» سوپرادمین (۲۰۲۶-۰۹-۲۴) — SuperAdminPaymentController.
    بالا: کارت‌های کلی (مستقل از فیلتر). وسط: فیلترها. بعد: خلاصه‌ی همون مجموعه‌ی فیلترشده و جدول.
--}}
@php
    $labels = \App\Http\Controllers\SuperAdmin\SuperAdminPaymentController::labels();
    $toman = fn ($n) => to_persian_num(number_format((int) $n));
    $hasFilters = count($filters) > 0;
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="font-bold text-lg">کیف پول و درآمد اشتراک سالن‌ها</h2>
        <a href="{{ route('superadmin.payments.export', request()->query()) }}" class="sa-btn">خروجی CSV (با همین فیلترها)</a>
    </div>

    {{-- کارت‌های کلی --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ([
            ['کل درآمد (همه‌ی زمان‌ها)', $toman($overview['all_time']).' تومان'],
            ['درآمد این ماه (شمسی)', $toman($overview['this_month']).' تومان'],
            ['درآمد امروز', $toman($overview['today']).' تومان'],
            ['پرداخت‌های در انتظار', to_persian_num((string) $overview['pending_now']).' فاکتور'],
        ] as [$title, $value])
            <div class="sa-card p-4">
                <div class="text-xs" style="color: var(--sa-text-dim);">{{ $title }}</div>
                <div class="text-lg font-bold mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- فیلترها --}}
    <form method="GET" action="{{ route('superadmin.payments.index') }}" class="sa-card p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="sa-label" for="q">جستجو</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="sa-input"
                       placeholder="نام یا آدرس سالن، کد پیگیری، Authority، شماره فاکتور (#۱۲)، نام یا موبایل ثبت‌کننده">
            </div>
            <div>
                <label class="sa-label" for="salon_id">سالن</label>
                <select id="salon_id" name="salon_id" class="sa-input">
                    <option value="">همه‌ی سالن‌ها</option>
                    @foreach ($salons as $s)
                        <option value="{{ $s->id }}" @selected(($filters['salon_id'] ?? null) == $s->id)>{{ $s->name }} ({{ $s->slug }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="status">وضعیت</label>
                <select id="status" name="status" class="sa-input">
                    <option value="">همه</option>
                    @foreach ($labels['statuses'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="payment_method">روش پرداخت</label>
                <select id="payment_method" name="payment_method" class="sa-input">
                    <option value="">همه</option>
                    @foreach ($labels['methods'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_method'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="subscription_type">پلن</label>
                <select id="subscription_type" name="subscription_type" class="sa-input">
                    <option value="">همه</option>
                    @foreach ($labels['plans'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['subscription_type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="date_field">تاریخ بر اساس</label>
                <select id="date_field" name="date_field" class="sa-input">
                    <option value="created_at" @selected(($filters['date_field'] ?? 'created_at') === 'created_at')>تاریخ ایجاد فاکتور</option>
                    <option value="paid_at" @selected(($filters['date_field'] ?? null) === 'paid_at')>تاریخ پرداخت</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="sa-label" for="date_from">از تاریخ</label>
                    <input id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sa-input" placeholder="۱۴۰۵/۰۷/۰۱" dir="ltr">
                </div>
                <div>
                    <label class="sa-label" for="date_to">تا تاریخ</label>
                    <input id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="sa-input" placeholder="۱۴۰۵/۰۷/۳۰" dir="ltr">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="sa-label" for="amount_min">حداقل مبلغ (تومان)</label>
                    <input id="amount_min" name="amount_min" type="number" min="0" value="{{ $filters['amount_min'] ?? '' }}" class="sa-input" dir="ltr">
                </div>
                <div>
                    <label class="sa-label" for="amount_max">حداکثر مبلغ (تومان)</label>
                    <input id="amount_max" name="amount_max" type="number" min="0" value="{{ $filters['amount_max'] ?? '' }}" class="sa-input" dir="ltr">
                </div>
            </div>
            <div>
                <label class="sa-label" for="sort">مرتب‌سازی</label>
                <select id="sort" name="sort" class="sa-input">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="sa-btn">اعمال فیلتر</button>
                @if ($hasFilters)
                    <a href="{{ route('superadmin.payments.index') }}" style="color: var(--sa-text-dim);">حذف فیلترها</a>
                @endif
            </div>
        </div>
        @if ($errors->any())
            <div class="mt-3 text-sm" style="color: var(--sa-danger);">{{ $errors->first() }}</div>
        @endif
    </form>

    {{-- خلاصه‌ی مجموعه‌ی فیلترشده --}}
    <div class="sa-card p-4 mb-6">
        <div class="text-sm font-bold mb-3">
            خلاصه‌ی {{ $hasFilters ? 'نتایج فیلترشده' : 'همه‌ی فاکتورها' }}
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><span style="color: var(--sa-text-dim);">جمع پرداخت‌شده:</span> <b>{{ $toman($summary['paid_sum']) }}</b> تومان</div>
            <div><span style="color: var(--sa-text-dim);">آنلاین:</span> {{ $toman($summary['online_sum']) }} — <span style="color: var(--sa-text-dim);">دستی:</span> {{ $toman($summary['manual_sum']) }}</div>
            <div>
                <span style="color: var(--sa-text-dim);">تعداد:</span> {{ to_persian_num((string) $summary['total_count']) }}
                (<span style="color: var(--sa-success);">{{ to_persian_num((string) $summary['paid_count']) }} موفق</span>،
                {{ to_persian_num((string) $summary['pending_count']) }} در انتظار،
                <span style="color: var(--sa-danger);">{{ to_persian_num((string) $summary['failed_count']) }} ناموفق</span>)
            </div>
            <div><span style="color: var(--sa-text-dim);">سالن‌های پرداخت‌کننده:</span> {{ to_persian_num((string) $summary['paying_salons']) }}</div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm">
            @foreach ($summary['by_plan'] as $type => $row)
                <div class="rounded p-2" style="background: var(--sa-bg);">
                    <div style="color: var(--sa-text-dim);">{{ $labels['plans'][$type] }}</div>
                    <div>{{ to_persian_num((string) $row['count']) }} خرید — {{ $toman($row['total']) }} تومان</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- جدول --}}
    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="py-3 px-3">#</th>
                    <th class="py-3 px-3">سالن</th>
                    <th class="py-3 px-3">پلن</th>
                    <th class="py-3 px-3">مبلغ (تومان)</th>
                    <th class="py-3 px-3">روش</th>
                    <th class="py-3 px-3">وضعیت</th>
                    <th class="py-3 px-3">کد پیگیری</th>
                    <th class="py-3 px-3">ثبت‌کننده</th>
                    <th class="py-3 px-3">ایجاد</th>
                    <th class="py-3 px-3">پرداخت</th>
                    <th class="py-3 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr style="border-bottom: 1px solid var(--sa-border);">
                        <td class="py-3 px-3 persian-number">{{ to_persian_num((string) $invoice->id) }}</td>
                        <td class="py-3 px-3">
                            <div class="font-medium">{{ $invoice->salon?->name ?? '—' }}</div>
                            <div class="text-xs" style="color: var(--sa-text-dim);" dir="ltr">{{ $invoice->salon?->slug }}</div>
                        </td>
                        <td class="py-3 px-3">{{ $labels['plans'][$invoice->subscription_type] ?? $invoice->subscription_type }}</td>
                        <td class="py-3 px-3">{{ $toman($invoice->amount) }}</td>
                        <td class="py-3 px-3">{{ $labels['methods'][$invoice->payment_method] ?? $invoice->payment_method }}</td>
                        <td class="py-3 px-3">
                            <span style="color: {{ ['paid' => 'var(--sa-success)', 'failed' => 'var(--sa-danger)'][$invoice->status] ?? 'var(--sa-accent)' }};">
                                {{ $labels['statuses'][$invoice->status] ?? $invoice->status }}
                            </span>
                        </td>
                        <td class="py-3 px-3" dir="ltr">{{ $invoice->ref_id ?? '—' }}</td>
                        <td class="py-3 px-3">{{ $invoice->createdBy?->name ?? '—' }}</td>
                        <td class="py-3 px-3 persian-number">{{ jalali_date($invoice->created_at, 'Y/m/d H:i') }}</td>
                        <td class="py-3 px-3 persian-number">{{ $invoice->paid_at ? jalali_date($invoice->paid_at, 'Y/m/d H:i') : '—' }}</td>
                        <td class="py-3 px-3"><a href="{{ route('superadmin.payments.show', $invoice->id) }}" style="color: var(--sa-accent);">جزئیات</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">
                            {{ $hasFilters ? 'هیچ فاکتوری با این فیلترها پیدا نشد.' : 'هنوز هیچ پرداختی ثبت نشده است.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
