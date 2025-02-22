@extends('layouts.admin')

@section('title', 'ویرایش متخصص')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">ویرایش اطلاعات {{ $specialist->name }}</h1>
                <a href="{{ route('admin.specialists.show', ['specialist' => $specialist->id]) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                    بازگشت
                </a>
            </div>

            <form action="{{ route('admin.specialists.update', $specialist->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block mb-2">نام متخصص</label>
                    <input type="text" name="name" value="{{ old('name', $specialist->name) }}"
                           class="w-full border rounded px-3 py-2" required>
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2">شماره تماس</label>
                    <input type="text" name="phone" value="{{ old('phone', $specialist->phone) }}"
                           class="w-full border rounded px-3 py-2" required dir="ltr">
                    @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2">ایمیل</label>
                    <input type="email" name="email" value="{{ old('email', $specialist->email) }}"
                           class="w-full border rounded px-3 py-2" required dir="ltr">
                    @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2">خدمات</label>
                    <div class="space-y-4">
                        @foreach($services as $category)
                            <div class="border rounded-lg p-4">
                                <h3 class="font-bold mb-3">{{ $category->name }}</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    @foreach($category->services as $service)
                                        <label class="flex items-center">
                                            <input type="checkbox"
                                                   name="services[]"
                                                   value="{{ $service->id }}"
                                                   class="ml-2"
                                                {{ in_array($service->id, old('services', $specialist->services->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <span>{{ $service->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('services')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded">
                        ذخیره تغییرات
                    </button>
                    <a href="{{ route('admin.specialists.show', $specialist) }}"
                       class="bg-gray-200 text-gray-700 px-6 py-2 rounded">
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
