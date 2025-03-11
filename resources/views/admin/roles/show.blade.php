@extends('layouts.admin')

@section('title', 'نمایش نقش')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">نمایش جزئیات نقش</h1>
                <p class="text-sm text-gray-500">جزئیات نقش "{{ $role->label }}" و کاربران دارای این نقش</p>
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

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
            <!-- اطلاعات نقش -->
            <div class="md:col-span-8">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 h-full">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-gray-800">اطلاعات نقش</h2>
                            <div class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $users->total() }} کاربر
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">عنوان نمایشی:</div>
                                    <div class="font-medium">{{ $role->label }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">نام فنی:</div>
                                    <div class="font-medium" dir="ltr">{{ $role->name }}</div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">تاریخ ایجاد:</div>
                                    <div class="font-medium" dir="ltr">{{ verta($role->created_at)->formatDatetime() }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">آخرین بروزرسانی:</div>
                                    <div class="font-medium" dir="ltr">{{ verta($role->updated_at)->formatDatetime() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- عملیات -->
            <div class="md:col-span-4">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 h-full">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">عملیات</h2>
                        <div class="space-y-3">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="flex items-center w-full p-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                ویرایش نقش
                            </a>

                            <a href="{{ route('admin.roles.assign.form', $role) }}" class="flex items-center w-full p-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                اختصاص به کاربر
                            </a>

                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex items-center w-full p-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors"
                                        data-confirm-delete data-confirm-message="آیا از حذف این نقش اطمینان دارید؟">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    حذف نقش
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- کاربران دارای نقش -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-800">کاربران دارای این نقش</h2>
                    <a href="{{ route('admin.roles.assign.form', $role) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        افزودن کاربر
                    </a>
                </div>

                @if($users->isEmpty())
                    <div class="py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <p class="text-gray-500 mb-4">هیچ کاربری با این نقش یافت نشد!</p>
                        <a href="{{ route('admin.roles.assign.form', $role) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            اختصاص نقش به کاربر
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm">
                                <th class="py-3 px-6 text-right font-medium">نام کاربر</th>
                                <th class="py-3 px-6 text-right font-medium">شماره موبایل</th>
                                <th class="py-3 px-6 text-right font-medium">ایمیل</th>
                                <th class="py-3 px-6 text-right font-medium">عملیات</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50 text-sm transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-500" dir="ltr">{{ $user->phone }}</td>
                                    <td class="py-4 px-6 text-gray-500">{{ $user->email }}</td>
                                    <td class="py-4 px-6">
                                        <form action="{{ route('admin.roles.remove.user', [$role, $user]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 transition-colors flex items-center"
                                                    data-confirm-delete data-confirm-message="آیا از حذف این نقش از کاربر اطمینان دارید؟">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                                                </svg>
                                                حذف نقش
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pt-4 border-t border-gray-100 mt-4">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
