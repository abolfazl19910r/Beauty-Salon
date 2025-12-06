@extends('layouts.admin')

@section('title', 'ویرایش دسترسی')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">ویرایش دسترسی</h1>
                <p class="text-sm text-gray-500">ویرایش دسترسی «{{ $permission->label }}»</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="{{ route('admin.permissions.show', $permission) }}" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    مشاهده جزئیات
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            <div class="p-6">
                <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                نام فنی (انگلیسی) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $permission->name) }}"
                                   dir="ltr"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="view-users, create-bookings"
                                   required>
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">نام فنی باید به انگلیسی و بدون فاصله باشد</p>
                        </div>

                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700 mb-2">
                                عنوان نمایشی (فارسی) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="label"
                                   id="label"
                                   value="{{ old('label', $permission->label) }}"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="مشاهده کاربران، ایجاد رزرو"
                                   required>
                            @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">عنوان نمایشی که به کاربران نمایش داده می‌شود</p>
                        </div>

                        <div>
                            <label for="group" class="block text-sm font-medium text-gray-700 mb-2">
                                گروه <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="group"
                                   id="group"
                                   value="{{ old('group', $permission->group) }}"
                                   list="groups-list"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="کاربران، رزروها، تنظیمات"
                                   required>
                            <datalist id="groups-list">
                                @foreach($groups as $group)
                                    <option value="{{ $group }}">
                                @endforeach
                            </datalist>
                            @error('group')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">گروه دسترسی</p>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                توضیحات
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="3"
                                      class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                      placeholder="توضیحات کامل درباره این دسترسی...">{{ old('description', $permission->description) }}</textarea>
                            @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if($permission->roles->count() > 0)
                        <div class="mt-6 p-4 bg-yellow-50 border-r-4 border-yellow-400 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.765 1.36-.207 3.099-1.743 3.099H4.42c-1.536 0-2.508-1.739-1.743-3.099l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <h3 class="text-sm font-medium text-yellow-800">توجه!</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>این دسترسی در <strong>{{ $permission->roles->count() }}</strong> نقش استفاده شده است:</p>
                                        <ul class="list-disc list-inside mt-1">
                                            @foreach($permission->roles as $role)
                                                <li>{{ $role->label }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="flex justify-end space-x-3 space-x-reverse">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-6 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                ذخیره تغییرات
                            </button>
                            <a href="{{ route('admin.permissions.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-6 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                انصراف
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
