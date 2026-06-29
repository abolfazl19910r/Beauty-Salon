@extends('layouts.admin')
@section('title', 'ایجاد نقش جدید')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">ایجاد نقش جدید</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">تعریف نقش جدید برای کاربران سیستم</p>
            </div>
            <a href="{{ route('admin.roles.index') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">نام فنی (انگلیسی) <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" dir="ltr" required
                               placeholder="editor, manager"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('name')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        <p class="text-xs mt-1" style="color:var(--admin-text-light)">به انگلیسی و بدون فاصله</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">عنوان نمایشی (فارسی) <span class="text-red-500">*</span></label>
                        <input type="text" name="label" value="{{ old('label') }}" required
                               placeholder="ویراستار، مدیر"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('label')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <h2 class="text-base font-semibold mb-4 pt-4 flex items-center gap-2"
                    style="color:var(--admin-text);border-top:1px solid var(--admin-border)">
                    <svg class="w-5 h-5" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    تخصیص دسترسی‌ها
                </h2>

                @if($permissions->isEmpty())
                    <div class="p-4 rounded-lg text-sm mb-4" style="background:#fffbeb;border-right:3px solid #d97706;color:#92400e">
                        هیچ دسترسی برای تخصیص پیدا نشد. ابتدا دسترسی‌ها را تعریف کنید.
                    </div>
                @else
                    @foreach($permissions as $groupName => $groupPermissions)
                        <div class="mb-5 rounded-xl p-4" style="background:var(--admin-bg);border:1px solid var(--admin-border)">
                            <h3 class="text-sm font-semibold mb-3" style="color:var(--admin-text)">{{ $groupName ?? 'دسترسی‌های عمومی' }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($groupPermissions as $permission)
                                    <label class="flex items-start gap-2 p-2 rounded-lg cursor-pointer border transition-colors"
                                           style="border-color:var(--admin-border)">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                               class="mt-0.5 rounded" style="accent-color:var(--admin-accent)"
                                            {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                        <div>
                                            <span class="text-sm font-medium block" style="color:var(--admin-text)">{{ $permission->label }}</span>
                                            <span class="text-xs font-mono" style="color:var(--admin-text-light)">{{ $permission->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="flex gap-3 pt-4" style="border-top:1px solid var(--admin-border)">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg"
                            style="background:var(--admin-accent)">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        ذخیره نقش
                    </button>
                    <a href="{{ route('admin.roles.index') }}"
                       class="inline-flex items-center px-5 py-2 text-sm rounded-lg border"
                       style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                        انصراف
                    </a>
                </div>
            </form>
        </div>

    </div>
@endsection
