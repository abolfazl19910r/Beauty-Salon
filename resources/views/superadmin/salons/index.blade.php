@extends('layouts.superadmin')

@section('title', 'مدیریت سالن‌ها')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-lg">همه‌ی سالن‌ها</h2>
        <a href="{{ route('superadmin.salons.create') }}" class="sa-btn">+ سالن جدید</a>
    </div>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="py-3 px-4">نام / آدرس</th>
                    <th class="py-3 px-4">ادمین</th>
                    <th class="py-3 px-4">سقف / مصرف متخصص</th>
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
                        <td class="py-3 px-4">{{ $salon->subscription_ends_at->format('Y-m-d') }}</td>
                        <td class="py-3 px-4">
                            @if ($salon->is_suspended)
                                <span style="color: var(--sa-danger);">تعلیق‌شده</span>
                            @elseif ($salon->subscription_ends_at->isPast())
                                <span style="color: var(--sa-danger);">منقضی</span>
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
                        <td colspan="6" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">هنوز سالنی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $salons->links() }}</div>
@endsection
