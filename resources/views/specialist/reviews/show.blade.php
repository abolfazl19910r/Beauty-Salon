@extends('layouts.specialist')

@section('title', 'جزئیات نظر')

@section('content')
    <div class="max-w-4xl mx-auto fade-in">
        <div class="mb-6">
            <a href="{{ route('specialist.reviews.index') }}" class="inline-flex items-center text-gray-600 hover:text-purple-600 transition">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
                بازگشت به لیست نظرات
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-8 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-2xl font-bold">
                            {{ mb_substr($review->user->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">{{ $review->user->name }}</h2>
                            <p class="text-white text-opacity-80">{{ $review->service->name }}</p>
                        </div>
                    </div>
                    <div class="text-left">
                        <div class="flex items-center gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-8 h-8 {{ $i <= $review->overall_rating ? 'text-yellow-300' : 'text-white text-opacity-30' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="text-white text-opacity-80 text-sm">{{ verta($review->reviewed_at)->format('Y/m/d - H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        امتیازات جزئی
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-xl p-4 border-2 border-blue-200">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 font-medium">کیفیت کار</span>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->quality_rating ? 'text-blue-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-xl p-4 border-2 border-green-200">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 font-medium">رفتار متخصص</span>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->behavior_rating ? 'text-green-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="bg-teal-50 rounded-xl p-4 border-2 border-teal-200">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 font-medium">تمیزی</span>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->cleanliness_rating ? 'text-teal-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="bg-orange-50 rounded-xl p-4 border-2 border-orange-200">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 font-medium">سرعت</span>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->speed_rating ? 'text-orange-500' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($review->comment)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            نظر کتبی
                        </h3>
                        <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200">
                            <p class="text-gray-700 leading-relaxed text-lg">{{ $review->comment }}</p>
                        </div>
                    </div>
                @endif

                @if($review->specialist_response)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            پاسخ شما
                        </h3>
                        <div class="bg-gradient-to-br from-green-50 to-teal-50 rounded-xl p-6 border-2 border-green-200">
                            <p class="text-gray-700 leading-relaxed text-lg mb-3">{{ $review->specialist_response }}</p>
                            <p class="text-sm text-gray-500">پاسخ داده شده در: {{ verta($review->responded_at)->format('Y/m/d - H:i') }}</p>
                        </div>

                        <div class="flex gap-3 mt-4">
                            <button onclick="document.getElementById('editResponseModal').classList.remove('hidden')"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                                ویرایش پاسخ
                            </button>
                            <form action="{{ route('specialist.reviews.delete-response', $review->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('آیا از حذف پاسخ اطمینان دارید؟')"
                                        class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-red-700 transition">
                                    حذف پاسخ
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            پاسخ به نظر
                        </h3>
                        <form action="{{ route('specialist.reviews.respond', $review->id) }}" method="POST">
                            @csrf
                            <textarea name="response" rows="5" required
                                      class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                      placeholder="پاسخ خود را اینجا بنویسید... (حداکثر 1000 کاراکتر)">{{ old('response') }}</textarea>
                            @error('response')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                    class="mt-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg">
                                ✅ ارسال پاسخ
                            </button>
                        </form>
                    </div>
                @endif

                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">اطلاعات نوبت</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">شماره نوبت:</span>
                            <span class="font-bold text-gray-800 mr-2">#{{ $review->booking_id }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">تاریخ نوبت:</span>
                            <span class="font-bold text-gray-800 mr-2">{{ verta($review->booking->booking_time)->format('Y/m/d') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">شماره تماس:</span>
                            <span class="font-bold text-gray-800 mr-2 dir-ltr">{{ $review->user->phone }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">وضعیت پرداخت:</span>
                            <span class="font-bold {{ $review->booking->payment_status === 'paid' ? 'text-green-600' : 'text-red-600' }} mr-2">
                            {{ $review->booking->payment_status === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}
                        </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editResponseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl p-8 max-w-2xl w-full">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">ویرایش پاسخ</h3>
            <form action="{{ route('specialist.reviews.update-response', $review->id) }}" method="POST">
                @csrf
                @method('PUT')
                <textarea name="response" rows="6" required
                          class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 mb-4">{{ $review->specialist_response }}</textarea>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-purple-600 text-white py-3 rounded-xl font-bold hover:bg-purple-700 transition">
                        ذخیره تغییرات
                    </button>
                    <button type="button" onclick="document.getElementById('editResponseModal').classList.add('hidden')"
                            class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
