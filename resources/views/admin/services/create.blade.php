@extends('layouts.admin')

@section('title', 'افزودن خدمت جدید')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-6 pb-4 border-b">
                <svg class="w-6 h-6 ml-2 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <h1 class="text-2xl font-bold">افزودن خدمت جدید</h1>
            </div>

            <form action="{{ route('admin.services.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-6">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-700">نام خدمت</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                           required>
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block mb-2 text-sm font-medium text-gray-700">دسته‌بندی</label>
                    <select id="category_id"
                            name="category_id"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">بدون دسته‌بندی</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block mb-2 text-sm font-medium text-gray-700">توضیحات</label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="price" class="block mb-2 text-sm font-medium text-gray-700">قیمت (تومان)</label>
                        <input type="number"
                               id="price"
                               name="price"
                               value="{{ old('price') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               required>
                        @error('price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="duration" class="block mb-2 text-sm font-medium text-gray-700">مدت زمان (دقیقه)</label>
                        <input type="number"
                               id="duration"
                               name="duration"
                               value="{{ old('duration') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               required>
                        @error('duration')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="image" class="block mb-2 text-sm font-medium text-gray-700">تصویر</label>
                    <div class="flex items-center">
                        <label class="w-full flex flex-col items-center px-4 py-6 bg-white text-blue-500 rounded-lg border border-blue-200 border-dashed cursor-pointer hover:bg-blue-50 transition-colors">
                            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span class="mt-2 text-sm text-gray-600" id="file-name-display">انتخاب تصویر خدمت</span>
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

                <div class="flex justify-between pt-4 border-t">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        ذخیره
                    </button>
                    <a href="{{ route('admin.services.index') }}"
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
