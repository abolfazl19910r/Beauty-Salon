@extends('layouts.app')

@section('title', 'تایید دو مرحله‌ای')

@section('content')
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-pink-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">تایید دو مرحله‌ای</h2>
                <p class="text-gray-600 mt-2">کد تایید به شماره همراه شما ارسال شد</p>
            </div>

            <div id="two-factor-auth" class="space-y-6">
                <div class="text-center">
                    <div class="bg-blue-50 p-4 rounded-lg text-sm text-blue-600 mb-4">
                        کد تایید ۶ رقمی ارسال شده به تلفن همراه خود را وارد کنید
                    </div>

                    <div class="flex justify-center gap-2 mb-6">
                        <input type="text" maxlength="1" class="w-12 h-12 text-center border rounded-lg text-2xl focus:border-pink-500 focus:ring-1 focus:ring-pink-500" />
                        <input type="text" maxlength="1" class="w-12 h-12 text-center border rounded-lg text-2xl focus:border-pink-500 focus:ring-1 focus:ring-pink-500" />
                        <input type="text" maxlength="1" class="w-12 h-12 text-center border rounded-lg text-2xl focus:border-pink-500 focus:ring-1 focus:ring-pink-500" />
                        <input type="text" maxlength="1" class="w-12 h-12 text-center border rounded-lg text-2xl focus:border-pink-500 focus:ring-1 focus:ring-pink-500" />
                        <input type="text" maxlength="1" class="w-12 h-12 text-center border rounded-lg text-2xl focus:border-pink-500 focus:ring-1 focus:ring-pink-500" />
                        <input type="text" maxlength="1" class="w-12 h-12 text-center border rounded-lg text-2xl focus:border-pink-500 focus:ring-1 focus:ring-pink-500" />
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <button class="text-gray-400">
                            ارسال مجدد کد
                            <span class="flex items-center">
                                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                02:00
                            </span>
                        </button>
                    </div>

                    <button class="w-full mt-6 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        تایید
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
