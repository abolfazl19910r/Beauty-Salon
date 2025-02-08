@extends('layouts.app')

@section('title', 'مدیریت دسته‌بندی‌ها')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">مدیریت دسته‌بندی‌ها</h1>
            <a href="{{ route('admin.categories.create') }}"
               class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                افزودن دسته‌بندی جدید
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-right">نام</th>
                    <th class="px-6 py-3">تعداد خدمات</th>
                    <th class="px-6 py-3">تصویر</th>
                    <th class="px-6 py-3">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($categories as $category)
                    <tr>
                        <td class="px-6 py-4">{{ $category->name }}</td>
                        <td class="px-6 py-4">{{ $category->services->count() }}</td>
                        <td class="px-6 py-4">
                            @if($category->image)
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}"
                                     class="w-16 h-16 object-cover rounded">
                            @else
                                <span class="text-gray-400">بدون تصویر</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.categories.edit', $category) }}"
                               class="text-blue-500 hover:text-blue-700">ویرایش</a>

                            <form action="{{ route('admin.categories.destroy', $category) }}"
                                  method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-500 mr-4 hover:text-red-700"
                                        onclick="return confirm('آیا مطمئن هستید؟')">
                                    حذف
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $categories->links() }}
        </div>
    </div>
@endsection
