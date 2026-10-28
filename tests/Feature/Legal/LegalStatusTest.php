<?php

namespace Tests\Feature\Legal;

use App\Models\LegalAcceptance;
use App\Models\Salon;
use App\Models\User;
use App\Services\Legal\LegalStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پذیرش دوباره‌ی قوانین (۲۰۲۶-۱۰-۲۸): فقط تغییر «مهم» (major) پذیرش دوباره می‌خواهد؛ نسخه‌ی پذیرفته‌شده با آخرین نسخه‌ی مهم
 * مقایسه می‌شود. پیش‌تر نسخه فقط ثبت می‌شد و با نسخه‌ی فعلی هرگز مقایسه نمی‌شد.
 */
class LegalStatusTest extends TestCase
{
    use RefreshDatabase;

    private function accept(User $user, string $document, string $version, ?Salon $salon = null): void
    {
        LegalAcceptance::create([
            'user_id' => $user->id, 'salon_id' => $salon?->id, 'document' => $document, 'version' => $version,
            'source' => 'web', 'accepted_at' => now(),
        ]);
    }

    private function bump(string $document, string $version, bool $major, string $summary = 'تغییر'): void
    {
        config(["legal.versions.{$document}" => $version, "legal.changes.{$document}.{$version}" => ['major' => $major, 'summary' => $summary]]);
    }

    public function test_every_version_has_a_change_entry_and_sortable_format(): void
    {
        foreach ((array) config('legal.versions') as $document => $version) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $version);
            $this->assertArrayHasKey($version, (array) config("legal.changes.{$document}"), "{$document}: نسخه‌ی فعلی خلاصه‌ی تغییر ندارد");
            foreach (array_keys((array) config("legal.changes.{$document}")) as $listed) {
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $listed);
            }
        }
    }

    public function test_nobody_is_pending_while_no_change_is_major(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->assertSame([], app(LegalStatus::class)->pending($user, ['terms', 'privacy']));

        $this->bump('terms', '1405-09-01', false);
        $this->assertSame([], app(LegalStatus::class)->pending($user, ['terms', 'privacy']), 'تغییر جزئی کسی را قفل نمی‌کند');
    }

    public function test_a_major_change_makes_older_acceptances_pending_with_the_summaries_since(): void
    {
        $old = User::factory()->create(['user_type' => 'customer']);
        $fresh = User::factory()->create(['user_type' => 'customer']);
        $legacy = User::factory()->create(['user_type' => 'customer']);
        $this->accept($old, 'terms', '1405-08-03');

        $this->bump('terms', '1405-09-01', true, 'شرایط لغو عوض شد');
        $this->bump('terms', '1405-09-10', false, 'اصلاح نگارش');
        $this->accept($fresh, 'terms', '1405-09-01');

        $pending = app(LegalStatus::class)->pending($old, ['terms', 'privacy']);
        $this->assertCount(1, $pending);
        $this->assertSame('terms', $pending[0]['document']);
        $this->assertSame('1405-09-10', $pending[0]['version'], 'پذیرش با نسخه‌ی فعلی ثبت می‌شود');
        $this->assertSame('1405-08-03', $pending[0]['accepted_version']);
        $this->assertSame(['1405-09-10', '1405-09-01'], array_column($pending[0]['changes'], 'version'));
        $this->assertSame('شرایط لغو عوض شد', $pending[0]['changes'][1]['summary']);

        $this->assertSame([], app(LegalStatus::class)->pending($fresh, ['terms']), 'نسخه‌ی مهم را پذیرفته؛ تغییر جزئی بعدش قفل نمی‌کند');
        $this->assertCount(1, app(LegalStatus::class)->pending($legacy, ['terms']), 'کاربر بی سابقه‌ی پذیرش هم باید بپذیرد');
    }

    public function test_a_major_version_ahead_of_the_current_one_is_ignored(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);
        $this->accept($user, 'terms', '1405-08-03');
        config(['legal.changes.terms.1405-12-01' => ['major' => true, 'summary' => 'آینده']]);

        $this->assertNull(app(LegalStatus::class)->requiredVersion('terms'));
        $this->assertSame([], app(LegalStatus::class)->pending($user, ['terms']));
    }

    public function test_salon_terms_are_per_salon_and_privacy_per_user(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $coOwner = User::factory()->create(['is_admin' => true]);
        $salonA = Salon::factory()->create(['slug' => 'status-a']);
        $salonB = Salon::factory()->create(['slug' => 'status-b']);
        $this->bump('salon_terms', '1405-09-01', true);

        $this->accept($coOwner, 'salon_terms', '1405-09-01', $salonA);

        $this->assertSame([], app(LegalStatus::class)->pending($owner, ['salon_terms'], $salonA), 'مالک دیگر همین سالن پذیرفته');
        $this->assertCount(1, app(LegalStatus::class)->pending($owner, ['salon_terms'], $salonB));
    }

    public function test_old_apps_are_detected_by_version_header(): void
    {
        $status = app(LegalStatus::class);

        $this->assertFalse($status->appSupportsReacceptance(null));
        $this->assertFalse($status->appSupportsReacceptance('0.3.6'));
        $this->assertTrue($status->appSupportsReacceptance('0.3.7'));
        $this->assertTrue($status->appSupportsReacceptance('0.4.0 (15)'));
    }
}
