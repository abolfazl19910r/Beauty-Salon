@extends('layouts.admin')

@section('title', 'جزئیات نظر')

@section('content')
    <div class="max-w-5xl mx-auto fade-in">
        <div class="mb-6">
            <a href="{{ route('admin.reviews.index') }}" class="inline-flex items-center text-gray-600 hover:text-blue-600 transition">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
                بازگشت به لیست نظرات
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold">جزئیات نظر #{{ $review->id }}</h1>
                    <div class="flex gap-2">
                        @if($review->is_approved)
                            <span class="px-4 py-2 bg-green-500 bg-opacity-30 backdrop-blur rounded-lg font-bold">✓ تایید شده</span>
                        @else
                            <span class="px-4 py-2 bg-red-500 bg-opacity-30 backdrop-blur rounded-lg font-bold">✗ رد شده</span>
                        @endif
                        @if($review->is_featured)
                            <span class="px-4 py-2 bg-yellow-500 bg-opacity-30 backdrop-blur rounded-lg font-bold">⭐ ویژه</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white bg-opacity-10 backdrop-blur rounded-xl p-4">
                        <p class="text-white text-opacity-80 text-sm mb-1">کاربر</p>
                        <p class="font-bold text-lg">{{ $review->user->name }}</p>
                        <p class="text-sm text-white text-opacity-70">{{ $review->user->phone }}</p>
                    </div>
                    <div class="bg-white bg-opacity-10 backdrop-blur rounded-xl p-4">
                        <p class="text-white text-opacity-80 text-sm mb-1">متخصص</p>
                        <p class="font-bold text-lg">{{ $review->specialist->name }}</p>
                    </div>
                    <div class="bg-white bg-opacity-10 backdrop-blur rounded-xl p-4">
                        <p class="text-white text-opacity-80 text-sm mb-1">سرویس</p>
                        <p class="font-bold text-lg">{{ $review->service->name }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="text-center mb-8 pb-8 border-b">
                    <p class="text-gray-600 mb-2">امتیاز کلی</p>
                    <div class="flex items-center justify-center gap-2 mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-10 h-10 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        @endfor
                    </div>
                    <p class="text-4xl font-bold text-gray-800">{{ $review->overall_rating }}/5</p>
                    <p class="text-sm text-gray-500 mt-2">{{ verta($review->reviewed_at)->format('Y/m/d - H:i') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div class="bg-blue-50 rounded-xl p-5 border-2 border-blue-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-800">کیفیت کار</span>
                            <span class="text-2xl font-bold text-blue-600">{{ $review->quality_rating }}/5</span>
                        </div>
                        <div class="flex gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= $review->quality_rating ? 'text-blue-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-xl p-5 border-2 border-green-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-800">رفتار متخصص</span>
                            <span class="text-2xl font-bold text-green-600">{{ $review->behavior_rating }}/5</span>
                        </div>
                        <div class="flex gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= $review->behavior_rating ? 'text-green-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                    </div>

                    <div class="bg-teal-50 rounded-xl p-5 border-2 border-teal-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-800">تمیزی</span>
                            <span class="text-2xl font-bold text-teal-600">{{ $review->cleanliness_rating }}/5</span>
                        </div>
                        <div class="flex gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= $review->cleanliness_rating ? 'text-teal-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                    </div>

                    <div class="bg-orange-50 rounded-xl p-5 border-2 border-orange-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-800">سرعت</span>
                            <span class="text-2xl font-bold text-orange-600">{{ $review->speed_rating }}/5</span>
                        </div>
                        <div class="flex gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= $review->speed_rating ? 'text-orange-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                    </div>
                </div>

                @if($review->comment)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            نظر کتبی
                        </h3>
                        <div class="bg-gray-50 rounded-xl p-6 border-r-4 border-purple-500">
                            <p class="text-gray-700 leading-relaxed text-lg">{{ $review->comment }}</p>
                        </div>
                    </div>
                @endif

                @if($review->specialist_response)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            پاسخ متخصص
                        </h3>
                        <div class="bg-green-50 rounded-xl p-6 border-r-4 border-green-500">
                            <p class="text-gray-700 leading-relaxed text-lg mb-2">{{ $review->specialist_response }}</p>
                            <p class="text-sm text-gray-500">{{ verta($review->responded_at)->format('Y/m/d - H:i') }}</p>
                        </div>
                    </div>
                @endif

                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">اطلاعات نوبت</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">شماره نوبت:</span>
                            <p class="font-bold text-gray-800">#{{ $review->booking_id }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">تاریخ نوبت:</span>
                            <p class="font-bold text-gray-800">{{ verta($review->booking->booking_time)->format('Y/m/d') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600">وضعیت پرداخت:</span>
                            <p class="font-bold {{ $review->booking->payment_status === 'paid' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $review->booking->payment_status === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-600">مبلغ:</span>
                            <p class="font-bold text-gray-800">{{ number_format($review->booking->prepayment_amount) }} تومان</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if(!$review->is_approved)
                        <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition shadow-lg">
                                ✓ تایید نظر
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition shadow-lg">
                                ✗ رد نظر
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.reviews.toggle-featured', $review->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-yellow-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-yellow-700 transition shadow-lg">
                            {{ $review->is_featured ? '☆ حذف از ویژه' : '⭐ علامت‌گذاری ویژه' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.bookings.show', $review->booking_id) }}"
                       class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg">
                        مشاهده نوبت
                    </a>

                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('آیا از حذف این نظر اطمینان دارید؟')"
                                class="bg-gray-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-700 transition shadow-lg">
                            🗑️ حذف نظر
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
