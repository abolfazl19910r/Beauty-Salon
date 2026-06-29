@extends('layouts.admin')
@section('title', 'افزودن دسترسی جدید')

@section('content')
    <div class="container px-6 mx-auto max-w-2xl">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">افزودن دسترسی جدید</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">تعریف دسترسی جدید در سیستم</p>
            </div>
            <a href="{{ route('admin.permissions.index') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <form action="{{ route('admin.permissions.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">
                            نام فنی (انگلیسی) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" dir="ltr" required
                               placeholder="view-users, create-bookings"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('name')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        <p class="text-xs mt-1" style="color:var(--admin-text-light)">به انگلیسی، بدون فاصله، با - جدا کنید</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">
                            عنوان نمایشی (فارسی) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="label" value="{{ old('label') }}" required
                               placeholder="مشاهده کاربران"
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        @error('label')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">
                            گروه <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="group" value="{{ old('group') }}" list="groups-list" required
                               placeholder="کاربران، رزروها..."
                               class="w-full rounded-lg px-3 py-2 text-sm"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        <datalist id="groups-list">
                            @foreach($groups as $group)<option value="{{ $group }}">@endforeach
                        </datalist>
                        @error('group')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        <p class="text-xs mt-1" style="color:var(--admin-text-light)">گروه موجود انتخاب یا گروه جدید تایپ کنید</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">توضیحات</label>
                        <textarea name="description" rows="3"
                                  placeholder="توضیح کوتاه درباره این دسترسی..."
                                  class="w-full rounded-lg px-3 py-2 text-sm"
                                  style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- Help --}}
                <div class="p-4 rounded-lg mb-5 text-sm"
                     style="background:#eff6ff;border-right:3px solid #2563eb;color:#1e40af">
                    <strong class="block mb-1">نکات نام‌گذاری:</strong>
                    از پیشوندهای استاندارد استفاده کنید:
                    <code class="px-1 rounded mx-0.5" style="background:#dbeafe">view-</code>
                    <code class="px-1 rounded mx-0.5" style="background:#dbeafe">create-</code>
                    <code class="px-1 rounded mx-0.5" style="background:#dbeafe">edit-</code>
                    <code class="px-1 rounded mx-0.5" style="background:#dbeafe">delete-</code>
                    <code class="px-1 rounded mx-0.5" style="background:#dbeafe">manage-</code>
                </div>

                <div class="flex gap-3 pt-4" style="border-top:1px solid var(--admin-border)">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg"
                            style="background:var(--admin-accent)">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        ذخیره دسترسی
                    </button>
                    <a href="{{ route('admin.permissions.index') }}"
                       class="inline-flex items-center px-5 py-2 text-sm rounded-lg border"
                       style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                        انصراف
                    </a>
                </div>
            </form>
        </div>

    </div>
@endsection
