<?php

namespace App\Http\Controllers\Admin\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Services\Payment\Zarinpal\ZarinpalConnectService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ⭐ «اتصال حساب زرین‌پال» در صفحه‌ی «درگاه‌های پرداخت» مالک (بسته‌ی 5ز، ۲۰۲۶-۱۰-۳۰) — ZarinpalConnectService. فقط مالک (گروه
 * salon.owner)؛ تا services.zarinpal.connect.client_id خالی است همه‌ی مسیرها ۴۰۴.
 */
class AdminZarinpalConnectController extends Controller
{
    public function __construct(private readonly ZarinpalConnectService $service)
    {
        abort_unless(ZarinpalConnectService::enabled(), 404);
    }

    private function back(array $result): RedirectResponse
    {
        return redirect()->route('admin.payment-gateways.index')->with($result['level'], $result['message']);
    }

    public function connect(CurrentSalon $current): RedirectResponse
    {
        return redirect()->away($this->service->authorizeUrl($current->get()));
    }

    public function callback(Request $request, CurrentSalon $current): RedirectResponse
    {
        return $this->back($this->service->complete(
            $current->get(),
            $request->user(),
            is_string($request->query('code')) ? $request->query('code') : null,
            is_string($request->query('state')) ? $request->query('state') : null,
            is_string($request->query('error')) ? mb_substr($request->query('error'), 0, 100) : null,
        ));
    }

    public function sync(CurrentSalon $current): RedirectResponse
    {
        return $this->back($this->service->sync($current->get()));
    }

    public function requestTerminal(Request $request, CurrentSalon $current): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'string', 'max:32', 'regex:/^[0-9]+$/'],
            'support_phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'name' => ['required', 'string', 'max:100'],
        ], [
            'bank_account_id.required' => 'حساب بانکی تسویه‌ی درگاه را انتخاب کنید.',
            'support_phone.regex' => 'شماره‌ی پشتیبانی درگاه باید موبایل ۱۱ رقمی باشد (۰۹...).',
        ], ['name' => 'نام درگاه']);

        return $this->back($this->service->requestTerminal($current->get(), $data['bank_account_id'], $data['support_phone'], $data['name']));
    }

    public function disconnect(CurrentSalon $current): RedirectResponse
    {
        $this->service->disconnect($current->get());

        return redirect()->route('admin.payment-gateways.index')->with('success', 'اتصال حساب زرین‌پال قطع شد؛ کد پذیرنده‌ی ثبت‌شده سر جایش ماند.');
    }
}
