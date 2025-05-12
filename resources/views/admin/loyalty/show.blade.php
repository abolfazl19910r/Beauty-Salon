@extends('layouts.admin')

@section('title', 'نمایش پاداش')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                نمایش پاداش: {{ $reward->title }}
            </h1>
            <div>
                <a href="{{ route('admin.loyalty.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت به مدیریت امتیازات
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden fade-in">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">جزئیات پاداش</h2>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">عنوان:</span>
                                <span class="text-gray-800 font-medium">{{ $reward->title }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">توضیحات:</span>
                                <span class="text-gray-800">{{ $reward->description ?? 'بدون توضیحات' }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">امتیاز مورد نیاز:</span>
                                <span class="text-gray-800 font-medium persian-number">{{ number_format($reward->required_points) }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">نوع تخفیف:</span>
                                <span class="text-gray-800">
                                    {{ $reward->discount_type === 'fixed' ? 'مبلغ ثابت' : 'درصدی' }}
                                </span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">مقدار تخفیف:</span>
                                <span class="text-gray-800 font-medium persian-number">
                                    {{ number_format($reward->discount_amount) }}
                                    {{ $reward->discount_type === 'percentage' ? '%' : 'تومان' }}
                                </span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">حداکثر استفاده:</span>
                                <span class="text-gray-800 font-medium persian-number">{{ number_format($reward->max_uses) }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">تعداد استفاده شده:</span>
                                <span class="text-gray-800 font-medium persian-number">{{ number_format($reward->used_count) }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-1/3 text-sm text-gray-500">وضعیت:</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $reward->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $reward->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">اقدامات</h2>
                            <div class="space-y-3">
                                @if($reward)
                                    <a href="{{ route('admin.loyalty.edit', $reward) }}"
                                       class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        ویرایش پاداش
                                    </a>
                                @else
                                    <span>پاداش یافت نشد</span>
                                @endif

                                <form action="{{ route('admin.loyalty.destroy', $reward) }}" method="POST" class="inline-flex mr-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-confirm-delete
                                            class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        حذف پاداش
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">تاریخ‌ها</h3>
                            <div class="space-y-2 text-sm text-gray-700">
                                <p>ایجاد شده در: <span class="persian-number">{{ $reward->created_at->format('Y/m/d H:i') }}</span></p>
                                <p>آخرین به‌روزرسانی: <span class="persian-number">{{ $reward->updated_at->format('Y/m/d H:i') }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
