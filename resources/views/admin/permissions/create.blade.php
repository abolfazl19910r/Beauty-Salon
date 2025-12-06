@extends('layouts.admin')

@section('title', 'افزودن دسترسی جدید')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">افزودن دسترسی جدید</h1>
                <p class="text-sm text-gray-500">تعریف دسترسی جدید در سیستم</p>
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

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            <div class="p-6">
                <form action="{{ route('admin.permissions.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                نام فنی (انگلیسی) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   dir="ltr"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                   placeholder="view-users, create-bookings"
                                   required>
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">نام فنی باید به انگلیسی و بدون فاصله باشد (از - استفاده کنید)</p>
                        </div>

                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700 mb-2">
                                عنوان نمایشی (فارسی) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="label"
                                   id="label"
                                   value="{{ old('label') }}"
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
                            <div class="flex gap-2">
                                <input type="text"
                                       name="group"
                                       id="group"
                                       value="{{ old('group') }}"
                                       list="groups-list"
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                       placeholder="کاربران، رزروها، تنظیمات"
                                       required>
                                <datalist id="groups-list">
                                    @foreach($groups as $group)
                                        <option value="{{ $group }}">
                                    @endforeach
                                </datalist>
                            </div>
                            @error('group')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">گروه دسترسی (می‌توانید گروه جدید وارد کنید یا از لیست انتخاب کنید)</p>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                توضیحات
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="3"
                                      class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                      placeholder="توضیحات کامل درباره این دسترسی...">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">توضیحات کوتاه درباره کاربرد این دسترسی</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 border-r-4 border-blue-400 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="mr-3">
                                <h3 class="text-sm font-medium text-blue-800">نکات مهم:</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>نام فنی باید یکتا باشد و از حروف انگلیسی و - استفاده کنید</li>
                                        <li>از نام‌گذاری استاندارد استفاده کنید: <code class="bg-blue-100 px-1 rounded">view-</code>, <code class="bg-blue-100 px-1 rounded">create-</code>, <code class="bg-blue-100 px-1 rounded">edit-</code>, <code class="bg-blue-100 px-1 rounded">delete-</code>, <code class="bg-blue-100 px-1 rounded">manage-</code></li>
                                        <li>گروه‌بندی منطقی دسترسی‌ها را رعایت کنید</li>
                                        <li>بعد از ایجاد دسترسی، می‌توانید آن را به نقش‌ها اختصاص دهید</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="flex justify-end space-x-3 space-x-reverse">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-6 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                ذخیره دسترسی
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
