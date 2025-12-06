@extends('layouts.admin')

@section('title', 'مدیریت نقش‌ها')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">مدیریت نقش‌ها</h1>
                <p class="text-sm text-gray-500">مدیریت نقش‌ها و دسترسی‌های کاربران سیستم</p>
            </div>
            <div class="mt-4 md:mt-0">
                @permission('manage-roles')
                <a href="{{ route('admin.roles.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    افزودن نقش جدید
                </a>
                @endpermission
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            @if($roles->isEmpty())
                <div class="p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-gray-500 mb-4">هیچ نقشی در سیستم تعریف نشده است!</p>
                    @permission('manage-roles')
                    <a href="{{ route('admin.roles.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        ایجاد اولین نقش
                    </a>
                    @endpermission
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="bg-gray-50 text-gray-600 text-sm">
                            <th class="py-3 px-6 text-right font-medium">عنوان نقش</th>
                            <th class="py-3 px-6 text-right font-medium">نام فنی</th>
                            <th class="py-3 px-6 text-right font-medium">تعداد کاربران</th>
                            <th class="py-3 px-6 text-right font-medium">تاریخ ایجاد</th>
                            <th class="py-3 px-6 text-right font-medium">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($roles as $role)
                            <tr class="hover:bg-gray-50 text-sm transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-medium text-gray-900">{{ $role->label }}</div>
                                </td>
                                <td class="py-4 px-6 text-gray-500" dir="ltr">{{ $role->name }}</td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium leading-none text-blue-800 bg-blue-100 rounded-full">
                                        {{ $role->users_count }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-gray-500" dir="ltr">
                                    {{ verta($role->created_at)->format('Y/m/d H:i') }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3 space-x-reverse">
                                        <a href="{{ route('admin.roles.show', $role) }}"
                                           class="text-blue-600 hover:text-blue-800 transition-colors"
                                           title="نمایش">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        @permission('manage-roles')
                                        <a href="{{ route('admin.roles.edit', $role) }}"
                                           class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                           title="ویرایش">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.roles.assign.form', $role) }}"
                                           class="text-green-600 hover:text-green-800 transition-colors"
                                           title="اختصاص به کاربر">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-800 transition-colors"
                                                    title="حذف"
                                                    data-confirm-delete
                                                    data-confirm-message="آیا از حذف این نقش اطمینان دارید؟">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                        @endpermission
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
