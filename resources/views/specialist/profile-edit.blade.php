@extends('layouts.specialist')

@section('title', 'ویرایش پروفایل')

@section('content')
    <div class="fade-in max-w-4xl mx-auto space-y-6">

        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa flex items-center">
                <svg class="w-5 h-5 ml-2 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                ویرایش پروفایل
            </h1>
            <a href="{{ route('specialist.profile.show') }}"
               class="px-4 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition flex items-center text-sm"
               style="border: 1px solid var(--specialist-border);">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                بازگشت
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Personal info --}}
            <div class="specialist-card overflow-hidden flex flex-col">
                <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                    <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        اطلاعات شخصی
                    </h2>
                </div>

                <form method="POST" action="{{ route('specialist.profile.update') }}" class="p-6 flex-grow flex flex-col justify-between">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-xs text-[var(--specialist-plum-muted)] mb-2">نام و نام خانوادگی</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                   class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   required>
                            @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs text-[var(--specialist-plum-muted)] mb-2">شماره تماس</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                   class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   dir="ltr" required>
                            @error('phone') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                    </div>
                    {{-- ⭐ Fix (test-writing session 6): removed the "email" input — the users
                         table has no email column at all, so this field was purely cosmetic
                         and submitting a value into it crashed the request with a fatal SQL
                         error (unique:users,email against a non-existent column). --}}

                    <div class="mt-6">
                        <button type="submit" class="specialist-cta w-full justify-center px-6 py-2 rounded-lg transition-opacity hover:opacity-90 flex items-center font-bold">
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>

            {{-- Password --}}
            <div class="specialist-card overflow-hidden flex flex-col">
                <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                    <h2 class="text-sm font-bold text-amber-300 font-serif-fa flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        تغییر رمز عبور
                    </h2>
                </div>

                <form method="POST" action="{{ route('specialist.profile.password') }}" class="p-6 flex-grow flex flex-col justify-between">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-xs text-[var(--specialist-plum-muted)] mb-2">رمز عبور فعلی</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-amber-400"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   required>
                            @error('current_password') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-xs text-[var(--specialist-plum-muted)] mb-2">رمز عبور جدید</label>
                            <input type="password" name="password" id="password"
                                   class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-amber-400"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   required>
                            @error('password') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs text-[var(--specialist-plum-muted)] mb-2">تکرار رمز عبور</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-amber-400"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   required>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full justify-center px-6 py-2 rounded-lg transition-opacity hover:opacity-90 flex items-center font-bold text-[#2B1A05]"
                                style="background: linear-gradient(135deg, #F5C56B, #D98A2B);">
                            تغییر رمز عبور
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
