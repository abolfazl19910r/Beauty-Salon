@extends('layouts.admin')
@section('title', 'مدیریت نظرات')

@section('content')
    <div class="fade-in">

        {{-- Heather --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    مدیریت نظرات
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">بررسی و مدیریت نظرات کاربران</p>
            </div>
            <a href="{{ route('admin.reviews.stats') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                آمار نظرات
            </a>
        </div>

        {{-- Statistics cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #3B82F6;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل نظرات</p>
                <p class="text-2xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($totalReviews) }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #16A34A;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">تایید شده</p>
                <p class="text-2xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($approvedReviews) }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #DC2626;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">نظرات منفی</p>
                <p class="text-2xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($negativeReviews) }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #F59E0B;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">میانگین امتیاز</p>
                <p class="text-2xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($averageRating, 1) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl p-4 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.reviews.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-36">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">وضعیت</label>
                    <select name="status" class="w-full text-sm rounded-lg px-3 py-2 outline-none"
                            style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        <option value="">همه</option>
                        <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>تایید شده</option>
                        <option value="pending"  {{ request('status')=='pending'  ? 'selected' : '' }}>در انتظار</option>
                        <option value="featured" {{ request('status')=='featured' ? 'selected' : '' }}>ویژه</option>
                    </select>
                </div>
                <div class="flex-1 min-w-36">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">امتیاز</label>
                    <select name="rating" class="w-full text-sm rounded-lg px-3 py-2 outline-none"
                            style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        <option value="">همه امتیازها</option>
                        @for($i=5; $i>=1; $i--)
                            <option value="{{ $i }}" {{ request('rating')==$i ? 'selected' : '' }}>{{ $i }} ستاره</option>
                        @endfor
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">اعمال فیلتر</button>
                    @if(request('status') || request('rating'))
                        <a href="{{ route('admin.reviews.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);">پاک</a>
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
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">کاربر</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">متخصص</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">خدمت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">امتیاز</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($reviews as $review)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                         style="background:var(--admin-accent); color:#fff;">
                                        {{ mb_substr($review->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <span style="color:var(--admin-text);">{{ $review->user->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3" style="color:var(--admin-text);">{{ $review->specialist->name ?? '—' }}</td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">{{ $review->service->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php $r = $review->overall_rating ?? 0; @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold persian-number"
                                      style="background:{{ $r>=4 ? '#F0FDF4' : ($r>=3 ? '#FFFBEB' : '#FEF2F2') }};
                                         color:{{ $r>=4 ? '#166534' : ($r>=3 ? '#92400E' : '#991B1B') }};">
                                ★ {{ $r }}
                            </span>
                            </td>
                            <td class="px-4 py-3 persian-number text-xs" style="color:var(--admin-text-dim);">
                                {{ verta($review->reviewed_at ?? $review->created_at)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    @if($review->is_approved)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">تایید شده</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#FFFBEB; color:#92400E;">در انتظار</span>
                                    @endif
                                    @if($review->is_featured)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#EFF6FF; color:#1D4ED8;">ویژه</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.reviews.show', $review->id) }}" title="مشاهده"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:var(--admin-accent);"
                                       onmouseover="this.style.background='var(--admin-accent-light)'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    @if(!$review->is_approved)
                                        <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <button type="submit" title="تایید"
                                                    class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                    style="color:#16A34A;"
                                                    onmouseover="this.style.background='#F0FDF4'"
                                                    onmouseout="this.style.background=''">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" title="حذف"
                                                data-confirm-delete data-confirm-message="آیا از حذف این نظر اطمینان دارید؟"
                                                class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                style="color:#DC2626;"
                                                onmouseover="this.style.background='#FEF2F2'"
                                                onmouseout="this.style.background=''">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">هیچ نظری یافت نشد</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($reviews->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $reviews->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
