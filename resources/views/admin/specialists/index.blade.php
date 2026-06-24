@extends('layouts.admin')
@section('title', 'مدیریت متخصصین')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    مدیریت متخصصین
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">مشاهده و مدیریت تمام متخصصین سالن</p>
            </div>
            @permission('create-specialists')
            <a href="{{ route('admin.specialists.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
               style="background:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-accent-hover)'"
               onmouseout="this.style.background='var(--admin-accent)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن متخصص
            </a>
            @endpermission
        </div>

        <div class="rounded-xl p-4 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.specialists.index') }}" method="GET" class="flex gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" style="color:var(--admin-text-light);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="جستجو بر اساس نام یا شماره تماس..."
                           class="w-full text-sm rounded-lg px-4 py-2 pr-9 outline-none transition"
                           style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);"
                           onfocus="this.style.borderColor='var(--admin-accent)'"
                           onblur="this.style.borderColor='var(--admin-border)'">
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                        style="background:var(--admin-accent);"
                        onmouseover="this.style.background='var(--admin-accent-hover)'"
                        onmouseout="this.style.background='var(--admin-accent)'">جستجو</button>
                @if(request('search'))
                    <a href="{{ route('admin.specialists.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                       style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                       onmouseover="this.style.background='var(--admin-border)'"
                       onmouseout="this.style.background='var(--admin-accent-light)'">پاک کردن</a>
                @endif
            </form>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">متخصص</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">شماره تماس</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">ایمیل</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">خدمات</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">نوبت‌های امروز</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($specialists as $specialist)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                         style="background:var(--admin-accent); color:#fff;">
                                        {{ mb_substr($specialist->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium" style="color:var(--admin-text);">{{ $specialist->name }}</p>
                                        <p class="text-xs" style="color:var(--admin-text-light);">متخصص زیبایی</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm" dir="ltr" style="color:var(--admin-text-dim);">{{ $specialist->phone }}</td>
                            <td class="px-4 py-3 text-sm" style="color:var(--admin-text-dim);">{{ $specialist->email ?? '—' }}</td>
                            <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium persian-number"
                                  style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                {{ $specialist->services->count() }} خدمت
                            </span>
                            </td>
                            <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold persian-number"
                                  style="background:#EFF6FF; color:#1D4ED8;">
                                {{ $specialist->bookings_count ?? 0 }}
                            </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.specialists.show', $specialist) }}" title="مشاهده"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:var(--admin-accent);"
                                       onmouseover="this.style.background='var(--admin-accent-light)'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    @permission('edit-specialists')
                                    <a href="{{ route('admin.specialists.edit', $specialist) }}" title="ویرایش"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:#7C3AED;"
                                       onmouseover="this.style.background='#F5F3FF'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    @endpermission
                                    @permission('delete-specialists')
                                    <form action="{{ route('admin.specialists.destroy', $specialist) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" title="حذف"
                                                data-confirm-delete data-confirm-message="آیا از حذف {{ $specialist->name }} اطمینان دارید؟ تمام نوبت‌ها و برنامه‌های کاری مرتبط حذف خواهند شد."
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
                                    @endpermission
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">
                                <svg class="w-10 h-10 mx-auto mb-3" style="color:var(--admin-text-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                                هیچ متخصصی یافت نشد
                                @permission('create-specialists')
                                <br><a href="{{ route('admin.specialists.create') }}" class="inline-block mt-3 text-xs px-3 py-1.5 rounded-lg text-white" style="background:var(--admin-accent);">افزودن متخصص جدید</a>
                                @endpermission
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($specialists->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $specialists->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
