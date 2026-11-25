<?php

namespace Tests\Feature\Web;

use App\Models\Plan;
use App\Models\Salon;
use App\Services\Plans\DefaultPlans;
use App\Support\CurrentSalon;
use App\Support\Themes\SalonAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * یافته‌ی آزمون Chrome بسته‌ی ۵ج: عنوان صفحه‌ی خانه نام سالن را دو بار داشت (layouts/app نام را اضافه می‌کند) و طلایی ماهرو
 * ادعای «سالن زیبایی لوکس» را هم. حالا عنوان خانه = فقط نام سالن، در همه‌ی قالب‌ها و در og:title.
 */
class SalonHomeTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_title_has_the_salon_name_once_in_every_template(): void
    {
        app(DefaultPlans::class)->sync();
        /** @var Salon $salon */
        $salon = app(CurrentSalon::class)->get();
        $name = $salon->name;

        foreach (['', 'signature:rose', 'editorial:sage', 'showcase:turquoise', 'app:lilac'] as $site) {
            $salon->forceFill([
                'site_theme' => $site ?: null, 'plan_id' => Plan::byKey('clinic')->id, 'subscription_type' => '12m', 'trial_ends_at' => null,
                'subscription_ends_at' => now()->addYear(),
            ])->save();
            SalonAppearance::flush();
            Cache::flush();

            $html = $this->get(route('home'))->assertOk()->getContent();
            $this->assertStringContainsString('<title>'.e($name).'</title>', $html, $site ?: 'mahru');
            $this->assertStringContainsString('<meta property="og:title" content="'.e($name).'">', $html, $site ?: 'mahru');
            $this->assertStringNotContainsString('سالن زیبایی لوکس', $html);
        }

        // صفحه‌های دیگر همچنان «عنوان | نام سالن»
        $this->get(route('services.index'))->assertOk()->assertSee('<title>خدمات | '.e($name).'</title>', false);
    }
}
