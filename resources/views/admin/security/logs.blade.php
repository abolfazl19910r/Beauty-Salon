@extends('layouts.admin')
@section('title', 'لاگ‌های امنیتی')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    لاگ‌های امنیتی
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    تاریخچه‌ی تلاش‌های ورود، فعالیت‌های مشکوک و تغییرات حساس کل کاربران سایت.
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.security.users') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    وضعیت کاربران
                </a>
                <a href="{{ route('admin.security.settings') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    تنظیمات
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">رخداد (۳۰ روز اخیر)</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:var(--admin-text);">{{ $stats['logs_last_30_days'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">هشدار (۳۰ روز اخیر)</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#D97706;">{{ $stats['warnings_last_30_days'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">ورود ناموفق (۲۴ ساعت اخیر)</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#DC2626;">{{ $stats['failed_logins_last_24h'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">کاربران با ۲FA فعال</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#16A34A;">{{ $stats['users_with_2fa'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.security.logs') }}"
              class="rounded-xl p-4 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end"
              style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div>
                <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">نوع رخداد</label>
                <select name="event" class="w-full rounded-lg px-3 py-2 text-sm" style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                    <option value="">همه</option>
                    @foreach(['login_attempt' => 'تلاش برای ورود', 'session_terminated' => 'پایان یک نشست', 'all_sessions_terminated' => 'پایان تمام نشست‌ها', 'payment_attempt' => 'تلاش برای پرداخت امن', 'profile_change' => 'تغییر پروفایل'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">سطح</label>
                <select name="level" class="w-full rounded-lg px-3 py-2 text-sm" style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                    <option value="">همه</option>
                    <option value="info" @selected(request('level') === 'info')>عادی</option>
                    <option value="warning" @selected(request('level') === 'warning')>هشدار</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">از تاریخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-lg px-3 py-2 text-sm" style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">تا تاریخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg px-3 py-2 text-sm" style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white self-end" style="background-color: var(--admin-accent);">فیلتر</button>
            </div>
        </form>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background-color: var(--admin-accent-light);">
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">کاربر</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">رخداد</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">سطح</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">IP</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr style="border-top: 1px solid var(--admin-border);">
                            <td class="px-4 py-3" style="color:var(--admin-text);">
                                @if($log->user)
                                    {{ $log->user->name }}
                                    <span class="text-xs persian-number" style="color:var(--admin-text-light);" dir="ltr">{{ $log->user->phone }}</span>
                                @else
                                    <span style="color:var(--admin-text-light);">ناشناس</span>
                                @endif
                            </td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">{{ $log->event_label }}</td>
                            <td class="px-4 py-3">
                                @if($log->level === 'warning')
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:rgba(217,119,6,0.12); color:#D97706;">هشدار</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:rgba(22,163,74,0.12); color:#16A34A;">عادی</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number" dir="ltr" style="color:var(--admin-text-dim);">{{ $log->ip_address ?: '—' }}</td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ jalali_date($log->created_at, 'Y/m/d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center" style="color:var(--admin-text-light);">لاگی با این فیلترها یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
@endsection
