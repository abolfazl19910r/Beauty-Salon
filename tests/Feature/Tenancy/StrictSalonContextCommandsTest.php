<?php

namespace Tests\Feature\Tenancy;

use App\Exceptions\MissingSalonContextException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * حالت سخت‌گیر BelongsToSalon (۲۰۲۶-۱۰-۰۱): همه‌ی دستورهای زمان‌بندی‌شده و دستورهای نگهداری، مثل cron، بدون سالن جاری
 * و روی داده‌ی دو سالن اجرا می‌شوند. هیچ‌کدام نباید MissingSalonContextException بدهند یا آن را بگیرند و لاگ کنند.
 */
class StrictSalonContextCommandsTest extends TestCase
{
    use RefreshDatabase;

    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([app(CurrentSalon::class)->get(), Salon::factory()->create(['slug' => 'strict-b'])] as $salon) {
            app(CurrentSalon::class)->set($salon);
            $specialist = Specialist::factory()->create();
            $service = BeautyService::factory()->create(['duration' => 30]);
            $customer = User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer']);
            Booking::factory()->create([ // یادآوری فردا
                'specialist_id' => $specialist->id, 'service_id' => $service->id, 'user_id' => $customer->id,
                'booking_time' => now()->addDay()->setTime(11, 0), 'status' => 'confirmed', 'payment_status' => 'paid',
            ]);
            Booking::factory()->create([ // پرداخت‌نشده‌ی قدیمی برای لغو خودکار
                'specialist_id' => $specialist->id, 'service_id' => $service->id, 'user_id' => $customer->id,
                'booking_time' => now()->addDays(3)->setTime(15, 0), 'status' => 'pending_payment', 'payment_status' => 'unpaid',
                'created_at' => now()->subHours(2),
            ]);
        }

        app(CurrentSalon::class)->clear(); // مثل cron / worker
        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $text = $e->message.' '.json_encode($e->context, JSON_UNESCAPED_UNICODE);
            if (str_contains($text, 'without a current salon')) {
                $this->logged[] = $text;
            }
        });
    }

    public static function commands(): array
    {
        return [
            ['bookings:send-reminders', []],
            ['bookings:cleanup', ['--no-interaction' => true]],
            ['wallet:settle-pending', []],
            ['review-tokens:cleanup', []],
            ['reports:cleanup-exports', []],
            ['payments:reconcile', []],
            ['payouts:refresh-vandar-tokens', []],
            ['model:prune', ['--model' => [\App\Models\ShortLink::class, \App\Models\IdempotencyKey::class]]],
            ['tenancy:repair-legacy-rows', []],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('commands')]
    public function test_scheduled_and_maintenance_commands_run_without_a_current_salon(string $command, array $options): void
    {
        try {
            $exit = Artisan::call($command, $options);
        } catch (MissingSalonContextException $e) {
            $this->fail("{$command}: ".$e->getMessage());
        }

        $this->assertSame([], $this->logged, "{$command} logged a salon-context error");
        $this->assertContains($exit, [0], "{$command} exit code; output: ".Artisan::output());
        $this->assertNull(app(CurrentSalon::class)->id(), "{$command} left a current salon behind");
    }

    public function test_the_cancel_unpaid_bookings_job_runs_without_a_current_salon(): void
    {
        app()->call([new \App\Jobs\CancelUnpaidBookings, 'handle']);

        $this->assertSame([], $this->logged);
        $this->assertSame(2, Booking::withoutGlobalScopes()->where('status', 'cancelled')->where('cancelled_by', 'system')->count());
    }
}
