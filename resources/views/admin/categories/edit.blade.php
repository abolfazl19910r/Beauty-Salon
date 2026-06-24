@extends('layouts.admin')
@section('title', 'ویرایش دسته‌بندی')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input,.form-textarea { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus,.form-textarea:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
    </style>
@endpush

@section('content')
    <div class="fade-in max-w-2xl">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">ویرایش دسته‌بندی</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $category->name }}</p>
            </div>
            <a href="{{ route('admin.categories.show', $category) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-5 mb-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                @csrf @method('PUT')
                <div class="space-y-4 mb-5">
                    <div>
                        <label class="form-label">نام دسته‌بندی <span style="color:#DC2626;">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-input" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <textarea name="description" rows="4" class="form-textarea">{{ old('description', $category->description) }}</textarea>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg" style="background:var(--admin-accent-light);">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                        </svg>
                        <span class="text-sm" style="color:var(--admin-text-dim);">
                        تعداد خدمات این دسته‌بندی:
                        <span class="font-bold persian-number" style="color:var(--admin-accent);">{{ $category->services->count() }}</span>
                    </span>
                    </div>
                </div>
                <div class="flex justify-between pt-4" style="border-top:1px solid var(--admin-border);">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                        </svg>
                        ذخیره تغییرات
                    </button>
                    <a href="{{ route('admin.categories.index') }}"
                       class="px-6 py-2.5 rounded-lg text-sm font-medium"
                       style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                       onmouseover="this.style.background='var(--admin-border)'"
                       onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                </div>
            </form>
        </div>

        @permission('delete-categories')
        <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid #FCA5A5;">
            <p class="text-sm font-medium mb-3" style="color:#991B1B;">حذف دسته‌بندی</p>
            <p class="text-xs mb-3" style="color:var(--admin-text-dim);">با حذف این دسته‌بندی، خدمات مرتبط بدون دسته‌بندی می‌مانند.</p>
            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST">
                @csrf @method('DELETE')
                <button type="button" data-confirm-delete data-confirm-message="آیا از حذف دسته‌بندی «{{ $category->name }}» اطمینان دارید؟"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium"
                        style="background:#FEF2F2; color:#991B1B;"
                        onmouseover="this.style.background='#FEE2E2'"
                        onmouseout="this.style.background='#FEF2F2'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    </svg>
                    حذف دسته‌بندی
                </button>
            </form>
        </div>
        @endpermission
    </div>
@endsection
