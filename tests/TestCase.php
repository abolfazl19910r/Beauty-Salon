<?php

namespace Tests;

use App\Models\Salon;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    /**
     * تمام تست‌ها (چه نیاز به دیتابیس داشته باشند چه نه) هیچ‌وقت نباید واقعاً به Kavenegar وصل شوند —
     * چه به این دلیل که شبکه‌ی تست به api.kavenegar.com دسترسی ندارد، و چه چون طبق مستندات پروژه
     * (Rasta_unified_prompt.md) یک تماس synchronous واقعی به Kavenegar می‌تواند ۲۰-۳۰ ثانیه طول بکشد —
     * که کل سوییت تست را غیرقابل‌اجرا می‌کرد. این mock پیش‌فرض همیشه true برمی‌گرداند (بدون هیچ side
     * effect)، دقیقاً معادل رفتار SMSService::send()/sendTemplate() در حالت local بدون
     * KAVENEGAR_SEND_IN_LOCAL. تست‌هایی که می‌خواهند محتوای واقعی پیامک را assert کنند، می‌توانند خودشان
     * $this->mock(SMSService::class, ...) را در متد تست override کنند.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SMSService::class, Mockery::mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendTemplate')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendVerificationCode')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendBookingConfirmation')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendBookingReminder')->andReturn(true)->byDefault();
        }));

        // ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2): salon_id is
        // NOT NULL on every salon-owned table (Specialist, Booking, WalletSetting, AdminWallet,
        // etc. — see BelongsToSalon), and BelongsToSalon only auto-fills it from
        // app(CurrentSalon::class)->id() when that's set. Almost none of this project's ~900
        // existing tests go through the salon-resolving HTTP middleware (commit 3) — they call
        // factories and services directly — so without this, every one of them would start
        // failing on the NOT NULL constraint the moment this trait is applied. Binding one
        // default salon here, for every test, keeps all of that code working exactly as before:
        // it's the single-tenant-shaped default every non-SaaS-specific test implicitly runs
        // "inside". Tests that specifically exercise cross-salon isolation create their OWN
        // additional salons and call CurrentSalon::set()/clear() explicitly to move between them
        // (see AdminBookingSlotConflictTest and the future SalonManagementTest for that pattern).
        //
        // Guarded by Schema::hasTable() rather than a bare try/catch around the whole thing —
        // this project's Unit tests (tests/Unit/*) extend this same base class but don't use
        // RefreshDatabase, so `salons` genuinely won't exist for them and this must be a no-op,
        // not a masked failure.
        // Reuses the exact salon migration 2026_08_29_000103_backfill_default_salon_and_salon_id
        // already created (slug 'rasta') rather than inserting a second one — RefreshDatabase
        // runs that migration once for the whole suite and wraps only each individual test in a
        // rolled-back transaction, so a plain create() here with the same slug would collide
        // with the migration's own row on the unique constraint. firstOrCreate() also means this
        // still works standalone if some future test setup migrates without that backfill step.
        if (Schema::hasTable('salons')) {
            $defaultSalon = Salon::firstOrCreate(
                ['slug' => 'rasta'],
                Salon::factory()->make(['name' => 'سالن زیبایی راستا', 'slug' => 'rasta'])->toArray()
            );

            $this->app->make(CurrentSalon::class)->set($defaultSalon);
        }
    }
}
