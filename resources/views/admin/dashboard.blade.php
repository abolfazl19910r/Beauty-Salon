@extends('layouts.admin')

@section('title', 'پنل مدیریت')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">داشبورد</h1>
                <p class="text-sm text-gray-500">خلاصه وضعیت سیستم مدیریت سالن زیبایی</p>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="bg-white p-2 rounded-lg shadow-sm flex gap-2 text-sm">
                    <button class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-md font-medium">امروز</button>
                    <button class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-md">هفته</button>
                    <button class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-md">ماه</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-blue-50 text-blue-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">نوبت‌های امروز</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800">14</h3>
                            <span class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full">
                                <span class="font-medium">+12.5%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="#" class="text-xs text-blue-600 hover:text-blue-800 flex items-center justify-between">
                        <span>مشاهده همه</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-green-50 text-green-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">درآمد کل</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800" dir="ltr">12,560,000</h3>
                            <span class="text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded-full">
                                <span class="font-medium">+5.2%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="#" class="text-xs text-green-600 hover:text-green-800 flex items-center justify-between">
                        <span>گزارش مالی</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-purple-50 text-purple-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">کاربران</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800">265</h3>
                            <span class="text-xs bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded-full">
                                <span class="font-medium">+3.8%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="#" class="text-xs text-purple-600 hover:text-purple-800 flex items-center justify-between">
                        <span>مشاهده همه</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-pink-50 text-pink-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">متخصصین</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800">8</h3>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="#" class="text-xs text-pink-600 hover:text-pink-800 flex items-center justify-between">
                        <span>مشاهده همه</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">نمودار درآمد (۷ روز گذشته)</h2>
                </div>
                <div class="p-5">
                    <div id="revenue-chart" class="h-80">
                        <div class="flex justify-center items-center h-full">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">خدمات محبوب</h2>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 flex-shrink-0 bg-blue-100 text-blue-500 rounded-lg flex items-center justify-center ml-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium">کوتاهی مو</h3>
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: 85%"></div>
                                    </div>
                                    <span class="mr-2 text-sm text-gray-500">85%</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="w-10 h-10 flex-shrink-0 bg-green-100 text-green-500 rounded-lg flex items-center justify-center ml-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium">رنگ مو</h3>
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-green-600 h-1.5 rounded-full" style="width: 72%"></div>
                                    </div>
                                    <span class="mr-2 text-sm text-gray-500">72%</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="w-10 h-10 flex-shrink-0 bg-purple-100 text-purple-500 rounded-lg flex items-center justify-center ml-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium">مانیکور</h3>
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-purple-600 h-1.5 rounded-full" style="width: 64%"></div>
                                    </div>
                                    <span class="mr-2 text-sm text-gray-500">64%</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="w-10 h-10 flex-shrink-0 bg-yellow-100 text-yellow-500 rounded-lg flex items-center justify-center ml-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium">اصلاح ابرو</h3>
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-yellow-600 h-1.5 rounded-full" style="width: 45%"></div>
                                    </div>
                                    <span class="mr-2 text-sm text-gray-500">45%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">آمار متخصصین</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="text-sm text-gray-600 bg-gray-50">
                            <th class="px-5 py-3 text-right font-medium">متخصص</th>
                            <th class="px-5 py-3 text-right font-medium">نوبت‌های امروز</th>
                            <th class="px-5 py-3 text-right font-medium">نرخ تکمیل</th>
                            <th class="px-5 py-3 text-right font-medium">درآمد</th>
                            <th class="px-5 py-3 text-right font-medium">عملکرد</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <tr class="text-sm hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold ml-2">
                                        ر
                                    </div>
                                    <span class="font-medium">رضا محمدی</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">4</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ml-2">
                                        <div class="bg-green-600 h-1.5 rounded-full" style="width: 92%"></div>
                                    </div>
                                    <span>92%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">1,240,000</td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">عالی</span>
                            </td>
                        </tr>
                        <tr class="text-sm hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-xs font-bold ml-2">
                                        س
                                    </div>
                                    <span class="font-medium">سارا احمدی</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">6</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ml-2">
                                        <div class="bg-green-600 h-1.5 rounded-full" style="width: 85%"></div>
                                    </div>
                                    <span>85%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">1,560,000</td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">عالی</span>
                            </td>
                        </tr>
                        <tr class="text-sm hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold ml-2">
                                        م
                                    </div>
                                    <span class="font-medium">مینا کریمی</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">2</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ml-2">
                                        <div class="bg-yellow-600 h-1.5 rounded-full" style="width: 65%"></div>
                                    </div>
                                    <span>65%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">780,000</td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">خوب</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 text-center">
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800">مشاهده همه متخصصین</a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">نوبت‌های اخیر</h2>
                </div>
                <div class="p-5 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center">
                            <span class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold ml-2">ن</span>
                            <div>
                                <h3 class="text-sm font-medium">نرگس رضایی</h3>
                                <p class="text-xs text-gray-500">کوتاهی مو</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                            15:30
                        </span>
                    </div>
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center">
                            <span class="w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-xs font-bold ml-2">ف</span>
                            <div>
                                <h3 class="text-sm font-medium">فاطمه محمدی</h3>
                                <p class="text-xs text-gray-500">رنگ مو</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                            16:00
                        </span>
                    </div>
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center">
                            <span class="w-8 h-8 rounded-full bg-pink-500 text-white flex items-center justify-center text-xs font-bold ml-2">ز</span>
                            <div>
                                <h3 class="text-sm font-medium">زهرا کریمی</h3>
                                <p class="text-xs text-gray-500">مانیکور</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                            تکمیل شده
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold ml-2">م</span>
                            <div>
                                <h3 class="text-sm font-medium">مریم احمدی</h3>
                                <p class="text-xs text-gray-500">اصلاح ابرو</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                            لغو شده
                        </span>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-100 text-center">
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800">مشاهده همه نوبت‌ها</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const chartElement = document.getElementById('revenue-chart');
                if (chartElement) {
                    chartElement.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-gray-500">اطلاعات نمودار در حال بارگذاری است...</p></div>';
                }
            }, 1500);
        });
    </script>
@endpush
