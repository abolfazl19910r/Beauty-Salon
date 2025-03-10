@extends('layouts.admin')

@section('title', 'مدیریت خدمات')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">مدیریت خدمات</h1>
            <a href="{{ route('admin.services.create') }}"
               class="inline-flex items-center bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg shadow hover:shadow-md transition-all duration-200">
                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                افزودن خدمت جدید
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-x-auto" dir="rtl">
            <table class="w-full text-sm" dir="rtl">
                <thead>
                <tr class="bg-gradient-to-r from-blue-50 to-blue-100 text-blue-800">
                    <th class="px-6 py-3 text-right">تصویر</th>
                    <th class="px-6 py-3 text-right">نام</th>
                    <th class="px-6 py-3 text-right">دسته‌بندی</th>
                    <th class="px-6 py-3 text-right">قیمت</th>
                    <th class="px-6 py-3 text-right">مدت زمان</th>
                    <th class="px-6 py-3 text-right">عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($services as $service)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-right">
                            @if($service->image)
                                <img src="{{ $service->image_url }}"
                                     alt="{{ $service->name }}"
                                     class="w-16 h-16 object-cover rounded-lg shadow">
                            @else
                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-medium">{{ $service->name }}</td>
                        <td class="px-6 py-4 text-right">
                            @if($service->category)
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                    {{ $service->category->name }}
                                </span>
                            @else
                                <span class="text-gray-400">بدون دسته‌بندی</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-medium text-green-600 persian-number">{{ number_format($service->price) }}</span>
                            <span class="text-xs text-gray-500 mr-1">تومان</span>
                        </td>
                        <td class="px-6 py-4 text-right persian-number">
                            {{ $service->duration }} دقیقه
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.services.edit', $service) }}"
                                   class="group inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                                    <svg class="w-4 h-4 mr-1 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>ویرایش</span>
                                </a>

                                <form action="{{ route('admin.services.destroy', ['service' => $service->id]) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('آیا از حذف این خدمت اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="group inline-flex items-center text-red-600 hover:text-red-800 transition-colors">
                                        <svg class="w-4 h-4 mr-1 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        <span>حذف</span>
                                    </button>
                                </form>
                            </div>
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
