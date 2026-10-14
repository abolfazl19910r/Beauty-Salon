<?php

namespace App\Http\Controllers\Admin\SalonSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalonSettings\UpdateSalonSettingsRequest;
use App\Services\Salon\SalonLogoService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * ⭐ صفحه‌ی «اطلاعات سالن» در پنل مدیریت (۲۰۲۶-۰۹-۲۴) — مالک سالن خودش نام، شعار، معرفی، لوگو،
 * آدرس، تلفن، سابقه و ساعات کاری سالنش رو ویرایش می‌کنه (قبلاً فقط موقع ثبت‌نام یا توسط سوپرادمین).
 * فقط مالک (middleware salon.owner)؛ منشی/ادمین staff دسترسی نداره. آدرس اختصاصی (slug) عمداً
 * قابل تغییر نیست تا لینک‌هایی که قبلاً برای مشتری‌ها فرستاده شده خراب نشن.
 */
class AdminSalonSettingsController extends Controller
{
    public function __construct(protected readonly SalonLogoService $logoService) {}

    public function edit(): View
    {
        return view('admin.salon-settings.edit', ['salon' => app(CurrentSalon::class)->get()]);
    }

    public function update(UpdateSalonSettingsRequest $request): RedirectResponse
    {
        $salon = app(CurrentSalon::class)->get();

        // لوگوی تازه فقط با امکان «لوگوی اختصاصی» پلن؛ حذف لوگوی قبلی همیشه مجاز (بسته‌ی پلن‌ها)
        if ($request->hasFile('logo') && ! $salon->hasFeature(\App\Support\Features\Feature::CustomLogo)) {
            return back()->withInput()->withErrors(['logo' => 'لوگوی اختصاصی در پلن سالن شما نیست.']);
        }
        if ($request->hasFile('logo_dark') && ! $salon->hasFeature(\App\Support\Features\Feature::CustomLogo)) {
            return back()->withInput()->withErrors(['logo_dark' => 'لوگوی اختصاصی در پلن سالن شما نیست.']);
        }
        if ($request->hasFile('logo_dark') && ! $salon->logo_path && ! $request->hasFile('logo')) {
            return back()->withInput()->withErrors(['logo_dark' => 'لوگوی پس‌زمینه‌ی تیره فقط کنار لوگوی اصلی پذیرفته می‌شود؛ اول لوگوی اصلی را بارگذاری کنید.']);
        }

        $salon->update([
            'name' => $request->validated('name'),
            'sms_name' => $request->validated('sms_name') ?: null,
            'tagline' => $request->validated('tagline'),
            'bio' => $request->validated('bio'),
            ...$request->salonContactAttributes(),
        ]);

        // ⭐ ۲۰۲۶-۰۹-۲۵: کد پذیرنده‌ی زرین‌پال دیگه اینجا نیست — صفحه‌ی «درگاه‌های پرداخت»
        // (AdminPaymentGatewayController) جایگزینش شد؛ مقدار ارسالی به این مسیر نادیده گرفته می‌شه.
        // توکن Payout هم (مرحله‌ی ۳، ۲۰۲۶-۰۹-۲۵) به ویرایش درگاه زرین‌پال در همان صفحه منتقل شد.

        // ⭐ بسته‌ی د: لوگوی اصلی (با سنجش لحن) و لوگوی دوم برای پس‌زمینه‌ی تیره
        if ($error = $this->logoService->syncFromRequest($salon, $request)) {
            return back()->withInput()->withErrors(['logo_dark' => $error]);
        }

        return redirect()->route('admin.salon-settings.edit')->with('success', 'اطلاعات سالن ذخیره شد.');
    }
}
