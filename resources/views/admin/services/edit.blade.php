@extends('layouts.app')

@section('title', 'ویرایش خدمت')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">ویرایش خدمت</h1>

            <form action="{{ route('admin.services.update', $service) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block mb-2">نام خدمت</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $service->name) }}"
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
                                {{ old('category_id', $service->category_id) == $category->id ? 'selected' : '' }}>
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
                              class="w-full border rounded px-3 py-2">{{ old('description', $service->description) }}</textarea>
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
                               value="{{ old('price', $service->price) }}"
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
                               value="{{ old('duration', $service->duration) }}"
                               class="w-full border rounded px-3 py-2"
                               required>
                        @error('duration')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="image" class="block mb-2">تصویر</label>
                    @if($service->image)
                        <div class="mb-2">
                            <img src="{{ $service->image_url }}"
                                 alt="{{ $service->name }}"
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
                    <a href="{{ route('admin.services.index') }}"
                       class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
