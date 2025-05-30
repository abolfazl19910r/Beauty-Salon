@extends('layouts.admin')

@section('title', 'ایجاد مقاله جدید')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">ایجاد مقاله جدید</h1>
            <a href="{{ route('admin.blog.index') }}" class="btn btn-secondary">بازگشت به لیست مقالات</a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.blog.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2">عنوان مقاله</label>
                    <input
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        class="w-full border rounded p-2"
                        required
                    >
                </div>
                <div>
                    <label class="block mb-2">دسته‌بندی</label>
                    <select name="category_id" class="w-full border rounded p-2" required>
                        <option value="">انتخاب دسته‌بندی</option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                {{ old('category_id') == $category->id ? 'selected' : '' }}
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="block mb-2">محتوا</label>
                <textarea
                    name="content"
                    rows="6"
                    class="w-full border rounded p-2"
                    required
                >{{ old('content') }}</textarea>
            </div>

            <div class="mt-4">
                <label class="block mb-2">چکیده (اختیاری)</label>
                <textarea
                    name="excerpt"
                    rows="3"
                    class="w-full border rounded p-2"
                >{{ old('excerpt') }}</textarea>
            </div>

            <div class="mt-4">
                <label class="block mb-2">تصویر (اختیاری)</label>
                <input
                    type="file"
                    name="image"
                    accept="image/*"
                    class="w-full border rounded p-2"
                >
            </div>

            <div class="mt-4 flex items-center">
                <label class="inline-flex items-center ml-4">
                    <input
                        type="checkbox"
                        name="is_published"
                        value="1"
                        {{ old('is_published') ? 'checked' : '' }}
                        class="form-checkbox"
                    >
                    <span class="mr-2">منتشر شود</span>
                </label>

                <div>
                    <label class="block mb-2">تاریخ انتشار</label>
                    <input
                        type="text"
                        name="published_at_jalali"
                        value="{{ old('published_at_jalali', \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i')) }}"
                        class="border rounded p-2"
                        placeholder="مثال: 1403/03/15 14:30"
                    >
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary">ذخیره مقاله</button>
            </div>
        </form>
    </div>
@endsection
