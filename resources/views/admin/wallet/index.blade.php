@extends('layouts.admin')
@section('title', 'کیف پول‌ها')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                مدیریت کیف پول‌ها
            </h1>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('admin.wallet.settle-pending') }}" method="POST"
                      class="flex items-center gap-2 rounded-lg px-3 py-1.5"
                      style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    @csrf
                    <label class="flex items-center gap-1.5 text-xs whitespace-nowrap" style="color:var(--admin-text-dim);">
                        <input type="checkbox" name="ignore_delay" value="1" class="rounded">
                        نادیده گرفتن مهلت تسویه
                    </label>
                    <button type="submit"
                            data-confirm-action
                            data-confirm-message="این عملیات درآمدهای سررسیدشده‌ی در انتظار تسویه‌ی همه‌ی متخصصین (یا در صورت انتخاب گزینه‌ی بالا، حتی سررسیدنشده‌ها) را به موجودی قابل‌برداشت منتقل می‌کند. ادامه می‌دهید؟"
                            data-confirm-text="بله، تسویه شود"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white whitespace-nowrap"
                            style="background:#16A34A;"
                            onmouseover="this.style.background='#15803D'"
                            onmouseout="this.style.background='#16A34A'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        تسویه‌ی دستی همه‌ی در‌انتظارها
                    </button>
                </form>
                <a href="{{ route('admin.wallet.withdrawals') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    درخواست‌های برداشت
                </a>
            </div>
        </div>

        {{-- Statistics cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #3B82F6;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل موجودی متخصصین</p>
                <p class="text-xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($totalBalance) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">تومان</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #16A34A;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل درآمد متخصصین</p>
                <p class="text-xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($totalEarned) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">تاکنون</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #DC2626;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل برداشت‌ها</p>
                <p class="text-xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($totalWithdrawn) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">پرداخت شده</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #F59E0B;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">بلوکه شده</p>
                <p class="text-xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($totalPending) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">در انتظار تسویه</p>
            </div>
        </div>

        {{-- Search --}}
        <div class="rounded-xl p-4 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form method="GET" action="{{ route('admin.wallet.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="نام متخصص یا شماره تماس..."
                           class="w-full text-sm rounded-lg px-3 py-2 outline-none transition"
                           style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);"
                           onfocus="this.style.borderColor='var(--admin-accent)'"
                           onblur="this.style.borderColor='var(--admin-border)'">
                </div>
                <div class="min-w-40">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">مرتب‌سازی</label>
                    <select name="sort_by" class="w-full text-sm rounded-lg px-3 py-2 outline-none"
                            style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        <option value="balance_desc" {{ request('sort_by')=='balance_desc' ? 'selected' : '' }}>بیشترین موجودی</option>
                        <option value="balance_asc"  {{ request('sort_by')=='balance_asc'  ? 'selected' : '' }}>کمترین موجودی</option>
                        <option value="earned_desc"  {{ request('sort_by')=='earned_desc'  ? 'selected' : '' }}>بیشترین درآمد</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">جستجو</button>
                    @if(request()->anyFilled(['search','sort_by']))
                        <a href="{{ route('admin.wallet.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">پاک</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">متخصص</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">موجودی فعلی</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">در انتظار</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">کل درآمد</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">وضعیت شبا</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($wallets as $wallet)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                         style="background:var(--admin-accent); color:#fff;">
                                        {{ mb_substr($wallet->specialist?->name ?? '؟', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium" style="color:var(--admin-text);">{{ $wallet->specialist?->name ?? '—' }}</p>
                                        <p class="text-xs" dir="ltr" style="color:var(--admin-text-dim);">{{ $wallet->specialist?->phone ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold persian-number" style="color:#16A34A;">{{ number_format($wallet->balance) }}</span>
                                <span class="text-xs" style="color:var(--admin-text-light);"> ت</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="persian-number" style="color:#F59E0B;">{{ number_format($wallet->pending_amount ?? 0) }}</span>
                                <span class="text-xs" style="color:var(--admin-text-light);"> ت</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="persian-number" style="color:var(--admin-text-dim);">{{ number_format($wallet->total_earned) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($wallet->iban)
                                    @if($wallet->iban_verified)
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">تایید شده</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:#FFFBEB; color:#92400E;">در انتظار</span>
                                    @endif
                                @else
                                    <span class="text-xs" style="color:var(--admin-text-light);">ثبت نشده</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.wallet.show', $wallet) }}"
                                   class="text-xs px-2.5 py-1 rounded-lg transition-colors"
                                   style="color:var(--admin-accent); background:var(--admin-accent-light);"
                                   onmouseover="this.style.background='var(--admin-border)'"
                                   onmouseout="this.style.background='var(--admin-accent-light)'">جزئیات</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">هیچ کیف پولی یافت نشد</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($wallets->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $wallets->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
