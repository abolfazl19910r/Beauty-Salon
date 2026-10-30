<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Payments\PaymentReturnUrl;
use App\Services\Payment\SubscriptionPaymentService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۲ (۲۰۲۶-۱۰-۳۰): هر ترمینال زرین‌پال یک `domain` دارد و آدرس بازگشت با دامنه‌ی دیگر ‎-14 می‌گیرد. آدرس بازگشت
 * دیگر روی هاست همان درخواست (ساب‌دامین، `arisheh.ir/s/rasta`، هاست لینک پرداخت اپ) ساخته نمی‌شود: پرداخت‌های سالن همیشه روی
 * دامنه‌ی خود سالن (`{slug}.CENTRAL_DOMAIN`؛ با CENTRAL_DOMAIN خالی همان APP_URL) و اشتراک/بسته‌ی پیامک (درگاه پلتفرم) روی دامنه‌ی مرکزی.
 */
class PaymentReturnDomainTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->salon->paymentGateways()->create(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => '11111111-2222-3333-4444-555555555555'], 'priority' => 1, 'is_active' => true]);
        Http::fake(['*/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'S000000000000000000000000000000ab12'], 'errors' => []])]);
    }

    private function startBookingPayment(string $host): PaymentTransaction
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid', 'status' => 'pending_payment', 'prepayment_amount' => 60000,
        ]);

        $path = route('payment.process', $booking, false);
        $this->actingAs($user)->post("http://{$host}{$path}")->assertRedirect();

        return PaymentTransaction::where('payable_id', $booking->id)->sole();
    }

    private function sentCallback(): string
    {
        $sent = collect(Http::recorded())->map(fn ($pair) => $pair[0])->first(fn ($r) => str_contains($r->url(), 'request.json'));

        return (string) $sent['callback_url'];
    }

    public function test_salon_payment_returns_to_the_salons_own_subdomain_whatever_host_started_it(): void
    {
        config(['app.url' => 'https://arisheh.ir', 'app.central_domain' => 'arisheh.ir']);

        $tx = $this->startBookingPayment('arisheh.ir');

        $this->assertSame("https://{$this->salon->slug}.arisheh.ir/payments/return/{$tx->public_id}", $this->sentCallback());
    }

    public function test_without_a_central_domain_the_return_is_on_app_url_not_the_request_host(): void
    {
        config(['app.url' => 'http://192.168.1.2:8000', 'app.central_domain' => null]);

        $tx = $this->startBookingPayment('127.0.0.1:8000');

        $this->assertSame("http://192.168.1.2:8000/payments/return/{$tx->public_id}", $this->sentCallback());
    }

    public function test_platform_payments_return_to_the_central_domain(): void
    {
        config(['app.url' => 'https://arisheh.ir', 'app.central_domain' => 'arisheh.ir']);
        $invoice = Invoice::factory()->create(['salon_id' => $this->salon->id, 'amount' => 1500000]);

        app(SubscriptionPaymentService::class)->createPayment($invoice);

        $tx = PaymentTransaction::where('purpose', 'subscription')->sole();
        $this->assertSame("https://arisheh.ir/payments/return/{$tx->public_id}", $this->sentCallback());
    }

    public function test_helper_and_owner_page_name_the_domain_to_register(): void
    {
        config(['app.url' => 'https://arisheh.ir', 'app.central_domain' => 'arisheh.ir']);
        $this->assertSame("{$this->salon->slug}.arisheh.ir", PaymentReturnUrl::domain($this->salon));
        $this->assertSame('arisheh.ir', PaymentReturnUrl::domain(null));

        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))
            ->assertOk()
            ->assertSee('در پنل زرین‌پال این دامنه را ثبت کنید')
            ->assertSee("{$this->salon->slug}.arisheh.ir");
    }

    public function test_connection_test_uses_the_same_domain(): void
    {
        config(['app.url' => 'https://arisheh.ir', 'app.central_domain' => 'arisheh.ir']);
        $owner = User::factory()->create(['is_admin' => true]);
        $gateway = $this->salon->paymentGateways()->sole();

        $this->actingAs($owner)->post(route('admin.payment-gateways.test', $gateway->id))->assertSessionHas('success');

        $this->assertStringStartsWith("https://{$this->salon->slug}.arisheh.ir/", $this->sentCallback());
    }
}
