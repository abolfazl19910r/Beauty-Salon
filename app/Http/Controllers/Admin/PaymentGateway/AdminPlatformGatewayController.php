<?php

namespace App\Http\Controllers\Admin\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Payments\PlatformGateway;
use App\Services\Payment\PlatformGateway\PlatformGatewayService;
use App\Support\CurrentSalon;
use App\Support\PayoutDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * درخواست «درگاه پرداخت پلتفرم» از پنل مالک (۲۰۲۶-۱۱-۲۲) — کارت پایین صفحه‌ی «درگاه‌های پرداخت». فقط مالک (گروه salon.owner)
 * و فقط وقتی PLATFORM_GATEWAY_ENABLED روشن است. مقصد واریز سهم سالن: شبا، کارت یا شماره‌ی حساب.
 */
class AdminPlatformGatewayController extends Controller
{
    public function store(Request $request, CurrentSalon $currentSalon, PlatformGatewayService $service): RedirectResponse
    {
        abort_unless(PlatformGateway::enabled(), 404);
        $salon = $currentSalon->get();

        if ($service->hasOpenRequest($salon)) {
            return back()->with('error', 'درخواست درگاه پلتفرم شما در انتظار بررسی یا تأییدشده است.');
        }

        $data = $request->validate([
            'payout_method' => ['required', Rule::in(array_keys(PayoutDestination::METHODS))],
            'destination' => ['required', 'string', 'max:64'],
            'payout_bank' => ['nullable', 'string', 'max:64'],
            'account_holder' => ['required', 'string', 'max:190'],
            'owner_note' => ['nullable', 'string', 'max:1000'],
            'accept_fee' => ['accepted'],
        ], [
            'payout_method.required' => 'روش واریز سهم سالن را انتخاب کنید.',
            'destination.required' => 'شماره‌ی شبا، کارت یا حساب را بنویسید.',
            'account_holder.required' => 'نام صاحب حساب را بنویسید (باید با نام ثبت‌شده در بانک یکی باشد).',
            'accept_fee.accepted' => 'برای ارسال درخواست، کسر کارمزد پلتفرم از هر پرداخت را بپذیرید.',
        ]);

        $normalized = match ($data['payout_method']) {
            'iban' => PayoutDestination::iban($data['destination']),
            'card' => PayoutDestination::card($data['destination']),
            default => PayoutDestination::account($data['destination']),
        };
        if ($normalized === null) {
            return back()->withInput()->withErrors(['destination' => match ($data['payout_method']) {
                'iban' => 'شماره‌ی شبا معتبر نیست (۲۴ رقم بعد از IR؛ رقم کنترلی را بررسی کنید).',
                'card' => 'شماره‌ی کارت معتبر نیست (۱۶ رقم).',
                default => 'شماره‌ی حساب باید ۶ تا ۲۰ رقم باشد.',
            }]);
        }
        if ($data['payout_method'] === 'account' && blank($data['payout_bank'] ?? null)) {
            return back()->withInput()->withErrors(['payout_bank' => 'برای واریز به شماره‌ی حساب، نام بانک را بنویسید.']);
        }

        $service->submit($salon, $request->user(), ['destination' => $normalized] + $data);

        return redirect()->to(route('admin.payment-gateways.index').'#platform-gateway')
            ->with('success', 'درخواست درگاه پلتفرم فرستاده شد؛ پس از بررسی، نتیجه را پیامک می‌کنیم.');
    }
}
