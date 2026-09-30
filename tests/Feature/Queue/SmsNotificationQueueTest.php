<?php

namespace Tests\Feature\Queue;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\Booking\BookingStatusUpdated;
use App\Support\CurrentSalon;
use App\Support\Queues;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: هر اعلانی که پیامک می‌فرستد، پیامکش از صف sms می‌رود — ۱۱ اعلانی که تا امروز همزمان بودند هم.
 * اعلان داخلی همان‌ها همزمان می‌ماند تا salon_id را از سالن جاری درخواست بگیرد.
 */
class SmsNotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<class-string> */
    private function smsNotifications(): array
    {
        return collect(glob(app_path('Notifications/**/*.php')) ?: [])
            ->merge(glob(app_path('Notifications/*/*/*.php')) ?: [])
            ->merge(glob(app_path('Notifications/*/*/*/*.php')) ?: [])
            ->unique()
            ->filter(fn ($f) => ! str_contains($f, '/Concerns/') && str_contains(file_get_contents($f), 'function toSms'))
            ->map(fn ($f) => 'App\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($f, app_path().'/')))
            ->values()->all();
    }

    public function test_every_sms_notification_is_queued_with_its_sms_channel_on_the_sms_queue(): void
    {
        $classes = $this->smsNotifications();
        $this->assertGreaterThanOrEqual(16, count($classes));

        foreach ($classes as $class) {
            $this->assertTrue(is_subclass_of($class, ShouldQueue::class), "{$class} is not queued");
            $queues = (new \ReflectionClass($class))->newInstanceWithoutConstructor()->viaQueues();
            $this->assertSame(Queues::SMS, $queues['sms'] ?? null, "{$class} does not send SMS on the sms queue");
        }
    }

    public function test_the_sms_goes_to_the_queue_while_the_in_app_notification_is_stored_now_with_its_salon(): void
    {
        config(['queue.default' => 'database']);
        $salon = app(CurrentSalon::class)->get();
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);
        $booking = Booking::factory()->create(['user_id' => $customer->id]);

        DB::table('jobs')->delete(); // کارهای صف‌شده‌ی ساخت نوبت (اعلان مدیر) به این تست مربوط نیستند
        $customer->notify(new BookingStatusUpdated($booking, 'confirmed'));

        $jobs = DB::table('jobs')->get();
        $this->assertCount(1, $jobs);
        $this->assertSame(Queues::SMS, $jobs[0]->queue);
        $this->assertStringContainsString('sms', json_decode($jobs[0]->payload, true)['data']['command']);
        $stored = DB::table('user_notifications')->where('notifiable_id', $customer->id)->get();
        $this->assertCount(1, $stored);
        $this->assertSame($salon->id, (int) $stored[0]->salon_id);

        // worker صف sms همان پیامک را با SMSService از container و سهمیه‌ی همان سالن می‌فرستد
        $sent = [];
        $this->mock(\App\Services\SMSService::class, function ($mock) use (&$sent) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $message, $salonId = null) use (&$sent) {
                $sent[] = [$phone, $salonId];

                return true;
            });
        });
        app(CurrentSalon::class)->clear();
        \Illuminate\Support\Facades\Artisan::call('queue:work', ['--queue' => Queues::SMS, '--stop-when-empty' => true, '--memory' => 4096]);

        $this->assertSame([[$customer->phone, $booking->salon_id]], $sent);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('user_notifications')->where('notifiable_id', $customer->id)->count()); // داخلی دوباره ثبت نشد
    }
}
