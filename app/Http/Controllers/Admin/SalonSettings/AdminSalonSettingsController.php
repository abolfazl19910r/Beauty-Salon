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

        $salon->update([
            'name' => $request->validated('name'),
            'tagline' => $request->validated('tagline'),
            'bio' => $request->validated('bio'),
            ...$request->salonContactAttributes(),
        ]);

        // ⭐ ۲۰۲۶-۰۹-۲۴: کد پذیرنده‌ی زرین‌پال خود سالن — فقط وقتی فیلد واقعاً در فرم بوده (خالی = پرداخت
        // آنلاین غیرفعال). درخواستی که اصلاً این فیلد رو نداره نباید بی‌صدا درگاه سالن رو پاک کنه.
        // ⭐ ۲۰۲۶-۰۹-۲۴: توکن Payout هیچ‌وقت در فرم نمایش داده نمی‌شه؛ فیلد خالی یعنی «بدون تغییر».
        if ($request->boolean('remove_zarinpal_payout_api_key')) {
            $salon->update(['zarinpal_payout_api_key' => null]);
        } elseif (filled($request->validated('zarinpal_payout_api_key'))) {
            $salon->update(['zarinpal_payout_api_key' => trim($request->validated('zarinpal_payout_api_key'))]);
        }

        if ($request->exists('zarinpal_merchant_id')) {
            $salon->update(['zarinpal_merchant_id' => \App\Support\ZarinpalMerchant::normalize($request->validated('zarinpal_merchant_id'))]);
        }

        if ($request->hasFile('logo')) {
            $this->logoService->replace($salon, $request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            $this->logoService->remove($salon);
        }

        return redirect()->route('admin.salon-settings.edit')->with('success', 'اطلاعات سالن ذخیره شد.');
    }
}
