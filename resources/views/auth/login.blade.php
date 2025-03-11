<?php
use Illuminate\Support\Facades\Route;
/** @var \Illuminate\Support\ViewErrorBag $errors */
?>
<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-pink-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">ورود به سالن زیبایی</h2>
                <p class="text-gray-600 mt-2">برای رزرو نوبت وارد شوید</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="phone" :value="__('شماره موبایل')" class="font-medium text-gray-700" />
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                        </div>
                        <x-text-input id="phone"
                                      class="block w-full pr-10 text-right"
                                      type="text"
                                      name="phone"
                                      :value="old('phone')"
                                      required
                                      autofocus
                                      autocomplete="phone"
                                      dir="ltr"
                                      placeholder="09123456789" />
                    </div>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('رمز عبور')" class="font-medium text-gray-700" />
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <x-text-input id="password"
                                      class="block w-full pr-10"
                                      type="password"
                                      name="password"
                                      required
                                      autocomplete="current-password" />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me"
                               type="checkbox"
                               class="rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                               name="remember">
                        <span class="mr-2 text-sm text-gray-600">{{ __('مرا به خاطر بسپار') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-pink-600 hover:text-pink-500"
                           href="{{ route('password.request') }}">
                            {{ __('فراموشی رمز عبور') }}
                        </a>
                    @endif
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        {{ __('ورود') }}
                    </button>
                </div>

                <div class="text-center mt-4">
                    <span class="text-gray-600">حساب کاربری ندارید؟</span>
                    <a href="{{ route('register') }}" class="text-pink-600 hover:text-pink-500 mr-1">ثبت نام کنید</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
