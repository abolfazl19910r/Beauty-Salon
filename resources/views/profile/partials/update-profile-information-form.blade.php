<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            اطلاعات پروفایل
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            بروزرسانی اطلاعات پروفایل و آدرس ایمیل خود.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="نام" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" value="شماره موبایل" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" required dir="ltr" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div>
                <p class="text-sm mt-2 text-gray-800">
                    ایمیل شما تایید نشده است.
                    <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md">
                        برای ارسال مجدد ایمیل تایید کلیک کنید.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-medium text-sm text-green-600">
                        یک لینک تایید جدید به آدرس ایمیل شما ارسال شد.
                    </p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>ذخیره</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >ذخیره شد.</p>
            @endif
        </div>
    </form>
</section>
