<?php

namespace App\Http\Controllers\Admin\BookingPolicy;

use App\Exceptions\BookingModeException;
use App\Http\Controllers\Controller;
use App\Models\BookingPolicy;
use App\Models\LoyaltySetting;
use App\Models\WalletSetting;
use App\Services\Booking\BookingModeSwitcher;
use App\Services\Booking\BookingPaymentPolicy;
use App\Support\Features\Feature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفحه‌ی «نحوه‌ی رزرو» مالک سالن (بسته‌ی 5ه، ۲۰۲۶-۱۰-۲۸): با/بدون پیش‌پرداخت و توضیح هر حالت، و بخش‌های بعدی همان صفحه. هر بخش فرم و
 * مسیر ذخیره‌ی جدای خودش را دارد.
 */
class AdminBookingPolicyController extends Controller
{
    public function __construct(
        private readonly BookingPaymentPolicy $paymentPolicy,
        private readonly BookingModeSwitcher $switcher,
    ) {}

    public function edit(): View
    {
        $salon = $this->paymentPolicy->salon();

        return view('admin.booking-policy.edit', [
            'salon' => $salon,
            'policy' => BookingPolicy::current(),
            'wallet' => WalletSetting::get(),
            'hasOnlinePaymentFeature' => (bool) $salon?->hasFeature(Feature::OnlinePayment),
            'canPrepay' => $this->paymentPolicy->canPrepay($salon),
            'effectiveMode' => $this->paymentPolicy->effectiveMode(),
            'hasLoyaltyFeature' => (bool) $salon?->hasFeature(Feature::Loyalty),
            'loyalty' => [
                'enabled' => LoyaltySetting::getValue(LoyaltySetting::NO_PREPAY_ENABLED) === '1',
                'fixed' => LoyaltySetting::getValue(LoyaltySetting::NO_PREPAY_FIXED),
                'per_amount' => LoyaltySetting::getValue(LoyaltySetting::NO_PREPAY_PER_AMOUNT),
            ],
        ]);
    }

