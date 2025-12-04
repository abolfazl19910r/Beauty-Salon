<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-16 h-16 text-pink-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">تایید شماره موبایل</h2>
                <p class="text-gray-600 mt-2">کد تایید به شماره موبایل شما ارسال شد</p>
                @if(isset($user))
                    <p class="text-pink-600 font-medium mt-1 dir-ltr">{{ $user->phone }}</p>
                @endif
            </div>

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border-r-4 border-green-400 text-green-700 rounded">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border-r-4 border-red-400 text-red-700 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.verify') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="code" class="block font-medium text-gray-700 mb-2">کد تایید 6 رقمی</label>
                    <div class="mt-2">
                        <input id="code"
                               class="block w-full text-center text-2xl tracking-widest px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               type="text"
                               name="code"
                               required
                               autofocus
                               maxlength="6"
                               pattern="[0-9]{6}"
                               placeholder="------"
                               dir="ltr"
                               inputmode="numeric" />
                    </div>
                    <div id="timer-container" class="mt-3 text-center">
                        <p class="text-sm text-gray-600">زمان باقی‌مانده:</p>
                        <p id="countdown-timer" class="text-2xl font-bold text-pink-600 tabular-nums">02:00</p>
                    </div>
                </div>

                <div>
                    <button id="submit-btn" type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        تایید و ورود به حساب
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('register.resend') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-pink-600 hover:text-pink-500 underline">
                        ارسال مجدد کد تایید
                    </button>
                </form>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('register') }}" class="text-sm text-gray-600 hover:text-gray-800">
                    بازگشت به صفحه ثبت نام
                </a>
            </div>

            <div class="mt-6 p-4 bg-gradient-to-r from-pink-50 to-purple-50 rounded-lg border border-pink-200">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-pink-500 mt-0.5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-gray-700">
                        <p class="font-medium">خوش آمدید!</p>
                        <p class="mt-1">با تایید شماره موبایل، می‌توانید از تمام خدمات سالن زیبایی استفاده کنید</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p>پیامک دریافت نشد؟ لطفاً بخش هرزنامه (Spam) را بررسی کنید</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;

            if (value.length === 6) {
                // e.target.closest('form').submit();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            let timeLeft = 120;
            const timerDisplay = document.getElementById('countdown-timer');
            const resendButton = document.querySelector('form[action$="resend"] button');
            if (!timerDisplay) return;
            if (resendButton) {
                resendButton.disabled = true;
                resendButton.style.opacity = '0.5';
                resendButton.style.cursor = 'not-allowed';
                resendButton.textContent = 'ارسال مجدد کد تایید';
            }

            const timerInterval = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerDisplay.textContent = "00:00";
                    timerDisplay.classList.remove('text-pink-600');
                    timerDisplay.classList.add('text-red-500');
                    if (resendButton) {
                        resendButton.disabled = false;
                        resendButton.style.opacity = '1';
                        resendButton.style.cursor = 'pointer';
                        resendButton.textContent = 'ارسال مجدد کد تایید (کلیک کنید)';
                    }
                } else {
                    timeLeft--;
                }
            }, 1000);
        });
    </script>
</x-guest-layout>
