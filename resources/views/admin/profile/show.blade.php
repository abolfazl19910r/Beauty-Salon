@extends('layouts.admin')
@section('title', 'پروفایل من')

@section('content')
    <div class="max-w-3xl mx-auto">

        {{-- Header --}}
        <div class="rounded-t-xl px-6 py-5" style="background:var(--admin-accent)">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl font-bold flex-shrink-0"
                     style="background:rgba(255,255,255,.2);color:#fff">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">{{ $user->name }}</h1>
                    <p class="text-sm" style="color:rgba(255,255,255,.7)">مدیر سیستم</p>
                </div>
                <a href="{{ route('admin.profile.edit') }}"
                   class="mr-auto inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg font-medium"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    ویرایش
                </a>
            </div>
        </div>

        <div class="rounded-b-xl p-6 grid grid-cols-1 md:grid-cols-2 gap-5" style="background:var(--admin-surface);border:1px solid var(--admin-border);border-top:none">

            {{-- Personal information --}}
            <div class="rounded-xl p-5" style="background:var(--admin-bg);border:1px solid var(--admin-border)">
                <h3 class="text-sm font-semibold mb-4 flex items-center gap-2" style="color:var(--admin-text)">
                    <svg class="w-4 h-4" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    اطلاعات شخصی
                </h3>
                <div class="space-y-3 text-sm">
                    @foreach([['نام','name'],['ایمیل','email'],['شماره تماس','phone']] as [$lbl,$field])
                        <div class="flex items-center gap-3">
                            <span class="w-28 flex-shrink-0" style="color:var(--admin-text-dim)">{{ $lbl }}</span>
                            <span class="font-medium" style="color:var(--admin-text)">{{ $user->$field }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Account status --}}
            <div class="rounded-xl p-5" style="background:var(--admin-bg);border:1px solid var(--admin-border)">
                <h3 class="text-sm font-semibold mb-4 flex items-center gap-2" style="color:var(--admin-text)">
                    <svg class="w-4 h-4" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    وضعیت حساب
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-28 flex-shrink-0" style="color:var(--admin-text-dim)">نقش</span>
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium" style="background:var(--admin-accent-light);color:var(--admin-accent)">مدیر</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-28 flex-shrink-0" style="color:var(--admin-text-dim)">وضعیت</span>
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium bg-green-100 text-green-700">فعال</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-28 flex-shrink-0" style="color:var(--admin-text-dim)">آخرین ورود</span>
                        <span style="color:var(--admin-text)">{{ jalali_date(now(), 'Y/m/d') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
