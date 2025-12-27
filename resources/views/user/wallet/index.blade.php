@extends('layouts.app')

@section('title', 'کیف پول من')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <svg class="w-8 h-8 ml-2 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    کیف پول من
                </h1>
                <p class="text-gray-600 mt-2">مدیریت موجودی و تراکنش‌های مالی شما</p>
            </div>
            <a href="{{ route('wallet.charge') }}"
               class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-3 rounded-xl hover:opacity-90 transition-opacity flex items-center shadow-lg">
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                شارژ کیف پول
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold opacity-90">موجودی فعلی</h3>
                    <svg class="w-8 h-8 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-3xl font-bold persian-number">{{ number_format($wallet->balance) }}</p>
                <p class="text-sm opacity-80 mt-1">تومان</p>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold opacity-90">بازگشت وجه این ماه</h3>
                    <svg class="w-8 h-8 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </div>
                <p class="text-3xl font-bold persian-number">{{ number_format($currentMonthRefunds) }}</p>
                <p class="text-sm opacity-80 mt-1">تومان</p>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold opacity-90">پرداختی این ماه</h3>
                    <svg class="w-8 h-8 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <p class="text-3xl font-bold persian-number">{{ number_format($currentMonthSpent) }}</p>
                <p class="text-sm opacity-80 mt-1">تومان</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-pink-500 to-purple-600 p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                تراکنش‌های اخیر
                            </h2>
                            <a href="{{ route('wallet.transactions') }}"
                               class="text-white hover:text-pink-100 text-sm flex items-center transition">
                                مشاهده همه
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="p-6">
                        @forelse($recentTransactions as $transaction)
                            <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition rounded-lg px-3 -mx-3">
                                <div class="flex items-center space-x-4 space-x-reverse flex-1">
                                    <div class="flex-shrink-0">
                                        @if($transaction->type === 'refund')
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </div>
                                        @elseif($transaction->type === 'payment')
                                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $transaction->description }}
                                        </p>
                                        <div class="flex items-center mt-1 text-xs text-gray-500">
                                            <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ \Morilog\Jalali\Jalalian::forge($transaction->created_at)->format('Y/m/d - H:i') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="text-left mr-4">
                                    <p class="text-lg font-bold persian-number {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                                    </p>
                                    <p class="text-xs text-gray-500">تومان</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">هیچ تراکنشی وجود ندارد</p>
                                <p class="text-gray-400 text-sm mt-2">تراکنش‌های مالی شما اینجا نمایش داده می‌شود</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        اطلاعات کیف پول
                    </h3>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-sm text-gray-600">موجودی کل</span>
                            <span class="text-lg font-bold text-gray-900 persian-number">{{ number_format($wallet->balance) }} تومان</span>
                        </div>

                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-sm text-gray-600">کل واریزی‌ها</span>
                            <span class="text-sm font-semibold text-green-600 persian-number">{{ number_format($wallet->total_deposited) }} تومان</span>
                        </div>

                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-sm text-gray-600">کل پرداختی‌ها</span>
                            <span class="text-sm font-semibold text-red-600 persian-number">{{ number_format($wallet->total_spent) }} تومان</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">آخرین بروزرسانی</span>
                            <span class="text-xs text-gray-500">
                            {{ \Morilog\Jalali\Jalalian::forge($wallet->updated_at)->format('Y/m/d H:i') }}
                        </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-pink-50 to-purple-50 rounded-xl p-6 border border-pink-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        نکات مهم
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 ml-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            بازگشت وجه لغو نوبت‌ها به کیف پول شما واریز می‌شود
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 ml-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            می‌توانید از موجودی برای پرداخت نوبت‌های بعدی استفاده کنید
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 ml-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            تمام تراکنش‌ها در سیستم ثبت و قابل پیگیری هستند
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
