@extends('layouts.superadmin')

@section('title', 'احراز: '.($verification->salon?->name ?? ''))

@section('content')
    @php $jalali = fn ($at) => $at ? \Morilog\Jalali\Jalalian::fromCarbon($at)->format('Y/m/d H:i') : '—'; @endphp
    <div class="grid lg:grid-cols-3 gap-5" data-verification-show>
        <div class="lg:col-span-2 sa-card sa-card-pad">
            <h2 class="sa-card-title">{{ $verification->salon?->name }}</h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div><dt style="color: var(--sa-text-dim);">آدرس سالن</dt><dd dir="ltr" class="text-right">{{ $verification->salon ? \App\Support\SalonAddress::short($verification->salon) : '—' }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">وضعیت سالن</dt><dd>{{ $verification->salon?->verificationLabel() }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">نام مالک</dt><dd>{{ $verification->owner_name }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">کد ملی مالک</dt><dd dir="ltr" class="text-right" data-national-code>{{ $verification->owner_national_code }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">موبایل مالک (حساب)</dt><dd dir="ltr" class="text-right">{{ $verification->salon?->owner()?->phone ?? '—' }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">نوع پروانه</dt><dd>{{ $verification->licenseLabel() }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">شناسه‌ی یکتای پروانه</dt><dd dir="ltr" class="text-right">{{ $verification->license_unique_id }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">ارسال</dt><dd>{{ $jalali($verification->created_at) }} — {{ $verification->submitter?->name }}</dd></div>
                <div><dt style="color: var(--sa-text-dim);">روش</dt><dd>{{ $verification->provider === 'manual' ? 'دستی (ابرمدیر)' : $verification->provider }}</dd></div>
            </dl>
            @if ($verification->license_file_path)
                <a href="{{ route('superadmin.verifications.file', $verification) }}" target="_blank" class="sa-btn mt-4 inline-block">دیدن فایل پروانه</a>
            @endif
            <p class="text-xs mt-4 leading-6" style="color: var(--sa-text-dim);">
                شناسه‌ی یکتا را در درگاه ملی مجوزها (استعلام پروانه) با نام مالک و نوع پروانه تطبیق دهید. اتصال خودکار به امتا بعداً (مرحله‌ی ب).
            </p>
            @if ($sameNationalCode->isNotEmpty())
                <div class="mt-4 p-3 rounded-lg text-sm" style="background: var(--sa-soft);" data-same-national-code>
                    همین کد ملی برای سالن‌های دیگر هم آمده: {{ $sameNationalCode->map(fn ($v) => $v->salon?->name)->filter()->unique()->implode('، ') }}
                </div>
            @endif
        </div>

        <div class="space-y-5">
            @if ($verification->status === 'pending')
                <form method="POST" action="{{ route('superadmin.verifications.approve', $verification) }}" class="sa-card sa-card-pad">
                    @csrf
                    <button type="submit" class="sa-btn w-full">تأیید و باز کردن سایت سالن</button>
                </form>
                <form method="POST" action="{{ route('superadmin.verifications.reject', $verification) }}" class="sa-card sa-card-pad space-y-3">
                    @csrf
                    <label class="sa-label" for="reason">دلیل رد (برای مالک پیامک می‌شود)</label>
                    <textarea id="reason" name="reason" rows="3" maxlength="500" required class="sa-input">{{ old('reason') }}</textarea>
                    @error('reason')<p class="text-sm" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                    <button type="submit" class="sa-btn w-full" style="background: var(--sa-danger);">رد</button>
                </form>
            @else
                <div class="sa-card sa-card-pad text-sm">
                    {{ $verification->status === 'approved' ? 'تأیید شد' : 'رد شد' }} — {{ $jalali($verification->reviewed_at) }} — {{ $verification->reviewer?->name }}
                    @if ($verification->rejection_reason)<div class="mt-2">{{ $verification->rejection_reason }}</div>@endif
                </div>
            @endif

            @if ($history->isNotEmpty())
                <div class="sa-card sa-card-pad text-sm">
                    <h3 class="font-bold mb-2">ارسال‌های قبلی</h3>
                    @foreach ($history as $h)
                        <div class="py-1"><a href="{{ route('superadmin.verifications.show', $h) }}" style="color: var(--sa-accent);">{{ $jalali($h->created_at) }}</a> — {{ ['pending' => 'در انتظار', 'approved' => 'تأیید', 'rejected' => 'رد'][$h->status] ?? $h->status }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
