@extends('layouts.admin')
@section('title', 'مدیریت اطلاعیه‌ها')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    مدیریت اطلاعیه‌ها
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">اطلاعیه‌های سیستم برای کاربران</p>
            </div>
            <a href="{{ route('admin.announcements.create') }}"
               class="px-4 py-2 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
               style="background-color: var(--admin-accent);">
                + اطلاعیه جدید
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">کل اطلاعیه‌ها</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:var(--admin-text);">{{ $totalAnnouncements }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">فعال</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#16A34A;">{{ $activeAnnouncements }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">در انتظار انتشار</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#D97706;">{{ $pendingAnnouncements }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">منقضی‌شده</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#DC2626;">{{ $expiredAnnouncements }}</p>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background-color: var(--admin-accent-light);">
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">عنوان</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">نوع</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">اولویت</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">تاریخ انتشار</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($announcements as $announcement)
                        <tr style="border-top: 1px solid var(--admin-border);">
                            <td class="px-4 py-3" style="color:var(--admin-text);">{{ $announcement->title }}</td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">
                                @switch($announcement->type)
                                    @case('maintenance') تعمیرات @break
                                    @case('promotion') تبلیغاتی @break
                                    @default عمومی
                                @endswitch
                            </td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ $announcement->priority }}</td>
                            <td class="px-4 py-3">
                                @if($announcement->is_active)
                                    <span class="px-2 py-1 rounded-full text-xs" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">فعال</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs" style="background-color: rgba(148,163,184,0.15); color: var(--admin-text-dim);">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number" dir="ltr" style="color:var(--admin-text-dim);">
                                {{ $announcement->published_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.announcements.edit', $announcement) }}"
                                       class="text-xs px-2 py-1 rounded" style="color: var(--admin-accent);">ویرایش</a>
                                    <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST">
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
                            <td colspan="6" class="px-4 py-8 text-center" style="color:var(--admin-text-dim);">
                                هیچ اطلاعیه‌ای ثبت نشده است.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $announcements->links() }}
            </div>
        </div>
    </div>
@endsection
