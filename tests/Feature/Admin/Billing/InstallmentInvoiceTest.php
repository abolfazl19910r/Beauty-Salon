<?php

namespace Tests\Feature\Admin\Billing;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\PlatformCreditEntry;
use App\Models\Salon;
use App\Models\User;
use App\Services\Payment\InvoiceInstallments;
use App\Services\Payment\InvoiceService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱ (راه «ب»): فاکتور اشتراک/ارتقای بالای سقف ۱۰۰ میلیون تومانی هر پرداخت زرین‌پال در چند قسط پرداخت
 * می‌شود. هر قسط یک ردیف payment_transactions با payable همان فاکتور و شماره‌ی قسط، و در زرین‌پال `metadata.order_id = INV-{id}` و
 * توضیح «قسط k از n». اشتراک فقط با آخرین قسط فعال می‌شود. فاکتور نیمه‌پرداخت ۷ روز باز می‌ماند؛ بعد مبلغ پرداخت‌شده «اعتبار پلتفرم»
 * سالن می‌شود و از خرید بعدی کم می‌شود.
 */
class InstallmentInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    private int $authority = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->update(['max_specialists_count' => 100]);
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->freezeSecond();
    }

    /** هر درخواست یک اتوریتی تازه؛ verify همیشه موفق مگر $verifyCode */
    private function fakeGateway(int $verifyCode = 100): void
    {
        Http::fake([
            '*request.json' => function () {
                $this->authority++;

                return Http::response(['data' => ['code' => 100, 'authority' => 'SINST'.$this->authority], 'errors' => []]);
            },
            '*verify.json' => $verifyCode === 100
                ? Http::response(['data' => ['code' => 100, 'ref_id' => 90000 + $this->authority], 'errors' => []])
                : Http::response(['data' => [], 'errors' => ['code' => $verifyCode, 'message' => 'x', 'validations' => []]], 422),
        ]);
    }

    private function price(string $type = '12m'): int
    {
        return app(InvoiceService::class)->priceFor($type, $this->salon->fresh());
    }

    private function buy(string $type = '12m'): Invoice
    {
        $this->actingAs($this->owner)->post(route('admin.billing.purchase'), ['subscription_type' => $type])->assertRedirect();

        return Invoice::latest('id')->first();
    }

    private function returnFromGateway(Invoice $invoice, string $status = 'OK')
    {
        return $this->actingAs($this->owner)->get(route('admin.billing.callback', [
            'invoice' => $invoice->id, 'Authority' => $invoice->fresh()->authority, 'Status' => $status,
        ]));
    }

    private function requests(): \Illuminate\Support\Collection
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn (Request $r) => str_contains($r->url(), 'request.json'))->values();
    }

    public function test_an_invoice_above_the_limit_is_paid_in_linked_installments_and_activates_once(): void
    {
        $this->fakeGateway();
        $total = $this->price();
        $this->assertGreaterThan(200_000_000, $total);
        $endsBefore = $this->salon->fresh()->subscription_ends_at;

        $invoice = $this->buy();

        $this->assertSame(3, $invoice->installments);
        $part = (int) ceil($total / 3);
        $first = $this->requests()->first();
        $this->assertSame($part * 10, $first['amount']);
        $this->assertSame('INV-'.$invoice->id, $first['metadata']['order_id']);
        $this->assertStringContainsString('قسط ۱ از ۳', $first['description']);
        $this->assertSame(1, PaymentTransaction::where('payable_id', $invoice->id)->sole()->installment);

        $this->returnFromGateway($invoice)->assertRedirect(route('admin.billing.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'قسط ۱ از ۳'));
        $invoice->refresh();
        $this->assertTrue($invoice->isPending(), 'تا آخرین قسط پرداخت‌نشده می‌ماند');
        $this->assertSame($part, $invoice->paid_amount);
        $this->assertTrue($invoice->partial_expires_at->equalTo(now()->addDays(InvoiceInstallments::OPEN_DAYS)));
        $this->assertEquals($endsBefore, $this->salon->fresh()->subscription_ends_at, 'اشتراک هنوز تمدید نشده');

        $this->actingAs($this->owner)->get(route('admin.billing.index'))->assertSee('قسط ۲ از ۳');

        foreach ([2, 3] as $k) {
            $this->actingAs($this->owner)->post(route('admin.billing.installment', $invoice))->assertRedirect();
            $this->returnFromGateway($invoice);
        }

        $invoice->refresh();
        $this->assertTrue($invoice->isPaid());
        $this->assertSame($total, $invoice->paid_amount);
        $amounts = $this->requests()->map(fn ($r) => $r['amount'] / 10)->all();
        $this->assertSame([$part, $part, $total - 2 * $part], $amounts, 'قسط آخر باقی‌مانده است');
        $this->assertSame([1, 2, 3], PaymentTransaction::where('payable_id', $invoice->id)->orderBy('id')->pluck('installment')->all());
        $this->assertTrue($this->salon->fresh()->subscription_ends_at->gt($endsBefore));
    }

    public function test_a_small_invoice_is_a_single_payment_as_before(): void
    {
        $this->salon->update(['max_specialists_count' => 10]);
        $this->fakeGateway();

        $invoice = $this->buy('1m');

        $this->assertSame(1, $invoice->installments);
        $this->assertStringNotContainsString('قسط', $this->requests()->first()['description']);
        $this->returnFromGateway($invoice);
        $this->assertTrue($invoice->fresh()->isPaid());
    }

    public function test_a_failed_later_installment_keeps_the_invoice_open_but_a_failed_first_one_closes_it(): void
    {
        $this->fakeGateway();
        $invoice = $this->buy();
        $this->returnFromGateway($invoice);

        $this->actingAs($this->owner)->post(route('admin.billing.installment', $invoice));
        $this->returnFromGateway($invoice, 'NOK')->assertSessionHasErrors();
        $this->assertTrue($invoice->fresh()->isPending(), 'قسط دوم ناموفق ← فاکتور باز می‌ماند');
        $this->assertSame(1, $invoice->fresh()->installments_paid);

        $other = $this->buy();
        $this->returnFromGateway($other, 'NOK');
        $this->assertSame('failed', $other->fresh()->status);
    }

    public function test_after_seven_days_an_unfinished_invoice_becomes_platform_credit_used_on_the_next_purchase(): void
    {
        $this->fakeGateway();
        $invoice = $this->buy();
        $this->returnFromGateway($invoice);
        $paid = $invoice->fresh()->paid_amount;

        $this->travelTo(now()->addDays(InvoiceInstallments::OPEN_DAYS)->addMinute());
        $this->artisan('billing:expire-installments')->assertSuccessful();

        $this->assertSame('failed', $invoice->fresh()->status);
        $this->assertSame($paid, app(\App\Services\Payment\PlatformCredit::class)->balance($this->salon));
        $this->actingAs($this->owner)->get(route('admin.billing.index'))->assertSee('اعتبار پلتفرم');

        // خرید یک‌ماهه کوچک‌تر از اعتبار: بی درگاه پرداخت می‌شود
        $this->salon->update(['max_specialists_count' => 10]);
        $price = $this->price('1m');
        $this->assertLessThan($paid, $price);
        $requestsBefore = $this->requests()->count();
        $next = $this->buy('1m');

        $this->assertTrue($next->isPaid());
        $this->assertSame($price, $next->credit_applied);
        $this->assertSame($requestsBefore, $this->requests()->count(), 'بی درگاه');
        $this->assertSame($paid - $price, app(\App\Services\Payment\PlatformCredit::class)->balance($this->salon));

        // آزمون دستی ۲۰۲۶-۱۰-۳۱: تاریخچه «ناموفق» و روش «آنلاین» با کد پیگیری «CREDIT» نشان می‌داد
        $this->actingAs($this->owner)->get(route('admin.billing.index'))
            ->assertSee('بسته شد — مبلغ پرداخت‌شده اعتبار پلتفرم شد')
            ->assertSeeInOrder(['از اعتبار پلتفرم', 'اعتبار پلتفرم', 'پرداخت‌شده'])
            ->assertDontSee('CREDIT');
    }

    public function test_credit_smaller_than_the_invoice_lowers_the_gateway_amount_and_a_failed_purchase_gives_it_back(): void
    {
        $this->salon->update(['max_specialists_count' => 10]);
        app(\App\Services\Payment\PlatformCredit::class)->add($this->salon, 400_000, 'manual', note: 'آزمون');
        $this->fakeGateway(-51);

        $invoice = $this->buy('1m');

        $this->assertSame(400_000, $invoice->credit_applied);
        $this->assertSame(($this->price('1m') - 400_000) * 10, $this->requests()->first()['amount']);
        $this->assertSame(0, app(\App\Services\Payment\PlatformCredit::class)->balance($this->salon), 'رزرو شد');

        $this->returnFromGateway($invoice);

        $this->assertSame('failed', $invoice->fresh()->status);
        $this->assertSame(400_000, app(\App\Services\Payment\PlatformCredit::class)->balance($this->salon), 'برگشت');
    }

    public function test_reconcile_adds_an_abandoned_paid_installment_to_its_invoice(): void
    {
        $this->fakeGateway();
        $invoice = $this->buy();
        $tx = PaymentTransaction::where('payable_id', $invoice->id)->sole();
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['created_at' => now()->subMinutes(61), 'updated_at' => now()->subMinutes(61)]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $invoice->refresh();
        $this->assertTrue($invoice->isPending());
        $this->assertSame(1, $invoice->installments_paid);
        $this->assertSame('paid', $tx->fresh()->status);
        $this->assertFalse($tx->fresh()->needs_attention, 'معلوم است مال کدام فاکتور است؛ بررسی دستی لازم نیست');
    }

    public function test_a_late_installment_of_a_closed_invoice_becomes_credit(): void
    {
        $this->fakeGateway();
        $invoice = $this->buy();
        $tx = PaymentTransaction::where('payable_id', $invoice->id)->sole();
        app(InvoiceInstallments::class)->fail($invoice->fresh());
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['status' => 'pending', 'created_at' => now()->subMinutes(61), 'updated_at' => now()->subMinutes(61)]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame((int) ceil($this->price() / 3), app(\App\Services\Payment\PlatformCredit::class)->balance($this->salon));
        $this->assertSame(1, PlatformCreditEntry::where('reason', 'late_payment')->count());
    }

    /** ⭐ آزمون دستی ۲۰۲۶-۱۰-۳۱ (بند ۱۸): «پرداخت» و «انصراف» هم‌زمان؛ OK قسط را ثبت کرد و NOK همان Authority بعدش «لغو شد» نشان می‌داد */
    public function test_a_late_cancel_of_an_already_paid_installment_says_it_was_paid(): void
    {
        $this->fakeGateway();
        $invoice = $this->buy();
        $this->returnFromGateway($invoice);
        $this->actingAs($this->owner)->post(route('admin.billing.installment', $invoice));
        $this->returnFromGateway($invoice);
        $this->assertSame(2, $invoice->fresh()->installments_paid);

        $this->returnFromGateway($invoice, 'NOK')->assertRedirect(route('admin.billing.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('info', fn ($m) => str_contains($m, 'قسط ۲ از ۳') && str_contains($m, 'قبلاً پرداخت'));
        $this->assertSame(2, $invoice->fresh()->installments_paid);
        $this->actingAs($this->owner)->get(route('admin.billing.index'))->assertSee('قسط ۲ از ۳ قبلاً پرداخت و ثبت شده است.');
    }

    /** ⭐ تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱: فاکتوری که به درگاه رفت و هیچ پاسخی نیامد، بعد از یک روز خودش «ناموفق» شود */
    public function test_an_unanswered_pending_invoice_fails_after_a_day(): void
    {
        $this->salon->update(['max_specialists_count' => 10]);
        $this->fakeGateway();
        $invoice = $this->buy('1m');

        $this->travelTo(now()->addHours(InvoiceInstallments::ABANDONED_RESERVATION_HOURS)->subMinute());
        $this->artisan('billing:expire-installments')->assertSuccessful();
        $this->assertTrue($invoice->fresh()->isPending(), 'هنوز زود است');

        $this->travelTo(now()->addMinutes(2));
        $this->artisan('billing:expire-installments')->assertSuccessful();
        $this->assertSame('failed', $invoice->fresh()->status);
        $this->assertSame(0, PlatformCreditEntry::count(), 'اعتبار رزروشده‌ای نبود');
    }
}
