@extends('layouts.superadmin')

@section('title', 'جستجو')

@section('content')
    {{-- بسته‌ی ۱ ظاهری: جستجوی سراسری نوار بالا (سالن، شماره‌ی موبایل، فاکتور) — پیش‌تر فقط «جستجوی شماره» --}}
    <form method="GET" class="sa-card p-4 mb-5 flex flex-wrap gap-3 items-end" role="search">
        <div class="flex-1" style="min-width: 16rem;">
            <label class="sa-label" for="q">نام یا آدرس سالن، شماره‌ی موبایل (دست‌کم ۴ رقم) یا شماره‌ی فاکتور / کد پیگیری</label>
            <input id="q" name="q" type="search" class="sa-input" value="{{ $q }}" placeholder="مثلاً راستا یا 09121234567" autofocus>
        </div>
        <button type="submit" class="sa-btn">جستجو در همه‌ی سالن‌ها</button>
    </form>

    @php
        $total = collect($results)->sum(fn ($c) => $c->count());
        $tooShort = $numeric ? strlen($query) < 4 && $results['invoices']->isEmpty() : mb_strlen(trim((string) $q)) < 2;
    @endphp
    @if (trim((string) $q) !== '' && $tooShort && $total === 0)
        <div class="sa-card p-6 text-center" style="color: var(--sa-text-dim);">{{ $numeric ? 'دست‌کم ۴ رقم وارد کنید.' : 'دست‌کم دو حرف وارد کنید.' }}</div>
    @elseif (trim((string) $q) !== '')
        @php
            $roleLabel = function ($user) {
                if ($user->roles->contains('name', 'super-admin')) return 'ابرمدیر';
                $pivot = $user->salons->first()?->pivot?->role;
                if ($pivot === 'owner') return 'مالک سالن';
                if ($pivot) return 'کادر مدیریت';
                if ($user->roles->contains('name', 'specialist')) return 'کارشناس';
                return $user->user_type === 'customer' ? 'مشتری' : 'کادر';
            };
        @endphp
        @if ($total === 0)
            <div class="sa-card p-6 text-center" style="color: var(--sa-text-dim);">برای «<bdi>{{ $q }}</bdi>» چیزی پیدا نشد.</div>
        @endif

        @if ($results['salons']->isNotEmpty())
            <div class="sa-card mb-5 overflow-x-auto" data-search-salons>
                <h2 class="font-bold px-5 pt-4 pb-2">سالن‌ها ({{ fa_num($results['salons']->count()) }})</h2>
                <table class="w-full text-sm text-right">
                    <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">سالن</th><th class="py-2 px-4">مالک</th><th class="py-2 px-3">پلن</th><th class="py-2 px-3">اشتراک</th><th class="py-2 px-3">وضعیت</th></tr></thead>
                    <tbody>
                    @foreach ($results['salons'] as $salon)
                        @php $owner = $salon->admins->firstWhere('pivot.role', 'owner'); @endphp
                        <tr style="border-top: 1px solid var(--sa-border);" data-search-salon>
                            <td class="py-2 px-4"><a href="{{ route('superadmin.salons.edit', $salon) }}" class="font-bold" style="color: var(--sa-accent);">{{ $salon->name }}</a>
                                <div><span class="sa-ltr" style="color: var(--sa-text-dim);">{{ \App\Support\SalonAddress::short($salon) }}</span></div></td>
                            <td class="py-2 px-4">{{ $owner?->name ?? '—' }}@if ($owner?->phone)<div class="text-xs" style="color: var(--sa-text-dim);">{{ \App\Support\Fa::digits($owner->phone) }}</div>@endif</td>
                            @include('superadmin.partials.salon-cells', ['cells' => ['plan', 'subscription', 'status']])
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($results['invoices']->isNotEmpty())
            <div class="sa-card mb-5 overflow-x-auto" data-search-invoices>
                <h2 class="font-bold px-5 pt-4 pb-2">فاکتورها ({{ fa_num($results['invoices']->count()) }})</h2>
                <table class="w-full text-sm text-right">
                    <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">فاکتور</th><th class="py-2 px-4">سالن</th><th class="py-2 px-3">مبلغ</th><th class="py-2 px-3">وضعیت</th><th class="py-2 px-3">کد پیگیری</th><th class="py-2 px-3">تاریخ</th></tr></thead>
                    <tbody>
                    @foreach ($results['invoices'] as $invoice)
                        <tr style="border-top: 1px solid var(--sa-border);" data-search-invoice>
                            <td class="py-2 px-4"><a href="{{ route('superadmin.payments.show', $invoice) }}" class="font-bold" style="color: var(--sa-accent);">#{{ fa_num($invoice->id) }}</a></td>
                            <td class="py-2 px-4">{{ $invoice->salon?->name ?? '—' }}</td>
                            <td class="py-2 px-3">{{ fa_money($invoice->amount, true) }}</td>
                            <td class="py-2 px-3"><x-sa.pill :tone="['paid' => 'success', 'failed' => 'danger'][$invoice->status] ?? 'warn'">{{ ['paid' => 'پرداخت‌شده', 'pending' => 'در انتظار', 'failed' => 'ناموفق'][$invoice->status] ?? $invoice->status }}</x-sa.pill></td>
                            <td class="py-2 px-3"><span class="sa-ltr">{{ $invoice->ref_id ?? '—' }}</span></td>
                            <td class="py-2 px-3">{{ fa_date($invoice->paid_at ?? $invoice->created_at) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($results['users']->isNotEmpty())
            <div class="sa-card mb-5 overflow-x-auto">
                <h2 class="font-bold px-5 pt-4 pb-2">حساب‌های کاربری ({{ to_persian_num((string) $results['users']->count()) }})</h2>
                <table class="w-full text-sm text-right">
                    <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">نام</th><th class="py-2 px-4">موبایل</th><th class="py-2 px-4">نقش</th><th class="py-2 px-4">سالن</th><th class="py-2 px-4">عضویت</th></tr></thead>
                    <tbody>
                    @foreach ($results['users'] as $user)
                        @php $salon = $user->salons->first() ?? ($user->salon_id ? (object) ['id' => $user->salon_id, 'name' => $salons[$user->salon_id] ?? '#'.$user->salon_id] : null); @endphp
                        <tr style="border-top: 1px solid var(--sa-border);" data-search-user>
                            <td class="py-2 px-4 font-bold">{{ $user->name }}</td>
                            <td class="py-2 px-4" dir="ltr" style="text-align: right;">{{ \App\Support\Fa::digits($user->phone) }}</td>
                            <td class="py-2 px-4"><span class="sa-badge" style="margin: 0;">{{ $roleLabel($user) }}</span></td>
                            <td class="py-2 px-4">
                                @if ($salon)<a href="{{ route('superadmin.salons.edit', $salon->id) }}" style="color: var(--sa-accent);">{{ $salon->name }}</a>@else — @endif
                            </td>
                            <td class="py-2 px-4" style="color: var(--sa-text-dim);">{{ $user->created_at ? fa_date($user->created_at) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($results['specialists']->isNotEmpty())
            <div class="sa-card mb-5 overflow-x-auto">
                <h2 class="font-bold px-5 pt-4 pb-2">کارشناسان ({{ to_persian_num((string) $results['specialists']->count()) }})</h2>
                <table class="w-full text-sm text-right">
                    <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">نام</th><th class="py-2 px-4">موبایل</th><th class="py-2 px-4">سالن</th><th class="py-2 px-4">وضعیت</th></tr></thead>
                    <tbody>
                    @foreach ($results['specialists'] as $sp)
                        <tr style="border-top: 1px solid var(--sa-border);">
                            <td class="py-2 px-4 font-bold">{{ $sp->name }}</td>
                            <td class="py-2 px-4" dir="ltr" style="text-align: right;">{{ \App\Support\Fa::digits($sp->phone) }}</td>
                            <td class="py-2 px-4"><a href="{{ route('superadmin.salons.edit', $sp->salon_id) }}" style="color: var(--sa-accent);">{{ $salons[$sp->salon_id] ?? '#'.$sp->salon_id }}</a></td>
                            <td class="py-2 px-4">{{ $sp->trashed() ? 'حذف‌شده' : 'فعال' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($results['demos']->isNotEmpty())
            <div class="sa-card overflow-x-auto">
                <h2 class="font-bold px-5 pt-4 pb-2">درخواست‌های دمو ({{ to_persian_num((string) $results['demos']->count()) }})</h2>
                <table class="w-full text-sm text-right">
                    <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">نام</th><th class="py-2 px-4">سالن</th><th class="py-2 px-4">زمان</th><th class="py-2 px-4">پیگیری</th></tr></thead>
                    <tbody>
                    @foreach ($results['demos'] as $demo)
                        <tr style="border-top: 1px solid var(--sa-border);">
                            <td class="py-2 px-4 font-bold">{{ $demo->name }}</td>
                            <td class="py-2 px-4">{{ $demo->salon_name ?: '—' }}</td>
                            <td class="py-2 px-4">{{ fa_date($demo->created_at) }}</td>
                            <td class="py-2 px-4">{{ $demo->handled_at ? 'پیگیری‌شده' : 'پیگیری‌نشده' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
@endsection
