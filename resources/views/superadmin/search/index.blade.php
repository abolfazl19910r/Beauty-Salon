@extends('layouts.superadmin')

@section('title', 'جستجوی شماره')

@section('content')
    <form method="GET" class="sa-card p-4 mb-5 flex flex-wrap gap-3 items-end">
        <div class="flex-1" style="min-width: 16rem;">
            <label class="sa-label" for="q">شماره‌ی موبایل (کامل یا بخشی از آن، دست‌کم ۴ رقم)</label>
            <input id="q" name="q" dir="ltr" class="sa-input" value="{{ $q }}" placeholder="09121234567" autofocus>
        </div>
        <button type="submit" class="sa-btn">جستجو در همه‌ی سالن‌ها</button>
    </form>

    @if ($query !== '' && strlen($query) < 4)
        <div class="sa-card p-6 text-center" style="color: var(--sa-text-dim);">دست‌کم ۴ رقم وارد کنید.</div>
    @elseif (strlen($query) >= 4)
        @php
            $roleLabel = function ($user) {
                if ($user->roles->contains('name', 'super-admin')) return 'ابرمدیر';
                $pivot = $user->salons->first()?->pivot?->role;
                if ($pivot === 'owner') return 'مالک سالن';
                if ($pivot) return 'کادر مدیریت';
                if ($user->roles->contains('name', 'specialist')) return 'متخصص';
                return $user->user_type === 'customer' ? 'مشتری' : 'کادر';
            };
            $total = $results['users']->count() + $results['specialists']->count() + $results['demos']->count();
        @endphp
        @if ($total === 0)
            <div class="sa-card p-6 text-center" style="color: var(--sa-text-dim);">با شماره‌ی <span dir="ltr">{{ $query }}</span> چیزی پیدا نشد.</div>
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
                            <td class="py-2 px-4" dir="ltr" style="text-align: right;">{{ $user->phone }}</td>
                            <td class="py-2 px-4"><span class="sa-badge" style="margin: 0;">{{ $roleLabel($user) }}</span></td>
                            <td class="py-2 px-4">
                                @if ($salon)<a href="{{ route('superadmin.salons.edit', $salon->id) }}" style="color: var(--sa-accent);">{{ $salon->name }}</a>@else — @endif
                            </td>
                            <td class="py-2 px-4" style="color: var(--sa-text-dim);">{{ $user->created_at ? jalali_date($user->created_at) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($results['specialists']->isNotEmpty())
            <div class="sa-card mb-5 overflow-x-auto">
                <h2 class="font-bold px-5 pt-4 pb-2">متخصص‌ها ({{ to_persian_num((string) $results['specialists']->count()) }})</h2>
                <table class="w-full text-sm text-right">
                    <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">نام</th><th class="py-2 px-4">موبایل</th><th class="py-2 px-4">سالن</th><th class="py-2 px-4">وضعیت</th></tr></thead>
                    <tbody>
                    @foreach ($results['specialists'] as $sp)
                        <tr style="border-top: 1px solid var(--sa-border);">
                            <td class="py-2 px-4 font-bold">{{ $sp->name }}</td>
                            <td class="py-2 px-4" dir="ltr" style="text-align: right;">{{ $sp->phone }}</td>
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
                            <td class="py-2 px-4">{{ jalali_date($demo->created_at) }}</td>
                            <td class="py-2 px-4">{{ $demo->handled_at ? 'پیگیری‌شده' : 'پیگیری‌نشده' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
@endsection
