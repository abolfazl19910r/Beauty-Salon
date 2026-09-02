@extends('layouts.superadmin')

@section('title', 'داشبورد')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="sa-card p-5">
            <div class="sa-label">سالن‌های فعال</div>
            <div class="text-2xl font-bold">{{ $stats['active_salons'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">مجموع متخصصین</div>
            <div class="text-2xl font-bold">{{ $stats['total_specialists'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">نزدیک به انقضا (۷ روز)</div>
            <div class="text-2xl font-bold" style="color: var(--sa-accent);">{{ $stats['expiring_soon'] }}</div>
        </div>
        <div class="sa-card p-5">
            <div class="sa-label">منقضی‌شده</div>
            <div class="text-2xl font-bold" style="color: var(--sa-danger);">{{ $stats['expired'] }}</div>
        </div>
    </div>

    <div class="sa-card p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">سالن‌های اخیر</h2>
            <a href="{{ route('superadmin.salons.index') }}" class="text-sm" style="color: var(--sa-accent);">مشاهده همه ←</a>
        </div>
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim);" class="border-b" style="border-color: var(--sa-border);">
                    <th class="py-2">نام سالن</th>
                    <th class="py-2">آدرس</th>
                    <th class="py-2">متخصص</th>
                    <th class="py-2">اشتراک تا</th>
                    <th class="py-2">وضعیت</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentSalons as $salon)
                    <tr class="border-b" style="border-color: var(--sa-border);">
                        <td class="py-3">{{ $salon->name }}</td>
                        <td class="py-3" style="color: var(--sa-text-dim);">/s/{{ $salon->slug }}</td>
                        <td class="py-3">{{ $salon->specialists_count }} / {{ $salon->max_specialists_count }}</td>
                        <td class="py-3">{{ $salon->subscription_ends_at->format('Y-m-d') }}</td>
                        <td class="py-3">
                            @if ($salon->is_suspended)
                                <span style="color: var(--sa-danger);">تعلیق‌شده</span>
                            @elseif ($salon->subscription_ends_at->isPast())
                                <span style="color: var(--sa-danger);">منقضی</span>
                            @else
                                <span style="color: var(--sa-success);">فعال</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
