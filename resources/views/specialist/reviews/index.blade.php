@extends('layouts.specialist')

@section('title', 'نظرات و ارزیابی‌ها')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .rating-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .rating-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            transition: width 0.5s ease;
        }
    </style>
@endpush

@section('content')
    <div class="fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white text-opacity-80 text-sm mb-1">میانگین امتیاز</p>
                        <p class="text-4xl font-bold">{{ number_format($averageRating, 1) }}</p>
                        <p class="text-white text-opacity-70 text-xs mt-1">از 5 ستاره</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg border-r-4 border-blue-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">تعداد نظرات</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total']) }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg border-r-4 border-green-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">نظرات 5 ستاره</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['five_star']) }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg border-r-4 border-orange-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">نیاز به پاسخ</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $reviews->where('specialist_response', null)->count() }}</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                توزیع امتیازات
            </h3>
            <div class="space-y-4">
                @foreach([5, 4, 3, 2, 1] as $rating)
                    @php
                        $count = $stats[['five_star', 'four_star', 'three_star', 'two_star', 'one_star'][$rating - 1]];
                        $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1 w-20">
                            <span class="font-semibold text-gray-700">{{ $rating }}</span>
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="rating-bar">
                                <div class="rating-bar-fill" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-600 w-16 text-left">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <button onclick="toggleFilters()" class="w-full flex items-center justify-between text-lg font-bold text-gray-800 mb-4">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                فیلتر نظرات
            </span>
                <svg id="filter-icon" class="w-5 h-5 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <form method="GET" id="filterForm" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">امتیاز</label>
                        <select name="rating" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500">
                            <option value="">همه امتیازات</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                                    {{ $i }} ستاره
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت پاسخ</label>
                        <select name="responded" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500">
                            <option value="">همه</option>
                            <option value="1" {{ request('responded') === '1' ? 'selected' : '' }}>پاسخ داده شده</option>
                            <option value="0" {{ request('responded') === '0' ? 'selected' : '' }}>بدون پاسخ</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                        <select name="sort_by" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500">
                            <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>جدیدترین</option>
                            <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>قدیمی‌ترین</option>
                            <option value="highest_rating" {{ request('sort_by') == 'highest_rating' ? 'selected' : '' }}>بالاترین امتیاز</option>
                            <option value="lowest_rating" {{ request('sort_by') == 'lowest_rating' ? 'selected' : '' }}>پایین‌ترین امتیاز</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                        <input type="text" name="date_from" id="date_from" value="{{ request('date_from') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500"
                               placeholder="1403/01/01" autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                        <input type="text" name="date_to" id="date_to" value="{{ request('date_to') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500"
                               placeholder="1403/12/29" autocomplete="off">
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-purple-700 transition">
                        اعمال فیلتر
                    </button>
                    <a href="{{ route('specialist.reviews.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-200 transition">
                        حذف فیلترها
                    </a>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($reviews as $review)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                    {{ mb_substr($review->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ $review->user->name }}</h4>
                                    <p class="text-sm text-gray-500">{{ $review->service->name }}</p>
                                </div>
                            </div>
                            <div class="text-left">
                                <div class="flex items-center gap-1 mb-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                                <p class="text-xs text-gray-500">{{ verta($review->reviewed_at)->format('Y/m/d') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 p-4 bg-gray-50 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">کیفیت</p>
                                <p class="font-bold text-blue-600">{{ $review->quality_rating }}/5</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">رفتار</p>
                                <p class="font-bold text-green-600">{{ $review->behavior_rating }}/5</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">تمیزی</p>
                                <p class="font-bold text-teal-600">{{ $review->cleanliness_rating }}/5</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500 mb-1">سرعت</p>
                                <p class="font-bold text-orange-600">{{ $review->speed_rating }}/5</p>
                            </div>
                        </div>

                        @if($review->comment)
                            <div class="mb-4 p-4 bg-blue-50 rounded-lg border-r-4 border-blue-500">
                                <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                            </div>
                        @endif

                        @if($review->specialist_response)
                            <div class="mb-4 p-4 bg-purple-50 rounded-lg border-r-4 border-purple-500">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                    <span class="text-sm font-bold text-purple-600">پاسخ شما:</span>
                                </div>
                                <p class="text-gray-700">{{ $review->specialist_response }}</p>
                                <p class="text-xs text-gray-500 mt-2">{{ verta($review->responded_at)->format('Y/m/d H:i') }}</p>
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <a href="{{ route('specialist.reviews.show', $review->id) }}"
                               class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition text-center">
                                مشاهده جزئیات
                            </a>
                            @if(!$review->specialist_response)
                                <a href="{{ route('specialist.reviews.show', $review->id) }}"
                                   class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-purple-700 transition text-center">
                                    پاسخ دادن
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl shadow p-12 text-center">
                    <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-gray-500 text-lg">هنوز نظری دریافت نکرده‌اید.</p>
                </div>
            @endforelse
        </div>

        @if($reviews->hasPages())
            <div class="mt-6">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://unpkg.com/jquery@3.6.0/dist/jquery.min.js"></script>
        <script src="https://unpkg.com/persian-date@latest/dist/persian-date.min.js"></script>
        <script src="https://unpkg.com/persian-datepicker@latest/dist/js/persian-datepicker.min.js"></script>
        <script>
            $(document).ready(function() {
                $("#date_from, #date_to").persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    calendar: {
                        persian: { locale: 'fa' }
                    }
                });
            });

            function toggleFilters() {
                const form = document.getElementById('filterForm');
                const icon = document.getElementById('filter-icon');
                form.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        </script>
    @endpush
@endsection
