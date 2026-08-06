@extends('layouts.app')

@section('title', 'امنیت حساب کاربری')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('profile.edit') }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-0.5">حساب کاربری</p>
                <h1 class="text-2xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">امنیت حساب</h1>
            </div>
        </div>

        <div class="space-y-5">

            {{-- Security score --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">امتیاز امنیتی حساب</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-end gap-2 mb-3">
                        <span class="text-4xl font-bold {{ $security_score >= 70 ? 'text-emerald-400' : ($security_score >= 40 ? 'text-amber-400' : 'text-red-400') }}">{{ $security_score }}</span>
                        <span class="text-sm text-[#F8F3E9]/45 mb-1">از ۱۰۰</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-black/30 overflow-hidden">
                        <div class="h-full rounded-full {{ $security_score >= 70 ? 'bg-emerald-400' : ($security_score >= 40 ? 'bg-amber-400' : 'bg-red-400') }}"
                             style="width: {{ $security_score }}%"></div>
                    </div>
                    <p class="text-xs text-[#F8F3E9]/45 mt-3">
                        این امتیاز بر اساس فعال بودن تایید دو مرحله‌ای، قدرت و تازگی رمز عبور، و نبود فعالیت مشکوک اخیر محاسبه می‌شود.
                    </p>
                </div>
            </div>

            {{-- 2FA --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">احراز هویت دو مرحله‌ای</h2>
                </div>
                <div class="p-6 flex items-center justify-between">
                    <p class="text-sm text-[#F8F3E9]/60">وضعیت:
                        <span class="{{ $two_factor_enabled ? 'text-emerald-400' : 'text-[#F8F3E9]/50' }} font-semibold">
                            {{ $two_factor_enabled ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </p>
                    <a href="{{ route('security.2fa') }}"
                       class="px-5 py-2 rounded-xl text-sm font-semibold border border-[#C9A24B]/25 text-[#F8F3E9]/85 hover:bg-white/5 transition-colors">
                        مدیریت تنظیمات
                    </a>
                </div>
            </div>

            {{-- Sessions --}}
            <a href="{{ route('security.sessions') }}"
               class="block bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden hover:border-[#C9A24B]/25 transition-colors">
                <div class="p-6 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <div>
                            <h2 class="font-bold text-sm text-[#E6CD8A]">نشست‌های فعال</h2>
                            <p class="text-xs text-[#F8F3E9]/45">{{ $active_sessions_count }} دستگاه در حال حاضر وارد شده</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#F8F3E9]/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </div>
            </a>

            {{-- Recent activity --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">فعالیت‌های اخیر</h2>
                    <a href="{{ route('security.activity') }}" class="text-xs text-[#C9A24B] mr-auto hover:underline">مشاهده همه</a>
                </div>
                <div class="divide-y divide-[#C9A24B]/10">
                    @forelse ($recent_activities as $activity)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $activity->level === 'warning' ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                                <span class="text-sm text-[#F8F3E9]/80">{{ $activity->event_label }}</span>
                            </div>
                            <span class="text-xs text-[#F8F3E9]/40">{{ jalali_date($activity->created_at, 'Y/m/d H:i') }}</span>
                        </div>
                    @empty
                        <p class="px-6 py-6 text-sm text-[#F8F3E9]/45 text-center">هنوز فعالیتی ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            {{-- Password --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-sm text-[#E6CD8A] mb-1">رمز عبور</h2>
                        <p class="text-xs text-[#F8F3E9]/45">
                            @if ($last_password_change)
                                آخرین تغییر: {{ jalali_date($last_password_change, 'Y/m/d') }}
                            @else
                                رمز عبور شما تا الان هیچ‌وقت تغییر نکرده است.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('profile.edit') }}#update-password"
                       class="px-5 py-2 rounded-xl text-sm font-semibold border border-[#C9A24B]/25 text-[#F8F3E9]/85 hover:bg-white/5 transition-colors">
                        تغییر رمز
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
