@extends('layouts.admin')
@section('title', 'وضعیت امنیتی کاربران')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    وضعیت امنیتی کاربران
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    فعالیت مشکوک اخیر، وضعیت تایید دو مرحله‌ای، و آخرین ورود موفق هر کاربر.
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.security.logs') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    لاگ‌های خام
                </a>
                <a href="{{ route('admin.security.settings') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    تنظیمات
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.security.users') }}" class="mb-5 flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="جست‌وجو بر اساس نام یا شماره موبایل..."
                   class="flex-1 rounded-lg px-3 py-2 text-sm" style="background:var(--admin-surface); border:1px solid var(--admin-border); color:var(--admin-text);">
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white" style="background-color: var(--admin-accent);">جست‌وجو</button>
        </form>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background-color: var(--admin-accent-light);">
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">کاربر</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">۲FA</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">فعالیت مشکوک (۳۰ روز)</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">آخرین ورود موفق</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr style="border-top: 1px solid var(--admin-border);">
                            <td class="px-4 py-3" style="color:var(--admin-text);">
                                {{ $user->name }}
                                <span class="text-xs persian-number block" style="color:var(--admin-text-light);" dir="ltr">{{ $user->phone }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->two_factor_enabled)
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:rgba(22,163,74,0.12); color:#16A34A;">فعال</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:var(--admin-accent-light); color:var(--admin-text-dim);">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number" style="color: {{ $user->suspicious_activity_count > 2 ? '#DC2626' : 'var(--admin-text-dim)' }};">
                                {{ $user->suspicious_activity_count }}
                            </td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                                {{ $user->last_successful_login_at ? jalali_date($user->last_successful_login_at, 'Y/m/d H:i') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center" style="color:var(--admin-text-light);">کاربری یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
@endsection
