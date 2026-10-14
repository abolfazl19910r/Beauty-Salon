{{--
    ⭐ بسته‌ی د — پیش‌نمایش لوگوی سالن روی پس‌زمینه‌ی تیره و روشن ماهرو، همان‌طور که سایت و اپ‌ها نشانش می‌دهند (با پلاک اگر لازم است).
    مشترک فرم تنظیمات سالن (مدیر) و ویرایش سالن (سوپرادمین)؛ فقط استایل درون‌خطی چون هر پنل متغیرهای رنگ خودش را دارد.
    ورودی: $salon (App\Models\Salon) — لوگوهای ذخیره‌شده، بدون توجه به پلن.
--}}
@php
    $tonePreview = \App\Models\Salon::appearanceFor($salon->storedLogoUrl(), $salon->logo_tone, $salon->storedLogoDarkUrl(), $salon->logo_dark_tone);
    $toneHint = match (true) {
        $salon->logo_tone === \App\Services\Salon\LogoToneAnalyzer::LIGHT
            => 'لوگو روشن است و روی تم روشن داخل پلاک تیره نشان داده می‌شود. اگر نسخه‌ی تیره‌ی لوگو را دارید، آن را لوگوی اصلی و همین را «لوگوی پس‌زمینه‌ی تیره» کنید تا پلاک لازم نباشد.',
        $salon->logo_tone === \App\Services\Salon\LogoToneAnalyzer::DARK && ! $salon->logo_dark_path
            => 'لوگو تیره است و روی تم تیره داخل پلاک روشن نشان داده می‌شود. برای نمایش بدون پلاک، نسخه‌ی روشن لوگو را در «لوگوی پس‌زمینه‌ی تیره» بگذارید.',
        $salon->logo_dark_path && $salon->logo_dark_tone === \App\Services\Salon\LogoToneAnalyzer::DARK
            => 'لوگوی پس‌زمینه‌ی تیره خودش تیره است و روی تم تیره داخل پلاک روشن نشان داده می‌شود؛ نسخه‌ی روشن‌تری بگذارید.',
        default => 'لوگو روی هر دو تم خواناست.',
    };
@endphp
@if ($tonePreview)
    <div class="mt-3" data-logo-tone-preview>
        <div class="flex gap-3">
            @foreach (['dark' => ['#15110E', '#F4EDE0', 'تم تیره', '#FBF8F2'], 'light' => ['#FBF8F2', '#21190F', 'تم روشن', '#1C1712']] as $previewTheme => [$previewBg, $previewTx, $previewLabel, $previewPlaque])
                <div class="flex-1 rounded-lg p-3 flex flex-col items-center gap-2" style="background:{{ $previewBg }};color:{{ $previewTx }};border:1px solid rgba(128,128,128,.25);">
                    <img src="{{ $tonePreview[$previewTheme]['url'] }}" alt="لوگو روی {{ $previewLabel }}" class="h-14 w-auto max-w-[140px] object-contain"
                         @if ($tonePreview[$previewTheme]['plaque']) style="background-color:{{ $previewPlaque }};padding:6px;border-radius:12px;" @endif>
                    <span class="text-xs">{{ $previewLabel }}{{ $tonePreview[$previewTheme]['plaque'] ? ' — با پلاک' : '' }}</span>
                </div>
            @endforeach
        </div>
        <p class="text-xs mt-2" style="opacity:.8;">{{ $toneHint }}</p>
    </div>
@endif
