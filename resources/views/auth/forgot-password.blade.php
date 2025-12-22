<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-pink-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">بازیابی رمز عبور</h2>
                <p class="text-gray-600 mt-2">شماره موبایل خود را وارد کنید</p>
            </div>

            <div class="mb-6 p-4 bg-blue-50 text-blue-600 rounded-lg text-sm">
                {{ __('رمز عبور خود را فراموش کرده‌اید؟ شماره موبایل خود را وارد کنید تا کد تایید برای شما پیامک شود.') }}
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            @if ($errors->any())
                <div class="mb-4">
                    <x-input-error :messages="$errors->all()" />
                </div>
            @endif

            <form method="POST" action="{{ route('password.send') }}" class="space-y-6">
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
                                      dir="ltr"
                                      placeholder="09123456789" />
                    </div>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        {{ __('ارسال کد تایید') }}
                    </button>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="text-sm text-pink-600 hover:text-pink-500">
                        بازگشت به صفحه ورود
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
