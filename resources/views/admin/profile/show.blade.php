@extends('layouts.admin')

@section('title', 'پروفایل من')

@section('content')
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-purple-600">
            <h2 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                پروفایل من
            </h2>
        </div>

        <div class="p-6">
            <div class="mb-8 flex items-center">
                <div class="w-24 h-24 rounded-full bg-blue-500 flex items-center justify-center text-white text-4xl font-bold">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div class="mr-6">
                    <h3 class="text-2xl font-bold">{{ $user->name }}</h3>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    <p class="text-gray-600 mt-1">{{ $user->phone }}</p>
                    <div class="mt-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            مدیر سیستم
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <h4 class="text-lg font-semibold mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        اطلاعات شخصی
                    </h4>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="text-gray-500">نام:</div>
                        <div class="col-span-2 font-medium">{{ $user->name }}</div>

                        <div class="text-gray-500">ایمیل:</div>
                        <div class="col-span-2 font-medium">{{ $user->email }}</div>

                        <div class="text-gray-500">شماره تماس:</div>
                        <div class="col-span-2 font-medium">{{ $user->phone }}</div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border">
                    <h4 class="text-lg font-semibold mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        وضعیت حساب
                    </h4>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="text-gray-500">نوع کاربر:</div>
                        <div class="col-span-2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">مدیر</span>
                        </div>

                        <div class="text-gray-500">وضعیت:</div>
                        <div class="col-span-2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">فعال</span>
                        </div>

                        <div class="text-gray-500">آخرین ورود:</div>
                        <div class="col-span-2 font-medium">{{ now()->format('Y/m/d H:i') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-5 rounded-lg border mb-6">
                <h4 class="text-lg font-semibold mb-3 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    امنیت حساب کاربری
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center justify-between p-3 bg-white rounded border">
                        <div>
                            <div class="font-medium">احراز هویت دو عاملی</div>
                            <div class="text-gray-500 text-xs mt-1">امنیت حساب کاربری خود را افزایش دهید</div>
                        </div>
                        <div>
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">غیرفعال</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-white rounded border">
                        <div>
                            <div class="font-medium">آخرین تغییر رمز عبور</div>
                            <div class="text-gray-500 text-xs mt-1">هر ۳ ماه یکبار رمز عبور خود را تغییر دهید</div>
                        </div>
                        <div>
                            <span class="text-sm">۱۴۰۲/۰۱/۰۱</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-left">
                <a href="{{ route('admin.profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                    ویرایش پروفایل
                </a>
            </div>
        </div>
    </div>
@endsection
