<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-pink-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">تایید رمز عبور</h2>
            </div>

            <div class="mb-6 p-4 bg-blue-50 text-blue-600 rounded-lg text-sm">
                {{ __('این یک بخش امن از برنامه است. لطفا قبل از ادامه رمز عبور خود را تایید کنید.') }}
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
                @csrf

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

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        {{ __('تایید') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
