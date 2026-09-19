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
        if (! isset($view->currentSalonName)) {
            $view->with('currentSalonName', $this->currentSalon->get()?->name ?? config('app.name', 'راستا'));
        }
    }
}
