<?php

namespace Tests\Feature\Http;

use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دو مسیر عمداً بدون CSRF: برگشت درگاه‌ها (بانک‌ها با POST بین‌سایتی و بدون کوکی برمی‌گردند) و گزارش نقض CSP (مرورگر
 * بی توکن می‌فرستد). میان‌افزار CSRF در تست خاموش است (runningUnitTests)، پس اگر معافیت این دو مسیر از کار بیفتد هیچ تست
 * دیگری قرمز نمی‌شود؛ اینجا محیط «local» می‌شود تا CSRF واقعاً اجرا شود (مثل StaleCsrfTokenTest).
 *
 * چرا جدا (بسته‌ی 5ج، ارتقای Laravel): در Laravel 13 میان‌افزار گروه web به PreventRequestForgery تغییر نام داد و
 * ValidateCsrfToken/VerifyCsrfToken فقط زیرکلاس آن شدند؛ withoutMiddleware با نام‌های قدیمی دیگر آن را برنمی‌دارد.
 */
class CsrfExemptRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
    }

    public function test_a_bank_post_back_without_token_or_cookie_reaches_the_gateway_return(): void
    {
        $tx = PaymentTransaction::create([
            'driver' => 'sep',
            'purpose' => 'booking',
            'amount_rial' => 100000,
            'callback_url' => 'http://127.0.0.1:8000/payments/callback',
        ]);

        $this->withHeaders(['Sec-Fetch-Site' => 'cross-site', 'Origin' => 'https://sep.shaparak.ir'])
            ->post('/payments/return/'.$tx->public_id, ['State' => 'OK', 'RefNum' => 'R1'])
            ->assertStatus(303)
            ->assertRedirectContains('tx='.$tx->public_id);
    }

    public function test_a_browser_csp_report_without_token_is_accepted(): void
    {
        $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->postJson('/csp-report', ['csp-report' => ['violated-directive' => 'script-src', 'blocked-uri' => 'inline']])
            ->assertNoContent();

        $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->postJson('/csp-report', ['csp-report' => ['violated-directive' => 'img-src', 'blocked-uri' => 'https://x.test']])
            ->assertNoContent();
    }

    public function test_csrf_is_really_enforced_on_other_routes_in_this_test(): void
    {
        $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->postJson('/demo-request', ['_token' => 'kohne'])
            ->assertStatus(419);
    }
}
