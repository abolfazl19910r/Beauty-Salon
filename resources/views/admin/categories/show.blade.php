@extends('layouts.admin')

@section('title', 'مشاهده جزئیات دسته‌بندی')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-6 pb-4 border-b">
                <svg class="w-6 h-6 ml-2 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <h1 class="text-2xl font-bold">مشاهده جزئیات دسته‌بندی</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    <div>
                        <h2 class="text-xl font-semibold mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            اطلاعات اصلی
                        </h2>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                            <div class="flex flex-col md:flex-row md:items-center">
                                <span class="font-medium text-gray-700 md:w-1/4">نام دسته‌بندی:</span>
                                <span class="text-gray-800">{{ $category->name }}</span>
                            </div>
                            <div class="flex flex-col md:flex-row md:items-center">
                                <span class="font-medium text-gray-700 md:w-1/4">اسلاگ:</span>
                                <span class="text-gray-800 text-left dir-ltr">{{ $category->slug }}</span>
                            </div>
                            <div class="flex flex-col md:flex-row md:items-center">
                                <span class="font-medium text-gray-700 md:w-1/4">دسته والد:</span>
                                <span class="text-gray-800">
                                    {{ $category->parent ? $category->parent->name : 'دسته اصلی' }}
                                </span>
                            </div>
                            <div class="flex flex-col md:flex-row md:items-center">
                                <span class="font-medium text-gray-700 md:w-1/4">وضعیت:</span>
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </div>
                            <div class="flex flex-col md:flex-row md:items-center">
                                <span class="font-medium text-gray-700 md:w-1/4">ترتیب نمایش:</span>
                                <span class="text-gray-800">{{ $category->order }}</span>
                            </div>
                            <div class="flex flex-col md:flex-row md:items-center">
                                <span class="font-medium text-gray-700 md:w-1/4">آیکون:</span>
                                <div class="flex items-center">
                                    @if($category->icon)
                                        <i class="{{ $category->icon }} text-2xl text-indigo-600 ml-2"></i>
                                        <span class="text-gray-800">{{ $category->icon }}</span>
                                    @else
                                        <span class="text-gray-500">بدون آیکون</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($category->description)
                        <div>
                            <h2 class="text-xl font-semibold mb-4 flex items-center">
                                <svg class="w-5 h-5 ml-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="17" y1="10" x2="3" y2="10"></line>
                                    <line x1="21" y1="6" x2="3" y2="6"></line>
                                    <line x1="21" y1="14" x2="3" y2="14"></line>
                                    <line x1="17" y1="18" x2="3" y2="18"></line>
                                </svg>
                                توضیحات
                            </h2>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-800 whitespace-pre-line">{{ $category->description }}</p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <h2 class="text-xl font-semibold mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            وضعیت
                        </h2>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">تعداد زیردسته‌ها:</span>
                                <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full">{{ $childrenCount }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">تعداد خدمات مرتبط:</span>
                                <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full">{{ $servicesCount }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">تاریخ ایجاد:</span>
                                <span class="text-gray-800">
                                    @if($category->created_at)
                                        {{ jalali_date($category->created_at, 'Y/m/d') }}
                                        {{ $category->created_at->format('H:i') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">آخرین بروزرسانی:</span>
                                <span class="text-gray-800">
                                    @if($category->updated_at)
                                        {{ jalali_date($category->updated_at, 'Y/m/d') }}
                                        {{ $category->updated_at->format('H:i') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($category->children) && $category->children->count() > 0)
                        <div>
                            <h2 class="text-xl font-semibold mb-4 flex items-center">
                                <svg class="w-5 h-5 ml-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="8" y1="6" x2="21" y2="6"></line>
                                    <line x1="8" y1="12" x2="21" y2="12"></line>
                                    <line x1="8" y1="18" x2="21" y2="18"></line>
                                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                </svg>
                                زیردسته‌ها
                            </h2>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($category->children as $child)
                                        <a href="{{ route('admin.categories.show', $child->id) }}" class="flex items-center p-2 hover:bg-indigo-50 rounded-lg transition-colors">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold ml-2">
                                                @if($child->icon)
                                                    <i class="{{ $child->icon }}"></i>
                                                @else
                                                    {{ mb_substr($child->name, 0, 1) }}
                                                @endif
                                            </div>
                                            <div>
                                                <span class="block text-gray-900">{{ $child->name }}</span>
                                                <span class="text-xs text-gray-500">{{ $child->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    @if($category->image)
                        <div>
                            <h2 class="text-xl font-semibold mb-4 flex items-center">
                                <svg class="w-5 h-5 ml-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                تصویر
                            </h2>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-full rounded-lg shadow-sm">
                            </div>
                        </div>
                    @endif

                    <div>
                        <h2 class="text-xl font-semibold mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            عملیات
                        </h2>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <a href="{{ route('admin.categories.edit', $category->id) }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center px-4 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                ویرایش دسته‌بندی
                            </a>

                            <form action="{{ route('admin.categories.toggle-status', $category->id) }}" method="POST" class="block w-full">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full {{ $category->is_active ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' }} text-white text-center px-4 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        @if($category->is_active)
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                        @else
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        @endif
                                    </svg>
                                    {{ $category->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                </button>
                            </form>

                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="block w-full">
                                @csrf
                                @method('DELETE')
                                <button type="button" data-confirm-delete="true" data-confirm-message="آیا از حذف {{ $category->name }} اطمینان دارید؟" class="w-full bg-red-500 hover:bg-red-600 text-white text-center px-4 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                    حذف دسته‌بندی
                                </button>
                            </form>

                            <a href="{{ route('admin.categories.index') }}" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 text-center px-4 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 12H5"></path>
                                    <path d="M12 19l-7-7 7-7"></path>
                                </svg>
                                بازگشت به لیست
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('[data-confirm-delete="true"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const message = this.getAttribute('data-confirm-message') || 'آیا از حذف این آیتم اطمینان دارید؟';

                    if (confirm(message)) {
                        this.closest('form').submit();
                    }
                });
            });
        });
    </script>
@endpush