    public function updateMode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_mode' => ['required', 'in:'.BookingPolicy::MODE_NONE.','.BookingPolicy::MODE_PREPAY],
            'prepayment_percentage' => ['required_if:payment_mode,prepay', 'nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_prepayment_amount' => ['required_if:payment_mode,prepay', 'nullable', 'numeric', 'min:0', 'max:100000000'],
        ], [], [
            'prepayment_percentage' => 'درصد پیش‌پرداخت',
            'minimum_prepayment_amount' => 'حداقل پیش‌پرداخت',
        ]);

        $before = BookingPolicy::current()->payment_mode;

        try {
            if ($data['payment_mode'] === BookingPolicy::MODE_PREPAY) {
                $this->switcher->assertCanPrepay((float) $data['prepayment_percentage'], (float) $data['minimum_prepayment_amount']);
                WalletSetting::get()->update([
                    'prepayment_percentage' => $data['prepayment_percentage'],
                    'minimum_prepayment_amount' => $data['minimum_prepayment_amount'],
                ]);
            }

            $waived = $this->switcher->switchTo($data['payment_mode']);
        } catch (BookingModeException $e) {
            return back()->withInput()->with('error', $e->getUserMessage());
        }

        $message = match (true) {
            $data['payment_mode'] === BookingPolicy::MODE_PREPAY && $before !== BookingPolicy::MODE_PREPAY => 'رزرو با پیش‌پرداخت روشن شد؛ فقط نوبت‌های تازه پیش‌پرداخت لازم دارند و نوبت‌های قبلی دست نخوردند.',
            $data['payment_mode'] === BookingPolicy::MODE_NONE && $before !== BookingPolicy::MODE_NONE => $waived > 0
                ? sprintf('رزرو بدون پیش‌پرداخت شد. %s نوبت در انتظار پرداخت حالا منتظر تأیید شماست و به مشتری‌ها و متخصص‌ها خبر داده شد.', to_persian_num((string) $waived))
                : 'رزرو بدون پیش‌پرداخت شد.',
            default => 'تنظیمات ذخیره شد.',
        };

        return redirect()->route('admin.booking-policy.edit')->with('success', $message);
    }

    /**
     * امتیاز باشگاه برای نوبت‌های «بدون پیش‌پرداخت» (تصمیم ۹): با کلید روشن هر دو عدد اجباری‌اند — هیچ مقدار پیش‌فرض یا ثابتی در کد نیست.
     */
    public function updateLoyalty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_prepay_points_enabled' => ['nullable', 'boolean'],
            'no_prepay_fixed_points' => ['required_if_accepted:no_prepay_points_enabled', 'nullable', 'integer', 'min:0', 'max:100000'],
            'no_prepay_amount_per_point' => ['required_if_accepted:no_prepay_points_enabled', 'nullable', 'integer', 'min:1', 'max:1000000000'],
        ], [
            'no_prepay_fixed_points.required_if_accepted' => 'با روشن کردن امتیاز، «امتیاز ثابت هر نوبت» را وارد کنید.',
            'no_prepay_amount_per_point.required_if_accepted' => 'با روشن کردن امتیاز، «هر چند تومان از قیمت خدمت = ۱ امتیاز» را وارد کنید.',
        ], [
            'no_prepay_fixed_points' => 'امتیاز ثابت هر نوبت',
            'no_prepay_amount_per_point' => 'هر چند تومان = ۱ امتیاز',
        ]);

        $enabled = $request->boolean('no_prepay_points_enabled');
        LoyaltySetting::setValue(LoyaltySetting::NO_PREPAY_ENABLED, $enabled ? '1' : '0', 'امتیاز نوبت‌های بدون پیش‌پرداخت');
        if (($data['no_prepay_fixed_points'] ?? null) !== null) {
            LoyaltySetting::setValue(LoyaltySetting::NO_PREPAY_FIXED, (int) $data['no_prepay_fixed_points'], 'امتیاز ثابت هر نوبت بدون پیش‌پرداخت انجام‌شده');
        }
        if (($data['no_prepay_amount_per_point'] ?? null) !== null) {
            LoyaltySetting::setValue(LoyaltySetting::NO_PREPAY_PER_AMOUNT, (int) $data['no_prepay_amount_per_point'], 'هر چند تومان از قیمت خدمت = ۱ امتیاز (بدون پیش‌پرداخت)');
        }

        return redirect()->route('admin.booking-policy.edit')->with('success', $enabled
            ? 'امتیاز نوبت‌های بدون پیش‌پرداخت روشن شد؛ از این پس هر نوبت انجام‌شده امتیاز می‌گیرد.'
            : 'امتیاز نوبت‌های بدون پیش‌پرداخت خاموش است.');
    }

    /** سقف نوبت آنلاین هر مشتری (تصمیم‌های ۱۲ و ۱۵): خالی یا صفر = بی‌سقف */
    public function updateLimits(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'daily_limit' => ['nullable', 'integer', 'min:0', 'max:50'],
            'active_limit' => ['nullable', 'integer', 'min:0', 'max:200'],
        ], [], [
            'daily_limit' => 'سقف نوبت در روز',
            'active_limit' => 'سقف نوبت‌های آینده‌ی فعال',
        ]);

        BookingPolicy::current()->update([
            'daily_limit' => $data['daily_limit'] ?? null,
            'active_limit' => $data['active_limit'] ?? null,
        ]);

        return redirect()->route('admin.booking-policy.edit')->with('success', 'سقف نوبت مشتری ذخیره شد.');
    }

    /** مشتری بدقول (تصمیم ۱۶): آستانه و بازه، و دو محافظ سالن بی درگاه */
    public function updateNoShow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_show_threshold' => ['nullable', 'integer', 'min:0', 'max:50'],
            'no_show_window_days' => ['required', 'integer', 'min:1', 'max:730'],
            'flagged_force_pending' => ['nullable', 'boolean'],
            'flagged_active_limit' => ['nullable', 'integer', 'min:0', 'max:50'],
        ], [], [
            'no_show_threshold' => 'تعداد «نیامد»',
            'no_show_window_days' => 'بازه‌ی روز',
            'flagged_active_limit' => 'سقف نوبت فعال مشتری بدقول',
        ]);

        BookingPolicy::current()->update([
            'no_show_threshold' => $data['no_show_threshold'] ?? null,
            'no_show_window_days' => (int) $data['no_show_window_days'],
            'flagged_force_pending' => $request->boolean('flagged_force_pending'),
            'flagged_active_limit' => $data['flagged_active_limit'] ?? null,
        ]);

        return redirect()->route('admin.booking-policy.edit')->with('success', 'تنظیمات مشتری بدقول ذخیره شد.');
    }
}
