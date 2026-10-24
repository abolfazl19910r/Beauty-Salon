<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\MarkClientRequest;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Review;
use App\Models\Salon;
use App\Models\SecurityLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CompletesSalonSignup;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * IP و user agent روی کارهای کلیدی (بسته‌ی مجوز 5د؛ ماده‌ی ۶۶۸ آیین دادرسی کیفری). 🐞 پیش‌تر ثبت و لغو نوبت، نظر، پیام تیکت،
 * ثبت‌نام سالن، تغییر رمز و موبایل هیچ ردی با IP نداشتند (فقط ورود و پرداخت امن).
 */
class KeyActionAuditTest extends TestCase
{
    use CompletesSalonSignup;
    use RefreshDatabase;
    use SalonContactPayload;

    /** مثل یک درخواست واقعی از مرورگر */
    private function asClient(string $ip = '5.1.1.1', string $ua = 'Mozilla/5.0 Test'): void
    {
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $ua]);
        $request->attributes->set(MarkClientRequest::ATTRIBUTE, true);
        $this->app->instance('request', $request);
    }

    private function last(string $event): ?SecurityLog
    {
        return SecurityLog::where('event', $event)->latest('id')->first();
    }

    public function test_booking_created_cancelled_and_rescheduled_carry_ip_user_agent_and_salon(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);
        $this->asClient();

        $booking = Booking::factory()->create(['user_id' => $customer->id, 'status' => 'pending', 'booking_time' => now()->addDays(2)]);
        $created = $this->last('booking_created');
        $this->assertNotNull($created);
        $this->assertSame('5.1.1.1', $created->ip_address);
        $this->assertSame('Mozilla/5.0 Test', $created->user_agent);
        $this->assertSame($customer->id, $created->user_id);
        $this->assertSame(app(CurrentSalon::class)->id(), $created->salon_id);
        $this->assertSame(['Booking', $booking->id], [$created->subject_type, $created->subject_id]);

        $booking->update(['booking_time' => now()->addDays(3)]);
        $this->assertNotNull($this->last('booking_rescheduled'));

        $booking->update(['status' => 'cancelled']);
        $this->assertSame('pending', $this->last('booking_cancelled')->context['from']);
    }

    public function test_review_ticket_and_ticket_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->asClient('5.2.2.2');

        Review::factory()->create();
        $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);
        SupportTicketMessage::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);

        foreach (['review_created', 'ticket_created', 'ticket_message_created'] as $event) {
            $this->assertSame('5.2.2.2', $this->last($event)?->ip_address, $event);
        }
    }

    public function test_password_and_phone_changes_record_old_and_new_phone(): void
    {
        $user = User::factory()->create(['phone' => '09120000001']);
        $this->asClient('5.3.3.3');

        $user->update(['password' => Hash::make('Another!Pass1')]);
        $user->update(['phone' => '09120000002']);

        $this->assertSame($user->id, $this->last('password_changed')->user_id, 'بازیابی رمز بی ورود: خود کاربر');
        // ترتیب کلیدها مهم نیست: ستون JSON در MySQL 8 کلیدها را مرتب می‌کند
        $this->assertEquals(['from' => '09120000001', 'to' => '09120000002'], $this->last('phone_changed')->context);
    }

    public function test_payments_started_and_finished(): void
    {
        $this->asClient('5.4.4.4');
        $tx = PaymentTransaction::create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(), 'salon_id' => app(CurrentSalon::class)->id(), 'driver' => 'zarinpal',
            'purpose' => 'booking', 'amount_rial' => 500000, 'status' => 'pending', 'callback_url' => 'https://x.test/cb',
        ]);
        $tx->update(['status' => 'paid']);

        $this->assertSame(500000, $this->last('payment_started')->context['amount_rial']);
        $this->assertSame('5.4.4.4', $this->last('payment_paid')->ip_address);
    }

    public function test_salon_signup_over_http_is_recorded_with_the_real_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])->post(route('salon-signup.store'), array_merge([
            'name' => 'سالن الماس', 'slug' => 'almas-audit', 'subscription_type' => '3m', 'owner_name' => 'سارا محمدی',
            'owner_phone' => '09121234599', 'owner_password' => 'Str0ng!Passw0rd', 'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload()));
        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])->confirmSalonSignup();

        $salon = Salon::where('slug', 'almas-audit')->firstOrFail();
        $log = SecurityLog::where('event', 'salon_created')->where('subject_id', $salon->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('5.5.5.5', $log->ip_address);
    }

    public function test_queue_and_scheduler_work_without_a_client_is_not_recorded(): void
    {
        // درخواستی که Laravel برای console می‌سازد REMOTE_ADDR=127.0.0.1 دارد؛ مثل لغو خودکار نوبت پرداخت‌نشده در scheduler
        Booking::factory()->create(['status' => 'pending'])->update(['status' => 'cancelled']);

        $this->assertSame(0, SecurityLog::count());
    }
}
