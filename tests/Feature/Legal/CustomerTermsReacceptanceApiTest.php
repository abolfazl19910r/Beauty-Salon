<?php

namespace Tests\Feature\Legal;

use App\Models\LegalAcceptance;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پذیرش دوباره‌ی قوانین در اپ مشتری (۲۰۲۶-۱۰-۲۸): اپ تازه (۰.۳.۷ به بعد) فهرست legal_pending را می‌گیرد و تا پذیرش نوبت/دوره‌ی تازه
 * 409 می‌گیرد؛ اپ قدیمی قفل نمی‌شود و پذیرشش ضمنی (app-implicit) با نسخه‌ی اپ ثبت می‌شود.
 */
class CustomerTermsReacceptanceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $salon = app(CurrentSalon::class)->get();
        $this->customer = User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer', 'phone' => '09121118888']);
        $this->token = $this->customer->createToken('A17', ['customer'], now()->addDay())->plainTextToken;
        config(['legal.versions.privacy' => '1405-09-01', 'legal.changes.privacy.1405-09-01' => ['major' => true, 'summary' => 'نگهداری IP شش ماه']]);
    }

    private function app(string $version)
    {
        return $this->withToken($this->token)->withHeaders(['X-App-Version' => $version]);
    }

    public function test_me_lists_pending_documents_and_new_bookings_are_refused_until_accepted(): void
    {
        $me = $this->app('0.3.7')->getJson('/api/v1/me')->assertOk();
        $this->assertSame(['privacy'], array_column($me->json('data.legal_pending'), 'document'), 'فقط سندی که تغییر مهم دارد');
        $this->assertSame('نگهداری IP شش ماه', $me->json('data.legal_pending.0.changes.0.summary'));
        $this->assertStringEndsWith('/privacy', $me->json('data.legal_pending.0.url'));

        $this->app('0.3.7')->postJson('/api/v1/customer/bookings/quote', [])->assertStatus(409)->assertJsonPath('error.code', 'terms_update_required');
        $this->app('0.3.7')->postJson('/api/v1/customer/bookings', [])->assertStatus(409);
        $this->app('0.3.7')->postJson('/api/v1/customer/courses/1/bookings', [])->assertStatus(409);
        $this->app('0.3.7')->getJson('/api/v1/customer/bookings')->assertOk();
        $this->app('0.3.7')->getJson('/api/v1/customer/profile')->assertOk();
        $this->assertSame(0, LegalAcceptance::count(), 'اپ تازه ضمنی ثبت نمی‌شود');

        $this->app('0.3.7')->postJson('/api/v1/customer/legal/accept', ['accept_terms' => false])->assertStatus(422);
        $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.11'])->app('0.3.7')
            ->postJson('/api/v1/customer/legal/accept', ['accept_terms' => true])->assertOk()->assertJsonPath('data.pending', []);

        $rows = LegalAcceptance::where('user_id', $this->customer->id)->get();
        $this->assertSame(['privacy'], $rows->pluck('document')->all());
        $this->assertSame(['app'], $rows->pluck('source')->unique()->values()->all());
        $this->assertSame('0.3.7', $rows->first()->app_version);
        $this->assertSame('5.6.7.11', $rows->first()->ip_address);
        $this->assertSame('1405-09-01', $rows->firstWhere('document', 'privacy')->version);

        $this->app('0.3.7')->getJson('/api/v1/customer/legal')->assertOk()->assertJsonPath('data.pending', []);
        $this->app('0.3.7')->postJson('/api/v1/customer/bookings/quote', [])->assertStatus(422);
    }

    public function test_old_apps_are_not_locked_and_acceptance_is_recorded_as_implicit(): void
    {
        $this->app('0.3.6')->postJson('/api/v1/customer/bookings/quote', [])->assertStatus(422);

        $rows = LegalAcceptance::where('user_id', $this->customer->id)->get();
        $this->assertSame(['privacy'], $rows->pluck('document')->all());
        $this->assertSame(['app-implicit'], $rows->pluck('source')->unique()->values()->all());
        $this->assertSame('0.3.6', $rows->first()->app_version);

        $this->app('0.3.6')->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.legal_pending', []);
        $this->assertSame(1, LegalAcceptance::count(), 'یک بار ثبت می‌شود');
    }

    public function test_nothing_is_pending_without_a_major_change(): void
    {
        config(['legal.changes.privacy.1405-09-01.major' => false]);
        $this->app('0.3.7')->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.legal_pending', []);
        $this->app('0.3.7')->postJson('/api/v1/customer/bookings/quote', [])->assertStatus(422);
    }

    public function test_staff_app_is_untouched(): void
    {
        $staff = \App\Models\Specialist::factory()->create();
        $staffToken = User::find($staff->user_id)->createToken('RN8', ['staff'], now()->addDay())->plainTextToken;
        $this->withToken($staffToken)->withHeaders(['X-App-Version' => '0.3.6'])->getJson('/api/v1/me')->assertOk()->assertJsonMissingPath('data.legal_pending');
        $this->assertSame(0, LegalAcceptance::count());
    }
}
