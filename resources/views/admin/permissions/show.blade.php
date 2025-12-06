@extends('layouts.admin')

@section('title', 'نمایش دسترسی')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">نمایش جزئیات دسترسی</h1>
                <p class="text-sm text-gray-500">جزئیات دسترسی «{{ $permission->label }}»</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('admin.permissions.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
            <div class="md:col-span-8">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 h-full">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-gray-800">اطلاعات دسترسی</h2>
                            <div class="px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ $permission->group }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">عنوان نمایشی:</div>
                                    <div class="font-medium text-lg">{{ $permission->label }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">نام فنی:</div>
                                    <div class="font-medium font-mono bg-gray-50 px-3 py-2 rounded" dir="ltr">{{ $permission->name }}</div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">تاریخ ایجاد:</div>
                                    <div class="font-medium" dir="ltr">{{ verta($permission->created_at)->formatDatetime() }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">آخرین به‌روزرسانی:</div>
                                    <div class="font-medium" dir="ltr">{{ verta($permission->updated_at)->formatDatetime() }}</div>
                                </div>
                            </div>
                        </div>

                        @if($permission->description)
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="text-sm text-gray-500 mb-1">توضیحات:</div>
                                <div class="text-gray-700">{{ $permission->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="md:col-span-4">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 h-full">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">عملیات</h2>
                        <div class="space-y-3">
                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="flex items-center w-full p-3 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                ویرایش دسترسی
                            </a>

                            <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex items-center w-full p-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors"
                                        data-confirm-delete data-confirm-message="آیا از حذف این دسترسی اطمینان دارید؟ این عمل برگشت‌پذیر نیست!">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    حذف دسترسی
                                </button>
                            </form>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">آمار</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">تعداد نقش‌ها:</span>
                                    <span class="font-bold text-blue-600">{{ $roles->count() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">کل کاربران:</span>
                                    <span class="font-bold text-green-600">{{ $roles->sum('users_count') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-800">نقش‌های دارای این دسترسی</h2>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        {{ $roles->count() }} نقش
                    </span>
                </div>

                @if($roles->isEmpty())
                    <div class="py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-gray-500 mb-4">هیچ نقشی این دسترسی را ندارد!</p>
                        <p class="text-sm text-gray-400">می‌توانید از بخش مدیریت نقش‌ها، این دسترسی را به نقش‌ها اختصاص دهید.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($roles as $role)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 mb-1">{{ $role->label }}</h3>
                                        <p class="text-sm text-gray-500 font-mono" dir="ltr">{{ $role->name }}</p>
                                    </div>
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="text-blue-600 hover:text-blue-800"
                                       title="نمایش نقش">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span>{{ $role->users_count }} کاربر</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
