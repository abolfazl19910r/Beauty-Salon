<?php

namespace Tests\Feature\Payment;

use App\Models\Salon;
use App\Models\User;
use App\Models\ZarinpalConnection;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۳ (۲۰۲۶-۱۰-۳۰): «اتصال حساب زرین‌پال» با OAuth 2.0 Authorization Code + PKCE (`/apiDocs/connect.html`). مالک سالن
 * حسابش را وصل می‌کند؛ ماهرو درگاه‌ها را با GraphQL (`Terminals`) می‌خواند، مرچنت (`key`) درگاهی که `domain` آن دامنه‌ی سالن است را
 * خودش پر می‌کند و اگر چنین درگاهی نیست، `TerminalAdd` می‌فرستد. تا `client_id` از پشتیبانی نیامده پشت config خاموش است و ورود
 * دستی مرچنت سر جایش. توکن رمزشده ذخیره می‌شود؛ کل فرایند سمت سرور.
 */
class ZarinpalConnectTest extends TestCase
{
    use RefreshDatabase;

    private const MERCHANT = 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb';

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
        $this->owner = User::factory()->create(['is_admin' => true]);
        config(['app.url' => 'https://arisheh.ir', 'app.central_domain' => 'arisheh.ir']);
    }

    private function enable(): void
    {
        config(['services.zarinpal.connect.client_id' => '1234', 'services.zarinpal.connect.mcc_id' => '186']);
    }

    private function domain(): string
    {
        return $this->salon->slug.'.arisheh.ir';
    }

    /** مرورگر را به authorizeInit می‌فرستد و state/verifier نشست را برمی‌گرداند. */
    private function startConnect(): array
    {
        $location = $this->actingAs($this->owner)->get(route('admin.payment-gateways.zarinpal.connect'))->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return [$location, $query, session('zarinpal_connect')];
    }

    private function fakeZarinpal(array $terminals, array $bankAccounts = []): void
    {
        Http::fake([
            'next.zarinpal.com/api/oauth/token' => Http::response(['access_token' => 'USER-ACCESS-TOKEN-xyz', 'expires_in' => 1296000, 'token_type' => 'Bearer']),
            'next.zarinpal.com/api/v4/graphql*' => function (Request $request) use ($terminals, $bankAccounts) {
                if (str_contains($request['query'], 'TerminalAdd')) {
                    return Http::response(['data' => ['TerminalAdd' => ['id' => '1789487', 'domain' => $request['variables']['domain'], 'status' => 'PENDING', 'key' => 'bbbbbbbb-1111-2222-3333-cccccccccccc', 'name' => $request['variables']['name']]]]);
                }

                return str_contains($request['query'], 'BankAccounts')
                    ? Http::response(['data' => ['resource' => $bankAccounts]])
                    : Http::response(['data' => ['Terminals' => $terminals]]);
            },
        ]);
    }

    public function test_hidden_until_a_client_id_is_configured(): void
    {
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertDontSee('اتصال حساب زرین‌پال');
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.zarinpal.connect'))->assertNotFound();
    }

    public function test_connect_redirects_to_zarinpal_with_pkce(): void
    {
        $this->enable();
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('اتصال حساب زرین‌پال');

        [$location, $query, $session] = $this->startConnect();

        $this->assertStringStartsWith('https://next.zarinpal.com/api/oauth/authorizeInit?', $location);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('1234', $query['client_id']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $session['verifier'], true)), '+/', '-_'), '='), $query['code_challenge']);
        $this->assertSame($session['state'], $query['state']);
        $this->assertSame('https://arisheh.ir/admin/payment-gateways/zarinpal/callback', $query['redirect_uri']);
        foreach (['terminal.read', 'terminal', 'bank_account.read'] as $scope) {
            $this->assertContains($scope, explode(' ', $query['scope']));
        }
        $this->assertGreaterThanOrEqual(43, strlen($session['verifier']));
    }

    public function test_a_wrong_state_is_refused_without_calling_zarinpal(): void
    {
        $this->enable();
        Http::fake();
        $this->startConnect();

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.zarinpal.callback', ['code' => 'C', 'state' => 'forged']))
            ->assertRedirect(route('admin.payment-gateways.index'))->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame(0, ZarinpalConnection::count());
    }

    public function test_callback_stores_an_encrypted_token_and_fills_the_merchant_of_the_matching_terminal(): void
    {
        $this->enable();
        $this->fakeZarinpal([
            ['id' => '1', 'status' => 'ACTIVE', 'domain' => 'other-site.ir', 'key' => 'cccccccc-1111-2222-3333-dddddddddddd', 'name' => 'دیگر'],
            ['id' => '1915487', 'status' => 'ACTIVE', 'domain' => 'https://'.$this->domain().'/', 'key' => self::MERCHANT, 'name' => 'راستا'],
        ]);
        [, $query, $session] = $this->startConnect();

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.zarinpal.callback', ['code' => 'AUTH-CODE', 'state' => $query['state']]))
            ->assertRedirect(route('admin.payment-gateways.index'))->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/oauth/token')
            && $r->isForm()
            && $r['grant_type'] === 'authorization_code' && $r['client_id'] === '1234' && $r['code'] === 'AUTH-CODE'
            && $r['code_verifier'] === $session['verifier'] && $r['redirect_uri'] === 'https://arisheh.ir/admin/payment-gateways/zarinpal/callback');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'graphql') && $r->hasHeader('Authorization', 'Bearer USER-ACCESS-TOKEN-xyz'));

        $gateway = $this->salon->fresh()->zarinpalGateway();
        $this->assertSame(self::MERCHANT, $gateway->credentials['merchant_id']);
        $this->assertTrue($gateway->is_active);

        $connection = ZarinpalConnection::where('salon_id', $this->salon->id)->sole();
        $this->assertSame('USER-ACCESS-TOKEN-xyz', $connection->access_token);
        $this->assertSame('1915487', $connection->terminal_id);
        $this->assertStringNotContainsString('USER-ACCESS-TOKEN', (string) DB::table('zarinpal_connections')->value('access_token'));
        $this->assertNull(session('zarinpal_connect'), 'state یک‌بارمصرف است');
    }

    public function test_without_a_terminal_for_the_salon_domain_the_owner_requests_one(): void
    {
        $this->enable();
        $this->fakeZarinpal(
            [['id' => '1', 'status' => 'ACTIVE', 'domain' => 'other-site.ir', 'key' => 'cccccccc-1111-2222-3333-dddddddddddd', 'name' => 'دیگر']],
            [['id' => '402811', 'iban' => 'IR123456789123456478945165', 'status' => 'ACTIVE', 'holder_name' => 'مالک', 'type' => 'PERSONAL', 'issuing_bank' => ['name' => 'بانک مهر ایران', 'slug' => 'Mehr']]],
        );
        [, $query] = $this->startConnect();
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.zarinpal.callback', ['code' => 'AUTH-CODE', 'state' => $query['state']]))
            ->assertSessionHas('warning');

        $this->assertNull($this->salon->fresh()->zarinpalGateway(), 'مرچنت درگاه دامنه‌ی دیگر برداشته نمی‌شود');
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))
            ->assertSee('درخواست درگاه زرین‌پال برای '.$this->domain())
            ->assertSee('IR123456789123456478945165');

        $this->actingAs($this->owner)->post(route('admin.payment-gateways.zarinpal.terminal'), [
            'bank_account_id' => '402811', 'support_phone' => '09121234567', 'name' => 'سالن راستا',
        ])->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => str_contains($r['query'] ?? '', 'TerminalAdd')
            && $r['variables'] === ['mcc_id' => '186', 'bank_account_id' => '402811', 'domain' => $this->domain(), 'support_phone' => '09121234567', 'name' => 'سالن راستا']);
        $this->assertSame('PENDING', ZarinpalConnection::sole()->requested_terminal['status']);
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('در انتظار تأیید زرین‌پال');
    }

    public function test_an_inactive_terminal_for_the_domain_is_not_used(): void
    {
        $this->enable();
        $this->fakeZarinpal([['id' => '7', 'status' => 'PENDING', 'domain' => $this->domain(), 'key' => self::MERCHANT, 'name' => 'راستا']]);
        [, $query] = $this->startConnect();

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.zarinpal.callback', ['code' => 'AUTH-CODE', 'state' => $query['state']]));

        $this->assertNull($this->salon->fresh()->zarinpalGateway());
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('PENDING');
    }

    public function test_only_the_owner_connects_and_disconnect_forgets_the_token(): void
    {
        $this->enable();
        $staff = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);
        $this->actingAs($staff)->get(route('admin.payment-gateways.zarinpal.connect'))->assertForbidden();

        ZarinpalConnection::create(['salon_id' => $this->salon->id, 'access_token' => 'T', 'connected_by' => $this->owner->id]);
        $this->actingAs($this->owner)->delete(route('admin.payment-gateways.zarinpal.disconnect'))->assertSessionHas('success');
        $this->assertSame(0, ZarinpalConnection::count());
    }
}
