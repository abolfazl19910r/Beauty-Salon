@extends('layouts.admin')
@section('title', 'نظرات حذف‌شده')

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    نظرات حذف‌شده
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">نظراتی که حذف شده‌اند و قابل بازگردانی هستند</p>
            </div>
            <a href="{{ route('admin.reviews.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-accent);">
                بازگشت به لیست نظرات
            </a>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @if($reviews->isEmpty())
                <div class="p-8 text-center text-sm" style="color:var(--admin-text-dim);">هیچ نظر حذف‌شده‌ای وجود ندارد.</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr style="border-bottom:1px solid var(--admin-border);">
                            <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">کاربر</th>
                            <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">متخصص</th>
                            <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">امتیاز</th>
                            <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">تاریخ حذف</th>
                            <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                            <tr style="border-bottom:1px solid var(--admin-border);">
                                <td class="px-4 py-3" style="color:var(--admin-text);">{{ $review->user->name ?? '—' }}</td>
                                <td class="px-4 py-3" style="color:var(--admin-text);">{{ $review->specialist->name ?? '—' }}</td>
                                <td class="px-4 py-3 persian-number" style="color:var(--admin-text);">{{ $review->overall_rating }}</td>
                                <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ $review->deleted_at?->format('Y/m/d H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <form action="{{ route('admin.reviews.restore', $review->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg" style="background:#DCFCE7; color:#16A34A;">بازگردانی</button>
                                        </form>
                                        <form action="{{ route('admin.reviews.force-delete', $review->id) }}" method="POST" data-confirm-delete>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg" style="background:#FEE2E2; color:#DC2626;">حذف دائمی</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
