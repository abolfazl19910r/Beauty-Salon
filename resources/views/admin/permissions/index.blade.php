@extends('layouts.admin')

@section('title', 'مدیریت دسترسی‌ها')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">مدیریت دسترسی‌ها</h1>
                <p class="text-sm text-gray-500">تعریف و مدیریت دسترسی‌های سیستم</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('admin.permissions.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    افزودن دسترسی جدید
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
            <form action="{{ route('admin.permissions.filter') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="نام، عنوان یا توضیحات..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">گروه</label>
                    <select name="group" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">همه گروه‌ها</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                {{ $group }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        جستجو
                    </button>
                    @if(request()->hasAny(['search', 'group']))
                        <a href="{{ route('admin.permissions.index') }}" class="mr-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                            پاک کردن فیلتر
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            @if($permissions->isEmpty())
                <div class="p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <p class="text-gray-500 mb-4">هیچ دسترسی تعریف نشده است!</p>
                    <a href="{{ route('admin.permissions.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        ایجاد اولین دسترسی
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="bg-gray-50 text-gray-600 text-sm">
                            <th class="py-3 px-6 text-right font-medium">عنوان</th>
                            <th class="py-3 px-6 text-right font-medium">نام فنی</th>
                            <th class="py-3 px-6 text-right font-medium">گروه</th>
                            <th class="py-3 px-6 text-right font-medium">توضیحات</th>
                            <th class="py-3 px-6 text-right font-medium">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($permissions as $permission)
                            <tr class="hover:bg-gray-50 text-sm transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-medium text-gray-900">{{ $permission->label }}</div>
                                </td>
                                <td class="py-4 px-6 text-gray-500" dir="ltr">{{ $permission->name }}</td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ $permission->group }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-gray-500 text-xs max-w-xs truncate">
                                    {{ $permission->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3 space-x-reverse">
                                        <a href="{{ route('admin.permissions.show', $permission) }}"
                                           class="text-blue-600 hover:text-blue-800 transition-colors"
                                           title="نمایش">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <a href="{{ route('admin.permissions.edit', $permission) }}"
                                           class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                           title="ویرایش">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>

                                        <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-800 transition-colors"
                                                    title="حذف"
                                                    data-confirm-delete
                                                    data-confirm-message="آیا از حذف این دسترسی اطمینان دارید؟">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    {{ $permissions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
@endpush
