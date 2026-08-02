@extends('layouts.admin')
@section('title', 'کدهای تخفیف')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                        <line x1="7" y1="7" x2="7.01" y2="7"/>
                    </svg>
                    کدهای تخفیف
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    ساخت و مدیریت دستی کد تخفیف — مستقل از کدهایی که مشتریان از طریق تبدیل امتیاز وفاداری می‌سازند.
                </p>
            </div>
            <a href="{{ route('admin.discount-codes.create') }}"
               class="px-4 py-2 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
               style="background-color: var(--admin-accent);">
                + کد تخفیف جدید
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(220,38,38,0.1); color: #DC2626;">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">کل کدها</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:var(--admin-text);">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">فعال و قابل استفاده</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#16A34A;">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">منقضی‌شده</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#DC2626;">{{ $stats['expired'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">ظرفیت تمام‌شده</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#D97706;">{{ $stats['used_up'] }}</p>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background-color: var(--admin-accent-light);">
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">کد</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">نوع / مقدار</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">استفاده</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">اختصاص به</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">انقضا</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($discountCodes as $discountCode)
                        <tr style="border-top: 1px solid var(--admin-border);">
                            <td class="px-4 py-3 font-medium persian-number" dir="ltr" style="color:var(--admin-text);">{{ $discountCode->code }}</td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                                @if($discountCode->type === 'percentage')
                                    {{ number_format((float) $discountCode->amount) }}٪
                                    @if($discountCode->max_amount)
                                        <span class="text-xs">(تا سقف {{ number_format((float) $discountCode->max_amount) }})</span>
                                    @endif
                                @else
                                    {{ number_format((float) $discountCode->amount) }} تومان
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                                {{ $discountCode->used_count }} / {{ $discountCode->max_uses }}
                            </td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">
                                {{ $discountCode->user?->name ?? 'همه‌ی کاربران' }}
                            </td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">
                                {{ $discountCode->expires_at ? jalali_date($discountCode->expires_at) : 'بدون انقضا' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($discountCode->isValid())
                                    <span class="px-2 py-1 rounded-full text-xs" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">فعال</span>
                                @elseif(!$discountCode->is_active)
                                    <span class="px-2 py-1 rounded-full text-xs" style="background-color: rgba(148,163,184,0.15); color: var(--admin-text-dim);">غیرفعال</span>
                                @elseif($discountCode->used_count >= $discountCode->max_uses)
                                    <span class="px-2 py-1 rounded-full text-xs" style="background-color: rgba(217,119,6,0.1); color: #D97706;">ظرفیت تمام</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs" style="background-color: rgba(220,38,38,0.1); color: #DC2626;">منقضی</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.discount-codes.edit', $discountCode) }}"
                                       class="text-xs px-2 py-1 rounded" style="color: var(--admin-accent);">ویرایش</a>
                                    <form action="{{ route('admin.discount-codes.destroy', $discountCode) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-confirm-delete
                                                class="text-xs px-2 py-1 rounded" style="color:#DC2626;">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center" style="color:var(--admin-text-dim);">
                                هنوز کد تخفیفی از این پنل ساخته نشده است.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $discountCodes->links() }}
            </div>
        </div>
    </div>
@endsection
