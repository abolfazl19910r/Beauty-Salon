@extends('layouts.app')

@section('title', 'شارژ موفق')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4">
                    <svg class="w-12 h-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">شارژ موفق!</h1>
                <p class="text-green-100">کیف پول شما با موفقیت شارژ شد</p>
            </div>

            <div class="p-8">
                <div class="bg-gray-50 rounded-xl p-6 mb-6">
                    <h2 class="font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        اطلاعات شارژ
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <span class="text-gray-600">مبلغ شارژ:</span>
                            <span class="font-bold text-green-600 text-xl persian-number">
                                {{ number_format($amount) }} تومان
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <span class="text-gray-600">کد پیگیری:</span>
                            <span class="font-semibold text-gray-900" dir="ltr">{{ $refId }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <span class="text-gray-600">تاریخ و ساعت:</span>
                            <span class="font-medium text-gray-900 persian-number" dir="ltr">
                                {{ verta()->format('Y/m/d H:i') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-blue-700 mb-1">موجودی جدید کیف پول</p>
                            <p class="text-4xl font-bold text-blue-900 persian-number">
                                {{ number_format($newBalance) }}
                            </p>
                            <p class="text-sm text-blue-600 mt-1">تومان</p>
                        </div>
                        <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 ml-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-green-800">
                            <p class="font-semibold mb-1">✓ شما می‌توانید:</p>
                            <ul class="list-disc list-inside space-y-1 text-green-700">
                                <li>از موجودی برای پرداخت نوبت‌های جدید استفاده کنید</li>
                                <li>تراکنش‌های خود را در کیف پول مشاهده کنید</li>
                                <li>در هر زمان موجودی خود را افزایش دهید</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('wallet.index') }}"
                       class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 px-6 rounded-xl hover:opacity-90 transition text-center font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        مشاهده کیف پول
                    </a>
                    <a href="{{ route('bookings.create') }}"
                       class="flex-1 bg-pink-500 text-white py-3 px-6 rounded-xl hover:opacity-90 transition text-center font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        رزرو نوبت جدید
                    </a>
                    <a href="{{ route('home') }}"
                       class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-xl hover:bg-gray-300 transition text-center font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        صفحه اصلی
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
