@extends('layouts.app')
@section('title', 'ثبت نظر')

@section('content')
    <style>
        .star-rating { display:flex; flex-direction:row-reverse; justify-content:center; gap:.4rem; }
        .star-rating input { display:none; }
        .star-rating label {
            cursor:pointer; font-size:2rem; color:rgba(248,243,233,0.15); transition:all .2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color:#E6CD8A; transform:scale(1.1);
        }
        .rating-category {
            background:#2E2117; border:1px solid rgba(201,162,75,0.1);
            border-radius:1rem; padding:1.5rem; margin-bottom:1rem; transition:all .3s;
        }
        .rating-category:hover { border-color:rgba(201,162,75,0.25); }
        .selected-rating {
            display:inline-block; margin-right:.5rem; padding:.25rem .75rem;
            background:linear-gradient(135deg,#E6CD8A,#C9A24B); color:#1A1410;
            border-radius:999px; font-weight:bold; font-size:.8rem;
        }
        .gold-textarea {
            background:rgba(248,243,233,0.04); border:1px solid rgba(201,162,75,0.2);
            color:#F8F3E9; border-radius:0.75rem; padding:0.875rem 1rem; width:100%;
        }
        .gold-textarea::placeholder { color:rgba(248,243,233,0.3); }
        .gold-textarea:focus { outline:none; border-color:#C9A24B; }
    </style>

    <div class="max-w-3xl mx-auto fade-in">

        {{-- Header --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/15 p-8 mb-6 text-center">
            <div class="w-16 h-16 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#E6CD8A]" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[#E6CD8A] mb-2" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">نظرسنجی و ارزیابی</h1>
            <p class="text-[#F8F3E9]/55 text-sm mb-6">نظر شما برای ما بسیار ارزشمند است</p>

            <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/8 p-5 flex items-center justify-between text-right">
                <div>
                    <h3 class="font-bold text-[#F8F3E9]">{{ $booking->service->name }}</h3>
                    <p class="text-[#F8F3E9]/55 text-sm mt-1">متخصص: {{ $booking->specialist->name }}</p>
                </div>
                <div class="text-left">
                    <p class="text-xs text-[#F8F3E9]/45">تاریخ خدمت</p>
                    <p class="font-semibold text-[#E6CD8A] text-sm persian-number">{{ verta($booking->booking_time)->format('Y/m/d') }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('reviews.store') }}" method="POST" class="space-y-1">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            @foreach([
                ['name'=>'overall_rating','title'=>'امتیاز کلی','icon'=>'star'],
                ['name'=>'quality_rating','title'=>'کیفیت کار','icon'=>'check'],
                ['name'=>'behavior_rating','title'=>'رفتار متخصص','icon'=>'smile'],
                ['name'=>'cleanliness_rating','title'=>'تمیزی','icon'=>'check2'],
                ['name'=>'speed_rating','title'=>'سرعت','icon'=>'bolt'],
            ] as $cat)
                <div class="rating-category">
                    <div class="text-center mb-4">
                        <h3 class="text-lg font-bold text-[#F8F3E9] mb-1">{{ $cat['title'] }}</h3>
                        <span class="selected-rating hidden" id="{{ str_replace('_rating','',$cat['name']) }}-text"></span>
                    </div>
                    <div class="star-rating" data-rating-name="{{ $cat['name'] }}">
                        @for ($i = 1; $i <= 5; $i++)
                            <input type="radio" name="{{ $cat['name'] }}" id="{{ $cat['name'] }}_{{ $i }}" value="{{ $i }}" required>
                            <label for="{{ $cat['name'] }}_{{ $i }}">★</label>
                        @endfor
                    </div>
                    @error($cat['name'])
                    <p class="text-red-400 text-sm mt-2 text-center">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="rating-category">
                <label class="block text-lg font-bold text-[#F8F3E9] mb-3">نظر شما (اختیاری)</label>
                <textarea name="comment" rows="4" class="gold-textarea"
                          placeholder="لطفاً تجربه خود را با ما به اشتراک بگذارید...">{{ old('comment') }}</textarea>
                <p class="text-xs text-[#F8F3E9]/40 mt-2">حداکثر ۵۰۰ کاراکتر</p>
                @error('comment')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 py-3.5 rounded-xl text-sm font-bold transition-all
                           bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                           hover:shadow-lg hover:shadow-[#C9A24B]/25">
                    ثبت نظر
                </button>
                <a href="{{ route('home') }}"
                   class="px-8 py-3.5 rounded-xl text-sm border border-[#C9A24B]/25
                      text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors text-center">
                    انصراف
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ratingTexts = { 1:'بسیار ضعیف', 2:'ضعیف', 3:'متوسط', 4:'خوب', 5:'عالی' };
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
