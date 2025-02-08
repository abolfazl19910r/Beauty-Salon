<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            حذف حساب کاربری
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            پس از حذف حساب کاربری، تمام منابع و داده‌های مرتبط با آن به طور دائمی حذف خواهند شد.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >حذف حساب کاربری</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                آیا از حذف حساب کاربری خود اطمینان دارید؟
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                این عملیات قابل بازگشت نیست. لطفاً برای تایید حذف حساب کاربری، رمز عبور خود را وارد کنید.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="رمز عبور" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="رمز عبور"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    انصراف
                </x-secondary-button>

                <x-danger-button class="mr-3">
                    حذف حساب کاربری
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
