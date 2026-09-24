<?php

namespace App\Http\Controllers\Admin\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentGateway\SavePaymentGatewayRequest;
use App\Models\SalonPaymentGateway;
use App\Payments\GatewayCatalog;
use App\Payments\GatewayManager;
use App\Services\Payment\SalonGatewayService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⭐ صفحه‌ی «درگاه‌های پرداخت» مالک سالن (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — افزودن، ویرایش، ترتیب،
 * فعال/غیرفعال، حذف و تست اتصال. فقط مالک (middleware salon.owner). SalonPaymentGateway عمداً
 * BelongsToSalon نداره، پس هر درگاه فقط از طریق $salon->paymentGateways() پیدا می‌شه (درگاه سالن
 * دیگه = ۴۰۴).
 */
class AdminPaymentGatewayController extends Controller
{
    public function __construct(
        protected readonly SalonGatewayService $service,
        protected readonly GatewayManager $manager,
    ) {}

    private function find(string $gatewayId): SalonPaymentGateway
    {
        return app(CurrentSalon::class)->get()->paymentGateways()->findOrFail((int) $gatewayId);
    }

    public function index(): View
    {
        $salon = app(CurrentSalon::class)->get();

        return view('admin.payment-gateways.index', [
            'salon' => $salon,
            'gateways' => $salon->paymentGateways()->orderBy('priority')->orderBy('id')->get(),
            'catalog' => GatewayCatalog::DRIVERS,
        ]);
    }

    public function store(SavePaymentGatewayRequest $request): RedirectResponse
    {
        $gateway = $this->service->create(app(CurrentSalon::class)->get(), $request->validated());

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "درگاه «{$gateway->displayName()}» اضافه شد. برای اطمینان «تست اتصال» را بزنید.");
    }

    public function update(Request $request, string $gatewayId): RedirectResponse
    {
        $gateway = $this->find($gatewayId);
        $request->attributes->set('gateway', $gateway);
        $validated = app(SavePaymentGatewayRequest::class)->validated();

        $this->service->update($gateway, $validated);

        return redirect()->route('admin.payment-gateways.index')->with('success', "درگاه «{$gateway->displayName()}» ذخیره شد.");
    }

    public function destroy(string $gatewayId): RedirectResponse
    {
        $gateway = $this->find($gatewayId);
        $name = $gateway->displayName();
        $this->service->delete($gateway);

        return redirect()->route('admin.payment-gateways.index')->with('success', "درگاه «{$name}» حذف شد.");
    }

    public function move(Request $request, string $gatewayId): RedirectResponse
    {
        $this->service->move($this->find($gatewayId), $request->input('direction') === 'up' ? 'up' : 'down');

        return redirect()->route('admin.payment-gateways.index');
    }

    public function test(string $gatewayId): RedirectResponse
    {
        $gateway = $this->find($gatewayId);
        $result = $this->manager->test($gateway, url('/'));

        return redirect()->route('admin.payment-gateways.index')->with(
            $result->success ? 'success' : 'error',
            $result->success
                ? "اتصال به «{$gateway->displayName()}» برقرار است و اطلاعات پذیرنده معتبر است (پولی جابه‌جا نشد)."
                : "تست «{$gateway->displayName()}» ناموفق بود: ".($result->message ?? 'خطای نامشخص'),
        );
    }
}
