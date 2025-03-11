<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-pink-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">ثبت نام در سالن زیبایی</h2>
                <p class="text-gray-600 mt-2">برای استفاده از خدمات ما ثبت نام کنید</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('نام')" class="font-medium text-gray-700" />
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <x-text-input id="name"
                                      class="block w-full pr-10"
                                      type="text"
                                      name="name"
                                      :value="old('name')"
                                      required
                                      autofocus
                                      autocomplete="name" />
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('شماره موبایل')" class="font-medium text-gray-700" />
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                        </div>
                        <x-text-input id="phone"
                                      class="block w-full pr-10"
                                      type="text"
                                      name="phone"
                                      :value="old('phone')"
                                      required
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
                                      autocomplete="new-password" />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('تکرار رمز عبور')" class="font-medium text-gray-700" />
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <x-text-input id="password_confirmation"
                                      class="block w-full pr-10"
                                      type="password"
                                      name="password_confirmation"
                                      required
                                      autocomplete="new-password" />
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        {{ __('ثبت نام') }}
                    </button>
                </div>

                <div class="text-center mt-4">
                    <span class="text-gray-600">قبلاً ثبت‌نام کرده‌اید؟</span>
                    <a href="{{ route('login') }}" class="text-pink-600 hover:text-pink-500 mr-1">ورود به حساب</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
