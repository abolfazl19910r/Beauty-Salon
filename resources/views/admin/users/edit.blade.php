@extends('layouts.admin')

@section('title', 'ویرایش کاربر')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">ویرایش کاربر</h1>
                <p class="text-sm text-gray-500">ویرایش اطلاعات کاربر {{ $user->name }}</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="{{ route('admin.users.show', $user) }}" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    مشاهده جزئیات
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
            <!-- فرم ویرایش کاربر -->
            <div class="md:col-span-8">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">اطلاعات اصلی</h2>
                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام و نام خانوادگی</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text"
                                               name="name"
                                               id="name"
                                               value="{{ old('name', $user->name) }}"
                                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                               placeholder="نام و نام خانوادگی کاربر">
                                    </div>
                                    @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <input type="text"
                                               name="phone"
                                               id="phone"
                                               value="{{ old('phone', $user->phone) }}"
                                               dir="ltr"
                                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                               placeholder="09123456789">
                                    </div>
                                    @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل (اختیاری)</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="email"
                                               name="email"
                                               id="email"
                                               value="{{ old('email', $user->email) }}"
                                               dir="ltr"
                                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                               placeholder="example@domain.com">
                                    </div>
                                    @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">تنظیمات دسترسی</label>
                                    <div class="mt-1 space-y-4">
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   name="is_admin"
                                                   id="is_admin"
                                                   value="1"
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                                            <label for="is_admin" class="mr-2 block text-sm font-medium text-gray-700">
                                                دسترسی مدیریت
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   id="is_active"
                                                   value="1"
                                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                                {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                            <label for="is_active" class="mr-2 block text-sm font-medium text-gray-700">
                                                حساب کاربری فعال
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">نقش‌های کاربر</label>
                                <div class="mt-1 border border-gray-300 rounded-md p-3 space-y-2 max-h-60 overflow-y-auto">
                                    @foreach($roles as $role)
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   name="roles[]"
                                                   id="role-{{ $role->id }}"
                                                   value="{{ $role->id }}"
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}>
                                            <label for="role-{{ $role->id }}" class="mr-2 block text-sm font-medium text-gray-700">
                                                {{ $role->label }} <span class="text-gray-400 text-xs">({{ $role->name }})</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('roles')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="border-t border-gray-200 pt-4 mt-6">
                                <div class="flex justify-end space-x-3 space-x-reverse">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        ذخیره تغییرات
                                    </button>
                                    <a href="{{ route('admin.users.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                        انصراف
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- بخش تغییر رمز عبور -->
            <div class="md:col-span-4">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">تغییر رمز عبور</h2>
                        <form action="{{ route('admin.users.password.reset', $user) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور جدید</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           dir="ltr"
                                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="حداقل 8 کاراکتر">
                                </div>
                                @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">تکرار رمز عبور جدید</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           dir="ltr"
                                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="تکرار رمز عبور">
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    تغییر رمز عبور
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- بخش عملیات -->
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 mt-6">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">عملیات</h2>
                        <div class="space-y-3">
                            <form action="{{ route('admin.users.status.update', $user) }}" method="POST" class="w-full">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                <button type="submit" class="flex items-center w-full p-2 {{ $user->is_active ? 'bg-red-50 hover:bg-red-100 text-red-700' : 'bg-green-50 hover:bg-green-100 text-green-700' }} rounded-lg transition-colors">
                                    @if($user->is_active)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        غیرفعال کردن کاربر
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        فعال کردن کاربر
                                    @endif
                                </button>
                            </form>

                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex items-center w-full p-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors"
                                        data-confirm-delete data-confirm-message="آیا از حذف این کاربر اطمینان دارید؟">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    حذف کاربر
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
