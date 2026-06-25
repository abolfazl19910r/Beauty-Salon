@extends('layouts.admin')
@section('title', 'مدیریت دسته‌بندی‌ها')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                        <polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                    </svg>
                    مدیریت دسته‌بندی‌ها
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">دسته‌بندی خدمات سالن</p>
            </div>
            @permission('create-categories')
            <a href="{{ route('admin.categories.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
               style="background:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-accent-hover)'"
               onmouseout="this.style.background='var(--admin-accent)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن دسته‌بندی
            </a>
            @endpermission
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">نام دسته‌بندی</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تعداد خدمات</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">توضیحات</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($categories as $category)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3 font-medium" style="color:var(--admin-text);">{{ $category->name }}</td>
                            <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium persian-number"
                                  style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                {{ $category->services_count ?? $category->services->count() }} خدمت
                            </span>
                            </td>
                            <td class="px-4 py-3 text-sm" style="color:var(--admin-text-dim);">
                                {{ \Illuminate\Support\Str::limit($category->description ?? '', 60) ?: '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.categories.show', $category) }}" title="مشاهده"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:var(--admin-accent);"
                                       onmouseover="this.style.background='var(--admin-accent-light)'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    @permission('edit-categories')
                                    <a href="{{ route('admin.categories.edit', $category) }}" title="ویرایش"
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
                                    @permission('delete-categories')
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" title="حذف"
                                                data-confirm-delete data-confirm-message="آیا از حذف دسته‌بندی «{{ $category->name }}» اطمینان دارید؟"
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
                            <td colspan="4" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">
                                هیچ دسته‌بندی ثبت نشده
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($categories, 'hasPages') && $categories->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
