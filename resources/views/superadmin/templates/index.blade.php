@extends('layouts.superadmin')

@section('title', 'قالب‌ها')

@section('content')
    <div class="sa-card p-5 mb-5 text-sm leading-7">
        <div class="font-bold mb-1">قالب‌های ظاهری سالن‌ها</div>
        <p style="color: var(--sa-text-dim);">
            «طلایی ماهرو» قالب پیش‌فرض و رایگان همه‌ی پلن‌هاست. علاوه بر آن {{ to_persian_num((string) $siteTotal) }} قالب برای سایت سالن، پنل مشتری و اپ
            و {{ to_persian_num((string) $adminTotal) }} قالب برای پنل مدیر طراحی می‌شود (بسته‌ی «و»؛ اول پیش‌نمایش و تأیید شما).
            عددهای این جدول یعنی هر پلن در هر بازه‌ی اشتراک <b style="color: var(--sa-text);">چند قالب علاوه بر پیش‌فرض</b> باز می‌کند.
            پایان اشتراک یا پایین آمدن پلن، سالن را به پیش‌فرض برمی‌گرداند و انتخابش برای تمدید نگه داشته می‌شود.
        </p>
        <p class="mt-1" style="color: var(--sa-warn);">انتخاب قالب برای سالن‌ها با ساخته شدن قالب‌ها فعال می‌شود؛ این عددها از همین حالا ذخیره‌اند.</p>
    </div>

    <form method="POST" action="{{ route('superadmin.templates.update') }}" class="sa-card overflow-x-auto">
        @csrf
        @method('PUT')
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">پلن</th>
                <th class="py-3 px-4">سایت · ۱ و ۳ ماهه</th>
                <th class="py-3 px-4">سایت · ۶ ماهه</th>
                <th class="py-3 px-4">سایت · ۱۲ ماهه</th>
                <th class="py-3 px-4">پنل مدیر</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($plans as $plan)
                <tr style="border-top: 1px solid var(--sa-border);" data-template-plan="{{ $plan->key }}">
                    <td class="py-3 px-4">
                        <div class="font-bold">{{ $plan->name }}</div>
                        <div class="text-xs" style="color: var(--sa-text-dim);">{{ $plan->is_trial ? 'آزمایشی' : ($plan->is_public ? 'عمومی' : 'غیرعمومی') }}{{ $plan->is_active ? '' : ' · غیرفعال' }}</div>
                    </td>
                    @foreach (['theme_site_count' => $siteTotal, 'theme_site_count_6m' => $siteTotal, 'theme_site_count_12m' => $siteTotal, 'theme_admin_count' => $adminTotal] as $field => $max)
                        <td class="py-3 px-4">
                            <input type="number" min="0" max="{{ $max }}" name="plans[{{ $plan->id }}][{{ $field }}]" class="sa-input" style="width: 5.5rem;"
                                   value="{{ old("plans.$plan->id.$field", $plan->$field) }}" @if ($plan->is_trial && $field !== 'theme_site_count' && $field !== 'theme_admin_count') title="آزمایشی بازه ندارد" @endif>
                            @if ($field === 'theme_site_count_12m' || $field === 'theme_admin_count')
                                <span class="text-xs" style="color: var(--sa-text-dim);">از {{ to_persian_num((string) $max) }}</span>
                            @endif
                            @error("plans.$plan->id.$field")<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4"><button type="submit" class="sa-btn">ذخیره</button></div>
    </form>
@endsection
