@extends('layouts.admin')

@section('title', 'ویرایش نقش')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">ویرایش نقش</h1>
                <p class="text-sm text-gray-500">ویرایش نقش «{{ $role->label }}»</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="{{ route('admin.roles.show', $role) }}" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    مشاهده جزئیات
                </a>

                <a href="{{ route('admin.roles.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            <div class="p-6">
                <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">نام فنی نقش (Name)</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">نام انگلیسی برای استفاده در کد (مثلاً editor)</p>
                        </div>

                        <div class="mb-4">
                            <label for="label" class="block text-sm font-medium text-gray-700">عنوان نمایشی (Label)</label>
                            <input type="text" name="label" id="label" value="{{ old('label', $role->label) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                            @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">عنوان نمایشی نقش که به کاربران نمایش داده می‌شود</p>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 border-t border-gray-200 pt-6 mt-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block ml-1.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2v4a2 2 0 01-2 2h-2m-2-4h4m-2 4h2m-2-4v4m-2-4h2m-2 4h2m-2-4v4m-2-4h4m-2-4h2m-2-4v4m-2-4h4m-2-4h2m-2-4v4" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h4a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v10a2 2 0 002 2h4" />
                        </svg>
                        تخصیص دسترسی‌ها
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">دسترسی‌هایی که کاربران با این نقش در بخش‌های مختلف سیستم خواهند داشت را انتخاب کنید.</p>

                    @if($permissions->isEmpty())
                        <div class="bg-yellow-50 border-r-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.765 1.36-.207 3.099-1.743 3.099H4.42c-1.536 0-2.508-1.739-1.743-3.099l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-800">
                                        هیچ دسترسی برای تخصیص پیدا نشد. لطفاً ابتدا در جدول `permissions` دسترسی‌ها را تعریف کنید.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @foreach ($permissions as $groupName => $groupPermissions)
                        <div class="mb-6 p-4 border border-gray-100 rounded-lg bg-gray-50">
                            <h3 class="text-lg font-bold text-gray-800 mb-3 pb-1 border-b border-gray-200">
                                {{ $groupName ?? 'دسترسی‌های عمومی' }}
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach ($groupPermissions as $permission)
                                    <label class="flex items-start space-x-3 space-x-reverse cursor-pointer p-2 rounded-md hover:bg-gray-100 transition-colors border border-gray-200">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                        <div class="ml-2 flex-1">
                                            <span class="text-sm font-medium text-gray-700 block">{{ $permission->label }}</span>
                                            <span class="text-xs text-gray-500 block">{{ $permission->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-end space-x-3 space-x-reverse">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                ذخیره تغییرات
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                انصراف
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
