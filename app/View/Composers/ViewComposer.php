<?php

namespace App\View\Composers;

use App\Support\CurrentSalon;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ViewComposer
{
    public function __construct(protected CurrentSalon $currentSalon) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function compose(View $view): void
    {
        if (! isset($view->errors)) {
            $view->with('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));
        }

        // ⭐ فاز ۲ SaaS، پیگیری محور «۳» (۲۰۲۶-۰۹-۱۹): باگ واقعی کشف‌شده هنگام تست دو سالن
        // کنار هم — نام سالن در ده‌ها ویو (صفحه‌ی اصلی، لایوت مشتری، لاگین، بلاگ، ...) به‌صورت
        // متن ثابت «راستا» هاردکد شده بود، نه از دیتابیس. اینجا یک متغیر امن و همیشه غیر-null
        // به همه‌ی ویوها share می‌شه: وقتی CurrentSalon ست باشه (تمام مسیرهای مشتری زیر
        // /s/{slug} یا ساب‌دامین)، نام واقعی همون سالنه؛ وقتی ست نباشه (پنل سوپرادمین که همه‌ی
        // سالن‌ها رو مدیریت می‌کنه، یا هر context بدون سالن مشخص)، به نام پلتفرم (config
        // app.name) برمی‌گرده — نه یک سالن خاص. این فقط متغیر رو در دسترس می‌ذاره؛ هر ویو باید
        // خودش از {{ $currentSalonName }} به‌جای متن ثابت استفاده کنه.
        $currentSalonName = $this->currentSalon->get()?->name ?? config('app.name', 'راستا');

        if (! isset($view->currentSalonName)) {
            $view->with('currentSalonName', $currentSalonName);
        }

        // ⭐ پیگیری «محور ۳» (۲۰۲۶-۰۹-۲۰): همون کشفی که $currentSalonName رو ساخت، صراحتاً
        // مستند کرد که متن‌های بازاریابی اطراف نام (tagline/bio) هنوز واقعاً generic و مشترک
        // بین همه‌ی سالن‌ها می‌مونن — چون Salon اون‌موقع ستونی براشون نداشت (migration
        // 2026_09_20_000000). حالا که ستون‌ها اضافه شدن، همون الگوی fallback امن رو تکرار
        // می‌کنیم: هر ویو {{ $currentSalonTagline }}/{{ $currentSalonBio }} رو صدا می‌زنه؛ اگه
        // سالن این فیلدها رو پر نکرده باشه (یا CurrentSalon اصلاً ست نباشه)، همون متن عمومی قبلی
        // که قبلاً هاردکد بود برمی‌گرده — یعنی یک سالن تازه‌ساخته هیچ‌وقت صفحه‌ی خالی نمی‌بینه.
        if (! isset($view->currentSalonTagline)) {
            $view->with(
                'currentSalonTagline',
                $this->currentSalon->get()?->tagline ?: 'بهترین خدمات زیبایی با متخصص‌ترین تیم'
            );
        }

        if (! isset($view->currentSalonBio)) {
            $view->with(
                'currentSalonBio',
                $this->currentSalon->get()?->bio
                    ?: "سالن زیبایی {$currentSalonName} فضایی آرام و لوکس را برای مراقبت کامل از مو، پوست و زیبایی شما فراهم کرده است. تیم ما متشکل از متخصصین باتجربه و دارای گواهینامه‌های بین‌المللی است."
            );
        }

        // ⭐ اطلاعات تماس و فعالیت سالن (۲۰۲۶-۰۹-۲۳) — همون الگوی name/tagline/bio، با یک فرق عمدی:
        // اینجا هیچ متن پیش‌فرض ساختگی‌ای برنمی‌گرده. قبلاً فوتر همه‌ی سالن‌ها یک آدرس/تلفن/ساعت
        // هاردکد مشترک نشون می‌داد، که برای هر سالنی جز یکی غلط بود؛ حالا اگه سالن مقداری نداشته
        // باشه (null / آرایه‌ی خالی)، ویو همون خط رو اصلاً نمایش نمی‌ده.
        $salon = $this->currentSalon->get();

        foreach ([
            'currentSalonAddress' => $salon?->address,
            'currentSalonPhone' => $salon?->phone,
            'currentSalonExperienceYears' => $salon?->experienceYears(),
            'currentSalonHours' => $salon?->workingHoursLines() ?? [],
        ] as $key => $value) {
            if (! isset($view->{$key})) {
                $view->with($key, $value);
            }
        }
    }
}
