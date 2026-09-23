@extends('layouts.superadmin')

@section('title', 'مدیریت سالن‌ها')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-lg">همه‌ی سالن‌ها</h2>
        <a href="{{ route('superadmin.salons.create') }}" class="sa-btn">+ سالن جدید</a>
    </div>

    {{-- ⭐ جستجو و فیلتر (۲۰۲۶-۰۹-۲۴) — SalonListFilter. --}}
    @php $hasFilters = count($filters) > 0; @endphp
    <form method="GET" action="{{ route('superadmin.salons.index') }}" class="sa-card p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="sa-label" for="q">جستجو</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="sa-input"
                       placeholder="نام یا آدرس اختصاصی سالن، نام یا موبایل ادمین، تلفن یا نشانی سالن">
            </div>
            <div>
                <label class="sa-label" for="status">وضعیت</label>
                <select id="status" name="status" class="sa-input">
                    <option value="">همه</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="subscription_type">پلن فعلی</label>
                <select id="subscription_type" name="subscription_type" class="sa-input">
                    <option value="">همه</option>
                    @foreach (['1m' => 'یک‌ماهه', '3m' => 'سه‌ماهه', '6m' => 'شش‌ماهه', '12m' => 'یک‌ساله'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['subscription_type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sa-label" for="quota">سقف متخصص</label>
                <select id="quota" name="quota" class="sa-input">
                    <option value="">همه</option>
                    <option value="full" @selected(($filters['quota'] ?? null) === 'full')>پر شده</option>
                    <option value="available" @selected(($filters['quota'] ?? null) === 'available')>ظرفیت خالی دارد</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="sa-label" for="started_from">«از تاریخ» از</label>
                    <input id="started_from" name="started_from" value="{{ $filters['started_from'] ?? '' }}" class="sa-input" placeholder="۱۴۰۵/۰۱/۰۱" dir="ltr">
                </div>
                <div>
                    <label class="sa-label" for="started_to">تا</label>
                    <input id="started_to" name="started_to" value="{{ $filters['started_to'] ?? '' }}" class="sa-input" placeholder="۱۴۰۵/۱۲/۲۹" dir="ltr">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="sa-label" for="ends_from">«اشتراک تا» از</label>
                    <input id="ends_from" name="ends_from" value="{{ $filters['ends_from'] ?? '' }}" class="sa-input" placeholder="۱۴۰۵/۰۷/۰۱" dir="ltr">
                </div>
                <div>
                    <label class="sa-label" for="ends_to">تا</label>
                    <input id="ends_to" name="ends_to" value="{{ $filters['ends_to'] ?? '' }}" class="sa-input" placeholder="۱۴۰۵/۰۷/۳۰" dir="ltr">
                </div>
            </div>
            <div>
                <label class="sa-label" for="sort">مرتب‌سازی</label>
                <select id="sort" name="sort" class="sa-input">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'name') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-4">
                <button type="submit" class="sa-btn">اعمال فیلتر</button>
                @if ($hasFilters)
                    <a href="{{ route('superadmin.salons.index') }}" style="color: var(--sa-text-dim);">حذف فیلترها</a>
                @endif
                <span class="mr-auto text-sm" style="color: var(--sa-text-dim);">{{ to_persian_num((string) $salons->total()) }} سالن</span>
            </div>
        </div>
        @if ($errors->any())
            <div class="mt-3 text-sm" style="color: var(--sa-danger);">{{ $errors->first() }}</div>
        @endif
    </form>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="py-3 px-4">نام / آدرس</th>
                    <th class="py-3 px-4">ادمین</th>
                    <th class="py-3 px-4">سقف / مصرف متخصص</th>
                    <th class="py-3 px-4">از تاریخ</th>
                    <th class="py-3 px-4">اشتراک تا</th>
                    <th class="py-3 px-4">وضعیت</th>
                    <th class="py-3 px-4">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salons as $salon)
                    <tr style="border-bottom: 1px solid var(--sa-border);">
                        <td class="py-3 px-4">
                            <div class="font-medium">{{ $salon->name }}</div>
                            <div style="color: var(--sa-text-dim);">/s/{{ $salon->slug }}</div>
                        </td>
                        <td class="py-3 px-4">
                            {{ optional($salon->admins->firstWhere('pivot.role', 'owner'))->name ?? '—' }}
                        </td>
                        <td class="py-3 px-4">{{ $salon->specialists_count }} / {{ $salon->max_specialists_count }}</td>
                        <td class="py-3 px-4">{{ $salon->subscription_started_at ? jalali_date($salon->subscription_started_at) : '—' }}</td>
                        <td class="py-3 px-4">{{ jalali_date($salon->subscription_ends_at) }}</td>
                        <td class="py-3 px-4">
                            @if ($salon->is_suspended)
                                <span style="color: var(--sa-danger);">تعلیق‌شده</span>
                            @elseif ($salon->subscription_ends_at->isPast())
                                <span style="color: var(--sa-danger);">منقضی</span>
                            @elseif ($salon->isOnTrial())
                                <span style="color: var(--sa-accent);">آزمایشی ({{ to_persian_num((string) $salon->trialDaysLeft()) }} روز)</span>
                            @else
                                <span style="color: var(--sa-success);">فعال</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('superadmin.salons.edit', $salon) }}" style="color: var(--sa-accent);">ویرایش</a>

                                <form method="POST" action="{{ route('superadmin.salons.renew', $salon) }}" class="flex items-center gap-1">
                                    @csrf
                                    <select name="subscription_type" class="sa-input" style="padding: 0.3rem 0.5rem; width: auto;">
                                        <option value="1m">۱ ماه</option>
                                        <option value="3m">۳ ماه</option>
                                        <option value="6m">۶ ماه</option>
                                        <option value="12m">۱۲ ماه</option>
                                    </select>
                                    <button type="submit" style="color: var(--sa-success);">تمدید</button>
                                </form>

                                @if ($salon->slug !== 'rasta')
                                    <form method="POST" action="{{ route('superadmin.salons.toggle-suspend', $salon) }}">
                                        @csrf
                                        <button type="submit" style="color: var(--sa-danger);">
                                            {{ $salon->is_suspended ? 'فعال‌سازی' : 'تعلیق' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">{{ $hasFilters ? 'هیچ سالنی با این فیلترها پیدا نشد.' : 'هنوز سالنی ثبت نشده است.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $salons->links() }}</div>
@endsection
