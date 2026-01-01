@extends('layouts.admin')

@section('title', 'مدیریت نظرات')

@section('content')
    <div class="fade-in">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">کل نظرات</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($totalReviews) }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">تایید شده</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($approvedReviews) }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">نظرات منفی</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($negativeReviews) }}</p>
                    </div>
                    <div class="bg-red-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-yellow-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">میانگین امتیاز</p>
                        <p class="text-3xl font-bold text-gray-800">{{ number_format($averageRating, 1) }}</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-4 mb-6">
            <a href="{{ route('admin.reviews.stats') }}"
               class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                آمار و گزارشات
            </a>
            <a href="{{ route('admin.reviews.trashed') }}"
               class="bg-gray-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-gray-700 transition-all shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                نظرات حذف شده
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <button onclick="toggleFilters()" class="w-full flex items-center justify-between text-lg font-bold text-gray-800">
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

            <form method="GET" id="filterForm" class="hidden mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">متخصص</label>
                        <select name="specialist_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">همه متخصصین</option>
                            @foreach($specialists as $specialist)
                                <option value="{{ $specialist->id }}" {{ request('specialist_id') == $specialist->id ? 'selected' : '' }}>
                                    {{ $specialist->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">امتیاز</label>
                        <select name="rating" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">همه امتیازات</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} ستاره</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت تایید</label>
                        <select name="is_approved" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">همه</option>
                            <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>تایید شده</option>
                            <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>رد شده</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">مرتب‌سازی</label>
                        <select name="sort_by" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="latest">جدیدترین</option>
                            <option value="oldest">قدیمی‌ترین</option>
                            <option value="highest_rating">بالاترین امتیاز</option>
                            <option value="lowest_rating">پایین‌ترین امتیاز</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                               placeholder="نام کاربر، متخصص، متن نظر...">
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer mt-6">
                            <input type="checkbox" name="negative" value="1" {{ request('negative') ? 'checked' : '' }}
                            class="w-5 h-5 text-red-600 rounded focus:ring-2 focus:ring-red-500">
                            <span class="text-sm font-medium text-gray-700">فقط نظرات منفی</span>
                        </label>
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer mt-6">
                            <input type="checkbox" name="has_response" value="0" {{ request('has_response') === '0' ? 'checked' : '' }}
                            class="w-5 h-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                            <span class="text-sm font-medium text-gray-700">بدون پاسخ</span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                        اعمال فیلتر
                    </button>
                    <a href="{{ route('admin.reviews.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-200 transition">
                        حذف فیلترها
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            @if($reviews->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-gray-500 text-lg">نظری یافت نشد.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">کاربر</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">متخصص</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">سرویس</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">امتیاز</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">تاریخ</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">وضعیت</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                        @foreach($reviews as $review)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                            {{ mb_substr($review->user->name, 0, 1) }}
                                        </div>
                                        <div class="mr-3">
                                            <p class="font-medium text-gray-800">{{ $review->user->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $review->user->phone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-800">{{ $review->specialist->name }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600">{{ $review->service->name }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <span class="font-bold text-lg {{ $review->overall_rating >= 4 ? 'text-green-600' : ($review->overall_rating >= 3 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $review->overall_rating }}
                                        </span>
                                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ verta($review->reviewed_at)->format('Y/m/d') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($review->is_approved)
                                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">✓ تایید شده</span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">✗ رد شده</span>
                                    @endif
                                    @if($review->is_featured)
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full mr-1">⭐ ویژه</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.reviews.show', $review->id) }}"
                                           class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded transition"
                                           title="مشاهده جزئیات">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('آیا از حذف این نظر اطمینان دارید؟')"
                                                    class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded transition"
                                                    title="حذف">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if($reviews->hasPages())
                    <div class="px-6 py-4 border-t bg-gray-50">
                        {{ $reviews->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleFilters() {
                const form = document.getElementById('filterForm');
                const icon = document.getElementById('filter-icon');
                form.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        </script>
    @endpush
@endsection
