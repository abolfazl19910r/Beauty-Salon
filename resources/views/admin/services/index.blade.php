@extends('layouts.app')

@section('title', 'مدیریت خدمات')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">مدیریت خدمات</h1>
            <a href="{{ route('admin.services.create') }}"
               class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                افزودن خدمت جدید
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-right">تصویر</th>
                    <th class="px-6 py-3">نام</th>
                    <th class="px-6 py-3">دسته‌بندی</th>
                    <th class="px-6 py-3">قیمت</th>
                    <th class="px-6 py-3">مدت زمان</th>
                    <th class="px-6 py-3">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($services as $service)
                    <tr>
                        <td class="px-6 py-4">
                            @if($service->image)
                                <img src="{{ $service->image_url }}"
                                     alt="{{ $service->name }}"
                                     class="w-16 h-16 object-cover rounded">
                            @else
                                <span class="text-gray-400">بدون تصویر</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $service->name }}</td>
                        <td class="px-6 py-4">{{ $service->category?->name ?? 'بدون دسته‌بندی' }}</td>
                        <td class="px-6 py-4">{{ number_format($service->price) }}</td>
                        <td class="px-6 py-4">{{ $service->duration }} دقیقه</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.services.edit', $service) }}"
                               class="text-blue-500 hover:text-blue-700">ویرایش</a>

                            <form action="{{ route('admin.services.destroy', $service) }}"
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
            {{ $services->links() }}
        </div>
    </div>
@endsection
