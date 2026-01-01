@extends('layouts.admin')

@section('title', 'آمار و گزارشات نظرات')

@section('content')
    <div class="fade-in">
        <div class="mb-6">
            <a href="{{ route('admin.reviews.index') }}" class="inline-flex items-center text-gray-600 hover:text-blue-600 transition">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
                بازگشت به لیست نظرات
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 ml-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    توزیع امتیازات
                </h3>
                <div class="space-y-4">
                    @foreach([5, 4, 3, 2, 1] as $rating)
                        @php
                            $count = $ratingDistribution[$rating] ?? 0;
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                        @endphp
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1 w-24">
                                <span class="font-bold text-gray-700">{{ $rating }}</span>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="h-8 bg-gray-200 rounded-lg overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 flex items-center justify-end px-2"
                                         style="width: {{ $percentage }}%">
                                        @if($percentage > 15)
                                            <span class="text-white text-sm font-bold">{{ number_format($percentage, 1) }}%</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 w-16 text-left">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-6 border-t text-center">
                    <p class="text-3xl font-bold text-gray-800 mb-2">{{ number_format($averageRating, 1) }}/5</p>
                    <p class="text-gray-600">میانگین امتیاز کل</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-8">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                    <svg class="w-6 h-6 ml-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    بهترین متخصصین
                </h3>
                <div class="space-y-3">
                    @forelse($topSpecialists as $index => $specialist)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-white rounded-lg hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">{{ $specialist->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $specialist->review_count }} نظر</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-2xl font-bold text-yellow-600">{{ number_format($specialist->avg_rating, 1) }}</span>
                                <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">داده‌ای برای نمایش وجود ندارد.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                نظرات منفی اخیر (نیاز به بررسی)
            </h3>
            <div class="space-y-4">
                @forelse($recentNegativeReviews as $review)
                    <div class="border-r-4 border-red-500 bg-red-50 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="font-bold text-gray-800">{{ $review->specialist->name }}</p>
                                <p class="text-sm text-gray-600">توسط {{ $review->user->name }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-xl font-bold text-red-600">{{ $review->overall_rating }}</span>
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                        @if($review->comment)
                            <p class="text-gray-700 text-sm mb-3">{{ Str::limit($review->comment, 150) }}</p>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">{{ verta($review->reviewed_at)->format('Y/m/d H:i') }}</span>
                            <a href="{{ route('admin.reviews.show', $review->id) }}"
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                مشاهده جزئیات ←
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-3 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>نظر منفی جدیدی وجود ندارد! 🎉</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                روند ماهانه (12 ماه اخیر)
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">ماه</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">تعداد نظرات</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase">میانگین امتیاز</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @forelse($monthlyStats as $stat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $stat->month }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ number_format($stat->count) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-lg {{ $stat->avg_rating >= 4 ? 'text-green-600' : ($stat->avg_rating >= 3 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ number_format($stat->avg_rating, 1) }}
                                    </span>
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">داده‌ای برای نمایش وجود ندارد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
