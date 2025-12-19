@extends('layouts.specialist')

@section('title', 'ویرایش پروفایل')

@section('content')
    <div class="max-w-4xl mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <svg class="w-6 h-6 ml-2 text-pink-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                ویرایش پروفایل
            </h1>
            <a href="{{ route('specialist.profile.show') }}"
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                بازگشت
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-pink-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        اطلاعات شخصی
                    </h2>
                </div>

                <form method="POST" action="{{ route('specialist.profile.update') }}" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">نام و نام خانوادگی</label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $user->name) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                   required>
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">شماره تماس</label>
                            <input type="text"
                                   name="phone"
                                   id="phone"
                                   value="{{ old('phone', $user->phone) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                   dir="ltr"
                                   required>
                            @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل (اختیاری)</label>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   value="{{ old('email', $user->email) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                   dir="ltr">
                            @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        تغییر رمز عبور
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        برای حفظ امنیت حساب کاربری، از رمز عبور قوی استفاده کنید
                    </p>
                </div>

                <form method="POST" action="{{ route('specialist.profile.password') }}" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور فعلی</label>
                            <input type="password"
                                   name="current_password"
                                   id="current_password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                   required>
                            @error('current_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور جدید</label>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                   required>
                            @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">تکرار رمز عبور جدید</label>
                            <input type="password"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                   required>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-gradient-to-r from-yellow-500 to-orange-600 text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            تغییر رمز عبور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
