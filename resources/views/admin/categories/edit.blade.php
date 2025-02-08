@extends('layouts.app')

@section('title', 'ویرایش دسته‌بندی')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">ویرایش دسته‌بندی</h1>

            <form action="{{ route('admin.categories.update', $category) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block mb-2">نام دسته‌بندی</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $category->name) }}"
                           class="w-full border rounded px-3 py-2"
                           required>
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block mb-2">توضیحات</label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              class="w-full border rounded px-3 py-2">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="image" class="block mb-2">تصویر</label>
                    @if($category->image)
                        <div class="mb-2">
                            <img src="{{ $category->image_url }}"
                                 alt="{{ $category->name }}"
                                 class="w-32 h-32 object-cover rounded">
                        </div>
                    @endif
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
                        بروزرسانی
                    </button>
                    <a href="{{ route('admin.categories.index') }}"
                       class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
