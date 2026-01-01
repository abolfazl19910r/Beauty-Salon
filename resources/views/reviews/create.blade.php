@extends('layouts.app')

@section('title', 'ثبت نظر')

@push('styles')
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.5rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #d1d5db;
            transition: all 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .rating-category {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .rating-category:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .selected-rating {
            display: inline-block;
            margin-right: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            border-radius: 9999px;
            font-weight: bold;
            font-size: 0.875rem;
        }
    </style>
@endpush

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl shadow-lg p-8 mb-6">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mb-4">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">نظرسنجی و ارزیابی</h1>
                <p class="text-gray-600">نظر شما برای ما بسیار ارزشمند است</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-4 border-b">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">{{ $booking->service->name }}</h3>
                        <p class="text-gray-600 text-sm">متخصص: {{ $booking->specialist->name }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-sm text-gray-500">تاریخ خدمت:</p>
                        <p class="font-semibold text-gray-700">{{ verta($booking->booking_time)->format('Y/m/d') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('reviews.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="rating-category">
                <div class="text-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 ml-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        امتیاز کلی
                    </h3>
                    <span class="selected-rating hidden" id="overall-text"></span>
                </div>
                <div class="star-rating" data-rating-name="overall_rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <input type="radio" name="overall_rating" id="overall_{{ $i }}" value="{{ $i }}" required>
                        <label for="overall_{{ $i }}">★</label>
                    @endfor
                </div>
                @error('overall_rating')
                <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                @enderror
            </div>

            <div class="rating-category">
                <div class="text-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700 mb-1 flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        کیفیت کار
                    </h3>
                    <span class="selected-rating hidden" id="quality-text"></span>
                </div>
                <div class="star-rating" data-rating-name="quality_rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <input type="radio" name="quality_rating" id="quality_{{ $i }}" value="{{ $i }}" required>
                        <label for="quality_{{ $i }}">★</label>
                    @endfor
                </div>
                @error('quality_rating')
                <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                @enderror
            </div>

            <div class="rating-category">
                <div class="text-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700 mb-1 flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        رفتار متخصص
                    </h3>
                    <span class="selected-rating hidden" id="behavior-text"></span>
                </div>
                <div class="star-rating" data-rating-name="behavior_rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <input type="radio" name="behavior_rating" id="behavior_{{ $i }}" value="{{ $i }}" required>
                        <label for="behavior_{{ $i }}">★</label>
                    @endfor
                </div>
                @error('behavior_rating')
                <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                @enderror
            </div>

            <div class="rating-category">
                <div class="text-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700 mb-1 flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        تمیزی
                    </h3>
                    <span class="selected-rating hidden" id="cleanliness-text"></span>
                </div>
                <div class="star-rating" data-rating-name="cleanliness_rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <input type="radio" name="cleanliness_rating" id="cleanliness_{{ $i }}" value="{{ $i }}" required>
                        <label for="cleanliness_{{ $i }}">★</label>
                    @endfor
                </div>
                @error('cleanliness_rating')
                <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                @enderror
            </div>

            <div class="rating-category">
                <div class="text-center mb-4">
                    <h3 class="text-lg font-bold text-gray-700 mb-1 flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        سرعت
                    </h3>
                    <span class="selected-rating hidden" id="speed-text"></span>
                </div>
                <div class="star-rating" data-rating-name="speed_rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <input type="radio" name="speed_rating" id="speed_{{ $i }}" value="{{ $i }}" required>
                        <label for="speed_{{ $i }}">★</label>
                    @endfor
                </div>
                @error('speed_rating')
                <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                @enderror
            </div>

            <div class="rating-category">
                <label class="block text-lg font-bold text-gray-800 mb-3 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    نظر شما (اختیاری)
                </label>
                <textarea name="comment" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder="لطفاً تجربه خود را با ما به اشتراک بگذارید...">{{ old('comment') }}</textarea>
                <p class="text-sm text-gray-500 mt-2">حداکثر 500 کاراکتر</p>
                @error('comment')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    ✅ ثبت نظر
                </button>
                <a href="{{ route('home') }}"
                   class="px-8 py-4 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                    انصراف
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ratingTexts = {
                    1: '😞 بسیار ضعیف',
                    2: '😐 ضعیف',
                    3: '🙂 متوسط',
                    4: '😊 خوب',
                    5: '😍 عالی'
                };

                document.querySelectorAll('.star-rating').forEach(container => {
                    const inputs = container.querySelectorAll('input[type="radio"]');
                    const ratingName = container.dataset.ratingName;
                    const textSpan = document.getElementById(ratingName.replace('_rating', '-text'));

                    inputs.forEach(input => {
                        input.addEventListener('change', function() {
                            if (textSpan) {
                                textSpan.textContent = ratingTexts[this.value];
                                textSpan.classList.remove('hidden');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
@endsection
