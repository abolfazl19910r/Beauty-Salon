@extends('layouts.app')

@section('title', 'افزودن متخصص جدید')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">افزودن متخصص جدید</h1>

            <form action="{{ route('admin.specialists.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="block mb-2">نام متخصص</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full border rounded px-3 py-2" required>
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2">شماره تماس</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border rounded px-3 py-2" required dir="ltr">
                    @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2">ایمیل</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border rounded px-3 py-2" required dir="ltr">
                    @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2">خدمات</label>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($services as $service)
                            <label class="flex items-center">
                                <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                       class="ml-2" {{ in_array($service->id, old('services', [])) ? 'checked' : '' }}>
                                <span>{{ $service->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('services')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded">
                        ذخیره
                    </button>
                    <a href="{{ route('admin.specialists.index') }}"
                       class="bg-gray-200 text-gray-700 px-6 py-2 rounded">
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
