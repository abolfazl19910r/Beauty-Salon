@extends('layouts.specialist')

@section('title', 'جزئیات نظر')

@section('content')
    <div class="max-w-4xl mx-auto fade-in space-y-6">

        <a href="{{ route('specialist.reviews.index') }}" class="inline-flex items-center text-[var(--specialist-text-dim)] hover:text-[var(--specialist-plum-light)] transition-colors">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
            بازگشت به لیست نظرات
        </a>

        <div class="specialist-card overflow-hidden">
            <div class="p-8 border-b" style="border-color: var(--specialist-border); background-color: rgba(216, 174, 224, 0.06);">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 specialist-cta rounded-full flex items-center justify-center text-2xl font-bold">
                            {{ mb_substr($review->user->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa">{{ $review->user->name }}</h2>
                            <p class="text-[var(--specialist-text-dim)]">{{ $review->service->name }}</p>
                        </div>
                    </div>
                    <div class="text-left">
                        <div class="flex items-center gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-7 h-7 {{ $i <= $review->overall_rating ? 'text-[var(--specialist-plum-light)]' : 'text-[var(--specialist-inactive)]' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="text-[var(--specialist-text-dim)] text-sm persian-number">{{ verta($review->reviewed_at)->format('Y/m/d - H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-4 flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        امتیازات جزئی
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @php
                            $subRatings = [
                                ['label' => 'کیفیت کار', 'value' => $review->quality_rating, 'class' => 'text-sky-400'],
                                ['label' => 'رفتار متخصص', 'value' => $review->behavior_rating, 'class' => 'text-emerald-400'],
                                ['label' => 'تمیزی', 'value' => $review->cleanliness_rating, 'class' => 'text-teal-400'],
                                ['label' => 'سرعت', 'value' => $review->speed_rating, 'class' => 'text-amber-400'],
                            ];
                        @endphp
                        @foreach($subRatings as $sr)
                            <div class="rounded-xl p-4" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                                <div class="flex items-center justify-between">
                                    <span class="text-[var(--specialist-text-dim)] font-medium">{{ $sr['label'] }}</span>
                                    <div class="flex items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= $sr['value'] ? $sr['class'] : 'text-[var(--specialist-inactive)]' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($review->comment)
                    <div class="mb-8">
                        <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            نظر کتبی
                        </h3>
                        <div class="rounded-xl p-6" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                            <p class="text-[var(--specialist-text)] leading-relaxed text-lg">{{ $review->comment }}</p>
                        </div>
                    </div>
                @endif

                @if($review->specialist_response)
                    <div class="mb-8">
                        <h3 class="text-sm font-bold text-emerald-300 font-serif-fa mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            پاسخ شما
                        </h3>
                        <div class="rounded-xl p-6" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <p class="text-[var(--specialist-text)] leading-relaxed text-lg mb-3">{{ $review->specialist_response }}</p>
                            <p class="text-sm text-[var(--specialist-plum-muted)] persian-number">پاسخ داده شده در: {{ verta($review->responded_at)->format('Y/m/d - H:i') }}</p>
                        </div>

                        <div class="flex gap-3 mt-4">
                            <button onclick="document.getElementById('editResponseModal').classList.remove('hidden')"
                                    class="px-6 py-2 rounded-lg font-bold transition text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)]"
                                    style="border: 1px solid var(--specialist-border);">
                                ویرایش پاسخ
                            </button>
                            <form action="{{ route('specialist.reviews.delete-response', $review->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('آیا از حذف پاسخ اطمینان دارید؟')"
                                        class="bg-red-600/90 text-white px-6 py-2 rounded-lg font-bold hover:bg-red-600 transition">
                                    حذف پاسخ
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="mb-8">
                        <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-4 flex items-center">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            پاسخ به نظر
                        </h3>
                        <form action="{{ route('specialist.reviews.respond', $review->id) }}" method="POST">
                            @csrf
                            <textarea name="response" rows="5" required
                                      class="w-full rounded-xl px-4 py-3 text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                      style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                      placeholder="پاسخ خود را اینجا بنویسید... (حداکثر 1000 کاراکتر)">{{ old('response') }}</textarea>
                            @error('response')
                            <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                    class="specialist-cta mt-4 px-8 py-3 rounded-xl font-bold transition-opacity hover:opacity-90">
                                ارسال پاسخ
                            </button>
                        </form>
                    </div>
                @endif

                <div class="rounded-xl p-6" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                    <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-4">اطلاعات نوبت</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-[var(--specialist-plum-muted)]">شماره نوبت:</span>
                            <span class="font-bold text-[var(--specialist-text)] mr-2 persian-number">#{{ $review->booking_id }}</span>
                        </div>
                        <div>
                            <span class="text-[var(--specialist-plum-muted)]">تاریخ نوبت:</span>
                            <span class="font-bold text-[var(--specialist-text)] mr-2 persian-number">{{ verta($review->booking->booking_time)->format('Y/m/d') }}</span>
                        </div>
                        <div>
                            <span class="text-[var(--specialist-plum-muted)]">شماره تماس:</span>
                            <span class="font-bold text-[var(--specialist-text)] mr-2" dir="ltr">{{ $review->user->phone }}</span>
                        </div>
                        <div>
                            <span class="text-[var(--specialist-plum-muted)]">وضعیت پرداخت:</span>
                            <span class="font-bold mr-2 {{ $review->booking->payment_status === 'paid' ? 'text-emerald-300' : 'text-red-300' }}">
                                {{ $review->booking->payment_status === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit response modal --}}
    <div id="editResponseModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="specialist-card p-8 max-w-2xl w-full border" style="border-color: var(--specialist-border);">
            <h3 class="text-lg font-bold text-[var(--specialist-text)] font-serif-fa mb-6">ویرایش پاسخ</h3>
            <form action="{{ route('specialist.reviews.update-response', $review->id) }}" method="POST">
                @csrf
                @method('PUT')
                <textarea name="response" rows="6" required
                          class="w-full rounded-xl px-4 py-3 mb-4 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                          style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">{{ $review->specialist_response }}</textarea>
                <div class="flex gap-3">
                    <button type="submit" class="specialist-cta flex-1 py-3 rounded-xl font-bold transition-opacity hover:opacity-90">
                        ذخیره تغییرات
                    </button>
                    <button type="button" onclick="document.getElementById('editResponseModal').classList.add('hidden')"
                            class="flex-1 py-3 rounded-xl font-bold transition text-[var(--specialist-text-dim)] hover:bg-white/5" style="border: 1px solid var(--specialist-border);">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
