@extends('layouts.admin')
@section('title', 'افزودن دسته‌بندی')

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
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">افزودن دسته‌بندی جدید</h1>
            <a href="{{ route('admin.categories.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="space-y-4 mb-5">
                    <div>
                        <label class="form-label">نام دسته‌بندی <span style="color:#DC2626;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input" required placeholder="مثلاً: رنگ مو">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <textarea name="description" rows="4" class="form-textarea" placeholder="توضیح مختصر درباره این دسته‌بندی...">{{ old('description') }}</textarea>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
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
                        ذخیره دسته‌بندی
                    </button>
                    <a href="{{ route('admin.categories.index') }}"
                       class="px-6 py-2.5 rounded-lg text-sm font-medium"
                       style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                       onmouseover="this.style.background='var(--admin-border)'"
                       onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                </div>
            </form>
        </div>
    </div>
@endsection
