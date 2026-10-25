@extends('layouts.admin')
@section('title', 'احراز سالن')

@php
    $input = 'w-full rounded-lg px-3 py-2 text-sm';
    $inputStyle = 'border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);';
    $box = 'background:var(--admin-surface); border:1px solid var(--admin-border);';
@endphp

@section('content')
    <div class="max-w-3xl mx-auto" data-verification-page>
        <h1 class="text-xl font-bold" style="color: var(--admin-text);">احراز سالن</h1>
        <p class="text-sm mt-1 mb-5 leading-7" style="color: var(--admin-text-dim);">
            طبق دستورالعمل ساماندهی سکوهای اینترنتی، {{ config('brand.name') }} باید هویت مالک و پروانه‌ی کسب هر سالن را بررسی کند. تا تأیید،
            سایت سالن و رزرو آنلاین برای مشتری‌ها بسته است؛ پنل مدیریت باز است تا خدمات، متخصص‌ها و ساعت‌ها را آماده کنید.
            شناسه‌ی یکتای پروانه همان شناسه‌ی پروانه‌ی الکترونیکی درگاه ملی مجوزهاست.
        </p>

        <div class="rounded-xl p-5 mb-5" style="{{ $box }}">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <span style="color: var(--admin-text);">وضعیت: <b data-verification-status="{{ $salon->verification_status }}">{{ $salon->verificationLabel() }}</b></span>
                @if ($salon->verified_at)
                    <span class="text-xs persian-number" style="color: var(--admin-text-dim);">{{ verta($salon->verified_at)->format('Y/m/d') }}</span>
                @endif
            </div>
            @if ($latest)
                <div class="text-sm mt-3 leading-7" style="color: var(--admin-text-dim);">
                    آخرین ارسال: {{ verta($latest->created_at)->format('Y/m/d H:i') }} — {{ $latest->licenseLabel() }}، شناسه‌ی <span dir="ltr">{{ $latest->license_unique_id }}</span>
                    @if ($latest->status === 'rejected' && $latest->rejection_reason)
                        <div class="mt-2 p-3 rounded-lg" style="background: rgb(var(--m-bad) / .12); color: rgb(var(--m-bad));" data-rejection-reason>دلیل رد: {{ $latest->rejection_reason }}</div>
                    @endif
                </div>
            @endif
        </div>

        @if (in_array($salon->verification_status, ['unverified', 'rejected'], true))
            @if ($errors->any())
                <div class="rounded-lg p-3 mb-4 text-sm" style="background:rgb(var(--m-bad) / .14); color:rgb(var(--m-bad));">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('admin.verification.store') }}" enctype="multipart/form-data" class="rounded-xl p-6 space-y-4" style="{{ $box }}">
                @csrf
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);" for="owner_name">نام و نام خانوادگی مالک (همان نام روی پروانه)</label>
                    <input id="owner_name" name="owner_name" value="{{ old('owner_name', $latest?->owner_name) }}" required maxlength="190" class="{{ $input }}" style="{{ $inputStyle }}">
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1" style="color:var(--admin-text);" for="owner_national_code">کد ملی مالک</label>
                        <input id="owner_national_code" name="owner_national_code" value="{{ old('owner_national_code') }}" required inputmode="numeric" maxlength="12" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}">
                    </div>
                    <div>
                        <label class="block text-sm mb-1" style="color:var(--admin-text);" for="license_type">نوع پروانه‌ی کسب</label>
                        <select id="license_type" name="license_type" required class="{{ $input }}" style="{{ $inputStyle }}">
                            <option value="">انتخاب کنید</option>
                            @foreach ($licenseTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('license_type', $latest?->license_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1" style="color:var(--admin-text);" for="license_unique_id">شناسه‌ی یکتای پروانه (درگاه ملی مجوزها)</label>
                        <input id="license_unique_id" name="license_unique_id" value="{{ old('license_unique_id', $latest?->license_unique_id) }}" required maxlength="40" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}">
                    </div>
                    <div>
                        <label class="block text-sm mb-1" style="color:var(--admin-text);" for="license_file">فایل پروانه (PDF یا عکس، حداکثر ۵ مگ)</label>
                        <input id="license_file" name="license_file" type="file" required accept=".pdf,.jpg,.jpeg,.png" class="{{ $input }}" style="{{ $inputStyle }}">
                    </div>
                </div>
                <p class="text-xs leading-6" style="color: var(--admin-text-dim);">کد ملی رمزنگاری‌شده و فایل پروانه روی دیسک خصوصی نگه داشته می‌شود و فقط مدیر پلتفرم برای بررسی آن را می‌بیند.</p>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold text-m-onsolid" style="background:var(--admin-accent);">فرستادن برای بررسی</button>
            </form>
        @endif
    </div>
@endsection
