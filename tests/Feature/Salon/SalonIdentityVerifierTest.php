<?php

namespace Tests\Feature\Salon;

use App\Contracts\Verification\SalonIdentityVerifier;
use App\Models\Salon;
use App\Models\SalonVerification;
use App\Models\User;
use App\Services\Salon\SalonVerificationService;
use App\Services\Verification\ManualSalonIdentityVerifier;
use App\Services\Verification\VerificationOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * مرحله‌ی ب احراز سالن (بسته‌ی مجوز 5د): رابط سرویس احراز با پیاده‌سازی دستی، تا امتا (یا شاهکار) وصل شود.
 */
class SalonIdentityVerifierTest extends TestCase
{
    use RefreshDatabase;

    private function submit(): SalonVerification
    {
        Storage::fake(SalonVerificationService::DISK);
        $salon = Salon::factory()->unverified()->create();

        return app(SalonVerificationService::class)->submit($salon, User::factory()->create(), [
            'owner_name' => 'سارا محمدی', 'owner_national_code' => '0499370899', 'license_type' => 'nail', 'license_unique_id' => 'ABC123456',
        ], UploadedFile::fake()->create('l.pdf', 10, 'application/pdf'));
    }

    private function fakeVerifier(VerificationOutcome $outcome): void
    {
        $this->app->instance(SalonIdentityVerifier::class, new class($outcome) implements SalonIdentityVerifier
        {
            public function __construct(private readonly VerificationOutcome $outcome) {}

            public function name(): string
            {
                return 'emta';
            }

            public function check(SalonVerification $verification): VerificationOutcome
            {
                return $this->outcome;
            }
        });
    }

    public function test_the_default_driver_is_manual_and_leaves_the_decision_to_the_super_admin(): void
    {
        $this->assertInstanceOf(ManualSalonIdentityVerifier::class, app(SalonIdentityVerifier::class));

        config(['verification.driver' => 'nonexistent']);
        $this->assertInstanceOf(ManualSalonIdentityVerifier::class, app(SalonIdentityVerifier::class), 'نام ناشناخته هرگز تأیید خودکار نیست');

        $v = $this->submit();
        $this->assertSame(['pending', 'manual'], [$v->status, $v->provider]);
        $this->assertSame('pending', $v->salon->fresh()->verification_status);
    }

    public function test_an_automatic_approval_opens_the_salon_and_keeps_the_reference(): void
    {
        $this->fakeVerifier(VerificationOutcome::approved('EMTA-77', ['matched' => true]));

        $v = $this->submit();

        $this->assertSame(['approved', 'emta', 'EMTA-77'], [$v->status, $v->provider, $v->provider_reference]);
        $this->assertNull($v->reviewed_by);
        $this->assertSame(['matched' => true], $v->provider_payload);
        $this->assertTrue($v->salon->fresh()->isVerified());
    }

    public function test_an_automatic_rejection_carries_the_reason(): void
    {
        $this->fakeVerifier(VerificationOutcome::rejected('پروانه منقضی است'));

        $v = $this->submit();

        $this->assertSame(['rejected', 'پروانه منقضی است'], [$v->status, $v->rejection_reason]);
        $this->assertSame('rejected', $v->salon->fresh()->verification_status);
    }
}
