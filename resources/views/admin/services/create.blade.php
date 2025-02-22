@extends('layouts.admin')

@section('title', 'افزودن خدمت جدید')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">افزودن خدمت جدید</h1>

            <form action="{{ route('admin.services.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block mb-2">نام خدمت</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="w-full border rounded px-3 py-2"
                           required>
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block mb-2">دسته‌بندی</label>
                    <select id="category_id"
                            name="category_id"
                            class="w-full border rounded px-3 py-2">
                        <option value="">بدون دسته‌بندی</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block mb-2">توضیحات</label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="price" class="block mb-2">قیمت (تومان)</label>
                        <input type="number"
                               id="price"
                               name="price"
                               value="{{ old('price') }}"
                               class="w-full border rounded px-3 py-2"
                               required>
                        @error('price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="duration" class="block mb-2">مدت زمان (دقیقه)</label>
                        <input type="number"
                               id="duration"
                               name="duration"
                               value="{{ old('duration') }}"
                               class="w-full border rounded px-3 py-2"
                               required>
                        @error('duration')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="image" class="block mb-2">تصویر</label>
                    <input type="file"
                           id="image"
                           name="image"
                           class="border rounded px-3 py-2"
                           accept="image/*">
                    @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-between">
                    <button type="submit"
                            class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        ذخیره
                    </button>
                    <a href="{{ route('admin.services.index') }}"
                       class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
