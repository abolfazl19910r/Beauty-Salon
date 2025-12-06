@extends('layouts.admin')

@section('title', 'ایجاد نقش جدید')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">ایجاد نقش جدید</h1>
                <p class="text-sm text-gray-500">از این بخش می‌توانید نقش جدیدی برای کاربران سیستم تعریف کنید</p>
            </div>
            <div class="mt-4 md:mt-0">
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
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام فنی (انگلیسی)</label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   dir="ltr"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="editor, manager"
                                   required>
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">نام فنی باید به انگلیسی و بدون فاصله باشد</p>
                        </div>

                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700 mb-2">عنوان نمایشی (فارسی)</label>
                            <input type="text"
                                   name="label"
                                   id="label"
                                   value="{{ old('label') }}"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="ویراستار، مدیر"
                                   required>
                            @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">عنوان نمایشی نقش که به کاربران نمایش داده می‌شود</p>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 border-t border-gray-200 pt-6 mt-6 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block ml-1.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        تخصیص دسترسی‌ها
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">دسترسی‌هایی که کاربران با این نقش خواهند داشت را انتخاب کنید</p>

                    @if($permissions->isEmpty())
                        <div class="bg-yellow-50 border-r-4 border-yellow-400 p-4 mb-6">
                            <p class="text-sm text-yellow-800">
                                هیچ دسترسی برای تخصیص پیدا نشد. لطفاً ابتدا دسترسی‌ها را تعریف کنید.
                            </p>
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
                                            {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
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
                                ذخیره نقش
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
