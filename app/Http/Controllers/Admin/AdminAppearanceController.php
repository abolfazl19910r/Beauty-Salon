<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CurrentSalon;
use App\Support\Themes\SalonAppearance;
use App\Support\Themes\ThemeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ⭐ «ظاهر سالن» (بسته‌ی «و»، ۲۰۲۶-۱۰-۱۵) — مالک سالن قالب سایت/پنل مشتری/پنل متخصص/اپ‌ها و قالب پنل مدیر را انتخاب می‌کند.
 * قالب‌های بیرون از پلن و بازه‌ی فعلی قفل‌اند («با ارتقا باز می‌شود») ولی پیش‌نمایششان باز است. انتخابی که بعداً با پایان
 * اشتراک یا پایین آمدن پلن قفل شود ذخیره می‌ماند و تا آن موقع پیش‌فرض نمایش داده می‌شود.
 */
class AdminAppearanceController extends Controller
{
    public function edit(): View
    {
        $salon = app(CurrentSalon::class)->get();
        SalonAppearance::flush();

        return view('admin.appearance.edit', [
            'salon' => $salon,
            'appearance' => SalonAppearance::for($salon),
            'layouts' => ThemeCatalog::layouts(),
            'palettes' => ThemeCatalog::palettes(),
            'adminThemes' => ThemeCatalog::adminThemes(),
            'plan' => $salon->effectivePlan(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $salon = app(CurrentSalon::class)->get();
        SalonAppearance::flush();
        $appearance = SalonAppearance::for($salon);

        $data = $request->validate([
            'site_theme' => ['required', 'string', 'max:40'],
            'admin_theme' => ['required', 'string', 'max:20'],
        ]);

        $errors = [];
        if (! ThemeCatalog::isSiteKey($data['site_theme'])) {
            $errors['site_theme'] = 'این قالب وجود ندارد.';
        } elseif (! $appearance->siteAllowed($data['site_theme']) && $data['site_theme'] !== $salon->site_theme) {
            $errors['site_theme'] = 'این قالب در پلن و بازه‌ی فعلی سالن شما باز نیست؛ با ارتقا یا اشتراک بلندتر باز می‌شود.';
        }
        if (! ThemeCatalog::isAdminKey($data['admin_theme'])) {
            $errors['admin_theme'] = 'این قالب وجود ندارد.';
        } elseif (! $appearance->adminAllowed($data['admin_theme']) && $data['admin_theme'] !== $salon->admin_theme) {
            $errors['admin_theme'] = 'این قالب پنل مدیر در پلن سالن شما باز نیست.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $salon->update([
            // پیش‌فرض‌ها null ذخیره می‌شوند تا تغییر پیش‌فرض پلتفرم در آینده به همه برسد
            'site_theme' => $data['site_theme'] === ThemeCatalog::DEFAULT_SITE ? null : $data['site_theme'],
            'admin_theme' => $data['admin_theme'] === ThemeCatalog::DEFAULT_ADMIN ? null : $data['admin_theme'],
        ]);
        SalonAppearance::flush();
        app(\App\Support\Themes\CurrentAppearance::class)->forget();
        // صفحه‌ی خانه‌ی کش‌شده نداریم (فقط داده‌ها کش می‌شوند)؛ اپ‌ها قالب را از API تنظیمات سالن می‌خوانند

        return redirect()->route('admin.appearance.edit')->with('success', 'ظاهر سالن ذخیره شد.');
    }
}
