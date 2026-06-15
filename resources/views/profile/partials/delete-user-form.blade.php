<section>
    <p class="text-sm text-[#F8F3E9]/55 mb-5 leading-7">
        پس از حذف حساب کاربری، تمام اطلاعات، نوبت‌ها و داده‌های مرتبط به‌صورت دائمی حذف خواهند شد. این عملیات قابل بازگشت نیست.
    </p>

    <button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
               border border-red-500/30 text-red-400
               hover:bg-red-500/10 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
        </svg>
        حذف حساب کاربری
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-start gap-3 mb-5">
                <div class="w-10 h-10 rounded-full bg-red-500/15 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-[#F8F3E9] mb-1">آیا از حذف حساب کاربری مطمئن هستید؟</h3>
                    <p class="text-sm text-[#F8F3E9]/55">
                        این عملیات قابل بازگشت نیست. لطفاً برای تأیید، رمز عبور خود را وارد کنید.
                    </p>
                </div>
            </div>

            <div class="mb-6">
                <x-input-label for="del_password" value="رمز عبور" />
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-[#C9A24B]/50" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <x-text-input id="del_password" name="password" type="password"
                                  class="pr-10" placeholder="رمز عبور" />
                </div>
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" x-on:click="$dispatch('close')"
                        class="px-5 py-2.5 rounded-xl text-sm border border-[#C9A24B]/25
                               text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                    انصراف
                </button>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold
                               bg-red-500/20 border border-red-500/30 text-red-400
                               hover:bg-red-500/30 transition-colors inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    تأیید و حذف حساب
                </button>
            </div>
        </form>
    </x-modal>
</section>
