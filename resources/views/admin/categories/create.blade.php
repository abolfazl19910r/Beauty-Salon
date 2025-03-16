@extends('layouts.admin')

@section('title', 'افزودن دسته‌بندی جدید')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-6 pb-4 border-b">
                <svg class="w-6 h-6 ml-2 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <h1 class="text-2xl font-bold">افزودن دسته‌بندی جدید</h1>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-700">نام دسته‌بندی</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                           required>
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="parent_id" class="block mb-2 text-sm font-medium text-gray-700">دسته والد</label>
                    <select id="parent_id" name="parent_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <option value="">بدون دسته والد (دسته اصلی)</option>
                        @foreach($parentCategories ?? [] as $category)
                            <option value="{{ $category->id }}" {{ old('parent_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block mb-2 text-sm font-medium text-gray-700">توضیحات</label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="order" class="block mb-2 text-sm font-medium text-gray-700">ترتیب نمایش</label>
                    <input type="number"
                           id="order"
                           name="order"
                           value="{{ old('order', 0) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    @error('order')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="icon" class="block mb-2 text-sm font-medium text-gray-700">آیکون</label>
                    <input type="text"
                           id="icon"
                           name="icon"
                           value="{{ old('icon') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                           placeholder="fa fa-example">
                    <p class="text-xs text-gray-500 mt-1">نام کلاس آیکون Font Awesome را وارد کنید</p>
                    @error('icon')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="image" class="block mb-2 text-sm font-medium text-gray-700">تصویر</label>
                    <div class="flex items-center">
                        <label class="w-full flex flex-col items-center px-4 py-6 bg-white text-indigo-500 rounded-lg border border-indigo-200 border-dashed cursor-pointer hover:bg-indigo-50 transition-colors">
                            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span class="mt-2 text-sm text-gray-600" id="file-name-display">انتخاب تصویر</span>
                            <input type="file"
                                   id="image"
                                   name="image"
                                   class="hidden"
                                   accept="image/*">
                        </label>
                    </div>
                    @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="is_active" class="inline-flex items-center">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <span class="mr-2 text-sm font-medium text-gray-700">فعال</span>
                    </label>
                    @error('is_active')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-between pt-4 border-t">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg shadow hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        ذخیره
                    </button>
                    <a href="{{ route('admin.categories.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5"></path>
                            <path d="M12 19l-7-7 7-7"></path>
                        </svg>
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('image');
            const fileNameDisplay = document.getElementById('file-name-display');

            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileName = this.files[0]?.name;
                    if (fileName) {
                        fileNameDisplay.textContent = fileName;
                    }
                });
            }
        });
    </script>
@endpush
