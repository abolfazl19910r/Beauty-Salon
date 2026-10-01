<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\SendBookingReminderJob;
use App\Jobs\SendBulkNotificationJob;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Notifications\Booking\CustomerBookingNotification;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Queues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * حالت سخت‌گیر BelongsToSalon (۲۰۲۶-۱۰-۰۱) در worker واقعی (queue:work روی database، بدون سالن جاری): jobها و اعلان‌های
 * صف‌شده‌ی دو سالن سالنِ خودشان را می‌گیرند (SetSalonForQueuedJob / salonId())، بی‌خطا اجرا می‌شوند و پیامک هر کدام
 * به سالن خودش شمرده می‌شود.
 */
class StrictSalonContextWorkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_queued_jobs_and_notifications_of_two_salons_run_in_a_worker_without_a_current_salon(): void
    {
        config(['queue.default' => 'database', 'queue.work_via_scheduler' => false]);
        $charged = [];
        $this->mock(SMSService::class, function ($m) use (&$charged) {
            $m->shouldReceive('send')->andReturnUsing(function ($phone, $text, $salonId = null) use (&$charged) {
                $charged[] = $salonId === null ? null : (int) $salonId;

                return true;
            });
            $m->shouldIgnoreMissing(true);
        });

        $salons = [app(CurrentSalon::class)->get(), Salon::factory()->create(['slug' => 'worker-b'])];
        foreach ($salons as $salon) {
            app(CurrentSalon::class)->set($salon);
            $booking = Booking::factory()->create([
                'specialist_id' => Specialist::factory()->create()->id, 'service_id' => BeautyService::factory()->create()->id,
                'user_id' => User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer'])->id,
                'booking_time' => now()->addHour(), 'status' => 'confirmed',
            ]);
            SendBookingReminderJob::dispatch($booking->id);
            $booking->user->notify(new CustomerBookingNotification($booking));
            SendBulkNotificationJob::dispatch(AdminNewBookingNotification::class, [$booking], User::class, [$booking->user_id]);
        }
        $this->assertGreaterThan(0, DB::table('jobs')->count());

        $errors = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$errors) {
            if (in_array($e->level, ['error', 'critical']) || str_contains($e->message.json_encode($e->context), 'without a current salon')) {
                $errors[] = $e->message.' '.json_encode($e->context, JSON_UNESCAPED_UNICODE);
            }
        });

        app(CurrentSalon::class)->clear();
        Artisan::call('queue:work', ['--stop-when-empty' => true, '--memory' => 4096, '--queue' => Queues::workerOrder()]);

        $this->assertSame(0, DB::table('jobs')->count(), 'jobs left in the queue');
        $this->assertSame([], DB::table('failed_jobs')->pluck('exception')->map(fn ($e) => strtok($e, "\n"))->all());
        $this->assertSame([], $errors);
        $this->assertNotEmpty($charged);
        $this->assertNotContains(null, $charged, 'an SMS was sent without a salon');
        $this->assertEqualsCanonicalizing([$salons[0]->id, $salons[1]->id], array_values(array_unique($charged)));
    }
}
