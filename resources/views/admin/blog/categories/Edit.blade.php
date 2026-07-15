@extends('layouts.admin')
@section('title', 'ویرایش دسته‌بندی')

@section('content')
    <div class="fade-in max-w-xl">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">ویرایش دسته‌بندی</h1>
            <a href="{{ route('admin.blog.categories.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.blog.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim);">نام دسته‌بندی <span style="color:#DC2626;">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        @error('name') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim);">توضیحات</label>
                        <textarea name="description" rows="3"
                                  class="w-full rounded-lg px-3 py-2 text-sm"
                                  style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">{{ old('description', $category->description) }}</textarea>
                        @error('description') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim);">ترتیب نمایش</label>
                        <input type="number" name="order" value="{{ old('order', $category->order) }}" min="0"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        @error('order') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 pt-4" style="border-top:1px solid var(--admin-border);">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
