<?php

namespace App\Http\Controllers\Admin\BookingPolicy;

use App\Exceptions\BookingModeException;
use App\Http\Controllers\Controller;
use App\Models\BookingPolicy;
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
}
